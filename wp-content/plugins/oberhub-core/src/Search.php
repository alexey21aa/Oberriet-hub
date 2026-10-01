<?php
namespace OberHub;
final class Search {
    public static function boot(): void {
        add_action('rest_api_init',function(){
            register_rest_route('oberhub/v1','/index',['methods'=>'GET','permission_callback'=>'__return_true','callback'=>function($r){
                $event=sanitize_key($r->get_param('event') ?? '');$dimension=sanitize_key($r->get_param('dimension') ?? '');
                if ($event==='language_switch' && in_array($dimension,['de','en','ru','uk'],true)) { nocache_headers();Analytics::count($event,$dimension);return rest_ensure_response(['counted'=>true]); }
                $intent=sanitize_key($r->get_param('intent') ?? '');$view=sanitize_key($r->get_param('view') ?? '');
                $data=dataset(!in_array($view,['public','router'],true) && $intent==='');
                if($view==='router'){$data['intents']=get_option('oh_intents',[]);}
                if ($intent) { nocache_headers(); }
                if ($intent && in_array($intent,array_column($data['services'],'id'),true)) { $event=sanitize_key($r->get_param('event') ?? 'search_success'); if (!in_array($event,['search_success','email_generated','official_link_click'],true)) { $event='search_success'; } Analytics::count($event,$intent); return rest_ensure_response(['counted'=>true]); }
                if ($intent==='unknown') { Analytics::count('search_zero_result','unknown'); return rest_ensure_response(['counted'=>true]); }
                $view=sanitize_key($r->get_param('view') ?? '');
                if (in_array($view,['public','router'],true)) { unset($data['answers'],$data['faqs'],$data['guides'],$data['organizations']); }if($view==='router'){$data['aliases']=[];}
                if ($view==='public') { $data['services']=array_slice($data['services'],0,40);$data['aliases']=[];$data['intents']=[]; }
                return rest_ensure_response($data);
            }]);
            register_rest_route('oberhub/v1','/search',['methods'=>['GET','POST'],'permission_callback'=>'__return_true','callback'=>[self::class,'search']]);
            // Optional generation is admin-only in this MVP. Public journeys remain free and deterministic.
            register_rest_route('oberhub/v1','/answer',['methods'=>'POST','permission_callback'=>fn()=>current_user_can('manage_options'),'callback'=>[self::class,'answer']]);
        });
    }
    public static function search($req) {
        $query=sanitize_text_field((string)($req->get_param('q') ?? ''));
        if(strlen($query)>600){return new \WP_Error('query_length','Query too long',['status'=>400]);}
        $lang=sanitize_key($req->get_param('lang') ?? 'de');if(!in_array($lang,['de','en','ru','uk'],true))$lang='de';
        // Short-lived salted buckets; no raw IP, query or personal data is retained.
        $identity=hash_hmac('sha256',(string)($_SERVER['REMOTE_ADDR']??'unknown'),wp_salt('nonce'));
        $bucket='oh_search_'.substr($identity,0,24).'_'.(int)floor(time()/60);$count=(int)get_transient($bucket);
        if($count>=90){return new \WP_Error('rate_limit','Please retry in one minute',['status'=>429]);}set_transient($bucket,$count+1,70);
        $result=Knowledge::search($query,$lang,(int)($req->get_param('page')??1),(int)($req->get_param('per_page')??10),sanitize_key($req->get_param('locality')??'all'),sanitize_key($req->get_param('type')??''));
        $response=rest_ensure_response($result);$response->header('Cache-Control','no-store');return $response;
    }
    public static function answer($req) {
        $question=sanitize_textarea_field($req->get_param('question') ?? ''); if (strlen($question)>2000) { return new \WP_Error('length','Question too long',['status'=>400]); }
        $ids=array_slice(array_map('sanitize_key',(array)$req->get_param('ids')),0,5);
        $data=dataset();$sources=array_column($data['sources'],null,'source_id');
        $context=array_values(array_filter($data['services'],fn($s)=>in_array($s['id'],$ids,true) && in_array($s['source_trust_level'] ?? '',['A','B'],true) && ($sources[$s['source_id']]['review_status'] ?? '')==='checked' && strtotime(($s['source_checked_at'] ?? '1970-01-01').' +'.($sources[$s['source_id']]['ttl_days'] ?? 30).' days')>=time()));
        $provider=(defined('OBERHUB_AI_ENDPOINT') && defined('OBERHUB_AI_KEY') && defined('OBERHUB_AI_MODEL')) ? new AI\OpenAICompatibleProvider(OBERHUB_AI_ENDPOINT,OBERHUB_AI_KEY,OBERHUB_AI_MODEL) : new AI\NullProvider();
        nocache_headers();return rest_ensure_response($provider->answer($question,$context));
    }
}
