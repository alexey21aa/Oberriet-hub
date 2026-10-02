<?php
namespace OberHub;
final class Security {
    public static function boot(): void {
        add_filter('xmlrpc_enabled','__return_false');add_filter('xmlrpc_methods',fn()=>[]);add_filter('wp_headers',[self::class,'headers']);
        add_filter('rest_endpoints',function($e){ if (!current_user_can('list_users')) { unset($e['/wp/v2/users'],$e['/wp/v2/users/(?P<id>[\\d]+)']); } return $e; });
        add_filter('the_generator','__return_empty_string');add_filter('pings_open','__return_false');add_filter('comments_open','__return_false');
        add_action('template_redirect',function(){ if (is_author()) { global $wp_query;$wp_query->set_404();status_header(404); } });
        // Enforce TOTP/recovery-code setup for administrators before public operation.
        add_filter('two_factor_enabled_providers_for_user',function($providers,$uid){ if (user_can($uid,'manage_options')) { $providers=['Two_Factor_Totp','Two_Factor_Backup_Codes']; } return $providers; },10,2);
        add_filter('two_factor_user_api_login_enable','__return_false');
        add_action('admin_init',function(){
            if (!current_user_can('manage_options') || !get_option('blog_public') || wp_doing_ajax()) { return; }
            global $pagenow;
            if (!in_array($pagenow,['profile.php','admin-post.php'],true) && (!class_exists('Two_Factor_Core') || !\Two_Factor_Core::is_user_using_two_factor(get_current_user_id()))) { wp_safe_redirect(admin_url('profile.php?oh_2fa=required'));exit; }
        });
        add_filter('authenticate',function($user){
            $key=self::login_key();$n=(int)get_transient($key);
            return $n>=5 ? new \WP_Error('oh_limited','Too many attempts. Try again in 15 minutes.') : $user;
        },99);
        add_action('wp_login_failed',function(){ $key=self::login_key();set_transient($key,(int)get_transient($key)+1,15*MINUTE_IN_SECONDS); });
        add_action('wp_login',function(){ delete_transient(self::login_key()); });
    }
    public static function login_key(): string { return 'oh_login_'.hash_hmac('sha256',$_SERVER['REMOTE_ADDR'] ?? '',wp_salt()); }
    public static function headers(array $h): array {
        $h['X-Content-Type-Options']='nosniff';$h['Referrer-Policy']='strict-origin-when-cross-origin';$h['Permissions-Policy']='camera=(), microphone=(), geolocation=()';$h['X-Frame-Options']='SAMEORIGIN';
        if (!is_admin()) { $h['Content-Security-Policy']="default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data:; connect-src 'self'; font-src 'self'; frame-ancestors 'self'; base-uri 'self'; form-action 'self'; object-src 'none'"; }
        if (is_ssl() && get_option('oh_hsts',false)) { $h['Strict-Transport-Security']='max-age=31536000'; }
        return $h;
    }
}
