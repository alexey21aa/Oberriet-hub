<?php
namespace OberHub;
final class Calendar {
    public static function boot(): void {
        add_action('rest_api_init',function(){register_rest_route('oberhub/v1','/calendar',['methods'=>'GET','permission_callback'=>'__return_true','callback'=>function($r){
            $locality=sanitize_key($r->get_param('locality') ?? 'all');if (!in_array($locality,['all','oberriet','montlingen','kriessern','eichenwies','kobelwald'],true)) { return new \WP_Error('locality','Invalid locality',['status'=>400]); }nocache_headers();Analytics::count('calendar_filter',$locality);return rest_ensure_response(array_values(array_filter(records('event'),fn($e)=>($locality==='all' || $e['locality']===$locality) && ($e['end_date'] ?? $e['date'])>=wp_date('Y-m-d'))));
        }]);});
    }
}
