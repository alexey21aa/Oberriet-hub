import fs from 'node:fs';
import assert from 'node:assert/strict';
import {PHP} from '@php-wasm/universal';
import {loadNodeRuntime} from '@php-wasm/node';
const php=new PHP(await loadNodeRuntime('8.3',{emscriptenOptions:{processId:process.pid}}));
const files=['QueryUnderstanding','Knowledge','Search'];
for(const file of files)php.writeFile('/'+file+'.php',fs.readFileSync(new URL('../wp-content/plugins/oberhub-core/src/'+file+'.php',import.meta.url)));
php.writeFile('/query-concepts.json',fs.readFileSync(new URL('../wp-content/plugins/oberhub-core/query-concepts.json',import.meta.url)));
const result=await php.run({code:`<?php
require '/Knowledge.php';require '/Search.php';
$k='\\OberHub\\Knowledge';$out=[];
foreach(['STRAßE Zürich Ёж','ПЕРЕЕЗД','сміття'] as $s)$out[]=$k::normalize($s);
$out[]=$k::distance('register','regsiter');$out[]=$k::distance('переезд','переезл');echo json_encode($out,JSON_UNESCAPED_UNICODE);
`});
if(result.errors)console.error(result.errors);const values=JSON.parse(result.text);assert.deepEqual(values,['strasse zurich еж','переезд','сміття',1,1]);console.log('PASS PHP WASM syntax, Unicode and Damerau distance');
php.exit();
