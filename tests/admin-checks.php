<?php
// Included from runtime-checks.php after WordPress has loaded; shared $check accumulator.
$check('Organization/answer/FAQ/guide admin types registered',post_type_exists('oh_organization') && post_type_exists('oh_answer') && post_type_exists('oh_faq') && post_type_exists('oh_guide'));
$good=\OberHub\seed();
$bad=$good;$bad['services'][]=$bad['services'][0];$check('Duplicate stable IDs rejected',in_array('Duplicate id: services/'.$bad['services'][0]['id'],\OberHub\Sources::validate_dataset($bad),true));
$bad=$good;$bad['events'][0]['date']='2026-02-30';$check('Impossible calendar date rejected',count(\OberHub\Sources::validate_dataset($bad))>0);
$bad=$good;$bad['services'][0]['source_id']='unknown-source';$check('Unknown provenance rejected',count(\OberHub\Sources::validate_dataset($bad))>0);
$bad=$good;$bad['services'][0]['description_short']['uk']=5;$check('Invalid localized field rejected',count(\OberHub\Sources::validate_dataset($bad))>0);
$bad=$good;$bad['services']='not-an-array';$check('Invalid collection shape rejected',count(\OberHub\Sources::validate_dataset($bad))>0);
$csv=fopen('php://temp','r+');fputcsv($csv,['collection','id','record_json']);fputcsv($csv,['services',$good['services'][0]['id'],wp_json_encode($good['services'][0],JSON_UNESCAPED_UNICODE)]);rewind($csv);$parsed=\OberHub\AdminDashboard::parse_csv(stream_get_contents($csv));fclose($csv);$check('CSV keeps all nested translations and provenance',$parsed['services'][0]===$good['services'][0]);
$check('CSV mismatched stable ID rejected',\OberHub\AdminDashboard::parse_csv("collection,id,record_json\nservices,wrong,\"{\"\"id\"\":\"\"different\"\"}\"\n")===null);
$check('CSV unsupported collection rejected',\OberHub\AdminDashboard::parse_csv("collection,id,record_json\nusers,admin,{}\n")===null);
$check('CSV malformed JSON rejected',\OberHub\AdminDashboard::parse_csv("collection,id,record_json\nservices,x,not-json\n")===null);
$check('Bulk review hooks registered',has_filter('bulk_actions-edit-oh_answer',[\OberHub\ContentTypes::class,'bulk_actions'])!==false);
$check('Source explicit HTTPS policy rejects fragments-as-host',!\OberHub\Sources::allowed('https://127.0.0.1/#https://www.oberriet.ch'));

$check('CSV spreadsheet formula ID escaped',\OberHub\AdminDashboard::csv_id('-unsafe')==="'-unsafe");
$bad=$good;$bad['sources'][0]['source_url']=['https://www.oberriet.ch'];$check('Non-scalar source URL rejected cleanly',count(\OberHub\Sources::validate_dataset($bad))>0);
