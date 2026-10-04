<?php
namespace OberHubV6;
/** Bounded live retrieval. No paid tier, arbitrary URL fetch or visitor-supplied API endpoint. */
final class LiveFallback {
 public static function retrieve(string $q,string $lang,string $locality,int $existing=0):array {
  $empty=['hits'=>[],'status'=>['mode'=>'unavailable','attempted'=>false]];
  if(LocalEntities::privateQuery($q)){$empty['status']['mode']='private-query-local-only';return $empty;}
  $plan=IntentPlan::make($q);$ids=$plan['categories']?:LocalEntities::concepts($q);$safe=!LocalEntities::highRisk($q)&&count($ids)>0;
  $key='oh025_live_'.hash_hmac('sha256',UniversalSearch::normalize($q).'|'.$lang.'|'.$locality,wp_salt('nonce'));
  if($safe&&is_array($cached=get_transient($key)))return ['hits'=>$cached,'status'=>['mode'=>'cached-live','attempted'=>false,'cached_discoveries'=>count($cached)]];
  $hits=[];$attempted=[];
  if($safe){$tags=[];foreach($ids as $cid)if(preg_match('/^osm:(shop|craft|office|healthcare|amenity|leisure|tourism|club):([a-z0-9_]+)$/',$cid,$m))$tags[]=[$m[1],$m[2]];
   if($tags&&!get_transient('oh_v8_overpass_cooldown')&&add_option('oh_v8_overpass_lock',time(),'','no')){
    try {
     $attempted[]='overpass';$parts=[];foreach(array_slice(array_unique($tags,SORT_REGULAR),0,6) as [$tag,$value])$parts[]='nwr(around:50000,47.32,9.568)["name"]["'.$tag.'"="'.$value.'"];';
     $res=wp_safe_remote_post('https://overpass-api.de/api/interpreter',['timeout'=>4,'redirection'=>0,'limit_response_size'=>1500000,'headers'=>['User-Agent'=>'OberrietHub/0.8 (+https://oberriethub.ch)'],'body'=>['data'=>'[out:json][timeout:4];('.implode('',$parts).');out center tags 60;']]);
     $code=is_wp_error($res)?0:wp_remote_retrieve_response_code($res);set_transient('oh_v8_overpass_cooldown',1,in_array($code,[429,406],true)?300:30);
     if($code===200){$data=json_decode(wp_remote_retrieve_body($res),true);if(empty($data['remark']))foreach($data['elements']??[] as $e){$t=$e['tags']??[];if(empty($t['name'])||($t['access']??'')==='private')continue;if(array_filter(array_keys($t),fn($k)=>preg_match('/^(disused|abandoned|proposed):/',$k)))continue;
      $id='osm:'.$e['type'].':'.$e['id'];if(in_array($id,LocalEntities::pack()['suppressed_ids']??[],true))continue;$url='https://www.openstreetmap.org/'.$e['type'].'/'.$e['id'];$where=$t['addr:city']??'Nearby region';$title=array_fill_keys(['de','en','ru','uk'],sanitize_text_field($t['name']));$categories=array_intersect_key($t,array_flip(['shop','craft','office','healthcare','amenity','leisure','tourism','club','cuisine']));
      $r=['id'=>$id,'name'=>$title['de'],'title'=>$title,'description_short'=>array_fill_keys(['de','en','ru','uk'],implode(' · ',array_values($categories)).' · '.$where),'location_label'=>$where,'locality'=>Knowledge::normalize($where),'coordinates'=>$e['center']??['lat'=>$e['lat']??null,'lon'=>$e['lon']??null],'category_tags'=>$categories,'source_url'=>$url,'official_url'=>SearchPolicy::https($t['website']??$t['contact:website']??'')?:$url,'website'=>SearchPolicy::https($t['website']??$t['contact:website']??''),'source_checked_at'=>gmdate('c'),'discovery_level'=>'open-data','source_trust_level'=>'D','verification_scope'=>'map-listing','offerings_verified'=>false,'opening_hours'=>$t['opening_hours']??null,'opening_hours_verified'=>false,'attribution'=>'© OpenStreetMap contributors · ODbL 1.0'];$r['description_full']=$r['description_short'];$r['distance_km']=LocalEntities::distance($r);$r['distance_origin']='Oberriet centre';
      // Respect an explicitly selected locality; regional expansion remains visible under all.
      if($locality!=='all'&&Knowledge::normalize($where)!==Knowledge::normalize($locality))continue;
      $hits[]=['record'=>$r,'type'=>'services','score'=>75,'evidence'=>LocalEntities::evidence($r)];
     }}
    }finally{delete_option('oh_v8_overpass_lock');}
   }elseif((int)get_option('oh_v8_overpass_lock',0)<time()-30){delete_option('oh_v8_overpass_lock');}
  }
  $config=AI\ProviderSettings::config();
  if(count($hits)+$existing<3&&!empty($config['enabled'])&&!empty($config['tavily_key'])&&!get_transient('oh_v8_tavily_cooldown')&&AI\ProviderSettings::reserve('tavily',25,900,'month')){
   $attempted[]='tavily';$place=$locality==='all'?(IntentPlan::make($q)['official']?'Oberriet Kanton St. Gallen Schweiz':'Oberriet Rheintal Schweiz'):$locality;
   $res=wp_safe_remote_post('https://api.tavily.com/search',['timeout'=>4,'redirection'=>0,'limit_response_size'=>100000,'headers'=>['Content-Type'=>'application/json','Authorization'=>'Bearer '.$config['tavily_key']],'body'=>wp_json_encode(['query'=>$q.' '.$place,'search_depth'=>'basic','max_results'=>5,'include_answer'=>false,'include_raw_content'=>false]+(LocalEntities::highRisk($q)?['include_domains'=>['sg.ch','admin.ch','oberriet.ch','svasg.ch']]:[]))]);
   $code=is_wp_error($res)?0:wp_remote_retrieve_response_code($res);if($code===429)set_transient('oh_v8_tavily_cooldown',1,300);
   if($code===200){$data=json_decode(wp_remote_retrieve_body($res),true);foreach($data['results']??[] as $r){$url=$r['url']??'';if(!wp_http_validate_url($url)||strpos($url,'https://')!==0)continue;
    $title=array_fill_keys(['de','en','ru','uk'],SearchPolicy::clip((string)($r['title']??''),120));$summary=array_fill_keys(['de','en','ru','uk'],SearchPolicy::clip((string)($r['content']??''),300));
    $record=['id'=>'web:'.hash('sha256',$url),'title'=>$title,'name'=>$title['de'],'description_short'=>$summary,'description_full'=>$summary,'source_url'=>$url,'official_url'=>$url,'source_checked_at'=>gmdate('c'),'location_label'=>'Web result — verify locality','locality'=>'unknown','discovery_level'=>'web-snippet','verification_scope'=>'web-snippet','source_trust_level'=>'D','attribution'=>'Public web search result','offerings_verified'=>false];
    $hits[]=['record'=>$record,'type'=>'services','score'=>65,'evidence'=>LocalEntities::evidence($record)];
   }}
  }
  if($safe&&$hits){set_transient($key,$hits,86400);$known=get_option('oh_v8_cached_discovery_ids',[]);$known=array_filter($known,fn($expires)=>$expires>time());foreach($hits as $hit)$known[hash('sha256',$hit['record']['id'])]=time()+86400;update_option('oh_v8_cached_discovery_ids',array_slice($known,-5000,null,true),false);}
  if($safe&&!$hits&&$attempted)set_transient($key,[],300);
  return ['hits'=>$hits,'status'=>['mode'=>$hits?'live-discovery':($attempted?'attempted-no-results':'unavailable'),'attempted'=>(bool)$attempted,'providers'=>$attempted,'cached_discoveries'=>count($hits)]];
 }
}
