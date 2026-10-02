import fs from 'node:fs';import assert from 'node:assert/strict';
import {PHP} from '@php-wasm/universal';import {loadNodeRuntime} from '@php-wasm/node';
const php=new PHP(await loadNodeRuntime('8.3',{emscriptenOptions:{processId:process.pid}}));
for(const name of ['Sources','SourceIngestion','Search','Knowledge'])php.writeFile('/'+name+'.php',fs.readFileSync(new URL('../wp-content/plugins/oberhub-core/src/'+name+'.php',import.meta.url)));
const result=await php.run({code:`<?php
const DAY_IN_SECONDS=86400;
function wp_parse_url($u,$component=-1){return parse_url($u,$component);}function is_wp_error($v){return $v instanceof Exception;}
function wp_remote_retrieve_response_code($r){return $r['code']??0;}function wp_remote_retrieve_body($r){return $r['body']??'';}function wp_remote_retrieve_header($r,$h){return $r['headers'][$h]??'';}
function current_user_can($cap){return ($GLOBALS['admin']??false)&&$cap==='manage_options';}function apply_filters($name,$value,...$args){return $value;}function get_option($k,$d=false){return $GLOBALS['options'][$k]??$d;}function update_option($k,$v,$a=false){$GLOBALS['options'][$k]=$v;}
require '/Sources.php';require '/Search.php';require '/Knowledge.php';
use OberHub\\Sources;use OberHub\\SourceIngestion;
$r=[];$test=function($n,$v)use(&$r){$r[]=['name'=>$n,'passed'=>(bool)$v];};
$base=['source_id'=>'official','source_url'=>'https://www.oberriet.ch/news','base_url'=>'https://www.oberriet.ch/','freshness_class'=>'F1','review_status'=>'checked','index_for_ai'=>true,'license_or_reuse_note'=>'Metadata and bounded excerpt with link','contains_personal_data_risk'=>false,'allow_paths'=>['/news'],'deny_paths'=>['/news/private']];
$test('Freshness classes precise',Sources::ttl(['freshness_class'=>'F0'])===600&&Sources::ttl(['freshness_class'=>'F3'])===604800&&Sources::ttl(['ttl_days'=>7])===604800);
$test('Never checked is stale',Sources::freshness($base,1700000000)['stale']);
$s=$base+['monitor_hash'=>hash('sha256','old'),'last_checked'=>gmdate('c',1700000000)];
$fresh=Sources::apply_response($s,['code'=>304],1700000100);$test('304 renews clock preserves editorial verification',$fresh['review_status']==='checked'&&!Sources::freshness($fresh,1700000101)['stale']);
$missing=Sources::apply_response($base,['code'=>304],1700000100);$test('304 without cached baseline rejected',$missing['fetch_status']==='error'&&empty($missing['fetched_at']));
$changed=Sources::apply_response($s,['code'=>200,'body'=>'new','headers'=>['etag'=>'abc']],1700000100);$test('Change requires review without pretending facts checked',$changed['review_status']==='needs_review'&&$changed['etag']==='abc');
$unchanged=Sources::apply_response($s,['code'=>200,'body'=>'old'],1700000100);$test('Unchanged body preserves review',$unchanged['review_status']==='checked');
$failure=Sources::apply_response($s,['code'=>429,'headers'=>['retry-after'=>'900']],1700000100);$failure2=Sources::apply_response($failure,new Exception('timeout'),1700001000);$test('Backoff and Retry-After',$failure['retry_at']===1700001000&&$failure2['retry_at']===1700001600);
$test('Failed request never advances checked time',$failure['last_checked']===$s['last_checked']&&Sources::freshness($failure,1700000101)['stale']);
$tooBig=Sources::apply_response($s,['code'=>200,'body'=>str_repeat('x',300001)],1700000100);$test('Truncated oversized content rejected',$tooBig['fetch_status']==='error');
$test('Unsafe host blocked',!SourceIngestion::permitted($base,'https://www.oberriet.ch.evil.example/news'));
$test('Login and denied paths blocked',!SourceIngestion::permitted($base,'https://www.oberriet.ch/news/private/1')&&!SourceIngestion::permitted($base,'https://www.oberriet.ch/news/login/'));
$test('Encoded path traversal blocked',!SourceIngestion::permitted($base,'https://www.oberriet.ch/news/%2e%2e/wp-admin/'));
$test('Personal data and permission optout blocked',!SourceIngestion::permitted(array_merge($base,['contains_personal_data_risk'=>true]),$base['source_url'])&&!SourceIngestion::permitted(array_merge($base,['index_for_ai'=>false]),$base['source_url']));
$robots="User-agent: *\nDisallow: /news\nAllow: /news/public\n";$test('Robots longest rule',!SourceIngestion::robotsAllowed($robots,$base['source_url'])&&SourceIngestion::robotsAllowed($robots,'https://www.oberriet.ch/news/public/1'));
$test('Specific robot group overrides wildcard',SourceIngestion::robotsAllowed("User-agent: *\nDisallow: /\nUser-agent: OberrietHub\nAllow: /news\n",$base['source_url']));
$xml='<urlset><url><loc>https://www.oberriet.ch/news/1</loc></url><url><loc>https://evil.example/1</loc></url><url><loc>https://www.oberriet.ch/news/private/1</loc></url></urlset>';
$test('Sitemap discovery bounded and allowlisted',SourceIngestion::discover($xml,'sitemap',$base['source_url'],$base)===['https://www.oberriet.ch/news/1']);
$html='<html><head><title>Notice</title></head><body><nav>PRIVATE NAV</nav><main><h1>School notice</h1><p>School opens Monday.</p><script>SECRET SCRIPT</script></main></body></html>';
$docs=SourceIngestion::extract($html,'html',$base['source_url'],$base);$test('HTML removes boilerplate and scripts',count($docs)===1&&$docs[0]['title']['de']==='School notice'&&!str_contains($docs[0]['description_short']['de'],'SECRET')&&!str_contains($docs[0]['description_short']['de'],'PRIVATE'));
$metadata=SourceIngestion::extract($html,'html', $base['source_url'],array_merge($base,['metadata_only'=>true]));$test('Metadata-only opt-in does not copy body text',$metadata[0]['description_short']['de']===''&&$metadata[0]['extraction_status']==='metadata');
$test('Ingested excerpts never marked verified or translated',$docs[0]['status']==='unreviewed'&&$docs[0]['translation_status']['ru']==='original-language'&&$docs[0]['verification_scope']==='automatic-snippet');
$feed='<rss><channel><item><title>Meeting</title><description>Public meeting</description><link>https://www.oberriet.ch/news/meeting</link></item></channel></rss>';$test('RSS extraction',SourceIngestion::extract($feed,'rss',$base['source_url'],$base)[0]['title']['de']==='Meeting');
$api='{"items":[{"title":"Public notice","summary":"Road closed","url":"https://www.oberriet.ch/news/road"}]}';$test('API extraction',SourceIngestion::extract($api,'api',$base['source_url'],$base)[0]['description_short']['de']==='Road closed');
$ics="BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nSUMMARY:Public event\r\nDTSTART;VALUE=DATE:20261002\r\nDESCRIPTION:Meeting\r\nEND:VEVENT\r\nEND:VCALENDAR";$test('ICS event metadata',SourceIngestion::extract($ics,'ics',$base['source_url'],$base)[0]['event_start_raw']==='20261002');
$test('PDF unsupported extractor honestly marked',SourceIngestion::extract('%PDF','pdf',$base['source_url'],$base)[0]['extraction_status']==='pdf-extractor-unavailable');
SourceIngestion::ingest($base,$base['source_url'],$html,'html');SourceIngestion::ingest($base,$base['source_url'],str_replace('School notice','Updated notice',$html),'html');$test('Changed pages replace old snippets not duplicate',count(get_option('oh_documents'))===1&&get_option('oh_documents')[0]['title']['de']==='Updated notice'&&get_option('oh_knowledge_dirty')===1);
$test('Extra domain needs explicit administrator approval',!Sources::allowed('https://regional.example.org/news')&&!Sources::approve_host('regional.example.org'));
$GLOBALS['admin']=true;$test('Approved new public root works without code changes',Sources::approve_host('regional.example.org')&&Sources::allowed('https://regional.example.org/news')&&!Sources::approve_host('localhost')&&!Sources::approve_host('127.0.0.1'));
echo json_encode($r);
`});if(result.errors)console.error(result.errors);const results=JSON.parse(result.text);for(const r of results)console.log((r.passed?'PASS ':'FAIL ')+r.name);assert.ok(results.every(r=>r.passed));php.exit();
