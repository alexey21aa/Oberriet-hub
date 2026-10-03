import fs from 'node:fs';
import assert from 'node:assert/strict';
import {PHP} from '@php-wasm/universal';
import {loadNodeRuntime} from '@php-wasm/node';
const php=new PHP(await loadNodeRuntime('8.3',{emscriptenOptions:{processId:process.pid}}));
php.mkdir('/addon');php.mkdir('/addon/src');
php.writeFile('/addon/ontology.json',fs.readFileSync('wp-content/plugins/oberhub-v6/ontology.json'));
php.writeFile('/addon/src/UniversalSearch.php',fs.readFileSync('wp-content/plugins/oberhub-v6/src/UniversalSearch.php'));
php.writeFile('/addon/src/Knowledge.php',fs.readFileSync('wp-content/plugins/oberhub-v6/src/Knowledge.php'));
php.writeFile('/addon/src/QueryUnderstanding.php',fs.readFileSync('wp-content/plugins/oberhub-v6/src/QueryUnderstanding.php'));
php.writeFile('/addon/query-concepts.json',fs.readFileSync('wp-content/plugins/oberhub-v6/query-concepts.json'));
php.writeFile('/aliases.json',fs.readFileSync('data/v7/ontology/concept_aliases.jsonl'));
const r=await php.run({code:`<?php
require '/addon/src/Knowledge.php';require '/addon/src/UniversalSearch.php';
$checks=[];function check($name,$passed){global $checks;$checks[]=['name'=>$name,'passed'=>$passed];}
foreach(explode("\n",trim(file_get_contents('/aliases.json'))) as $line){$a=json_decode($line,true);$r=OberHubV6\\UniversalSearch::resolve($a['term']);check('Alias '.$a['lang'].' '.$a['term'],in_array($a['concept_id'],$r['concept_ids'],true));}
foreach(['unreviewed test','unknown municipal form xyz','therapist qxyz'] as $q)check('Unknown '.$q,OberHubV6\\UniversalSearch::resolve($q)['concept_ids']===[]);
$r=OberHubV6\\UniversalSearch::resolve('колоноскопя');check('Cyrillic typo',in_array('need:health:colonoscopy',$r['concept_ids'],true));
$r=OberHubV6\\UniversalSearch::resolve('ПРОФОРИЕНТАЦИЯ');check('Cyrillic case',in_array('need:work:career-guidance',$r['concept_ids'],true));
$r=OberHubV6\\UniversalSearch::resolve('FÜHRERAUSWEIS UMTAUSCHEN');check('German case',in_array('need:transport:driving-licence-exchange',$r['concept_ids'],true));
echo json_encode($checks,JSON_UNESCAPED_UNICODE);`});
const checks=JSON.parse(r.text);const failed=checks.filter(c=>!c.passed);
const result={candidate_only:true,checks:checks.length,failed:failed.length,failures:failed,device_independent:true,live_deployed:false};
fs.writeFileSync('tests/results/v7-server.json',JSON.stringify(result,null,2)+'\n');console.log(result);php.exit();assert.equal(failed.length,0);
