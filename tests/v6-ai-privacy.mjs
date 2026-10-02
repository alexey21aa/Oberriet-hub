import fs from 'node:fs';
import assert from 'node:assert/strict';
import {PHP} from '@php-wasm/universal';
import {loadNodeRuntime} from '@php-wasm/node';
const php=new PHP(await loadNodeRuntime('8.3',{emscriptenOptions:{processId:process.pid}}));
for(const name of ['core','v6'])php.writeFile('/'+name+'.php',fs.readFileSync('wp-content/plugins/oberhub-'+name+'/src/AI/Gateway.php'));
const result=await php.run({code:`<?php
namespace {
define('OBERHUB_AI_PROVIDERS',[['enabled'=>true,'free_tier'=>true,'endpoint'=>'https://fixture.invalid','model'=>'fixture']]);$transients=[];$calls=0;$fixtureSource=['source_id'=>'source','review_status'=>'checked','verification_scope'=>'page','fetch_status'=>'ok','source_url'=>'https://provider.invalid/help'];$fixtureRecord=['id'=>'one','title'=>['en'=>'Reviewed source'],'description_short'=>['en'=>'Verified public service'],'source_id'=>'source','source_url'=>'https://provider.invalid/help','source_trust_level'=>'A','verification_scope'=>'page'];$fixtureStale=false;
function get_transient($k){global $transients;return $transients[$k]??false;}function set_transient($k,$v,$ttl){global $transients;$transients[$k]=$v;}function wp_salt($s){return 'public-test-fixture';}function get_option($k,$d=false){return $d;}function wp_json_encode($v){return json_encode($v);}
}
namespace OberHub {
function records($type){global $fixtureSource;return [$fixtureSource];}
class Sources {static function evidence($r,$a,$b){global $fixtureStale;return ['stale'=>$fixtureStale];}}
class Knowledge {static function search($q,$lang,$page,$n,$locality,$type){global $fixtureRecord;return ['results'=>[['record'=>$fixtureRecord]]];}static function tokens($q){return explode(' ',strtolower($q));}static function normalize($q){return strtolower($q);}}
}
namespace OberHubV6 {class Knowledge extends \\OberHub\\Knowledge {}}
namespace OberHub\\AI {
class OpenAICompatibleProvider {function __construct(...$args){}function answer($q,$context){global $calls;$calls++;return ['mode'=>'ai-draft','answer'=>'Verified public service: see the supplied source.','sources'=>['https://provider.invalid/help']];}}
class AnswerPacks {static function select(...$args){return null;}}
}
namespace OberHubV6\\AI {class OpenAICompatibleProvider extends \\OberHub\\AI\\OpenAICompatibleProvider {}class AnswerPacks extends \\OberHub\\AI\\AnswerPacks {}}
namespace {
require '/core.php';require '/v6.php';$checks=[];
// Provider is an in-process spy: no external inference or visitor text is transmitted.
$private=['I need stoma advice','my oncology appointment','ich brauche Palliativpflege','meine Stomaberatung','мне нужна паллиативная помощь','мой онкологический диагноз','мені потрібна паліативна допомога','my nursing needs','I have cancer','my income is low','my debt is growing','I need a divorce','my partner uses violence','ich habe Schulden','mein Einkommen reicht nicht','mir wird Gewalt angedroht','meine Scheidung','мне поставили диагноз','у меня долг','мой доход низкий','я хочу развод','мне угрожают насилием','мені потрібне розлучення','мій борг','мої доходи','мене переслідує насильник','my email is person@example.invalid','call +41 79 123 45 67'];
$public=['Спорт для детей','I need sports for kids','бесплатная юридическая консультация Rheintal','Spitex Pflege Oberriet'];
foreach(['OberHub\\AI\\Gateway','OberHubV6\\AI\\Gateway'] as $class){
 $originalSource=$fixtureSource;$originalRecord=$fixtureRecord;
 foreach(['routing-metadata','official-directory-contact-header-only','unknown-scope','unknown-fetch','error-fetch','unreviewed','stale','wrong-source-url','missing-source-url','untrusted','source-routing-only','source-header-only','source-scope-missing'] as $case){
  $fixtureSource=$originalSource;$fixtureRecord=$originalRecord;$fixtureStale=false;
  if(in_array($case,['routing-metadata','official-directory-contact-header-only','unknown-scope']))$fixtureRecord['verification_scope']=$case;
  if($case==='unknown-fetch')unset($fixtureSource['fetch_status']);
  if($case==='error-fetch')$fixtureSource['fetch_status']='error';
  if($case==='unreviewed')$fixtureSource['review_status']='needs_review';
  if($case==='stale')$fixtureStale=true;
  if($case==='wrong-source-url')$fixtureSource['source_url']='https://provider.invalid/other';
  if($case==='missing-source-url')unset($fixtureSource['source_url']);
  if($case==='untrusted')$fixtureRecord['source_trust_level']='C';
  if($case==='source-routing-only')$fixtureSource['verification_scope']='routing-metadata';
  if($case==='source-header-only')$fixtureSource['verification_scope']='official-directory-contact-header-only';
  if($case==='source-scope-missing')unset($fixtureSource['verification_scope']);
  $transients=[];$calls=0;$r=$class::answer('anonymous public service','en');
  $checks[]=['name'=>$class.' rejected context '.$case,'passed'=>$calls===0&&$r['mode']==='evidence-pack'&&count($r['records'])===1];
 }
 $fixtureSource=$originalSource;$fixtureRecord=$originalRecord;$fixtureStale=false;

 foreach($private as $q){$transients=[];$calls=0;$r=$class::answer($q,'ru');$checks[]=['name'=>$class.' private '.$q,'passed'=>$calls===0&&($r['privacy']??'')==='sensitive-question-not-sent'&&$r['retrieval']==='server'];}
 foreach($public as $q){$transients=[];$calls=0;$r=$class::answer($q,'ru');$checks[]=['name'=>$class.' public '.$q,'passed'=>$calls===1&&$r['mode']==='server-ai'];}
}
echo json_encode($checks,JSON_UNESCAPED_UNICODE);
}
`});
const checks=JSON.parse(result.text);fs.writeFileSync('tests/results/v6-ai-privacy.json',JSON.stringify(checks,null,2)+'\n');console.log(JSON.stringify({checks:checks.length,failed:checks.filter(x=>!x.passed)}));assert(checks.every(x=>x.passed));php.exit();
