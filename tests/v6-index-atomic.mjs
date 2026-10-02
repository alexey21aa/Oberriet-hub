import fs from 'node:fs';
import assert from 'node:assert/strict';
import {PHP} from '@php-wasm/universal';
import {loadNodeRuntime} from '@php-wasm/node';
const php=new PHP(await loadNodeRuntime('8.3',{emscriptenOptions:{processId:process.pid}}));
for(const name of ['core','v6']){
 php.mkdir('/'+name);php.mkdir('/'+name+'/src');
 for(const file of ['Knowledge.php','QueryUnderstanding.php'])php.writeFile('/'+name+'/src/'+file,fs.readFileSync('wp-content/plugins/oberhub-'+name+'/src/'+file));
 php.writeFile('/'+name+'/query-concepts.json',fs.readFileSync('wp-content/plugins/oberhub-'+name+'/query-concepts.json'));
}
const out=await php.run({code:`<?php
$opts=[];function get_option($k,$d=false){global $opts;return $opts[$k]??$d;}function update_option($k,$v,$autoload=false){global $opts;$opts[$k]=$v;}function wp_json_encode($v,$flags=0){return json_encode($v,$flags);}
require '/core/src/Knowledge.php';require '/v6/src/Knowledge.php';
// Real SQLite transactions with a wpdb adapter. MySQL/InnoDB live acceptance is separate.
class Database {
 public $prefix='wp_';public $db;public $fail='';public $engine='InnoDB';public $queries=[];
 function __construct(public $p){$this->db=new SQLite3(':memory:');foreach(['knowledge','terms','deletions'] as $t){$cols=$t==='knowledge'?'record_key TEXT PRIMARY KEY,record_type TEXT,record_json TEXT,phrases_json TEXT':($t==='terms'?'term TEXT,record_key TEXT,weight INT,PRIMARY KEY(term,record_key)':'signature TEXT,term TEXT,PRIMARY KEY(signature,term)');$this->db->exec('CREATE TABLE '.$p.$t.' ('.$cols.')');} $this->db->exec("INSERT INTO ".$p."knowledge VALUES ('old','services','{}','[]')");$this->db->exec("INSERT INTO ".$p."terms VALUES ('old','old',1)");$this->db->exec("INSERT INTO ".$p."deletions VALUES ('old','old')");}
 function prepare($sql,...$args){if(count($args)===1&&is_array($args[0]))$args=$args[0];foreach($args as $v)$sql=preg_replace('/%s/',"'".SQLite3::escapeString((string)$v)."'",$sql,1);return $sql;}
 function get_var($sql){return $this->engine;}
 function query($sql){$this->queries[]=$sql;$fail=$this->fail&&(($this->fail==='begin'&&$sql==='START TRANSACTION')||($this->fail==='delete'&&str_starts_with($sql,'DELETE'))||($this->fail==='terms'&&str_contains($sql,'INSERT IGNORE INTO '.$this->p.'terms'))||($this->fail==='deletions'&&str_contains($sql,'INSERT IGNORE INTO '.$this->p.'deletions'))||($this->fail==='commit'&&$sql==='COMMIT'));if($fail){$this->fail='';return false;}return $this->db->exec(str_replace(['START TRANSACTION','INSERT IGNORE'],['BEGIN TRANSACTION','INSERT OR IGNORE'],$sql))?1:false;}
 function insert($table,$row){if($this->fail==='record'){$this->fail='';return false;}$values=array_map(fn($v)=>"'".SQLite3::escapeString((string)$v)."'",array_values($row));return @$this->db->exec('INSERT INTO '.$table.' ('.implode(',',array_keys($row)).') VALUES ('.implode(',',$values).')')?1:false;}
 function snapshot(){return array_map(fn($t)=>$this->db->querySingle('SELECT COUNT(*) FROM '.$this->p.$t),['knowledge','terms','deletions']);}
}
$checks=[];function check($name,$ok){global $checks;$checks[]=['name'=>$name,'passed'=>(bool)$ok];}
$data=['services'=>[['id'=>'new','title'=>['ru'=>'спорт детей']]]];
foreach(['OberHub\\Knowledge'=>'wp_oh_','OberHubV6\\Knowledge'=>'wp_oh_v6_'] as $class=>$p){
 $statsKey=$p==='wp_oh_'?'oh_knowledge_stats':'oh_v6_knowledge_stats';
 foreach(['begin','delete','record','terms','deletions','commit','empty','duplicate','engine'] as $failure){$wpdb=new Database($p);$opts=[$statsKey=>['records'=>777]];$wpdb->fail=in_array($failure,['empty','duplicate','engine'])?'':$failure;if($failure==='engine')$wpdb->engine='MyISAM';$input=$failure==='empty'?[]:($failure==='duplicate'?['services'=>[$data['services'][0],$data['services'][0]]]:$data);$threw=false;try{$class::rebuild($input);}catch(Throwable $e){$threw=true;}check($class.' preserves old index on '.$failure,$threw&&$wpdb->snapshot()===[1,1,1]&&$opts[$statsKey]===['records'=>777]);if($failure==='engine')check($class.' rejects engine before deletes',!$wpdb->queries);}
 $wpdb=new Database($p);$opts=[];$r=$class::rebuild($data);check($class.' publishes complete replacement',$r['records']===1&&$wpdb->db->querySingle("SELECT record_key FROM ".$p."knowledge")==='services:new'&&$wpdb->snapshot()[1]>0&&$wpdb->snapshot()[2]>0&&$opts[$statsKey]===$r);
}
echo json_encode($checks);
`});
const checks=JSON.parse(out.text);fs.writeFileSync('tests/results/v6-index-atomic.json',JSON.stringify(checks,null,2)+'\n');
console.log(JSON.stringify({checks:checks.length,failed:checks.filter(x=>!x.passed)}));assert(checks.every(x=>x.passed));php.exit();
