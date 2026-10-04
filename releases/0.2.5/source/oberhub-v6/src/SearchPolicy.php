<?php
namespace OberHubV6;
/** Result policy shared by retrieval, API, and AI. Never upgrades map evidence to reviewed facts. */
final class SearchPolicy {
 const VERSION='0.2.5';
 public static function text($v,string $lang='de'):string {return is_array($v)?(string)($v[$lang]??$v['de']??$v['en']??''):(is_scalar($v)?(string)$v:'');}
 public static function clip(string $text,int $limit=300):string {
  $text=html_entity_decode(strip_tags($text),ENT_QUOTES|ENT_HTML5,'UTF-8');
  $text=preg_replace('/(?:accept all cookies|cookie settings|privacy preferences|alle cookies akzeptieren|manage consent|skip to content|toggle navigation)[^.!?]{0,160}/iu','',$text);
  $text=trim(preg_replace('/\s+/u',' ',$text));$chars=preg_split('//u',$text,-1,PREG_SPLIT_NO_EMPTY);
  return count($chars)>$limit?implode('',array_slice($chars,0,$limit-1)).'…':$text;
 }
 public static function https($url):string {
  if(!is_string($url)||!filter_var($url,FILTER_VALIDATE_URL))return '';
  $p=parse_url($url);if(($p['scheme']??'')!=='https'||isset($p['user'])||isset($p['pass'])||empty($p['host']))return '';
  if(filter_var($p['host'],FILTER_VALIDATE_IP)&&!filter_var($p['host'],FILTER_VALIDATE_IP,FILTER_FLAG_NO_PRIV_RANGE|FILTER_FLAG_NO_RES_RANGE))return '';
  return $url;
 }
 private static function artifactUrl(array $r):string {
  foreach(['artifact_url','form_url','download_url','document_url','selector_url','tool_url'] as $key)if($u=self::https($r[$key]??''))return $u;
  foreach($r['documents']??[] as $d)if(is_array($d)&&($u=self::https($d['url']??$d['source_url']??'')))return $u;
  return '';
 }
 public static function target(array $r,string $lang='de',string $type='services',bool $artifact=false):array {
  $kind=$r['result_kind']??rtrim($type,'s');$id=(string)($r['id']??'');
  if(($artifact||in_array($kind,['document','form'],true))&&($u=self::artifactUrl($r)))return ['url'=>$u,'internal'=>false,'action'=>'form'];
  if(preg_match('/^osm:(node|way|relation):(\d+)$/',$id,$m)){
   foreach(['website','official_url'] as $field)if(($u=self::https($r[$field]??''))&&!str_contains((string)parse_url($u,PHP_URL_HOST),'openstreetmap.org'))return ['url'=>$u,'internal'=>false,'action'=>'website'];
   return ['url'=>'https://www.openstreetmap.org/'.$m[1].'/'.$m[2],'internal'=>false,'action'=>'map'];
  }
  if(str_starts_with($id,'web:')||!empty($r['discovery_level'])){
   foreach(['official_url','source_url'] as $field)if($u=self::https($r[$field]??''))return ['url'=>$u,'internal'=>false,'action'=>'website'];
   return ['url'=>'','internal'=>false,'action'=>'source'];
  }
  if($kind==='service'){
   static $native=null;$native??=array_fill_keys(array_map('strval',array_column(\OberHub\records('service'),'id')),true);
   if(isset($native[$id]))return ['url'=>home_url('/'.$lang.'/services/'.rawurlencode($id).'/'),'internal'=>true,'action'=>'details'];
  }
  foreach(['official_url','source_url','url'] as $field)if($u=self::https($r[$field]??''))return ['url'=>$u,'internal'=>false,'action'=>in_array($kind,['form','document'],true)?'form':'website'];
  return ['url'=>'','internal'=>false,'action'=>'source'];
 }
 public static function country(array $r):string {
  $country=strtoupper((string)($r['country']??$r['country_code']??$r['jurisdiction']['country']??''));if(in_array($country,['CH','AT','LI','DE','UA'],true))return $country;
  $loc=Knowledge::normalize((string)($r['locality']??$r['location_label']??''));$compact=preg_replace('/[^a-z]/','',$loc);
  $at=explode(' ','dornbirn feldkirch hohenems bregenz bludenz hard rankweil gtzis gotzis lustenau lauterach brand altach lochau wolfurt klaus koblach sulz burs burserberg nuziders rthis rothis frastanz nenzing weiler zwischenwasser hchst hochst schwarzach melsat mader meiningen thuringen hrbranz horbranz gfis gofis alberschwende schlins gaiau fuach fussach ludesch');
  $li=explode(' ','vaduz schaan triesenberg triesen balzers ruggell gamprin eschen mauren schellenberg planken bendern');
  $ch=explode(' ','oberriet oberrietsg montlingen kriessern eichenwies kobelwald altstatten altstattensg ruthi rthisg eichberg rebstein marbach marbachsg balgach widnau diepoldsau au ausg stmargrethen stmargrethensg heerbrugg rheintal stgallen sankt gallen buchssg buchs sennwald appenzell rorschach arbon goldach gais wittenbach teufenar berneck heiden speicher mels trogen grabs wangs oberegg trubbach weissbad sevelen azmoos horn rorschacherberg rehetobel gams luchingen steinach sargans');
  if(in_array($compact,$at,true))return 'AT';if(in_array($compact,$li,true))return 'LI';if(in_array($compact,$ch,true))return 'CH';if(str_contains($compact,'lindau'))return 'DE';
  foreach(['website','official_url','source_url'] as $key){$h=strtolower((string)parse_url((string)($r[$key]??''),PHP_URL_HOST));foreach(['ch'=>'CH','at'=>'AT','li'=>'LI','de'=>'DE'] as $suffix=>$code)if(str_ends_with($h,'.'.$suffix))return $code;}
  return '';
 }
 public static function geo(array $r):int {
  $country=self::country($r);if(in_array($country,['AT','LI'],true))return 5;if($country!==''&&$country!=='CH')return 6;
  $loc=Knowledge::normalize((string)($r['locality']??''));if($country==='CH'&&preg_match('/(?:^|\.)(oberriet\.ch)$/',strtolower((string)parse_url($r['source_url']??'',PHP_URL_HOST))))return 0;if(in_array($loc,['oberriet','oberrietsg','montlingen','kriessern','eichenwies','kobelwald'],true))return 0;
  $d=$r['distance_km']??LocalEntities::distance($r);
  // A missing country remains unknown, even if a point is close to the border.
  if($country==='CH'&&$d!==null)return $d<=10?1:($d<=25?2:($d<=50?3:4));
  if($country==='CH')return 4;return 6;
 }
 public static function trust(array $r):string {
  if(($r['discovery_level']??'')==='web-snippet')return self::directory($r)?'directory':'web';
  if(!empty($r['discovery_level'])||str_starts_with((string)($r['id']??''),'osm:'))return !empty($r['website'])?'provider-site':'public-map';
  if(in_array($r['verification_scope']??'',['page','page-subscenario','primary-provider-page','structured-fact','official-form-selector'],true)&&in_array($r['status']??'',['checked','reviewed','verified'],true)&&in_array($r['source_trust_level']??'',['A','B'],true))return ($r['source_trust_level']==='A')?'verified':'reviewed';
  if(in_array($r['verification_scope']??'',['page','page-subscenario','primary-provider-page','structured-fact'],true))return 'reviewed';
  return self::directory($r)?'directory':(self::https($r['official_url']??'')?'provider-site':'public-map');
 }
 public static function directory(array $r):bool {
  $h=strtolower((string)parse_url((string)($r['source_url']??$r['official_url']??''),PHP_URL_HOST));
  return (bool)preg_match('/(?:^|\.)(?:tripadvisor\.[a-z.]+|local\.ch|search\.ch|yelp\.[a-z.]+|facebook\.com|youtube\.com)$/',$h);
 }
 public static function normalizeHit(array $hit,string $lang,array $plan):array {
  $r=$hit['record'];$type=$hit['type']??'services';
  if(!empty($plan['artifact'])){static $actions=null;$actions??=json_decode(file_get_contents(dirname(__DIR__).'/artifact-actions.json'),true)?:[];$action=$actions[$r['source_url']??'']??null;if($action){$r=array_merge($r,$action);}}
$discovery=!empty($r['discovery_level']);
  $kind=$discovery?(($r['discovery_level']??'')==='web-snippet'?'discovery':(isset($r['category_tags']['shop'])||isset($r['category_tags']['craft'])||isset($r['category_tags']['office'])||isset($r['category_tags']['amenity'])?'business':'place')):match($type){'services'=>'service','organizations'=>'organization','documents'=>'document','places'=>'place','events'=>'event','contacts'=>'contact',default=>'service'};
  if(!empty($r['artifact_url'])||!empty($r['form_url']))$kind='form';
  $r['result_kind']=$kind;$r['trust']=self::trust($r);$r['country']=self::country($r);
  $r['distance_km']=$r['distance_km']??LocalEntities::distance($r);$r['geo_tier']=self::geo($r);
  foreach(['title'=>120,'description_short'=>300] as $field=>$limit){if(is_array($r[$field]??null))foreach($r[$field] as &$v)$v=self::clip(self::text($v,$lang),$limit);else $r[$field]=self::clip(self::text($r[$field]??$r['name']??'',$lang),$limit);unset($v);}
  if($discovery){$r['description_full']=$r['description_short'];unset($r['raw_content'],$r['content'],$r['body']);}
  $r['target']=self::target($r,$lang,$type,!empty($plan['artifact']));
  $hit['record']=$r;$hit['result_kind']=$kind;$hit['trust']=$r['trust'];return $hit;
 }
 public static function allowed(array $r,array $plan):bool {
  if(empty($plan['official']))return true;
  $host=strtolower((string)parse_url((string)($r['source_url']??$r['official_url']??''),PHP_URL_HOST));
  $country=self::country($r);if($country!==''&&$country!==$plan['country'])return false;
  // Administrative fallback is confined to explicit official jurisdictions.
  if(!empty($r['discovery_level']))return $plan['country']==='CH'&&(bool)preg_match('/(?:^|\.)(sg\.ch|admin\.ch|oberriet\.ch|svasg\.ch)$/',$host);
  if($plan['domain']==='tax'&&$plan['country']==='CH'&&!preg_match('/(?:^|\.)(sg\.ch|admin\.ch|oberriet\.ch|ch\.ch)$/',$host))return false;
  $jur=$r['jurisdiction']??[];if(is_array($jur)&&!empty($jur['canton'])&&$plan['country']==='CH'&&strtoupper($jur['canton'])!==$plan['canton'])return false;
  return $country===$plan['country']||($country===''&&$plan['country']==='CH'&&preg_match('/(?:^|\.)(sg\.ch|admin\.ch|oberriet\.ch|svasg\.ch)$/',$host));
 }
 public static function tuple(array $hit,array $plan):array {
  $r=$hit['record'];$geo=$r['geo_tier'];$web=in_array($r['trust'],['web','directory'],true);
  $intent=2;
  $title=Knowledge::normalize(self::text($r['title']??$r['name']??''));
  foreach($plan['queries'] as $query){$n=Knowledge::normalize($query);if($title===$n){$intent=0;break;}if(strlen($n)>=4&&str_contains($title,$n))$intent=min($intent,1);}
  if(($r['match_reason']??'')==='category-match')$intent=min($intent,1);
  if(!empty($r['search_concepts'])&&!array_diff($plan['tokens'],$r['search_concepts']))$intent=0;

  if(($r['match_reason']??'')==='broad-category')$intent=3;if($web)$intent=4;
  $trust=array_search($r['trust'],['verified','reviewed','provider-site','public-map','directory','web'],true);
  // Lanes guarantee CH local > foreign website > web; text/website bonuses stay inside lanes.
  $lane=$web?4:($geo<=3?0:($geo===4?1:($geo===5?2:3)));
  $artifact=empty($plan['artifact'])?0:(in_array($r['result_kind'],['form','document'],true)||self::artifactUrl($r)!==''?0:(preg_match('/\.pdf(?:[?#]|$)|formular|formulare|download|etaxes|steuererklaerung/i',$r['target']['url']??'')?1:2));
  return [$lane,$intent,$artifact,$trust,$geo,!empty($r['website'])?0:1,-(float)($hit['score']??0),(float)($r['distance_km']??9999),(string)($r['id']??'')];
 }
 public static function collapse(array $hits):array {
  $out=[];$seen=[];$groups=[];
  foreach($hits as $hit){$r=$hit['record'];$id=(string)($r['service_id']??$r['id']);
   $name=Knowledge::normalize(self::text($r['provider_name']??$r['name']??$r['title']??''));
   $host=strtolower(preg_replace('/^www\./','',(string)parse_url((string)($r['website']??$r['official_url']??''),PHP_URL_HOST)));
   $keys=['id:'.$id];if($name!=='')$keys[]='name:'.$name.'|'.Knowledge::normalize((string)($r['location_label']??$r['locality']??'')).'|'.(isset($r['coordinates']['lat'])?round((float)$r['coordinates']['lat'],3).','.round((float)$r['coordinates']['lon'],3):'');
   if($name!==''&&$host&&!self::directory($r)&&$host!=='openstreetmap.org')$keys[]='host-name:'.$host.'|'.$name;
   foreach($keys as $key)if(isset($seen[$key])){$i=$seen[$key];if(!empty($r['components']))$out[$i]['record']['components']=array_values(array_unique(array_merge($out[$i]['record']['components']??[],$r['components'])));continue 2;}
   $parent=$r['parent_id']??$r['site_id']??$r['complex_id']??$r['enclosing_relation']??'';
   $child=preg_match('/sportbecken|familienbecken|kleinkind.*becken|entspannungs.*pool|schwimmbecken/iu',$name);
   $group=$parent!==''?'parent:'.$parent:($child&&$host&&$host!=='openstreetmap.org'?'facility:'.$host.'|'.($r['locality']??''):'');
   if($group!==''&&isset($groups[$group])){$i=$groups[$group];$out[$i]['record']['components'][]=self::text($r['title']??$r['name']??'');continue;}
   foreach($keys as $key)$seen[$key]=count($out);if($group!=='')$groups[$group]=count($out);$out[]=$hit;
  }
  return $out;
 }
}
