<?php
namespace OberHubV6;
final class IntentPlan {
 public static function make(string $q):array {
  $n=Knowledge::normalize($q);$words=Knowledge::tokens($q,true);
  $artifact=(bool)preg_match('/\b(formular\w*|form|forms|template|pdf|excel|download|application|selector|calculator|checklist|online tool|шаблон\w*|форм[аыуі]?|бланк\w*|скач\w*|завантаж\w*|заявлен\w*|заяв\w*|деклараци\w*|деклараці\w*)\b/iu',$n);
  $domains=[
   'tax'=>'tax|steuer|налог|подат|деклараци|деклараці',
   'law'=>'legal|recht|gesetz|court|divorce|scheidung|алим|алімен|развод|розлуч|право|закон|суд|custody',
   'welfare'=>'sozial|social welfare|пособ|социал|соціал|допомог',
   'residence'=>'asylum|permit|residence|anmeld|wohnsitz|bewilligung|убежищ|притул|пропис|реєстрац|регистрац',
   'school'=>'school|schule|школ',
   'medical'=>'diagnos|diagnostic|medicin|medizin|symptom|treat|лечени|лікуван|медицин|медицин|медицина|диагноз|діагноз|врач|лікар|страхов|страхув|versicherung|insurance'
  ];$domain='';foreach($domains as $d=>$pattern)if(preg_match('/'.$pattern.'/iu',$n)){$domain=$d;break;}
  $country='CH';foreach(['DE'=>'germany|deutschland|германи|німеччин','AT'=>'austria|osterreich|австри|австрі','LI'=>'liechtenstein|лихтеншт|ліхтеншт','UA'=>'ukraine|украин|україн'] as $c=>$pattern)if(preg_match('/'.$pattern.'/iu',$n)){$country=$c;break;}
  $offer='';$categories=[];$needles=[];$alias='';
  $mappings=[
   ['pizza','pizza|пицц|піца|піци|пицца',['osm:amenity:restaurant','osm:amenity:fast_food'],['pizza','pizz','пицц','піца'],'Pizza'],
   ['burger','burger|бургер',['osm:amenity:restaurant','osm:amenity:fast_food'],['burger','бургер'],'Burger'],
   ['kebab','kebab|kebap|doner|шаурм|шаверм|донер',['osm:amenity:restaurant','osm:amenity:fast_food'],['kebab','kebap','doner','döner','шаурм'],'Kebab'],
   ['haircut','hairdresser|haircut|coiffeur|friseur|парикмах|перукар|стриж',['osm:shop:hairdresser'],[],'Coiffeur'],
   ['nails','manicur|nagel|маникюр|манікюр',['osm:shop:beauty'],['nail','nagel','manicur','маникюр','манікюр'],'Nagelstudio'],
   ['phone-repair','phone repair|handy.*repar|ремонт телефона|ремонт телефону',['osm:shop:mobile_phone','osm:craft:electronics_repair'],['repair','repar','ремонт'],'Handyreparatur'],
   ['tyres','tyres|tires|reifen|шины|шини',['osm:shop:tyres'],[],'Reifen'],
   ['dentist','dentist|dentistry|zahnarzt|стоматолог',['osm:amenity:dentist','osm:healthcare:dentist'],[],'Zahnarzt'],
   ['pool','swimming pool|pool|schwimmbad|schwimmba|бассейн|басейн',['osm:leisure:swimming_pool','osm:leisure:water_park'],['pool','swimming','schwimm','freibad','hallenbad','badi','бассейн','басейн'],'Schwimmbad'],
   ['sauna','sauna|саун',['osm:leisure:sauna'],[],'Sauna']
  ];
  foreach($mappings as [$id,$pattern,$cats,$ns,$de])if(preg_match('/'.$pattern.'/iu',$n)){$offer=$id;$categories=$cats;$needles=$ns;$alias=$de;break;}
  if(!$offer&&count(explode(' ',$n))<=2&&strlen($n)>=4){foreach($mappings as [$id,$pattern,$cats,$ns,$de])foreach(explode('|',$pattern) as $term)if(!preg_match('/[.*+?()]/',$term)&&Knowledge::distance($n,$term)<=1){$offer=$id;$categories=$cats;$needles=$ns;$alias=$de;break 2;}}
  $providerDirectory=in_array($offer,['dentist'],true);
  $official=$domain!==''&&!$providerDirectory;
  $queries=[$q];if($alias!=='')$queries[]=$alias;
  if($domain==='tax'&&preg_match('/steuererkl|tax return|деклараци|деклараці/iu',$n))$queries=['Steuererklärung'];
  if($artifact&&$domain!==''&&$domain!=='tax'){$without=preg_replace('/\b(template|pdf|excel|download|шаблон\w*|бланк\w*|форм[аыуі]?|form|forms)\b/iu','',$n);if(trim($without)!=='')$queries[]=trim($without);}
  return ['query'=>$q,'normalized'=>$n,'tokens'=>$words,'tax_return'=>$domain==='tax'&&(bool)preg_match('/steuererkl|tax return|деклараци|деклараці/iu',$n),'domain'=>$domain,'official'=>$official,'risky'=>$domain!=='','artifact'=>$artifact,'country'=>$country,'canton'=>'SG','municipality'=>'Oberriet','offering'=>$offer,'categories'=>$categories,'needles'=>$needles,'queries'=>array_values(array_unique($queries))];
 }
 public static function matches(array $r,array $plan):bool {
  if(empty($plan['offering']))return true;
  $cats=$r['concept_ids']??[];foreach($r['category_tags']??[] as $k=>$v)$cats[]='osm:'.$k.':'.$v;
  $text=Knowledge::normalize(wp_json_encode(array_intersect_key($r,array_flip(['name','title','description_short','cuisine','offerings','category_tags','keywords','search_concepts','synonyms'])),JSON_UNESCAPED_UNICODE));
  if(in_array($plan['offering'],['pool','sauna'],true)&&preg_match('/restaurant|gastgewerbe|gastronom|pizzeria/iu',SearchPolicy::text($r['name']??$r['title']??''))&&!array_intersect($plan['categories'],$cats))return false;
  if($plan['offering']==='dentist'&&preg_match('/marketing|software|labor|labora|supply|handel/iu',SearchPolicy::text($r['name']??$r['title']??''))&&!array_intersect($plan['categories'],$cats))return false;
  if(in_array($plan['offering'],['pool','sauna','haircut','dentist','tyres'],true)&&array_intersect($plan['categories'],$cats))return true;
  if($plan['needles']){foreach($plan['needles'] as $needle)if(str_contains($text,Knowledge::normalize($needle)))return true;return false;}
  if(array_intersect($plan['categories'],$cats))return true;
  foreach(array_slice($plan['queries'],1) as $label)if(str_contains($text,Knowledge::normalize($label)))return true;
  return false;
 }
}
