<?php
namespace OberHub;
final class Analytics {
    public static function install(): void {
        global $wpdb;require_once ABSPATH.'wp-admin/includes/upgrade.php';$c=$wpdb->get_charset_collate();
        dbDelta("CREATE TABLE {$wpdb->prefix}oh_metrics (id bigint unsigned NOT NULL AUTO_INCREMENT, day date NOT NULL, event varchar(40) NOT NULL, dimension varchar(80) NOT NULL DEFAULT '', total bigint NOT NULL DEFAULT 0, PRIMARY KEY (id), UNIQUE KEY bucket (day,event,dimension)) $c;");
        dbDelta("CREATE TABLE {$wpdb->prefix}oh_logs (id bigint unsigned NOT NULL AUTO_INCREMENT, created_at datetime NOT NULL, kind varchar(20) NOT NULL, ip varchar(45) NOT NULL DEFAULT '', method varchar(12) NOT NULL DEFAULT '', path varchar(200) NOT NULL DEFAULT '', status smallint NOT NULL DEFAULT 0, user_agent varchar(200) NOT NULL DEFAULT '', referrer varchar(180) NOT NULL DEFAULT '', actor bigint NOT NULL DEFAULT 0, PRIMARY KEY (id), KEY retention (kind,created_at)) $c;");
    }
    public static function boot(): void {
        add_action('shutdown',[self::class,'request']);
        add_action('wp_login',fn($login,$user)=>self::audit('login',(int)$user->ID),10,2);
        add_action('wp_login_failed',fn()=>self::audit('login_failed',0,'security'));
    }
    public static function count(string $event,string $dimension=''): void {
        global $wpdb;$allowed=['page_view','search_success','search_zero_result','official_link_click','calendar_filter','waste_search','language_switch','email_generated'];
        if (!in_array($event,$allowed,true)) { return; }
        $wpdb->query($wpdb->prepare("INSERT INTO {$wpdb->prefix}oh_metrics (day,event,dimension,total) VALUES (%s,%s,%s,1) ON DUPLICATE KEY UPDATE total=total+1",wp_date('Y-m-d'),$event,substr(sanitize_key($dimension),0,80)));
    }
    public static function audit(string $action,int $object=0,string $kind='audit'): void {
        global $wpdb;$wpdb->insert($wpdb->prefix.'oh_logs',['created_at'=>gmdate('Y-m-d H:i:s'),'kind'=>$kind,'path'=>sanitize_key($action).'/'.$object,'actor'=>get_current_user_id()],['%s','%s','%s','%d']);
    }
    public static function request(): void {
        if (defined('WP_CLI') && WP_CLI || wp_doing_cron()) { return; } global $wpdb;
        // HTTP query strings, bodies, cookies and auth headers are never copied.
        $ip=$_SERVER['REMOTE_ADDR'] ?? ''; $ip=filter_var($ip,FILTER_VALIDATE_IP) ? $ip : '';
        $path=wp_parse_url($_SERVER['REQUEST_URI'] ?? '/',PHP_URL_PATH) ?: '/';
        // Unknown arbitrary paths may contain personal data. Log a category instead.
        $known=['/','/wp-login.php','/wp-json/oberhub/v1/index','/wp-json/oberhub/v1/calendar','/wp-json/oberhub/v1/answer'];
        if (strpos($path,'/wp-admin/')===0) { $path='/wp-admin/[private]'; }
        elseif (!in_array($path,$known,true) && !(Frontend::is_hub() && Frontend::valid() && $path=== '/'.Frontend::lang().'/'.(Frontend::path() ? Frontend::path().'/' : ''))) { $path='/[other]'; }
        $wpdb->insert($wpdb->prefix.'oh_logs',['created_at'=>gmdate('Y-m-d H:i:s'),'kind'=>'request','ip'=>$ip,'method'=>substr(sanitize_key($_SERVER['REQUEST_METHOD'] ?? ''),0,12),'path'=>substr($path,0,200),'status'=>http_response_code() ?: 200,'user_agent'=>substr(sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? ''),0,200),'referrer'=>substr(sanitize_text_field(wp_parse_url($_SERVER['HTTP_REFERER'] ?? '',PHP_URL_HOST) ?: ''),0,180)],['%s','%s','%s','%s','%s','%d','%s','%s']);
    }
    public static function purge(): void {
        global $wpdb;
        foreach (['request'=>7,'security'=>30,'audit'=>180] as $kind=>$days) { $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}oh_logs WHERE kind=%s AND created_at < %s",$kind,gmdate('Y-m-d H:i:s',time()-$days*DAY_IN_SECONDS))); }
        $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->prefix}oh_metrics WHERE day < %s",gmdate('Y-m-d',strtotime('-13 months'))));
    }
}
