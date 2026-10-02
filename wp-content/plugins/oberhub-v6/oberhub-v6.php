<?php
/**
 * Plugin Name: OberHub V6 Search Extension
 * Description: Additive device-independent retrieval and AI gateway. Keeps existing core and data.
 * Version: 0.1.2
 * Requires PHP: 8.2
 * License: GPL-2.0-or-later
 */
namespace OberHubV6;
if(!defined('ABSPATH'))exit;
require_once __DIR__.'/src/Knowledge.php';
foreach(['AIProvider','NullProvider','OpenAICompatibleProvider','AnswerPacks','Gateway'] as $class)require_once __DIR__.'/src/AI/'.$class.'.php';
function dataset():array{return \OberHub\dataset();}
function mark_index_dirty($mid,$pid,$key):void {
 if($key!=='_oh_record'||strpos((string)get_post_type($pid),'oh_')!==0||get_post_type($pid)==='oh_source')return;
 update_option('oh_v6_index_dirty',microtime(true),false);
 if(!wp_next_scheduled('oh_v6_refresh_index'))wp_schedule_single_event(time()+120,'oh_v6_refresh_index');
}
add_action('added_post_meta',__NAMESPACE__.'\\mark_index_dirty',10,3);
add_action('updated_post_meta',__NAMESPACE__.'\\mark_index_dirty',10,3);
add_action('before_delete_post',function($pid){mark_index_dirty(0,$pid,'_oh_record');},10,1);
function finish_index_refresh($revision):void {
 if(get_option('oh_v6_index_dirty',false)===$revision)update_option('oh_v6_index_dirty',false,false);
 else if(!wp_next_scheduled('oh_v6_refresh_index'))wp_schedule_single_event(time()+120,'oh_v6_refresh_index');
 update_option('oh_v6_ready',true,false);update_option('oh_v6_index_error','',false);
}
add_action('oh_v6_refresh_index',function(){
 if(!class_exists('OberHub\\ImportQueue'))return;
 $revision=get_option('oh_v6_index_dirty',false);if(!$revision)return;
 if(\OberHub\ImportQueue::active()){wp_schedule_single_event(time()+120,'oh_v6_refresh_index');return;}
 try {
  Knowledge::install();$stats=Knowledge::rebuild(dataset());
  if(empty($stats['records']))throw new \RuntimeException('Empty index');
  finish_index_refresh($revision);
 }catch(\Throwable $e){update_option('oh_v6_index_error','Refresh failed; retry scheduled.',false);wp_schedule_single_event(time()+300,'oh_v6_refresh_index');}
},10,0);
// Until explicitly prepared by an administrator, the live core routes remain untouched.
add_action('rest_api_init',function(){
 if(!class_exists('OberHub\\Knowledge'))return;
 register_rest_route('oberhub/v1','/v6/status',['methods'=>'GET','permission_callback'=>fn()=>current_user_can('manage_options'),'callback'=>fn()=>rest_ensure_response(['version'=>'0.1.2','ready'=>(bool)get_option('oh_v6_ready',false),'stats'=>get_option('oh_v6_knowledge_stats',[]),'index_dirty'=>(bool)get_option('oh_v6_index_dirty',false),'index_error'=>get_option('oh_v6_index_error',''),'import'=>\OberHub\ImportQueue::status()])]);
 register_rest_route('oberhub/v1','/v6/prepare',['methods'=>'POST','permission_callback'=>fn()=>current_user_can('manage_options'),'callback'=>function($req){
  if(\OberHub\ImportQueue::active())return new \WP_Error('busy','Finish existing import first',['status'=>409]);
  $body=$req->get_json_params() ?: [];
  if(array_key_exists('dataset',$body)) {
   // External batches use existing core validation and approved source domains.
   // No overwrite flag, raw SQL or source-host approval is accepted here.
   $delta=$body['dataset'];
   if(!is_array($delta)||strlen(wp_json_encode($delta))>1000000)return new \WP_Error('dataset','Invalid or oversized dataset',['status'=>400]);
   $allowed=array_merge(array_keys(\OberHub\ImportQueue::TYPES),['intents','aliases']);
   if(array_diff(array_keys($delta),$allowed))return new \WP_Error('dataset','Unknown dataset collection',['status'=>400]);
   $count=0;foreach($delta as $rows){if(!is_array($rows)||!array_is_list($rows))return new \WP_Error('dataset','Collections must be lists',['status'=>400]);$count+=count($rows);}
   if(!$count||$count>500)return new \WP_Error('dataset','Use batches of 1–500 records',['status'=>400]);
  }else{
   $batch=$body['batch']??'activities';
   if(!in_array($batch,['activities','life'],true))return new \WP_Error('batch','Unknown bundled batch',['status'=>400]);
   $delta=json_decode(file_get_contents(__DIR__.'/'.($batch==='life'?'life-services.json':'activities.json')),true);
   foreach($delta['sources'] as $source)\OberHub\Sources::approve_host(wp_parse_url($source['source_url'],PHP_URL_HOST));
  }
  return rest_ensure_response(\OberHub\ImportQueue::enqueue($delta,false));
 }]);
 register_rest_route('oberhub/v1','/v6/import-batch',['methods'=>'POST','permission_callback'=>fn()=>current_user_can('manage_options'),'callback'=>fn()=>rest_ensure_response(\OberHub\ImportQueue::process(25))]);
 register_rest_route('oberhub/v1','/v6/build-index',['methods'=>'POST','permission_callback'=>fn()=>current_user_can('manage_options'),'callback'=>function(){
  if(\OberHub\ImportQueue::active())return new \WP_Error('busy','Finish import first',['status'=>409]);
  $revision=get_option('oh_v6_index_dirty',false);
  Knowledge::install();$stats=Knowledge::rebuild(dataset());
  if(empty($stats['records']))return new \WP_Error('empty','No index built',['status'=>500]);
  finish_index_refresh($revision);return rest_ensure_response($stats);
 }]);
 if(!get_option('oh_v6_ready',false))return;
 register_rest_route('oberhub/v1','/search',['methods'=>['GET','POST'],'permission_callback'=>'__return_true','callback'=>function($req){
  $q=sanitize_text_field((string)($req->get_param('q')??''));if(strlen($q)>600)return new \WP_Error('length','Query too long',['status'=>400]);
  $lang=sanitize_key($req->get_param('lang')??'de');if(!in_array($lang,['de','en','ru','uk'],true))$lang='de';
  $id=hash_hmac('sha256',(string)($_SERVER['REMOTE_ADDR']??''),wp_salt('nonce'));$bucket='oh_v6_rate_'.substr($id,0,24).'_'.(int)floor(time()/60);$n=(int)get_transient($bucket);if($n>=90)return new \WP_Error('rate','Retry later',['status'=>429]);set_transient($bucket,$n+1,70);
  $result=Knowledge::search($q,$lang,(int)($req->get_param('page')??1),(int)($req->get_param('per_page')??10),sanitize_key($req->get_param('locality')??'all'),sanitize_key($req->get_param('type')??''));
  foreach($result['results'] as &$hit)$hit['evidence']=\OberHub\Sources::evidence($hit['record'],false,false);unset($hit);
  $result['engine']='v6-server-index';$response=rest_ensure_response($result);$response->header('Cache-Control','no-store');return $response;
 }],true);
 register_rest_route('oberhub/v1','/answer',['methods'=>'POST','permission_callback'=>'__return_true','callback'=>function($req){
  $q=sanitize_textarea_field((string)($req->get_param('question')??''));if(strlen($q)>2000||strlen(trim($q))<2)return new \WP_Error('length','Question length invalid',['status'=>400]);
  $lang=sanitize_key($req->get_param('lang')??'de');if(!in_array($lang,['de','en','ru','uk'],true))$lang='de';nocache_headers();$result=AI\Gateway::answer($q,$lang);$response=rest_ensure_response($result);if($result['mode']==='rate-limited')$response->set_status(429);return $response;
 }],true);
},40);
