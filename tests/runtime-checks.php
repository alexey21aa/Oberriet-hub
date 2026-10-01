<?php
ini_set('display_errors',1);require '/wordpress/wp-load.php';require_once ABSPATH.'wp-admin/includes/user.php';
$r=[];$check=function($name,$ok)use(&$r){$r[]=['name'=>$name,'passed'=>(bool)$ok];};
$check('PHP seed schema validation',!\OberHub\Sources::validate_dataset(\OberHub\dataset()));
foreach (['http://www.oberriet.ch/','https://127.0.0.1/','https://user:password@www.oberriet.ch/','https://www.oberriet.ch.evil.example/','https://www.oberriet.ch:8080/'] as $url) { $check('Source SSRF rejection '.$url,!\OberHub\Sources::allowed($url)); }
$bad=\OberHub\seed();$bad['services'][0]['official_url']='https://unapproved.example/';$check('Invalid import rejected',!!\OberHub\Sources::validate_dataset($bad));
$uid=wp_create_user('qa_editor','temporary-editor-password','editor@example.invalid');$user=new WP_User($uid);$user->set_role('editor');$check('Editors cannot manage Hub data',!user_can($uid,'manage_options') && !user_can($uid,get_post_type_object('oh_service')->cap->edit_posts));wp_delete_user($uid);
$check('XML-RPC methods disabled',apply_filters('xmlrpc_methods',['pingback.ping'=>true])===[]);
$check('Output escaping',strpos(esc_html('<img src=x onerror=alert(1)>'),'<img')===false);
$check('Two Factor plugin loaded',class_exists('Two_Factor_Core'));
foreach (glob('/wordpress/wp-content/plugins/oberhub-core/src/*.php') as $f) { try { token_get_all(file_get_contents($f),TOKEN_PARSE);$check('PHP syntax '.basename($f),true); } catch (ParseError $e) {$check('PHP syntax '.basename($f),false);} }
// Insert disposable expired records and prove the actual purge query removes them.
global $wpdb;
foreach (['request'=>8,'security'=>31,'audit'=>181] as $kind=>$days) { $wpdb->insert($wpdb->prefix.'oh_logs',['created_at'=>gmdate('Y-m-d H:i:s',time()-$days*DAY_IN_SECONDS),'kind'=>$kind,'path'=>'qa-expired']); }
$wpdb->insert($wpdb->prefix.'oh_metrics',['day'=>gmdate('Y-m-d',strtotime('-14 months')),'event'=>'page_view','dimension'=>'qa-expired','total'=>1]);
\OberHub\Analytics::purge();$check('Technical/security/audit retention purge',(int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}oh_logs WHERE path='qa-expired'")===0);$check('Aggregate 13-month purge',(int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}oh_metrics WHERE dimension='qa-expired'")===0);
\OberHub\Analytics::count('page_view','de');\OberHub\Analytics::count('page_view','de');$check('Aggregate counter increments',(int)$wpdb->get_var("SELECT total FROM {$wpdb->prefix}oh_metrics WHERE event='page_view' AND dimension='de'")>=2);
// Disposable local QA secret. The release blueprint does not contain or enroll this key.
update_user_meta(1,Two_Factor_Totp::SECRET_META_KEY,'JBSWY3DPEHPK3PXPJBSWY3DPEHPK3PXP');update_user_meta(1,Two_Factor_Core::ENABLED_PROVIDERS_USER_META_KEY,['Two_Factor_Totp']);update_user_meta(1,Two_Factor_Core::PROVIDER_USER_META_KEY,'Two_Factor_Totp');$check('TOTP is required for QA administrator',Two_Factor_Core::is_user_using_two_factor(1));
file_put_contents('/wordpress/wp-content/oberhub-runtime-qa.json',wp_json_encode($r));

file_put_contents('/wordpress/wp-content/oberhub-qa-clock.php', '<?php echo json_encode(["time"=>time()]);');
