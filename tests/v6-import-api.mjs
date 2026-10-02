import fs from 'node:fs';
import assert from 'node:assert/strict';
import {PHP} from '@php-wasm/universal';
import {loadNodeRuntime} from '@php-wasm/node';
const php=new PHP(await loadNodeRuntime('8.3',{emscriptenOptions:{processId:process.pid}}));
for(const dir of ['/addon','/addon/src','/addon/src/AI'])php.mkdir(dir);
for(const path of ['oberhub-v6.php','src/Knowledge.php','src/QueryUnderstanding.php','src/AI/AIProvider.php','src/AI/NullProvider.php','src/AI/OpenAICompatibleProvider.php','src/AI/AnswerPacks.php','src/AI/Gateway.php'])php.writeFile('/addon/'+path,fs.readFileSync('wp-content/plugins/oberhub-v6/'+path));
const out=await php.run({code:`<?php
namespace OberHub {class Knowledge {} class Sources {static $approvals=0;static function approve_host($h){self::$approvals++;}} class ImportQueue {const TYPES=['services'=>'service','sources'=>'source'];static $busy=false;static $calls=[];static function active(){return self::$busy;}static function enqueue($data,$overwrite){self::$calls[]=[$data,$overwrite];return ['queued'=>count($data['services']??[])];}}}
namespace {const ABSPATH='/';$routes=[];$admin=true;function add_action($name,$callback,$priority){$callback();}function register_rest_route($ns,$route,$spec,$override=false){global $routes;$routes[$route]=$spec;}function current_user_can($cap){global $admin;return $admin;}function get_option($key,$default=false){return $default;}function wp_json_encode($v){return json_encode($v);}function rest_ensure_response($v){return $v;}class WP_Error{function __construct(public $code,public $message,public $data){}}class Request{function __construct(private $body){}function get_json_params(){return $this->body;}}
require '/addon/oberhub-v6.php';$cb=$routes['/v6/prepare']['callback'];$checks=[];function check($name,$ok){global $checks;$checks[]=['name'=>$name,'passed'=>(bool)$ok];}
$admin=false;check('Admin permission required',!$routes['/v6/prepare']['permission_callback']());$admin=true;
foreach([null,'text',['unknown'=>[]],['services'=>['bad'=>'shape']],['services'=>[]],['services'=>array_fill(0,501,['id'=>'x'])],['services'=>[['title'=>str_repeat('x',1000001)]]]] as $data){$before=count(OberHub\\ImportQueue::$calls);$r=$cb(new Request(['dataset'=>$data]));check('Reject invalid dataset '.count($checks),$r instanceof WP_Error&&count(OberHub\\ImportQueue::$calls)===$before);}
$data=['services'=>[['id'=>'example']]];$r=$cb(new Request(['dataset'=>$data,'overwrite'=>true,'approve_hosts'=>['evil.example']]));check('Uses add-only core queue',$r['queued']===1&&OberHub\\ImportQueue::$calls[0]===[$data,false]);check('Does not approve external hosts',OberHub\\Sources::$approvals===0);
OberHub\\ImportQueue::$busy=true;$r=$cb(new Request(['dataset'=>$data]));check('Existing import is preserved',$r instanceof WP_Error&&$r->code==='busy'&&count(OberHub\\ImportQueue::$calls)===1);
echo json_encode($checks);}
`});
const checks=JSON.parse(out.text);fs.writeFileSync('tests/results/v6-import-api.json',JSON.stringify(checks,null,2)+'\n');console.log(JSON.stringify({checks:checks.length,failed:checks.filter(x=>!x.passed)}));assert(checks.every(x=>x.passed));php.exit();
