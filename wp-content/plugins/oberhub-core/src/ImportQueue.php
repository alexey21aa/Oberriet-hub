<?php
namespace OberHub;
/** Resumable private import. Options are non-autoloaded and never exposed by REST. */
final class ImportQueue {
    const STATE='oh_import_queue';
    const TYPES=['sources'=>'source','services'=>'service','contacts'=>'contact','organizations'=>'organization','answers'=>'answer','faqs'=>'faq','guides'=>'guide','events'=>'event','places'=>'place'];
    public static function boot(): void {
        add_action('wp_ajax_oh_import_batch',[self::class,'ajax']);
        add_action('admin_enqueue_scripts',function($hook){
            if ($hook!=='toplevel_page_oberhub' || !current_user_can('manage_options')) { return; }
            wp_enqueue_script('oh-import-queue',OBERHUB_URL.'assets/admin-import.js',[], '0.2.0',true);
            wp_localize_script('oh-import-queue','ohImportQueue',['url'=>admin_url('admin-ajax.php'),'nonce'=>wp_create_nonce('oh_import_batch')]);
        });
        add_action('admin_notices',function(){
            if (current_user_can('manage_options') && self::active()) { echo '<div class="notice notice-warning"><p>Oberriet Hub content import is pending. <a href="'.esc_url(admin_url('admin.php?page=oberhub')).'">Open the dashboard to continue safely in batches</a>.</p></div>'; }
        });
    }
    public static function status(): array { $state=get_option(self::STATE,[]);return is_array($state) ? array_intersect_key($state,array_flip(['stage','total','processed','imported','skipped','started_at','completed_at','error'])) : []; }
    public static function active(): bool { return in_array(self::status()['stage'] ?? '',['records','metadata','index','error'],true); }
    public static function enqueue(array $data,bool $overwrite=false): array {
        if (self::active()) { return ['errors'=>['An import is already pending; finish it before uploading another file.']]; }
        $errors=Sources::validate_dataset($data);if ($errors) { return ['errors'=>$errors]; }
        $token=bin2hex(random_bytes(12));$chunks=[];$total=0;
        foreach (self::TYPES as $collection=>$type) {
            foreach (array_chunk($data[$collection] ?? [],25) as $rows) {
                $key='oh_import_chunk_'.$token.'_'.count($chunks);
                if (!add_option($key,['type'=>$type,'rows'=>$rows],'',false)) { foreach ($chunks as $stored) { delete_option($stored); }return ['errors'=>['Could not store private import queue.']]; }
                $chunks[]=$key;$total+=count($rows);
            }
        }
        $metadata=array_intersect_key($data,array_flip(['waste','intents','aliases']));$metadataKey='oh_import_meta_'.$token;
        if (!add_option($metadataKey,$metadata,'',false)) { foreach ($chunks as $stored) { delete_option($stored); }return ['errors'=>['Could not store import metadata.']]; }
        $state=['stage'=>$chunks ? 'records' : 'metadata','chunks'=>$chunks,'chunk'=>0,'offset'=>0,'metadata'=>$metadataKey,'overwrite'=>$overwrite,'total'=>$total,'processed'=>0,'imported'=>0,'skipped'=>0,'started_at'=>gmdate('c'),'error'=>''];
        update_option(self::STATE,$state,false);return ['errors'=>[],'queued'=>$total];
    }
    public static function metadata(string $key,array $rows,bool $overwrite): array {
        $existing=get_option('oh_'.$key,[]);if (!is_array($existing)) { $existing=[]; }
        if ($overwrite || !$existing) { return $rows; }
        if ($key==='waste') { return $existing; }
        $merged=$existing;$seen=[];
        foreach ($existing as $row) { $seen[self::metadata_key($key,$row)]=true; }
        foreach ($rows as $row) { $id=self::metadata_key($key,$row);if (!isset($seen[$id])) { $merged[]=$row;$seen[$id]=true; } }
        return $merged;
    }
    private static function metadata_key(string $key,array $row): string {
        if ($key==='aliases') { return wp_json_encode([$row['service_id'] ?? '',$row['language'] ?? '',$row['phrase'] ?? ''],JSON_UNESCAPED_UNICODE); }
        $id=(string)($row['id'] ?? $row['service_id'] ?? $row['target_id'] ?? '');
        // Version 0.1 used service IDs directly; 0.2 names those curated intents original-ID.
        if($key==='intents' && empty($row['service_id']) && empty($row['target_id']) && !str_starts_with($id,'original-'))return 'original-'.$id;
        return $id;
    }
    public static function ajax(): void {
        if (!current_user_can('manage_options')) { wp_send_json_error(['message'=>'Forbidden'],403); }
        check_ajax_referer('oh_import_batch','nonce');
        try { $result=self::process(25);wp_send_json_success($result); }
        catch (\Throwable $e) { wp_send_json_error(['message'=>'Import paused. Refresh and resume; processed rows are preserved.'],500); }
    }
    public static function process(int $limit=25): array {
        if (!current_user_can('manage_options')) { throw new \RuntimeException('Forbidden'); }
        $state=get_option(self::STATE,[]);if (!self::active()) { return self::status(); }
        $lock='oh_import_lock';$expiry=(int)get_option($lock,0);
        if ($expiry && $expiry<time()) { delete_option($lock); }
        if (!add_option($lock,time()+120,'',false)) { return array_merge(self::status(),['busy'=>true]); }
        $GLOBALS['oberhub_defer_index']=true;
        try {
            if ($state['stage']==='error') { $state['stage']=$state['resume_stage'] ?? 'records';$state['error']=''; }
            $done=0;$limit=max(1,min(100,$limit));
            while ($state['stage']==='records' && $done<$limit) {
                $key=$state['chunks'][$state['chunk']] ?? null;
                if (!$key) { $state['stage']='metadata';break; }
                $chunk=get_option($key,null);
                if (!is_array($chunk)) { throw new \RuntimeException('Queue chunk is missing.'); }
                $row=$chunk['rows'][$state['offset']] ?? null;
                if (!$row) { delete_option($key);$state['chunk']++;$state['offset']=0;continue; }
                $id=(string)($row['id'] ?? $row['source_id']);$type=$chunk['type'];
                $old=get_posts(['post_type'=>'oh_'.$type,'post_status'=>'any','meta_key'=>'_oh_id','meta_value'=>$id,'numberposts'=>1]);
                if ($old && !$state['overwrite']) { $state['skipped']++; }
                else {
                    $title=$row['title'] ?? $row['authority'] ?? $row['question'] ?? $id;
                    $pid=wp_insert_post(['ID'=>$old ? $old[0]->ID : 0,'post_type'=>'oh_'.$type,'post_status'=>'publish','post_title'=>is_array($title) ? ($title['de'] ?? $id) : $title,'post_name'=>$row['slug'] ?? sanitize_title($id)],true);
                    if (is_wp_error($pid)) { throw new \RuntimeException($pid->get_error_message()); }
                    update_post_meta($pid,'_oh_id',$id);update_post_meta($pid,'_oh_record',$row);
                    foreach (['topic'=>'oh_topic','locality'=>'oh_locality','authority'=>'oh_authority'] as $field=>$tax) { if (!empty($row[$field])) { wp_set_object_terms($pid,(array)$row[$field],$tax); } }
                    $state['imported']++;
                }
                $state['offset']++;$state['processed']++;$done++;update_option(self::STATE,$state,false);
            }
            if ($state['stage']==='records' && $state['chunk']>=count($state['chunks'])) { $state['stage']='metadata'; }
            // Each phase uses a separate request; the browser shows the current phase.
            if ($state['stage']==='metadata' && $done===0) {
                $metadata=get_option($state['metadata'],null);if (!is_array($metadata)) { throw new \RuntimeException('Import metadata is missing.'); }
                foreach ($metadata as $key=>$rows) { update_option('oh_'.$key,self::metadata($key,$rows,(bool)$state['overwrite']),false); }
                delete_option($state['metadata']);$state['stage']='index';
            } elseif ($state['stage']==='index') {
                $GLOBALS['oberhub_record_cache']=[];Knowledge::install();Knowledge::rebuild(dataset());delete_option('oh_knowledge_dirty');
                foreach ($state['chunks'] as $key) { delete_option($key); }
                $state['stage']='complete';$state['completed_at']=gmdate('c');Analytics::audit('queued_import_complete',$state['imported']);
            }
            update_option(self::STATE,$state,false);return self::status();
        } catch (\Throwable $e) {
            $state['resume_stage']=$state['stage'];$state['stage']='error';$state['error']=sanitize_text_field($e->getMessage());update_option(self::STATE,$state,false);throw $e;
        } finally { delete_option($lock); }
    }
    public static function render(): void {
        $status=self::status();if (!$status) { return; }
        echo '<div id="oh-import-progress" data-active="'.(self::active() ? '1' : '0').'"><h2>Import progress</h2><p id="oh-import-status">'.esc_html(($status['stage'] ?? '').' · '.($status['processed'] ?? 0).' / '.($status['total'] ?? 0).' records').'</p><progress id="oh-import-meter" value="'.(int)($status['processed'] ?? 0).'" max="'.max(1,(int)($status['total'] ?? 0)).'"></progress><p><button type="button" class="button button-primary" id="oh-import-resume">Continue / resume import</button> <button type="button" class="button" id="oh-import-pause">Pause after this batch</button></p><p>Closing this page pauses the import. Reopen it to resume. Success is reported after all records and the search index are ready.</p></div>';
    }
}
