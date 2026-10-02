import {PHP} from '@php-wasm/universal';
import {loadNodeRuntime} from '@php-wasm/node';
import fs from 'node:fs';
const php = new PHP(await loadNodeRuntime('8.3', {emscriptenOptions:{processId:1010}}));
for (const name of ['SourceIngestion','Sources','ImportQueue']) php.writeFile('/'+name+'.php', fs.readFileSync(new URL('../wp-content/plugins/oberhub-core/src/'+name+'.php',import.meta.url)));
php.writeFile('/base.json',fs.readFileSync(new URL('../data/base-seed.json',import.meta.url)));
php.writeFile('/seed.json', fs.readFileSync(new URL('../data/seed.json',import.meta.url)));
const result = await php.run({code:`<?php
namespace { $opts=[];$posts=[];$admin=true;$indexed=0;
function current_user_can($c){global $admin;return $admin;}
function get_option($k,$d=false){global $opts;return $opts[$k]??$d;}function add_option($k,$v,$x='',$a=false){global $opts;if(array_key_exists($k,$opts))return false;$opts[$k]=$v;return true;}function update_option($k,$v,$a=false){global $opts;$opts[$k]=$v;return true;}function delete_option($k){global $opts;unset($opts[$k]);return true;}
function wp_json_encode($v,$o=0){return json_encode($v,$o);}function wp_parse_url($s){return parse_url($s);}function is_email($s){return filter_var($s,FILTER_VALIDATE_EMAIL);}function absint($v){return abs((int)$v);}function sanitize_title($v){return $v;}function sanitize_text_field($v){return strip_tags($v);}
function get_posts($q){global $posts;$out=[];foreach($posts as $p){if($p->post_type===$q['post_type'] && ($p->_oh_id??'')===$q['meta_value'])$out[]=$p;}return $out;}
function wp_insert_post($p,$e=false){global $posts;$id=$p['ID']?:count($posts)+1;$posts[$id]=(object)$p;$posts[$id]->ID=$id;return $id;}function is_wp_error($p){return false;}function update_post_meta($id,$key,$value){global $posts;$posts[$id]->$key=$value;}function wp_set_object_terms($i,$r,$t){}
}
namespace OberHub {class Knowledge {static function install(){}static function rebuild($d){global $indexed;$indexed++;}}class Analytics{static function audit($e,$n){}}function dataset(){return [];}}
namespace { require '/Sources.php';require '/ImportQueue.php';$seed=json_decode(file_get_contents('/seed.json'),true);$source=$seed['sources'][0];$s=$seed['services'][0];$s['source_id']=$source['source_id'];$s['source_url']=$source['source_url'];$data=['sources'=>[$source],'services'=>[],'intents'=>[],'aliases'=>[]];for($i=0;$i<61;$i++){$r=$s;$r['id']='queue-test-'.$i;$data['services'][]=$r;}$tests=[];
$r=OberHub\\ImportQueue::enqueue($data);$tests['enqueue_validated']=$r['errors']===[]&&$r['queued']===62;
$r=OberHub\\ImportQueue::process(7);$tests['batch_limit']=$r['processed']===7&&$indexed===0;
$r=OberHub\\ImportQueue::process(7);$tests['resumes_after_request']=$r['processed']===14&&count($posts)===14;
$tests['concurrent_enqueue_rejected']=count(OberHub\\ImportQueue::enqueue($data)['errors'])>0;
$admin=false;try{OberHub\\ImportQueue::process(7);$denied=false;}catch(Throwable $e){$denied=true;}$tests['capability_required']=$denied;$admin=true;
$turns=0;while(OberHub\\ImportQueue::active()&&$turns++<40)OberHub\\ImportQueue::process(7);$r=OberHub\\ImportQueue::status();$tests['complete_after_all_records']=$r['stage']==='complete'&&$r['processed']===62&&count($posts)===62;
$tests['index_once_at_end']=$indexed===1;$tests['private_chunks_removed']=count(array_filter(array_keys($opts),fn($k)=>str_starts_with($k,'oh_import_chunk_')||str_starts_with($k,'oh_import_meta_')))===0;
OberHub\\ImportQueue::enqueue($data);$turns=0;while(OberHub\\ImportQueue::active()&&$turns++<40)OberHub\\ImportQueue::process(25);$r=OberHub\\ImportQueue::status();$tests['repeat_without_overwrite_idempotent']=count($posts)===62&&$r['skipped']===62;
$expandedIntent=['id'=>'expanded','service_id'=>'queue-test-0','phrases'=>['Expanded intent']];$expandedAlias=['service_id'=>'queue-test-0','language'=>'ru','phrase'=>'расширенный поиск'];$existingWaste=[['id'=>'existing-waste']];
update_option('oh_intents',[$expandedIntent]);update_option('oh_aliases',[$expandedAlias]);update_option('oh_waste',$existingWaste);
$incoming=$data;$incoming['intents']=[['id'=>'incoming','service_id'=>'queue-test-0','phrases'=>['Incoming']]];$incoming['aliases']=[$expandedAlias,['service_id'=>'queue-test-0','language'=>'de','phrase'=>'Neu']];$incoming['waste']=[];
OberHub\\ImportQueue::enqueue($incoming);$turns=0;while(OberHub\\ImportQueue::active()&&$turns++<40)OberHub\\ImportQueue::process(25);
$tests['partial_import_preserves_expanded_intents']=get_option('oh_intents')[0]===$expandedIntent&&count(get_option('oh_intents'))===2;
$tests['partial_import_preserves_deduplicated_aliases']=get_option('oh_aliases')[0]===$expandedAlias&&count(get_option('oh_aliases'))===2;
$tests['partial_import_preserves_existing_waste']=get_option('oh_waste')===$existingWaste;
$tests['explicit_overwrite_replaces_metadata']=OberHub\\ImportQueue::metadata('aliases',[],true)===[];
$base=json_decode(file_get_contents('/base.json'),true);update_option('oh_intents',$seed['intents']);$compat=OberHub\\ImportQueue::metadata('intents',$base['intents'],false);$tests['legacy_intent_ids_do_not_duplicate_curated_intents']=count($compat)===count($seed['intents'])&&$compat===$seed['intents'];
$tests['lock_released']=!isset($opts['oh_import_lock']);echo json_encode($tests,JSON_PRETTY_PRINT);}
`});
console.log(result.text);
if (result.errors) console.error(result.errors);
const tests=JSON.parse(result.text);
fs.writeFileSync(new URL('./results/import-queue.json',import.meta.url),result.text+'\n');
process.exit(Object.values(tests).every(Boolean)?0:1);
