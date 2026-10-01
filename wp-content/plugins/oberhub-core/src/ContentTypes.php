<?php
namespace OberHub;
final class ContentTypes {
    public static function register(): void {
        foreach (['service'=>'Services','place'=>'Places','event'=>'Events','contact'=>'Contacts','source'=>'Sources','organization'=>'Organizations','answer'=>'Answers','faq'=>'FAQ','guide'=>'Guides'] as $slug=>$label) {
            register_post_type('oh_'.$slug,['label'=>$label,'public'=>false,'show_ui'=>true,'show_in_menu'=>'oberhub','show_in_rest'=>false,'supports'=>['title','revisions'],'capability_type'=>'post','map_meta_cap'=>false,'capabilities'=>array_fill_keys(['edit_post','read_post','delete_post','edit_posts','edit_others_posts','publish_posts','read_private_posts','delete_posts','delete_private_posts','delete_published_posts','delete_others_posts','edit_private_posts','edit_published_posts','create_posts'],'manage_options'),'menu_icon'=>'dashicons-location-alt']);
        }
        foreach (['oh_topic'=>'Topics','oh_locality'=>'Localities','oh_authority'=>'Authorities','oh_service_type'=>'Service types'] as $slug=>$label) { register_taxonomy($slug,['oh_service','oh_place','oh_event','oh_contact','oh_organization','oh_answer','oh_faq','oh_guide'],['label'=>$label,'public'=>false,'show_ui'=>true,'show_admin_column'=>true,'hierarchical'=>false,'capabilities'=>array_fill_keys(['manage_terms','edit_terms','delete_terms','assign_terms'],'manage_options')]); }
        foreach (['service','place','event','guide','contact','source','organization','answer','faq'] as $type) {
            add_filter('manage_oh_'.$type.'_posts_columns',[self::class,'columns']);
            add_action('manage_oh_'.$type.'_posts_custom_column',[self::class,'column'],10,2);
            add_filter('bulk_actions-edit-oh_'.$type,[self::class,'bulk_actions']);
            add_filter('handle_bulk_actions-edit-oh_'.$type,[self::class,'handle_bulk'],10,3);
        }
        add_action('add_meta_boxes',[self::class,'metabox']); add_action('save_post',[self::class,'save'],10,2);
    }
    public static function columns(array $columns): array { $columns['oh_stable_id']='Stable ID';$columns['oh_review']='Review / provenance';return $columns; }
    public static function column(string $column,int $pid): void {
        $row=get_post_meta($pid,'_oh_record',true);if (!is_array($row)) { return; }
        if ($column==='oh_stable_id') { echo esc_html($row['id'] ?? $row['source_id'] ?? ''); }
        if ($column==='oh_review') { echo esc_html(($row['review_status'] ?? $row['status'] ?? 'needs_review').' · '.($row['source_checked_at'] ?? $row['last_checked'] ?? 'unverified'));if (isset($row['http_status'])) { echo '<br>HTTP '.(int)$row['http_status']; } }
    }
    public static function bulk_actions(array $actions): array { $actions['oh_needs_review']='Mark needs review';$actions['oh_checked']='Mark editorial review checked';return $actions; }
    public static function handle_bulk(string $redirect,string $action,array $ids): string {
        if (!in_array($action,['oh_needs_review','oh_checked'],true) || !current_user_can('manage_options')) { return $redirect; }
        check_admin_referer('bulk-posts');$n=0;
        foreach (array_slice($ids,0,1000) as $pid) {
            $pid=absint($pid);if (strpos((string)get_post_type($pid),'oh_')!==0 || !current_user_can('edit_post',$pid)) { continue; }
            $row=get_post_meta($pid,'_oh_record',true);if (!is_array($row)) { continue; }$row['review_status']=$action==='oh_checked' ? 'checked' : 'needs_review';update_post_meta($pid,'_oh_record',$row);$n++;
        }
        Analytics::audit('bulk_review',$n);if (class_exists(__NAMESPACE__.'\\Knowledge')) { update_option('oh_knowledge_dirty',1,false); }
        return add_query_arg('oh_reviewed',$n,$redirect);
    }
    public static function metabox(): void { foreach (['service','place','event','guide','contact','source','organization','answer','faq'] as $t) { add_meta_box('oh_record','Curated record / translations / provenance',[self::class,'editor'],'oh_'.$t,'normal','high'); } }
    public static function editor($post): void {
        wp_nonce_field('oh_record','oh_record_nonce'); $row=get_post_meta($post->ID,'_oh_record',true) ?: [];
        echo '<p>DE is the editorial source. EN/RU/UK translations are marked stale when the German text changes. Unknown fees, deadlines and conditions must remain null. See Admin Guide for the JSON schema.</p><label for="oh-record-json">Record JSON</label><textarea id="oh-record-json" name="oh_record_json" style="width:100%;min-height:480px;font-family:monospace">'.esc_textarea(wp_json_encode($row,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)).'</textarea>';
    }
    public static function save(int $pid,$post): void {
        if (strpos($post->post_type,'oh_')!==0 || !isset($_POST['oh_record_json']) || wp_is_post_revision($pid) || wp_is_post_autosave($pid)) { return; }
        if (!current_user_can('manage_options') || !isset($_POST['oh_record_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['oh_record_nonce'])),'oh_record')) { return; }
        $row=json_decode(wp_unslash($_POST['oh_record_json']),true);
        $error=Sources::validate_record($row,substr($post->post_type,3));
        if ($error) { set_transient('oh_edit_error_'.get_current_user_id(),$error,60); return; }
        $id=(string)($row['id'] ?? $row['source_id']);
        $duplicates=get_posts(['post_type'=>$post->post_type,'post_status'=>'any','post__not_in'=>[$pid],'meta_key'=>'_oh_id','meta_value'=>$id,'numberposts'=>1]);
        if ($duplicates) { set_transient('oh_edit_error_'.get_current_user_id(),'Stable id already exists; edit that record instead.',60); return; }
        $old=get_post_meta($pid,'_oh_record',true);
        if (is_array($old) && (($old['title']['de'] ?? '')!==($row['title']['de'] ?? '') || ($old['description_short']['de'] ?? '')!==($row['description_short']['de'] ?? '') || ($old['description_full']['de'] ?? '')!==($row['description_full']['de'] ?? '') || ($old['question']['de'] ?? '')!==($row['question']['de'] ?? '') || ($old['answer']['de'] ?? '')!==($row['answer']['de'] ?? ''))) {
            foreach (['en','ru','uk'] as $l) { $row['translation_status'][$l]='stale'; }
        }
        update_post_meta($pid,'_oh_id',(string)($row['id'] ?? $row['source_id'])); update_post_meta($pid,'_oh_record',$row);
        Analytics::audit('record_updated',$pid);
        if (class_exists(__NAMESPACE__.'\\Knowledge')) { update_option('oh_knowledge_dirty',1,false); }
    }
}
