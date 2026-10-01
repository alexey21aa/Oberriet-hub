<?php
namespace OberHub;
final class Search {
    public static function boot(): void {
        add_action('rest_api_init',function(){
            register_rest_route('oberhub/v1','/index',['methods'=>'GET','permission_callback'=>'__return_true','callback'=>function($r){
                $event=sanitize_key($r->get_param('event') ?? '');$dimension=sanitize_key($r->get_param('dimension') ?? '');
                if ($event==='language_switch' && in_array($dimension,['de','en','ru','uk'],true)) { nocache_headers();Analytics::count($event,$dimension);return rest_ensure_response(['counted'=>true]); }
                $data=dataset();$intent=sanitize_key($r->get_param('intent') ?? '');
                if ($intent) { nocache_headers(); }
                if ($intent && in_array($intent,array_column($data['services'],'id'),true)) { $event=sanitize_key($r->get_param('event') ?? 'search_success'); if (!in_array($event,['search_success','email_generated','official_link_click'],true)) { $event='search_success'; } Analytics::count($event,$intent); return rest_ensure_response(['counted'=>true]); }
                if ($intent==='unknown') { Analytics::count('search_zero_result','unknown'); return rest_ensure_response(['counted'=>true]); }
                return rest_ensure_response($data);
            }]);
            // Optional generation is admin-only in this MVP. Public journeys remain free and deterministic.
            register_rest_route('oberhub/v1','/answer',['methods'=>'POST','permission_callback'=>fn()=>current_user_can('manage_options'),'callback'=>[self::class,'answer']]);
        });
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
