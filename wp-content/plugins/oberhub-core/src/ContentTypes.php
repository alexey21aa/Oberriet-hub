<?php
namespace OberHub;
final class ContentTypes {
    public static function register(): void {
        foreach (['service'=>'Services','place'=>'Places','event'=>'Events','guide'=>'Guides','contact'=>'Contacts','source'=>'Sources'] as $slug=>$label) {
            register_post_type('oh_'.$slug,['label'=>$label,'public'=>false,'show_ui'=>true,'show_in_menu'=>'oberhub','show_in_rest'=>false,'supports'=>['title','revisions'],'capability_type'=>'post','map_meta_cap'=>false,'capabilities'=>array_fill_keys(['edit_post','read_post','delete_post','edit_posts','edit_others_posts','publish_posts','read_private_posts','delete_posts','delete_private_posts','delete_published_posts','delete_others_posts','edit_private_posts','edit_published_posts','create_posts'],'manage_options'),'menu_icon'=>'dashicons-location-alt']);
        }
        foreach (['oh_topic'=>'Topics','oh_locality'=>'Localities','oh_authority'=>'Authorities','oh_service_type'=>'Service types'] as $slug=>$label) { register_taxonomy($slug,['oh_service','oh_place','oh_event','oh_contact'],['label'=>$label,'public'=>false,'show_ui'=>true,'show_admin_column'=>true,'hierarchical'=>false,'capabilities'=>array_fill_keys(['manage_terms','edit_terms','delete_terms','assign_terms'],'manage_options')]); }
        add_action('add_meta_boxes',[self::class,'metabox']); add_action('save_post',[self::class,'save'],10,2);
    }
    public static function metabox(): void { foreach (['service','place','event','guide','contact','source'] as $t) { add_meta_box('oh_record','Curated record / translations / provenance',[self::class,'editor'],'oh_'.$t,'normal','high'); } }
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
        $old=get_post_meta($pid,'_oh_record',true);
        if (is_array($old) && (($old['title']['de'] ?? '')!==($row['title']['de'] ?? '') || ($old['description_short']['de'] ?? '')!==($row['description_short']['de'] ?? '') || ($old['description_full']['de'] ?? '')!==($row['description_full']['de'] ?? ''))) {
            foreach (['en','ru','uk'] as $l) { $row['translation_status'][$l]='stale'; }
        }
        update_post_meta($pid,'_oh_id',(string)($row['id'] ?? $row['source_id'])); update_post_meta($pid,'_oh_record',$row);
        Analytics::audit('record_updated',$pid);
    }
}
