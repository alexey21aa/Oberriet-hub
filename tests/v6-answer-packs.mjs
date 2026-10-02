import fs from 'node:fs';
import assert from 'node:assert/strict';
import {PHP} from '@php-wasm/universal';
import {loadNodeRuntime} from '@php-wasm/node';
const php=new PHP(await loadNodeRuntime('8.3',{emscriptenOptions:{processId:process.pid}}));
for(const dir of ['/addon','/addon/src','/addon/src/AI'])php.mkdir(dir);
for(const path of ['src/Knowledge.php','src/QueryUnderstanding.php','src/AI/AnswerPacks.php','src/AI/Gateway.php','query-concepts.json','answer-packs.json','activities.json'])php.writeFile('/addon/'+path,fs.readFileSync('wp-content/plugins/oberhub-v6/'+path));
const out=await php.run({code:`<?php
require '/addon/src/Knowledge.php';require '/addon/src/AI/AnswerPacks.php';require '/addon/src/AI/Gateway.php';
function wp_json_encode($v){return json_encode($v);}
$context=json_decode(file_get_contents('/addon/activities.json'),true)['services'];$checks=[];
function check($name,$ok){global $checks;$checks[]=['name'=>$name,'passed'=>(bool)$ok];}
$groups=['children|sport'=>['Спорт для детей','Спорт доя детей','sports for kids','Kinder Sport'],'dance|women'=>['Танцы для женщин','танци доя женщин','women dance','Tanzen Frauen'],'children|indoor'=>['Развлекательные центры для детей','indoor children']];
foreach($groups as $key=>$queries)foreach($queries as $q)foreach(['de','en','ru','uk'] as $lang){$pack=OberHubV6\\AI\\AnswerPacks::select($q,$lang,$context);check($q.' '.$lang,$pack&&OberHubV6\\AI\\Gateway::valid($pack,$context));}
$a=OberHubV6\\AI\\AnswerPacks::select('Спорт для детей','ru',$context);$b=OberHubV6\\AI\\AnswerPacks::select('Спорт доя детей','ru',$context);check('Typo shares canonical pack',$a===$b);
$changed=$context;foreach($changed as &$r)if($r['id']==='v6-kiri-oberriet')$r['description_short']['ru']='Changed facts';unset($r);check('Changed facts invalidate pack',OberHubV6\\AI\\AnswerPacks::select('Спорт для детей','ru',$changed)===null);
$removed=array_values(array_filter($context,fn($r)=>$r['id']!=='v6-kiri-oberriet'));check('Missing reviewed source invalidates pack',OberHubV6\\AI\\AnswerPacks::select('Спорт для детей','ru',$removed)===null);
foreach(['Спорт для детей в Grabs','Спорт для детей 4 года','Спорт для детей бесплатно','мой диагноз спорт','unknown query'] as $q)check('Extra constraints do not reuse generic pack '.$q,OberHubV6\\AI\\AnswerPacks::select($q,'ru',$context)===null);
echo json_encode($checks,JSON_UNESCAPED_UNICODE);
`});
const checks=JSON.parse(out.text);fs.writeFileSync('tests/results/v6-answer-packs.json',JSON.stringify(checks,null,2)+'\n');console.log(JSON.stringify({checks:checks.length,failed:checks.filter(x=>!x.passed)}));assert(checks.every(x=>x.passed));php.exit();
