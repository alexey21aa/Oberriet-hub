<?php
namespace OberHub;
final class AdminDashboard {
    public static function boot(): void {
        add_action('admin_menu',function(){add_menu_page('Oberriet Hub','Oberriet Hub','manage_options','oberhub',[self::class,'page'],'dashicons-location-alt',25);});
        add_action('admin_post_oh_review',[self::class,'review']);add_action('admin_post_oh_links',[self::class,'links']);
        add_action('admin_post_oh_import',[self::class,'upload']);add_action('admin_post_oh_export',[self::class,'export']);add_action('admin_post_oh_settings',[self::class,'settings']);
        add_action('admin_notices',function(){
            $message=get_transient('oh_admin_message_'.get_current_user_id());if ($message) { echo '<div class="notice notice-success"><p>'.esc_html($message).'</p></div>';delete_transient('oh_admin_message_'.get_current_user_id()); }
            $e=get_transient('oh_edit_error_'.get_current_user_id());if ($e) { echo '<div class="notice notice-error"><p>'.esc_html($e).'</p></div>';delete_transient('oh_edit_error_'.get_current_user_id()); }
        });
    }
    public static function authorize(string $action): void { if (!current_user_can('manage_options')) { wp_die('Forbidden','',['response'=>403]); } check_admin_referer($action); }
    public static function upload(): void {
        self::authorize('oh_import');$f=$_FILES['seed'] ?? [];
        if (($f['error'] ?? 1)!==UPLOAD_ERR_OK || ($f['size'] ?? 0)>30000000 || !is_uploaded_file($f['tmp_name'])) { wp_die('Invalid upload'); }
        $raw=file_get_contents($f['tmp_name']);
        $d=strtolower(pathinfo($f['name'] ?? '',PATHINFO_EXTENSION))==='csv' ? self::parse_csv($raw) : json_decode($raw,true);
        if (!is_array($d)) { wp_die('Invalid JSON or CSV'); }
        $result=ImportQueue::enqueue($d,!empty($_POST['overwrite']));if ($result['errors']) { wp_die(esc_html(implode('; ',$result['errors']))); }
        Analytics::audit('import_queued',$result['queued']);wp_safe_redirect(admin_url('admin.php?page=oberhub'));exit;
    }
    public static function export(): void {
        self::authorize('oh_export');nocache_headers();
        if (($_GET['format'] ?? '')==='csv') { header('Content-Type: text/csv; charset=utf-8');header('Content-Disposition: attachment; filename=oberhub-content.csv');$out=fopen('php://output','w');fputcsv($out,['collection','id','record_json']);foreach(dataset() as $key=>$rows) { if (!is_array($rows) || !array_is_list($rows)) { continue; } foreach($rows as $row) { if (!is_array($row)) { continue; } fputcsv($out,[$key,self::csv_id((string)($row['id'] ?? $row['source_id'] ?? $row['service_id'] ?? '')),wp_json_encode($row,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)]); } } fclose($out);exit; }
        header('Content-Type: application/json; charset=utf-8');header('Content-Disposition: attachment; filename="oberhub-content.json"');echo wp_json_encode(dataset(),JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);exit;
    }
    public static function settings(): void {
        self::authorize('oh_settings');$identity=[];
        foreach (['operator','organization','responsible_person','address','phone','email','hosting_provider','hosting_country'] as $key) { $identity[$key]=$key==='email' ? sanitize_email(wp_unslash($_POST[$key] ?? '')) : sanitize_text_field(wp_unslash($_POST[$key] ?? '')); }
        $d=dataset();$d['intents']=isset($_POST['intents'])?json_decode(wp_unslash($_POST['intents']),true):$d['intents'];$d['waste']=isset($_POST['waste'])?json_decode(wp_unslash($_POST['waste']),true):$d['waste'];
        if (!is_array($d['intents']) || !is_array($d['waste'])) { wp_die('Invalid JSON settings'); }
        $errors=Sources::validate_dataset($d);if ($errors) { wp_die(esc_html(implode('; ',$errors))); }
        update_option('oh_operator',$identity,false);update_option('oh_intents',$d['intents'],false);update_option('oh_waste',$d['waste'],false);
        update_option('oh_hsts',!empty($_POST['hsts']) && is_ssl());Analytics::audit('settings_updated');wp_safe_redirect(admin_url('admin.php?page=oberhub'));exit;
    }
    public static function csv_id(string $id): string { return preg_match('/^[=+@-]/',$id) ? "'".$id : $id; }
    public static function parse_csv(string $raw): ?array {
        $f=fopen('php://temp','r+');fwrite($f,$raw);rewind($f);$header=fgetcsv($f);
        if ($header!==['collection','id','record_json']) { fclose($f);return null; }
        $d=[];$allowed=['services','contacts','organizations','answers','faqs','guides','events','places','sources','waste','intents','aliases'];$n=0;
        while (($cells=fgetcsv($f))!==false) {
            if ($cells===[null]) { continue; }
            if (count($cells)!==3 || !in_array($cells[0],$allowed,true) || ++$n>100000) { fclose($f);return null; }
            if (preg_match("/^'[=+@-]/",$cells[1])) { $cells[1]=substr($cells[1],1); }
            $row=json_decode($cells[2],true);
            if (!is_array($row) || (string)($row['id'] ?? $row['source_id'] ?? $row['service_id'] ?? '')!==$cells[1]) { fclose($f);return null; }
            $d[$cells[0]][]=$row;
        }
        fclose($f);return $d;
    }
    public static function links(): void {
        self::authorize('oh_links');$n=Sources::check_links(5,true);Analytics::audit('source_links_checked',$n);
        set_transient('oh_admin_message_'.get_current_user_id(),'Checked '.$n.' approved source links; inspect Sources for HTTP and review status.',60);
        wp_safe_redirect(admin_url('admin.php?page=oberhub'));exit;
    }
    public static function review(): void {
        self::authorize('oh_review');$type=sanitize_key(wp_unslash($_POST['record_type'] ?? ''));
        if (!in_array($type,['service','contact','organization','answer','faq','guide','event','place','source'],true)) { wp_die('Invalid record type'); }
        $ids=array_filter(array_map('absint',explode(',',wp_unslash($_POST['record_ids'] ?? ''))));
        if (!$ids || count($ids)>100) { wp_die('Provide between 1 and 100 WordPress record IDs.'); }
        $action=sanitize_key(wp_unslash($_POST['record_action'] ?? ''));if (!in_array($action,['publish','draft','needs_review','checked'],true)) { wp_die('Invalid review action'); }
        $n=0;
        foreach ($ids as $pid) {
            if (get_post_type($pid)!=='oh_'.$type || !current_user_can('edit_post',$pid)) { continue; }
            if (in_array($action,['publish','draft'],true)) { wp_update_post(['ID'=>$pid,'post_status'=>$action]); }
            else { $r=get_post_meta($pid,'_oh_record',true);if (!is_array($r)) { continue; }$r['review_status']=$action;$r['status']=$action;update_post_meta($pid,'_oh_record',$r); }
            $n++;
        }
        if (class_exists(__NAMESPACE__.'\\Knowledge')) { update_option('oh_knowledge_dirty',1,false); }
        Analytics::audit('bulk_content_review',$n);wp_safe_redirect(admin_url('admin.php?page=oberhub'));exit;
    }
    private static function review_form(): void {
        echo '<h2>Review / publishing / source monitoring</h2><p>Edit records in the left menu. WordPress Trash provides reversible deletion. Bulk actions apply only to the supplied WordPress IDs and selected content type.</p><form method="post" action="'.esc_url(admin_url('admin-post.php')).'"><input type="hidden" name="action" value="oh_review">';wp_nonce_field('oh_review');
        echo '<select name="record_type">';foreach (['service','contact','organization','answer','faq','guide','event','place','source'] as $type) { echo '<option value="'.esc_attr($type).'">'.esc_html(ucfirst($type)).'</option>'; }echo '</select> <label>Record IDs <input name="record_ids" required placeholder="123,124,125"></label> <select name="record_action"><option value="needs_review">Needs review</option><option value="checked">Editorial review checked</option><option value="draft">Unpublish to draft</option><option value="publish">Publish</option></select> <button class="button">Apply</button></form><form method="post" action="'.esc_url(admin_url('admin-post.php')).'"><input type="hidden" name="action" value="oh_links">';wp_nonce_field('oh_links');echo '<p><button class="button">Check next 5 approved source links</button></p></form>';
    }
    public static function page(): void {
        if (!current_user_can('manage_options')) { return; }global $wpdb;$d=dataset();$stale=0;$missing=0;$review=0;
        foreach ($d['services'] as $s) { foreach (['en','ru','uk'] as $l) { if (empty($s['title'][$l])) { $missing++; } if (($s['translation_status'][$l] ?? '')==='stale') { $stale++; } } }
        foreach ($d['sources'] as $s) { if (($s['review_status'] ?? '')!=='checked' || strtotime(($s['last_checked'] ?? '1970-01-01').' +'.($s['ttl_days'] ?? 30).' days')<time()) { $review++; } }
        $twofactor=class_exists('Two_Factor_Core') && \Two_Factor_Core::is_user_using_two_factor(get_current_user_id());
        echo '<div class="wrap"><h1>Oberriet Hub · Content health</h1><p>Independent civic navigation · DE editorial source · Europe/Zurich</p><p><strong>Admin 2FA: '.($twofactor ? 'configured' : 'SETUP REQUIRED — open your user profile and configure TOTP + recovery codes').'</strong></p><div style="display:flex;gap:24px;flex-wrap:wrap">';
        ImportQueue::render();
        foreach (['services','contacts','organizations','answers','faqs','guides','events','sources'] as $k) { echo '<div><h2>'.esc_html(ucfirst($k)).'</h2><p>'.count($d[$k] ?? []).'</p></div>'; }
        echo '</div><p>Sources needing review: '.(int)$review.' · Missing translations: '.(int)$missing.' · Stale translations: '.(int)$stale.'</p><p>AI: '.(defined('OBERHUB_AI_KEY') ? 'optional administrator preview' : 'free deterministic mode').'. Raw questions are never recorded.</p>';
        echo '<h2>Aggregate navigation / search intents (last 7 days)</h2><table class="widefat"><thead><tr><th>Event</th><th>Canonical intent / language</th><th>Total</th></tr></thead><tbody>';
        $rows=$wpdb->get_results($wpdb->prepare("SELECT event,dimension,SUM(total) AS n FROM {$wpdb->prefix}oh_metrics WHERE day >= %s GROUP BY event,dimension ORDER BY n DESC LIMIT 50",wp_date('Y-m-d',time()-7*DAY_IN_SECONDS)));
        foreach ($rows as $r) { echo '<tr><td>'.esc_html($r->event).'</td><td>'.esc_html($r->dimension).'</td><td>'.(int)$r->n.'</td></tr>'; } echo '</tbody></table><p>Only fixed intent IDs are counted; user-entered queries, names, addresses and drafts are not stored.</p>';
        echo '<h2>Import / export</h2><form method="post" enctype="multipart/form-data" action="'.esc_url(admin_url('admin-post.php')).'"><input type="hidden" name="action" value="oh_import">';wp_nonce_field('oh_import');echo '<label>Seed JSON / CSV (30 MB maximum) <input type="file" name="seed" accept="application/json,text/csv,.json,.csv" required></label> <label><input type="checkbox" name="overwrite" value="1"> Overwrite matching IDs</label> <button class="button">Import curated content</button></form><p><a class="button" href="'.esc_url(wp_nonce_url(admin_url('admin-post.php?action=oh_export'),'oh_export')).'">Export all curated content</a> <a class="button" href="'.esc_url(wp_nonce_url(admin_url('admin-post.php?action=oh_export&format=csv'),'oh_export')).'">Export CSV</a></p><p>CSV columns: collection, id, record_json. Nested translations and provenance stay in the JSON record cell. Import validates every row before writing.</p>';
        self::review_form();
        $o=get_option('oh_operator',[]);echo '<h2>Operator / synonyms / waste</h2><form method="post" action="'.esc_url(admin_url('admin-post.php')).'"><input type="hidden" name="action" value="oh_settings">';wp_nonce_field('oh_settings');
        foreach (['operator','organization','responsible_person','address','phone','email','hosting_provider','hosting_country'] as $k) { echo '<p><label>'.esc_html(ucfirst($k)).' <input name="'.esc_attr($k).'" value="'.esc_attr($o[$k] ?? '').'" class="regular-text"></label></p>'; }
        foreach (['intents','waste'] as $k) { if($k==='intents'&&count($d[$k])>100){echo '<p>Large search dictionaries: '.count($d[$k]).' intents. Use validated JSON/CSV import and export for batch editing; service-specific synonyms remain editable in Services.</p>';continue;}echo '<p><label>'.esc_html(ucfirst($k)).' JSON<textarea name="'.esc_attr($k).'" style="display:block;width:100%;height:160px">'.esc_textarea(wp_json_encode($d[$k],JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)).'</textarea></label></p>'; }
        echo '<label><input type="checkbox" name="hsts" value="1" '.checked(get_option('oh_hsts'),true,false).'> Enable HSTS after stable HTTPS</label><p><button class="button button-primary">Save settings</button></p></form>';
        echo '<h2>Private technical / audit logs</h2><p>Requests: 7 days · Security: 30 days · Admin audit: 180 days · Aggregates: 13 months. Host logs and backups require matching host settings.</p><table class="widefat"><thead><tr><th>UTC</th><th>Type</th><th>IP</th><th>Method</th><th>Path</th><th>Status</th></tr></thead><tbody>';
        foreach ($wpdb->get_results("SELECT created_at,kind,ip,method,path,status FROM {$wpdb->prefix}oh_logs ORDER BY id DESC LIMIT 30") as $r) { echo '<tr>';foreach (get_object_vars($r) as $v) { echo '<td>'.esc_html($v).'</td>'; }echo '</tr>'; }echo '</tbody></table></div>';
    }
}
