import fs from 'node:fs';
import assert from 'node:assert/strict';
import {PHP} from '@php-wasm/universal';
import {loadNodeRuntime} from '@php-wasm/node';
const php=new PHP(await loadNodeRuntime('8.3',{emscriptenOptions:{processId:process.pid}}));
for(const dir of ['/addon','/addon/src','/addon/src/AI'])php.mkdir(dir);
php.writeFile('/addon/oberhub-v6.php',fs.readFileSync('wp-content/plugins/oberhub-v6/oberhub-v6.php'));
// Replace index storage only; execute actual plugin hooks and REST callbacks.
php.writeFile('/addon/src/Knowledge.php',`<?php namespace OberHubV6; class Knowledge {static $calls=0;static $mode='ok';static function install(){}static function rebuild($data){self::$calls++;if(self::$mode==='throw')throw new \\RuntimeException('private failure');if(self::$mode==='race')update_option('oh_v6_index_dirty',99,false);return ['records'=>self::$mode==='empty'?0:3];}}`);
for(const name of ['AIProvider','NullProvider','OpenAICompatibleProvider','AnswerPacks','Gateway'])php.writeFile('/addon/src/AI/'+name+'.php','<?php');
const result=await php.run({code:`<?php
namespace OberHub {class Knowledge {} class ImportQueue {static $busy=false;static function active(){return self::$busy;}}function dataset(){return ['services'=>[]];}}
namespace {
const ABSPATH='/';$hooks=[];$routes=[];$opts=[];$events=[];$types=[1=>'oh_service',2=>'oh_source',3=>'post'];$checks=[];
function add_action($name,$cb,$priority=10,$args=1){global $hooks;$hooks[$name]=$cb;}
function register_rest_route($ns,$route,$spec,$override=false){global $routes;$routes[$route]=$spec;}
function get_option($k,$default=false){global $opts;return $opts[$k]??$default;}
function update_option($k,$v,$autoload=false){global $opts;$opts[$k]=$v;}
function get_post_type($id){global $types;return $types[$id]??false;}
function wp_next_scheduled($hook){global $events;return $events[0]??false;}
function wp_schedule_single_event($time,$hook){global $events;$events[]=$time;}
function rest_ensure_response($v){return $v;}
class WP_Error {function __construct(public $code,public $message,public $data){}}
function check($name,$ok){global $checks;$checks[]=['name'=>$name,'passed'=>(bool)$ok];}
function reset_state(){global $opts,$events;$opts=[];$events=[];OberHub\\ImportQueue::$busy=false;OberHubV6\\Knowledge::$mode='ok';OberHubV6\\Knowledge::$calls=0;}
function run_refresh(){global $hooks,$events;$events=[];$hooks['oh_v6_refresh_index']();}
require '/addon/oberhub-v6.php';$hooks['rest_api_init']();
reset_state();OberHubV6\\mark_index_dirty(0,3,'_oh_record');OberHubV6\\mark_index_dirty(0,2,'_oh_record');OberHubV6\\mark_index_dirty(0,1,'other');check('Ignore non-record and source changes',!$opts&&!$events);
OberHubV6\\mark_index_dirty(0,1,'_oh_record');check('Record edits mark dirty and schedule',get_option('oh_v6_index_dirty')>0&&count($events)===1);
OberHubV6\\mark_index_dirty(0,1,'_oh_record');check('Debounce repeated edits',count($events)===1);
run_refresh();check('Successful refresh clears dirty and sets ready',!get_option('oh_v6_index_dirty')&&get_option('oh_v6_ready')&&OberHubV6\\Knowledge::$calls===1);
run_refresh();check('Clean index avoids rebuild',OberHubV6\\Knowledge::$calls===1);
reset_state();$hooks['before_delete_post'](1);check('Deletion marks dirty',get_option('oh_v6_index_dirty')&&count($events)===1);
OberHub\\ImportQueue::$busy=true;run_refresh();check('Active import defers refresh',OberHubV6\\Knowledge::$calls===0&&count($events)===1&&get_option('oh_v6_index_dirty'));
OberHub\\ImportQueue::$busy=false;OberHubV6\\Knowledge::$mode='race';run_refresh();check('Concurrent edit remains dirty and schedules follow-up',get_option('oh_v6_index_dirty')===99&&count($events)===1);
OberHubV6\\Knowledge::$mode='ok';run_refresh();check('Follow-up clears latest revision',!get_option('oh_v6_index_dirty'));
reset_state();$opts=['oh_v6_index_dirty'=>12,'oh_v6_ready'=>true];OberHubV6\\Knowledge::$mode='throw';run_refresh();check('Failure preserves ready and dirty',get_option('oh_v6_ready')&&get_option('oh_v6_index_dirty')===12);check('Failure schedules retry without private error',count($events)===1&&$events[0]>=time()+299&&!str_contains(get_option('oh_v6_index_error'),'private'));
OberHubV6\\Knowledge::$mode='empty';run_refresh();check('Empty refresh preserves dirty and retries',get_option('oh_v6_index_dirty')===12&&count($events)===1);
OberHubV6\\Knowledge::$mode='ok';$routes['/v6/build-index']['callback']();check('Manual rebuild clears dirty and error',!get_option('oh_v6_index_dirty')&&get_option('oh_v6_index_error')==='');run_refresh();check('Pending cron does not duplicate manual rebuild',OberHubV6\\Knowledge::$calls===3);
$opts['oh_v6_index_dirty']=42;OberHubV6\\Knowledge::$mode='race';$routes['/v6/build-index']['callback']();check('Manual rebuild preserves concurrent changes',get_option('oh_v6_index_dirty')===99&&count($events)===1);
OberHub\\ImportQueue::$busy=true;$before=OberHubV6\\Knowledge::$calls;$r=$routes['/v6/build-index']['callback']();check('Manual rebuild refuses active import',$r instanceof WP_Error&&$r->code==='busy'&&OberHubV6\\Knowledge::$calls===$before);
echo json_encode($checks);
}
`});
const checks=JSON.parse(result.text);
fs.writeFileSync('tests/results/v6-freshness.json',JSON.stringify(checks,null,2)+'\n');
console.log(JSON.stringify({checks:checks.length,failed:checks.filter(x=>!x.passed)}));
assert(checks.every(x=>x.passed));php.exit();
