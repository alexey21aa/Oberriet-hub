<?php
/**
 * Plugin Name: OberHub Core
 * Description: Independent multilingual civic navigation, curated sources and private administration.
 * Version: 0.1.0
 * Requires at least: 6.9
 * Requires PHP: 8.2
 * License: GPL-2.0-or-later
 */
namespace OberHub;
if (!defined('ABSPATH')) { exit; }
define('OBERHUB_DIR', __DIR__);
define('OBERHUB_URL', plugin_dir_url(__FILE__));
foreach (['ContentTypes','Sources','Search','Calendar','Analytics','Security','AdminDashboard','Frontend'] as $class) { require_once __DIR__.'/src/'.$class.'.php'; }
foreach (['AIProvider','NullProvider','OpenAICompatibleProvider'] as $class) { require_once __DIR__.'/src/AI/'.$class.'.php'; }
function seed(): array { static $data; if ($data === null) { $data=json_decode(file_get_contents(__DIR__.'/seed.json'),true) ?: []; } return $data; }
function records(string $type): array {
    $posts=get_posts(['post_type'=>'oh_'.$type,'numberposts'=>-1,'post_status'=>'publish','orderby'=>'ID','order'=>'ASC']);
    return array_values(array_filter(array_map(fn($p)=>get_post_meta($p->ID,'_oh_record',true),$posts),'is_array'));
}
function dataset(): array {
    $data=seed();
    foreach (['services'=>'service','contacts'=>'contact','events'=>'event','places'=>'place','sources'=>'source'] as $key=>$type) { $data[$key]=records($type); }
    $data['waste']=get_option('oh_waste',$data['waste'] ?? []);
    $data['intents']=get_option('oh_intents',$data['intents'] ?? []);
    return $data;
}
function import(array $data, bool $overwrite=false): array {
    $errors=Sources::validate_dataset($data);
    if ($errors) { return ['errors'=>$errors]; }
    $n=0;
    foreach (['services'=>'service','contacts'=>'contact','events'=>'event','places'=>'place','sources'=>'source'] as $key=>$type) {
        foreach (($data[$key] ?? []) as $row) {
            $id=(string)($row['id'] ?? $row['source_id']);
            $old=get_posts(['post_type'=>'oh_'.$type,'post_status'=>'any','meta_key'=>'_oh_id','meta_value'=>$id,'numberposts'=>1]);
            if ($old && !$overwrite) { continue; }
            $title=$row['title'] ?? $row['authority'] ?? $id;
            $pid=wp_insert_post(['ID'=>$old ? $old[0]->ID : 0,'post_type'=>'oh_'.$type,'post_status'=>'publish','post_title'=>is_array($title) ? $title['de'] : $title,'post_name'=>$row['slug'] ?? sanitize_title($id)],true);
            if (is_wp_error($pid)) { return ['errors'=>[$pid->get_error_message()]]; }
            update_post_meta($pid,'_oh_id',$id); update_post_meta($pid,'_oh_record',$row);
            foreach (['topic'=>'oh_topic','locality'=>'oh_locality','authority'=>'oh_authority'] as $field=>$tax) { if (!empty($row[$field])) { wp_set_object_terms($pid,(array)$row[$field],$tax); } }
            $n++;
        }
    }
    foreach (['waste','intents'] as $key) { if (isset($data[$key])) { update_option('oh_'.$key,$data[$key],false); } }
    return ['imported'=>$n,'errors'=>[]];
}
function activate(): void {
    ContentTypes::register(); Analytics::install(); import(seed());
    update_option('users_can_register',0); update_option('default_comment_status','closed'); update_option('default_ping_status','closed'); update_option('timezone_string','Europe/Zurich');
    update_option('permalink_structure','/%postname%/');
    if (!wp_next_scheduled('oh_daily')) { wp_schedule_event(time()+300,'daily','oh_daily'); }
    Frontend::rewrite(); flush_rewrite_rules();
}
register_activation_hook(__FILE__,__NAMESPACE__.'\\activate');
register_deactivation_hook(__FILE__,function(){ wp_clear_scheduled_hook('oh_daily'); flush_rewrite_rules(); });
add_action('init',[ContentTypes::class,'register']);
Sources::boot(); Search::boot(); Calendar::boot(); Analytics::boot(); Security::boot(); AdminDashboard::boot(); Frontend::boot();
if (defined('WP_CLI') && WP_CLI) {
    \WP_CLI::add_command('oberhub import',function($args,$assoc){
        if (empty($args[0]) || !is_readable($args[0])) { \WP_CLI::error('Provide a readable seed JSON path.'); }
        $data=json_decode(file_get_contents($args[0]),true);
        if (!is_array($data)) { \WP_CLI::error('Invalid JSON.'); }
        $result=import($data,!empty($assoc['overwrite']));
        if ($result['errors']) { \WP_CLI::error(implode('; ',$result['errors'])); }
        \WP_CLI::success('Imported '.$result['imported'].' records.');
    });
}
