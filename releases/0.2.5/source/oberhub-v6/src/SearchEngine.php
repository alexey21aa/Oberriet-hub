<?php
namespace OberHubV6;
/** Retrieval snapshot: rank and collapse before paging. Local results never wait for live web. */
final class SearchEngine {
 public static function search(string $q,string $lang,int $page,int $perPage,string $locality,string $type,bool $live=false):array {
  $page=max(1,min(1000,$page));$perPage=max(1,min(25,$perPage));$plan=IntentPlan::make($q);
  $empty=['results'=>[],'total'=>0,'page'=>$page,'per_page'=>$perPage,'pages'=>0,'engine'=>'v025-local-first'];
  if(strlen(trim($q))<2)return $empty;
  $stats=get_option('oh_v6_knowledge_stats',[]);
  $key='oh025_search_'.hash_hmac('sha256',Knowledge::normalize($q).'|'.$lang.'|'.$locality.'|'.$type.'|'.($stats['indexed_at']??'').'|'.get_option('oh_v6_index_dirty','').'|'.(int)$live,wp_salt('nonce'));
  $cacheable=!LocalEntities::privateQuery($q);$snapshot=$cacheable?get_transient($key):false;
  if(!is_array($snapshot)){
   $pool=[];$seen=[];$queryType=$type==='documents'?'':$type;
   foreach($plan['queries'] as $query){
    $rows=Knowledge::search($query,$lang,1,25,$locality,$queryType,true)['results'];
    // Administrative/compound searches preserve type, audience and all anchor requirements.
    if(in_array($type,['','services'],true))foreach(['organizations','places','documents'] as $other)$rows=array_merge($rows,Knowledge::search($query,$lang,1,25,$locality,$other,true)['results']);
    foreach($rows as $h){$id=($h['type']??'').':'.($h['record']['id']??'');if(isset($seen[$id]))continue;$seen[$id]=true;$pool[]=$h;}
   }
   $resolved=UniversalSearch::resolve($q);
   if(!$pool&&count($resolved['concept_ids'])===1){
    $cid=$resolved['concept_ids'][0];$ontology=json_decode(file_get_contents(dirname(__DIR__).'/ontology.json'),true);
    $label=$ontology['concepts'][$cid]['labels']['de']??'';
    if($label&&Knowledge::normalize($label)!==Knowledge::normalize($q))$pool=Knowledge::search($label,$lang,1,25,$locality,$queryType,true)['results'];
   }
   if(in_array($type,['','services','business','place'],true))$pool=array_merge($pool,LocalEntities::hits($q,$locality));
   $pool=self::prepare($pool,$lang,$plan,$locality,$type);
   $unique=SearchPolicy::collapse($pool);$localCount=count(array_filter($unique,fn($h)=>$h['record']['geo_tier']<=3));
   $fallback=['mode'=>'not-needed','attempted'=>false];
   // Explicit second-stage request, only after local cards have been delivered.
   if($live&&count($unique)<3&&!(count($unique)>0&&$plan['official'])){$f=LiveFallback::retrieve($q,$lang,$locality,count($unique));$fallback=$f['status'];$pool=self::prepare(array_merge($unique,$f['hits']),$lang,$plan,$locality,$type);$unique=SearchPolicy::collapse($pool);}
   $local=array_values(array_filter($unique,fn($h)=>$h['record']['geo_tier']<=3));
   $radius=0;$thresholds=[0=>5,1=>10,2=>15];
   foreach($thresholds as $tier=>$minimum){$count=count(array_filter($local,fn($h)=>$h['record']['geo_tier']<=$tier));if($count<$minimum)$radius=[0=>10,1=>25,2=>50][$tier];else break;}
   // Additional nearby results stay accessible on later pages. Foreign entries are offered only if CH options are scarce.
   if($plan['country']==='CH'&&$localCount>=15)$unique=array_values(array_filter($unique,fn($h)=>!in_array($h['record']['country'],['AT','LI','DE'],true)));
   $snapshot=['results'=>$unique,'total'=>count($unique),'ontology'=>$resolved,'intent'=>array_intersect_key($plan,array_flip(['domain','artifact','country','canton','municipality','offering'])),'live_fallback'=>$fallback,'local_total'=>$localCount,'geo_fallback'=>['origin'=>'Oberriet centre','selected_tier'=>$radius,'expanded'=>$radius>0,'tiers_km'=>[10,25,50]],'cache_version'=>SearchPolicy::VERSION,'engine'=>'v025-local-first'];
   if($cacheable)set_transient($key,$snapshot,300);
  }
  $total=$snapshot['total'];$snapshot['results']=array_slice($snapshot['results'],($page-1)*$perPage,$perPage);
  return array_merge($snapshot,['page'=>$page,'per_page'=>$perPage,'pages'=>(int)ceil($total/$perPage)]);
 }
 private static function prepare(array $pool,string $lang,array $plan,string $locality,string $type):array {
  $rows=[];
  foreach($pool as $h){$r=$h['record'];
   if(!IntentPlan::matches($r,$plan)||!SearchPolicy::allowed($r,$plan))continue;
   if($plan['domain']==='tax'){$tokens=Knowledge::tokens(wp_json_encode(array_intersect_key($r,array_flip(['title','name','description_short','search_concepts','synonyms','keywords'])),JSON_UNESCAPED_UNICODE));if(!in_array('tax',$tokens,true))continue;if(!empty($plan['tax_return'])&&!preg_match('/steuererkl|tax return|деклараци|деклараці/iu',wp_json_encode(array_intersect_key($r,array_flip(['title','name','description_short','search_concepts','synonyms','keywords'])),JSON_UNESCAPED_UNICODE)))continue;}

   if($locality!=='all'&&!in_array(Knowledge::normalize((string)($r['locality']??'')),[Knowledge::normalize($locality),'all'],true))continue;
   $h=SearchPolicy::normalizeHit($h,$lang,$plan);
   if($type==='documents'&&!in_array($h['result_kind'],['document','form'],true))continue;
   if($type!==''&&!in_array($type,['services','documents'],true)&&$h['type']!==$type&&$h['result_kind']!==$type)continue;
   if($h['record']['target']['url']==='')continue;
   $h['_rank']=SearchPolicy::tuple($h,$plan);$rows[]=$h;
  }
  usort($rows,fn($a,$b)=>$a['_rank']<=>$b['_rank']);return $rows;
 }
}
