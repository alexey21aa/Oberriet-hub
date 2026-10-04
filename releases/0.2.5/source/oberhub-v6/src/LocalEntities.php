<?php
namespace OberHubV6;
/** Separate ODbL discovery corpus. A map listing never becomes a verified offering. */
final class LocalEntities {
 private static ?array $pack=null;
 public static function pack():array {return self::$pack??=json_decode(file_get_contents(dirname(__DIR__).'/discovered-entities.json'),true)?:[];}
 public static function record(array $r):array {
  $titles=[];$descriptions=[];
  foreach(['de','en','ru','uk'] as $lang){$labels=[];foreach(($r['concept_ids']??[]) as $cid){$category=self::pack()['categories'][$cid]??[];$label=$category[$lang]??$category['en']??$category['de']??'';if($label)$labels[]=$label;}
   $labels=array_values(array_unique($labels));$prefix=implode(' / ',array_slice($labels,0,2));
   // Keep the official venue/business name untouched, but explain the category in the UI language.
   $titles[$lang]=($lang==='de'||$prefix==='')?$r['name']:$prefix.' — '.$r['name'];
   $badge=!empty($r['website'])
    ?['de'=>'Öffentlicher Karteneintrag — Anbieter-Website vorhanden','en'=>'Public map listing — provider website available','ru'=>'Публичная запись карты — есть сайт организации','uk'=>'Публічний запис карти — є сайт організації'][$lang]
    :['de'=>'Karteneintrag — aktueller Betrieb nicht bestätigt','en'=>'Map listing — current operation not confirmed','ru'=>'Запись карты — актуальность организации не подтверждена','uk'=>'Запис карти — актуальність організації не підтверджена'][$lang];
   $descriptions[$lang]=trim(($prefix?$prefix.' · ':'').$r['location_label'].' · '.$badge);
  }
  $parts=explode(':',$r['id']);$url='https://www.openstreetmap.org/'.$parts[1].'/'.$parts[2];
  $website=filter_var((string)($r['website']??''),FILTER_VALIDATE_URL)?(string)$r['website']:'';
  return array_merge($r,['title'=>$titles,'description_short'=>$descriptions,'description_full'=>$descriptions,'source_url'=>$url,'official_url'=>$website?:$url,'link_kind'=>$website?'provider-website':'map-source','source_trust_level'=>'D','status'=>'discovered','discovery_level'=>'open-data','verification_scope'=>$website?'map-listing-with-website':'map-listing','attribution'=>'© OpenStreetMap contributors · ODbL 1.0','offerings_verified'=>false,'opening_hours_verified'=>false,'current_operation_verified'=>false]);
 }
 public static function evidence(array $r):array {return ['source_url'=>$r['source_url'],'checked_at'=>$r['source_checked_at']??null,'confidence'=>'discovery','review_status'=>'map-listing','requires_confirmation'=>true,'opening_hours_verified'=>false,'attribution'=>$r['attribution']??'Public web discovery','stale'=>strtotime($r['source_checked_at']??'')<time()-30*86400];}
 public static function privateQuery(string $q):bool {
  if(preg_match('/[\w.+-]+@[\w.-]+\.[a-z]{2,}|\+?\d[\d\s().-]{8,}\d|\b756[.\s-]?\d{4}/iu',$q))return true;
  $personal=preg_match('/(?<![\p{L}\p{N}])(?:i|my|me|mine|ich|mir|mich|mein\w*|мне|меня|моя|мой|моего|я|моє|мій|мої|мене|мені)(?![\p{L}\p{N}])/iu',$q);
  return $personal && self::highRisk($q);
 }
 public static function highRisk(string $q):bool {return IntentPlan::make($q)['risky']||(bool)preg_match('/debt|income|salary|schulden|einkommen|долг|доход|зарплат|борг|дохід|abuse|violence|stalking|rape|gewalt|насил|угрож|погрож/iu',$q);}
 public static function concepts(string $q):array {
  $normalized=UniversalSearch::normalize($q);
  // Prefer narrow, ordinary category names over noisy taxonomy alias collisions.
  foreach([
   'osm:leisure:swimming_pool'=>['pool','pools','swimming pool','swimming pools','schwimmbad','schwimmbäder','schwimmbaeder','бассейн','бассейны','басейн','басейни'],
   'osm:leisure:playground'=>['playground','playgrounds','spielplatz','spielplätze','spielplaetze','детская площадка','детские площадки','дитячий майданчик'],
   'osm:amenity:restaurant'=>['restaurant','restaurants','ресторан','рестораны','ресторань'],
   'osm:amenity:cafe'=>['cafe','café','cafes','cafés','кафе'],
   'osm:amenity:bar'=>['bar','bars','бар','бары'],
   'osm:amenity:pharmacy'=>['pharmacy','pharmacies','apotheke','apotheken','аптека','аптеки'],
   'osm:tourism:hotel'=>['hotel','hotels','отель','отели','готель','готелі'],
   'osm:leisure:sauna'=>['sauna','saunas','сауна','сауны','сауни']
  ] as $cid=>$aliases)if(in_array($normalized,$aliases,true))return [$cid];
  $r=UniversalSearch::resolve($q);$ids=($r['concept_ids']??[]);
  if(!$ids){$words=explode(' ',UniversalSearch::normalize($q));for($n=min(5,count($words));$n>=1;$n--)for($i=0;$i<=count($words)-$n;$i++){$r=UniversalSearch::resolve(implode(' ',array_slice($words,$i,$n)),false);$ids=array_merge($ids,($r['concept_ids']??[]));}}
  return array_values(array_unique($ids));
 }
 public static function broad(string $q):?string {
  $tokens=Knowledge::tokens($q);if(count($tokens)!==1)return null;$word=$tokens[0];
  foreach(['family'=>['children','family','familie','семья','сім я'],'leisure'=>['leisure','freizeit','досуг','дозвілля'],'sport'=>['sport'],'food'=>['food','essen','еда','їжа'],'health'=>['health','gesundheit','здоровье','здоров я'],'shopping'=>['shopping','einkaufen','покупки'],'nightlife'=>['nightlife','night','nacht','ночь','ніч'],'household'=>['household','haushalt','быт','побут'],'people'=>['women','men','adults','frauen','manner','женщины','мужчины','жінки','чоловіки'],'seniors'=>['seniors','senioren','пенсионеры','пенсіонери']] as $facet=>$aliases)if(in_array($word,$aliases,true))return $facet;
  return null;
 }
 private static function inBroad(array $r,string $b):bool {
  $t=$r['category_tags']??[];$amenity=$t['amenity']??'';$leisure=$t['leisure']??'';
  return match($b){
   'family'=>in_array($amenity,['school','kindergarten','childcare','music_school','library'],true)||in_array($leisure,['playground','sports_centre','swimming_pool','water_park','pitch','park','dance'],true),
   'sport'=>isset($t['leisure'])&&!in_array($leisure,['adult_gaming_centre','gambling','brothel'],true),
   'food'=>in_array($amenity,['restaurant','cafe','fast_food','food_court','ice_cream'],true)||in_array($t['shop']??'',['bakery','supermarket','convenience','butcher','greengrocer'],true),
   'health'=>isset($t['healthcare'])||in_array($amenity,['doctors','dentist','pharmacy','clinic','hospital'],true),
   'shopping'=>isset($t['shop']),
   'nightlife'=>in_array($amenity,['bar','pub','nightclub','stripclub','casino'],true),
   'household'=>isset($t['craft'])||in_array($t['shop']??'',['hardware','doityourself','furniture','appliance','electronics'],true),
   'seniors'=>in_array($amenity,['social_facility','community_centre','library'],true)||in_array($leisure,['park','garden'],true),
   default=>isset($t['leisure'])||isset($t['tourism'])||in_array($amenity,['cinema','theatre','arts_centre'],true)
  };
 }
 public static function distance(array $r):?float {
  $p=$r['coordinates']??[];if(!isset($p['lat'],$p['lon'])||!is_numeric($p['lat'])||!is_numeric($p['lon']))return null;
  $lat=deg2rad((float)$p['lat']);$origin=deg2rad(47.32);$a=sin(($lat-$origin)/2)**2+cos($lat)*cos($origin)*sin(deg2rad((float)$p['lon']-9.568)/2)**2;
  return round(6371*2*asin(min(1,sqrt($a))),1);
 }
 public static function hits(string $q,string $locality='all'):array {
  if(self::highRisk($q)&&empty(IntentPlan::make($q)['categories']))return []; // Clinical/legal/tax facts remain in the primary-source lane.
  $plan=IntentPlan::make($q);$ids=$plan['categories']?:self::concepts($q);$b=self::broad($q);$query=UniversalSearch::normalize($q);$words=Knowledge::tokens($q);$hits=[];$poolSites=[];
  $suppressed=array_fill_keys(self::pack()['suppressed_ids']??[],true);
  foreach(self::pack()['records']??[] as $r){if(isset($suppressed[$r['id']??'']))continue;
   if($locality!=='all'&&Knowledge::normalize($r['locality'])!==Knowledge::normalize($locality))continue;
   $matched=array_intersect($ids,($r['concept_ids']??[]));$name=$r['normalized_name']??UniversalSearch::normalize($r['name']);$score=0;
   if($matched)$score=100+count($matched)*10;
   elseif($b&&self::inBroad($r,$b))$score=80;
   elseif(!$ids&&!$b&&$query!==''&&str_contains($name,$query))$score=150;
   elseif(!$ids&&!$b) { $terms=explode(' ',Knowledge::normalize($name));$coverage=count(array_intersect($words,$terms))/max(1,count($words));if($coverage>=.8)$score=60*$coverage; }
   if(!$score||!IntentPlan::matches($r,$plan))continue;
   if(!$b&&QueryUnderstanding::needsActivity($words)&&array_intersect($words,['children','women','adults','indoor'])&&!array_intersect($ids,['osm:leisure:playground','osm:leisure:swimming_pool']))continue;
   $r=self::record($r);
   if(($r['category_tags']['leisure']??'')==='swimming_pool'&&preg_match('/^(?:sportbecken|familienbecken|kleinkinder.becken|entspannungs.pool|schwimmbecken)/iu',$r['name']??'')){
    $nearest=null;$best=.25;
    foreach(self::pack()['records']??[] as $parent){if(!array_intersect($parent['concept_ids']??[],['osm:leisure:swimming_pool','osm:leisure:water_park','osm:leisure:sports_centre']))continue;if(preg_match('/becken|entspannungs.pool/iu',$parent['name']??''))continue;
     $a=$r['coordinates']??[];$parentPoint=$parent['coordinates']??[];if(!isset($a['lat'],$a['lon'],$parentPoint['lat'],$parentPoint['lon']))continue;
     $d=111*sqrt(((float)$a['lat']-(float)$parentPoint['lat'])**2+(cos(deg2rad((float)$a['lat']))*((float)$a['lon']-(float)$parentPoint['lon']))**2);
     if($d<$best){$best=$d;$nearest=$parent;}
    }
    if($nearest){$childName=$r['name'];$r=self::record($nearest);$r['components']=[$childName];$r['parent_id']=$nearest['id'];}
    else{$point=$r['coordinates'];$site=null;foreach($poolSites as $candidate){$d=111*sqrt(((float)$point['lat']-(float)$candidate['lat'])**2+(cos(deg2rad((float)$point['lat']))*((float)$point['lon']-(float)$candidate['lon']))**2);if($d<=.18){$site=$candidate['id'];break;}}if(!$site){$site=$r['id'];$poolSites[]=array_merge($point,['id'=>$site]);}$r['parent_id']='pool-site:'.$site;}

   }

   $r['distance_km']=self::distance($r);$r['distance_origin']='Oberriet centre';$r['geo_tier']=($r['locality']==='oberriet')?0:(($r['distance_km']??999)<=10?10:(($r['distance_km']??999)<=25?25:(($r['distance_km']??999)<=50?50:100)));$r['match_reason']=$matched?'category-match':($b?'broad-category':'name-or-alias');
   $score+=max(0,25-($r['distance_km']??25));$score+=!empty($r['website'])?25:-20;$hits[]=['record'=>$r,'type'=>'services','score'=>round($score,2),'evidence'=>self::evidence($r)];
  }
  usort($hits,fn($a,$b)=>(($a['record']['geo_tier']??100)<=>($b['record']['geo_tier']??100))?:($b['score']<=>$a['score'])?:strcmp($a['record']['id'],$b['record']['id']));
  if($b){$groups=[];foreach($hits as $h){$t=$h['record']['category_tags'];$key=implode(':',array_slice($t,0,1));$groups[$key][]=$h;}$hits=[];while($groups){foreach($groups as $k=>&$group){$hits[]=array_shift($group);if(!$group)unset($groups[$k]);}unset($group);}}
  return $hits;
 }
}
