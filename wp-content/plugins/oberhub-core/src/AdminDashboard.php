<?php
namespace OberHub;
final class AdminDashboard {
    public static function boot(): void {
        add_action('admin_menu',function(){add_menu_page('Oberriet Hub','Oberriet Hub','manage_options','oberhub',[self::class,'page'],'dashicons-location-alt',25);});
        add_action('admin_post_oh_import',[self::class,'upload']);add_action('admin_post_oh_export',[self::class,'export']);add_action('admin_post_oh_settings',[self::class,'settings']);
        add_action('admin_notices',function(){
            $e=get_transient('oh_edit_error_'.get_current_user_id());if ($e) { echo '<div class="notice notice-error"><p>'.esc_html($e).'</p></div>';delete_transient('oh_edit_error_'.get_current_user_id()); }
        });
    }
    public static function authorize(string $action): void { if (!current_user_can('manage_options')) { wp_die('Forbidden',403); } check_admin_referer($action); }
    public static function upload(): void {
        self::authorize('oh_import');$f=$_FILES['seed'] ?? [];
        if (($f['error'] ?? 1)!==UPLOAD_ERR_OK || ($f['size'] ?? 0)>5000000 || !is_uploaded_file($f['tmp_name'])) { wp_die('Invalid upload'); }
        $d=json_decode(file_get_contents($f['tmp_name']),true);if (!is_array($d)) { wp_die('Invalid JSON'); }
        $result=import($d,!empty($_POST['overwrite']));if ($result['errors']) { wp_die(esc_html(implode('; ',$result['errors']))); }
        Analytics::audit('import',$result['imported']);wp_safe_redirect(admin_url('admin.php?page=oberhub'));exit;
    }
    public static function export(): void {
        self::authorize('oh_export');nocache_headers();header('Content-Type: application/json; charset=utf-8');header('Content-Disposition: attachment; filename="oberhub-content.json"');echo wp_json_encode(dataset(),JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);exit;
    }
    public static function settings(): void {
        self::authorize('oh_settings');$identity=[];
        foreach (['operator','address','email','hosting_provider','hosting_country'] as $key) { $identity[$key]=$key==='email' ? sanitize_email(wp_unslash($_POST[$key] ?? '')) : sanitize_text_field(wp_unslash($_POST[$key] ?? '')); }
        $d=dataset();$d['intents']=json_decode(wp_unslash($_POST['intents'] ?? '[]'),true);$d['waste']=json_decode(wp_unslash($_POST['waste'] ?? '[]'),true);
        if (!is_array($d['intents']) || !is_array($d['waste'])) { wp_die('Invalid JSON settings'); }
        $errors=Sources::validate_dataset($d);if ($errors) { wp_die(esc_html(implode('; ',$errors))); }
        update_option('oh_operator',$identity,false);update_option('oh_intents',$d['intents'],false);update_option('oh_waste',$d['waste'],false);
        update_option('oh_hsts',!empty($_POST['hsts']) && is_ssl());Analytics::audit('settings_updated');wp_safe_redirect(admin_url('admin.php?page=oberhub'));exit;
    }
    public static function page(): void {
        if (!current_user_can('manage_options')) { return; }global $wpdb;$d=dataset();$stale=0;$missing=0;$review=0;
        foreach ($d['services'] as $s) { foreach (['en','ru','uk'] as $l) { if (empty($s['title'][$l])) { $missing++; } if (($s['translation_status'][$l] ?? '')==='stale') { $stale++; } } }
        foreach ($d['sources'] as $s) { if (($s['review_status'] ?? '')!=='checked' || strtotime($s['last_checked'].' +'.($s['ttl_days'] ?? 30).' days')<time()) { $review++; } }
        $twofactor=class_exists('Two_Factor_Core') && \Two_Factor_Core::is_user_using_two_factor(get_current_user_id());
        echo '<div class="wrap"><h1>Oberriet Hub · Content health</h1><p>Independent civic navigation · DE editorial source · Europe/Zurich</p><p><strong>Admin 2FA: '.($twofactor ? 'configured' : 'SETUP REQUIRED — open your user profile and configure TOTP + recovery codes').'</strong></p><div style="display:flex;gap:24px;flex-wrap:wrap">';
        foreach (['services','contacts','events','sources'] as $k) { echo '<div><h2>'.esc_html(ucfirst($k)).'</h2><p>'.count($d[$k]).'</p></div>'; }
        echo '</div><p>Sources needing review: '.(int)$review.' · Missing translations: '.(int)$missing.' · Stale translations: '.(int)$stale.'</p><p>AI: '.(defined('OBERHUB_AI_KEY') ? 'optional administrator preview' : 'free deterministic mode').'. Raw questions are never recorded.</p>';
        echo '<h2>Aggregate navigation / search intents (last 7 days)</h2><table class="widefat"><thead><tr><th>Event</th><th>Canonical intent / language</th><th>Total</th></tr></thead><tbody>';
        $rows=$wpdb->get_results($wpdb->prepare("SELECT event,dimension,SUM(total) AS n FROM {$wpdb->prefix}oh_metrics WHERE day >= %s GROUP BY event,dimension ORDER BY n DESC LIMIT 50",wp_date('Y-m-d',time()-7*DAY_IN_SECONDS)));
        foreach ($rows as $r) { echo '<tr><td>'.esc_html($r->event).'</td><td>'.esc_html($r->dimension).'</td><td>'.(int)$r->n.'</td></tr>'; } echo '</tbody></table><p>Only fixed intent IDs are counted; user-entered queries, names, addresses and drafts are not stored.</p>';
        echo '<h2>Import / export</h2><form method="post" enctype="multipart/form-data" action="'.esc_url(admin_url('admin-post.php')).'"><input type="hidden" name="action" value="oh_import">';wp_nonce_field('oh_import');echo '<label>Seed JSON <input type="file" name="seed" accept="application/json,.json" required></label> <label><input type="checkbox" name="overwrite" value="1"> Overwrite matching IDs</label> <button class="button">Import curated content</button></form><p><a class="button" href="'.esc_url(wp_nonce_url(admin_url('admin-post.php?action=oh_export'),'oh_export')).'">Export all curated content</a></p>';
        $o=get_option('oh_operator',[]);echo '<h2>Operator / synonyms / waste</h2><form method="post" action="'.esc_url(admin_url('admin-post.php')).'"><input type="hidden" name="action" value="oh_settings">';wp_nonce_field('oh_settings');
        foreach (['operator','address','email','hosting_provider','hosting_country'] as $k) { echo '<p><label>'.esc_html(ucfirst($k)).' <input name="'.esc_attr($k).'" value="'.esc_attr($o[$k] ?? '').'" class="regular-text"></label></p>'; }
        foreach (['intents','waste'] as $k) { echo '<p><label>'.esc_html(ucfirst($k)).' JSON<textarea name="'.esc_attr($k).'" style="display:block;width:100%;height:160px">'.esc_textarea(wp_json_encode($d[$k],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)).'</textarea></label></p>'; }
        echo '<label><input type="checkbox" name="hsts" value="1" '.checked(get_option('oh_hsts'),true,false).'> Enable HSTS after stable HTTPS</label><p><button class="button button-primary">Save settings</button></p></form>';
        echo '<h2>Private technical / audit logs</h2><p>Requests: 7 days · Security: 30 days · Admin audit: 180 days · Aggregates: 13 months. Host logs and backups require matching host settings.</p><table class="widefat"><thead><tr><th>UTC</th><th>Type</th><th>IP</th><th>Method</th><th>Path</th><th>Status</th></tr></thead><tbody>';
        foreach ($wpdb->get_results("SELECT created_at,kind,ip,method,path,status FROM {$wpdb->prefix}oh_logs ORDER BY id DESC LIMIT 30") as $r) { echo '<tr>';foreach (get_object_vars($r) as $v) { echo '<td>'.esc_html($v).'</td>'; }echo '</tr>'; }echo '</tbody></table></div>';
    }
}
