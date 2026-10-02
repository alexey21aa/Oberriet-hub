import fs from 'node:fs';
import assert from 'node:assert/strict';
import {PHP} from '@php-wasm/universal';
import {loadNodeRuntime} from '@php-wasm/node';
import {createSearchIndex} from '../wp-content/plugins/oberhub-core/assets/search-engine.mjs';
const baseline=JSON.parse(fs.readFileSync('data/seed.json'));
const delta=JSON.parse(fs.readFileSync('data/v6-activities-delta.json'));
const life=JSON.parse(fs.readFileSync('data/v6-life-services-delta.json'));
const index=createSearchIndex({services:[...baseline.services,...delta.services,...life.services]});
const fixture={records:[...index.records].map(([record_key,v])=>({record_key,record_type:v.type,record_json:JSON.stringify(v.record),phrases_json:JSON.stringify(index.phrases.get(record_key))})),terms:[...index.terms].flatMap(([term,postings])=>[...postings].map(([record_key,weight])=>({term,record_key,weight}))),deletions:[...index.deletions].map(([signature,terms])=>({signature,terms:[...terms]}))};
const php=new PHP(await loadNodeRuntime('8.3',{emscriptenOptions:{processId:process.pid}}));
php.writeFile('/fixture.json',JSON.stringify(fixture));
php.writeFile('/query-concepts.json',fs.readFileSync('wp-content/plugins/oberhub-core/query-concepts.json'));
for(const name of ['Knowledge','QueryUnderstanding'])php.writeFile('/'+name+'.php',fs.readFileSync('wp-content/plugins/oberhub-core/src/'+name+'.php'));
php.writeFile('/Gateway.php',fs.readFileSync('wp-content/plugins/oberhub-core/src/AI/Gateway.php'));
const files=[];
function scan(dir){for(const e of fs.readdirSync(dir,{withFileTypes:true})){const p=dir+'/'+e.name;if(e.isDirectory())scan(p);else if(p.endsWith('.php'))files.push(p);}}
scan('wp-content');for(let i=0;i<files.length;i++)php.writeFile('/lint-'+i+'.php',fs.readFileSync(files[i]));
const out=await php.run({code:`<?php
const ARRAY_A='ARRAY_A';const DAY_IN_SECONDS=86400;function wp_json_encode($v){return json_encode($v);}
require '/Knowledge.php';require '/Gateway.php';
class FixtureDatabase {
 public $prefix='wp_';private $f;
 function __construct(){$this->f=json_decode(file_get_contents('/fixture.json'),true);}
 function prepare($sql,...$args){if(count($args)===1&&is_array($args[0]))$args=$args[0];foreach($args as $a)$sql=preg_replace('/%s/',"'".str_replace("'","''",(string)$a)."'",$sql,1);return $sql;}
 function esc_like($s){return $s;}
 function values($sql){preg_match_all("/'([^']*)'/u",$sql,$m);return $m[1];}
 function get_col($sql){$values=$this->values($sql);$out=[];foreach($this->f['deletions'] as $d)if(in_array($d['signature'],$values,true))$out=array_merge($out,$d['terms']);return array_unique($out);}
 function get_results($sql,$format){$values=$this->values($sql);$out=[];if(str_contains($sql,'oh_knowledge')){foreach($this->f['records'] as $r)if(in_array($r['record_key'],$values,true))$out[]=$r;}else{foreach($this->f['terms'] as $r)if(in_array($r['term'],$values,true)||(str_contains($sql,'LIKE')&&str_starts_with($r['term'],rtrim($values[0],'%'))))$out[]=$r;}return $out;}
}
$wpdb=new FixtureDatabase();$checks=[];
function check($label,$ok){global $checks;$checks[]=['name'=>$label,'passed'=>(bool)$ok];}
$groups=['sport'=>['Спорт для детей','Спорт доя детей','спорт дітям','sports for kids','Kinder Sport','спорт детям','спотр для детей'], 'dance'=>['Танцы для женщин','танци доя женщин','Tanzen für Frauen','women dance classes','танці для жінок'],'indoor'=>['Развлекательные центры для детей','indoor Kinder','indoor entertainment children','розважальні центри для дітей']];
foreach($groups as $concept=>$queries)foreach($queries as $q){$r=OberHub\\Knowledge::search($q,'ru',1,5);$ok=count($r['results'])>0;foreach($r['results'] as $hit)$ok=$ok&&in_array($concept,$hit['record']['search_concepts']??[],true);check('PHP server '.$q,$ok);}
foreach([['женские встречи','v6-life-rheintal-women'],['налоговые формы','v6-life-tax-forms'],['помощь с формами Oberriet','v6-life-oberriet-help'],['курсы немецкого','v6-life-german-courses'],['KulturLegi application','v6-life-kulturlegi-apply'],['health insurance subsidy','v6-life-ipv-apply']] as [$q,$id]){$r=OberHub\\Knowledge::search($q,'ru',1,5);check('PHP life '.$q,in_array($id,array_column(array_column($r['results'],'record'),'id'),true));}
$a=OberHub\\Knowledge::search('Спорт для детей','ru',1,5);check('Local Oberriet above Grabs',($a['results'][0]['record']['locality']??'')==='oberriet');
$b=OberHub\\Knowledge::search('Спорт доя детей','ru',1,5);check('Typo preserves same server evidence',array_column(array_column($a['results'],'record'),'id')===array_column(array_column($b['results'],'record'),'id'));
check('No invented unknown results',OberHub\\Knowledge::search('zyxqv987zzblorf')['total']===0);
$c=[['source_url'=>'https://www.rcog.ch/kinderringen']];
check('Reject uncited model answer',!OberHub\\AI\\Gateway::valid(['answer'=>str_repeat('unverified ',4),'sources'=>[]],$c));
check('Reject injected foreign URL',!OberHub\\AI\\Gateway::valid(['answer'=>'Try https://evil.example.org for the next step','sources'=>['https://www.rcog.ch/kinderringen']],$c));
check('Accept citation within supplied evidence',OberHub\\AI\\Gateway::valid(['answer'=>'See https://www.rcog.ch/kinderringen for the verified provider details','sources'=>['https://www.rcog.ch/kinderringen']],$c));
for($i=0;$i<${files.length};$i++){try{token_get_all(file_get_contents('/lint-'.$i.'.php'),TOKEN_PARSE);check('PHP syntax '.$i,true);}catch(ParseError $e){check('PHP syntax '.$i,false);}}
echo json_encode($checks,JSON_UNESCAPED_UNICODE);
`});
const checks=JSON.parse(out.text);console.log(JSON.stringify({checks:checks.length,failed:checks.filter(x=>!x.passed)}));
fs.writeFileSync('tests/results/v6-server.json',JSON.stringify(checks,null,2));assert(checks.every(x=>x.passed));php.exit();
