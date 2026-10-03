import fs from 'node:fs';
import assert from 'node:assert/strict';
import {PHP} from '@php-wasm/universal';
import {loadNodeRuntime} from '@php-wasm/node';
const php=new PHP(await loadNodeRuntime('8.3',{emscriptenOptions:{processId:process.pid}}));
php.mkdir('/addon');php.mkdir('/addon/src');
php.writeFile('/addon/ontology.json',JSON.stringify({concepts:{pharmacy:{labels:{de:'Apotheke',en:'Pharmacy',ru:'Аптека',uk:'Аптека'}}},terms:{pharmacy:['pharmacy'],'аптека':['pharmacy'],bank:['bank','riverbank']}}));
php.writeFile('/addon/src/UniversalSearch.php',fs.readFileSync('wp-content/plugins/oberhub-v6/src/UniversalSearch.php'));
const r=await php.run({code:`<?php
namespace OberHubV6 {class Knowledge {
static $calls=[];static $existing=false;
static function distance($a,$b){return $a===$b?0:2;}
static function search($q,$lang,$page,$n,$locality,$type){self::$calls[]=func_get_args();$yes=self::$existing||$q==='Apotheke';return ['results'=>$yes?[['record'=>['id'=>'pharmacy-page-'.$page],'score'=>42]]:[],'total'=>$yes?27:0,'page'=>$page,'per_page'=>$n,'pages'=>$yes?6:0];}
}}
namespace {require '/addon/src/UniversalSearch.php';$checks=[];
function check($name,$passed){global $checks;$checks[]=['name'=>$name,'passed'=>$passed];}
$r=OberHubV6\\UniversalSearch::search('Аптека','ru',2,5,'oberriet','services');
check('Cross-language fallback finds server results',$r['results'][0]['record']['id']==='pharmacy-page-2');
check('Pagination preserved',$r['total']===27&&$r['page']===2&&$r['per_page']===5&&$r['pages']===6);
check('German provider label selected',$r['ontology']['expanded_query']==='Apotheke');
foreach(OberHubV6\\Knowledge::$calls as $args)check('All filters preserved',$args[1]==='ru'&&$args[2]===2&&$args[3]===5&&$args[4]==='oberriet'&&$args[5]==='services');
OberHubV6\\Knowledge::$calls=[];OberHubV6\\Knowledge::$existing=true;
$r=OberHubV6\\UniversalSearch::search('Аптека','ru');check('Successful V6 results preserved',$r['total']===27&&count(OberHubV6\\Knowledge::$calls)===1&&!isset($r['ontology']['expanded_query']));
OberHubV6\\Knowledge::$calls=[];OberHubV6\\Knowledge::$existing=false;
$r=OberHubV6\\UniversalSearch::search('bank','ru');check('Ambiguous alias is not expanded',$r['total']===0&&$r['ontology']['ambiguous']&&count(OberHubV6\\Knowledge::$calls)===1);
OberHubV6\\Knowledge::$calls=[];$r=OberHubV6\\UniversalSearch::search('unknown test','ru');check('Unknown query stays empty',$r['total']===0&&count(OberHubV6\\Knowledge::$calls)===1);
echo json_encode($checks);}`});
const checks=JSON.parse(r.text);php.exit();
fs.writeFileSync('tests/results/v7-fallback.json',JSON.stringify({candidate_only:true,checks,live:false},null,2)+'\n');
console.log({checks:checks.length,failed:checks.filter(c=>!c.passed)});assert(checks.every(c=>c.passed));
