<?php
/**
 * Plugin Name: OberHub V6 Search Extension
 * Description: Additive device-independent retrieval and AI gateway. Keeps existing core and data.
 * Version: 0.1.0
 * Requires PHP: 8.2
 * License: GPL-2.0-or-later
 */
namespace OberHubV6;
if(!defined('ABSPATH'))exit;
require_once __DIR__.'/src/Knowledge.php';
foreach(['AIProvider','NullProvider','OpenAICompatibleProvider','Gateway'] as $class)require_once __DIR__.'/src/AI/'.$class.'.php';
function dataset():array{return \OberHub\dataset();}
// Until explicitly prepared by an administrator, the live core routes remain untouched.
add_action('rest_api_init',function(){
 if(!class_exists('OberHub\\Knowledge'))return;
 register_rest_route('oberhub/v1','/v6/status',['methods'=>'GET','permission_callback'=>fn()=>current_user_can('manage_options'),'callback'=>fn()=>rest_ensure_response(['version'=>'0.1.0','ready'=>(bool)get_option('oh_v6_ready',false),'stats'=>get_option('oh_v6_knowledge_stats',[]),'import'=>\OberHub\ImportQueue::status()])]);
 register_rest_route('oberhub/v1','/v6/prepare',['methods'=>'POST','permission_callback'=>fn()=>current_user_can('manage_options'),'callback'=>function(){
  if(\OberHub\ImportQueue::active())return new \WP_Error('busy','Finish existing import first',['status'=>409]);
  $delta=json_decode(file_get_contents(__DIR__.'/activities.json'),true);
  foreach($delta['sources'] as $source)\OberHub\Sources::approve_host(wp_parse_url($source['source_url'],PHP_URL_HOST));
  return rest_ensure_response(\OberHub\ImportQueue::enqueue($delta,false));
 }]);
 register_rest_route('oberhub/v1','/v6/import-batch',['methods'=>'POST','permission_callback'=>fn()=>current_user_can('manage_options'),'callback'=>fn()=>rest_ensure_response(\OberHub\ImportQueue::process(25))]);
 register_rest_route('oberhub/v1','/v6/build-index',['methods'=>'POST','permission_callback'=>fn()=>current_user_can('manage_options'),'callback'=>function(){
  if(\OberHub\ImportQueue::active())return new \WP_Error('busy','Finish import first',['status'=>409]);
  Knowledge::install();$stats=Knowledge::rebuild(dataset());
  if(empty($stats['records']))return new \WP_Error('empty','No index built',['status'=>500]);
  update_option('oh_v6_ready',true,false);return rest_ensure_response($stats);
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
