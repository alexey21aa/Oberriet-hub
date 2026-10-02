<?php
namespace OberHub;
/** Indexed, locally hosted civic knowledge. The index contains no visitor questions. */
require_once __DIR__.'/QueryUnderstanding.php';
final class Knowledge {
    const VERSION='2';
    public static function boot(): void {
        add_action('init',function(){if(class_exists(ImportQueue::class)&&ImportQueue::active())return; if(get_option('oh_knowledge_version')!==self::VERSION){ self::install(); self::rebuild(dataset()); } },30);
        add_action('updated_post_meta',function($mid,$pid,$key){ if($key==='_oh_record'&&get_post_type($pid)!=='oh_source'){update_option('oh_knowledge_dirty',1,false);} },10,3);
        add_action('deleted_post',function(){update_option('oh_knowledge_dirty',1,false);});
        add_action('shutdown',function(){if(!empty($GLOBALS['oberhub_defer_index'])||(class_exists(ImportQueue::class)&&ImportQueue::active()))return;if(get_option('oh_knowledge_dirty')){self::rebuild(dataset());delete_option('oh_knowledge_dirty');}});
    }
    public static function install(): void {
        global $wpdb;require_once ABSPATH.'wp-admin/includes/upgrade.php';$charset=$wpdb->get_charset_collate();$prefix=$wpdb->prefix.'oh_';
        dbDelta("CREATE TABLE {$prefix}knowledge (record_key varchar(190) NOT NULL, record_type varchar(24) NOT NULL, record_json longtext NOT NULL, phrases_json longtext NOT NULL, PRIMARY KEY (record_key)) $charset;");
        dbDelta("CREATE TABLE {$prefix}terms (term varchar(120) NOT NULL, record_key varchar(190) NOT NULL, weight smallint NOT NULL, PRIMARY KEY (term,record_key), KEY record_key (record_key)) $charset;");
        dbDelta("CREATE TABLE {$prefix}deletions (signature varchar(120) NOT NULL, term varchar(120) NOT NULL, PRIMARY KEY (signature,term)) $charset;");
        update_option('oh_knowledge_version',self::VERSION,false);
    }
    public static function normalize(string $text): string {
        $text=strtr($text,['Ä'=>'a','Ö'=>'o','Ü'=>'u','ä'=>'a','ö'=>'o','ü'=>'u','ß'=>'ss','Ё'=>'е','ё'=>'е','Ґ'=>'г','ґ'=>'г']);
        if(function_exists('mb_strtolower')){$text=mb_strtolower($text,'UTF-8');}else{$text=strtolower(strtr($text,array_combine(preg_split('//u','АБВГДЕЖЗИЙКЛМНОПРСТУФХЦЧШЩЪЫЬЭЮЯІЇЄ',-1,PREG_SPLIT_NO_EMPTY),preg_split('//u','абвгдежзийклмнопрстуфхцчшщъыьэюяіїє',-1,PREG_SPLIT_NO_EMPTY))));}
        if(class_exists('Normalizer')){$text=\Normalizer::normalize($text,\Normalizer::FORM_KD);}
        $text=preg_replace('/\p{M}/u','',$text);$text=preg_replace('/[^\p{L}\p{N}\s]/u',' ',$text);$text=preg_replace('/(?<![\p{L}\p{N}])доя(?![\p{L}\p{N}])/u','для',$text);return trim(preg_replace('/\s+/u',' ',$text));
    }
    public static function tokens(string $text): array {
        $stop=explode(' ','the a an of to in and for is i my do how where can me der die das und ein eine im in am mit von zu ich mein meine wo wie ist was на в и у а по за для з та що як де не мне мой моя це');
        return array_values(array_unique(array_map([QueryUnderstanding::class,'concept'],array_filter(explode(' ',self::normalize($text)),fn($w)=>self::length($w)>1&&!in_array($w,$stop,true)))));
    }
    private static function length(string $v):int{return count(preg_split('//u',$v,-1,PREG_SPLIT_NO_EMPTY));}
    private static function values($v):array {if(is_array($v)){$out=[];foreach($v as $item){$out=array_merge($out,self::values($item));}return $out;}return is_string($v)?[$v]:[];}
    private static function signatures(string $word):array{$a=[$word];$chars=preg_split('//u',$word,-1,PREG_SPLIT_NO_EMPTY);$n=count($chars);if($n>=4&&$n<=45){for($i=0;$i<$n;$i++){$copy=$chars;array_splice($copy,$i,1);$a[]=implode('',$copy);}}return array_unique($a);}
    public static function distance(string $a,string $b):int {
        if($a===$b)return 0;$aa=preg_split('//u',$a,-1,PREG_SPLIT_NO_EMPTY);$bb=preg_split('//u',$b,-1,PREG_SPLIT_NO_EMPTY);$n=count($aa);$m=count($bb);if(abs($n-$m)>1)return 2;$prev=range(0,$m);$before=null;
        for($i=1;$i<=$n;$i++){$row=[$i];for($j=1;$j<=$m;$j++){$row[$j]=min($row[$j-1]+1,$prev[$j]+1,$prev[$j-1]+($aa[$i-1]===$bb[$j-1]?0:1));if($i>1&&$j>1&&$aa[$i-1]===$bb[$j-2]&&$aa[$i-2]===$bb[$j-1]){$row[$j]=min($row[$j],$before[$j-2]+1);}}$before=$prev;$prev=$row;}return $prev[$m];
    }
    public static function rebuild(array $data):array {
        global $wpdb;$data['documents']=get_option('oh_documents',[]);$p=$wpdb->prefix.'oh_';$intentMap=[];$terms=[];$count=0;$navigation=[];foreach($data['answers']??[] as $answer){if(!empty($answer['service_id'])&&!empty($answer['kind']))$navigation[$answer['service_id']][$answer['kind']]=$answer['answer'];}$pendingTerms=[];$pendingDeletes=[];
        foreach($data['intents']??[] as $intent){$id=(string)($intent['service_id']??$intent['target_id']??$intent['id']);$intentMap[$id]=array_merge($intentMap[$id]??[],self::values($intent['phrases']??$intent['aliases']??$intent['question']??[]));}
        foreach($data['aliases']??[] as $alias){$id=(string)($alias['service_id']??'');$intentMap[$id]=array_merge($intentMap[$id]??[],self::values($alias['phrase']??[]));}
        $wpdb->query('START TRANSACTION');foreach(['knowledge','terms','deletions'] as $table){$wpdb->query("DELETE FROM {$p}{$table}");}
        foreach(['services','guides','faqs','answers','contacts','organizations','places','events','documents'] as $type){foreach($data[$type]??[] as $record){if(empty($record['id']))continue;if($type==='services'&&isset($navigation[$record['id']]))$record['_navigation_answers']=$navigation[$record['id']];$key=$type.':'.$record['id'];$fields=[[$record['title']??$record['name']??[],8],[$record['synonyms']??$record['aliases']??[],10],[$intentMap[(string)$record['id']]??[],12],[$record['question']??[],10],[$record['search_concepts']??[],12],[$record['keywords']??[],3],[$record['description_short']??[],2],[$record['answer']??[],2],[$record['description_full']??[],1]];$phrases=[];$weights=[];
            foreach($fields as [$value,$weight]){foreach(self::values($value) as $text){$phrase=self::normalize($text);if($weight>=8&&$phrase!=='')$phrases[]=['text'=>$phrase,'weight'=>$weight];foreach(self::tokens($text) as $term){if(self::length($term)>100)continue;$weights[$term]=max($weights[$term]??0,$weight);}}}
            $distinct=[];foreach($phrases as $phrase){if(!isset($distinct[$phrase['text']])||$distinct[$phrase['text']]['weight']<$phrase['weight'])$distinct[$phrase['text']]=$phrase;}$phrases=array_values($distinct);
            $wpdb->insert($p.'knowledge',['record_key'=>$key,'record_type'=>$type,'record_json'=>wp_json_encode($record,JSON_UNESCAPED_UNICODE),'phrases_json'=>wp_json_encode($phrases,JSON_UNESCAPED_UNICODE)]);
            foreach($weights as $term=>$weight){$pendingTerms[]=[$term,$key,$weight];if(count($pendingTerms)>=400){self::insertBatch($p.'terms',['term','record_key','weight'],$pendingTerms);$pendingTerms=[];}$terms[$term]=true;}$count++;
        }}
        self::insertBatch($p.'terms',['term','record_key','weight'],$pendingTerms);
        foreach(array_keys($terms) as $term){foreach(self::signatures((string)$term) as $signature){$pendingDeletes[]=[$signature,$term];if(count($pendingDeletes)>=400){self::insertBatch($p.'deletions',['signature','term'],$pendingDeletes);$pendingDeletes=[];}}}
        self::insertBatch($p.'deletions',['signature','term'],$pendingDeletes);
        $wpdb->query('COMMIT');$stats=['records'=>$count,'terms'=>count($terms),'indexed_at'=>gmdate('c')];update_option('oh_knowledge_stats',$stats,false);return $stats;
    }
    private static function localized($value,string $lang):string{return is_array($value)?(string)($value[$lang]??$value['de']??''):(is_string($value)?$value:'');}
    private static function quality(array $record,string $query,string $locality):float {
        $nearby=['oberriet'=>10,'montlingen'=>9,'kriessern'=>9,'eichenwies'=>9,'kobelwald'=>9,'altstatten'=>6,'rheintal'=>3,'grabs'=>1];$score=QueryUnderstanding::needsActivity(self::tokens($query))?($nearby[$record['locality']??'']??0):0.0;if(in_array($record['verification_scope']??'',['page','page-subscenario','primary-provider-page'],true))$score+=50;if(($record['status']??'')==='checked')$score+=.5;
        if(in_array($record['source_trust_level']??'', ['A','B'],true))$score+=.5;
        if(($record['verification_scope']??'')==='routing-metadata')$score-=.2;
        $checked=strtotime((string)($record['source_checked_at']??''));if($checked!==false&&$checked>=time()-90*DAY_IN_SECONDS)$score+=.3;
        $place=self::normalize((string)($record['locality']??''));if($place!==''&&$place!=='all'&&($place===self::normalize($locality)||str_contains($query,$place)))$score+=25;
        return $score;
    }
    private static function insertBatch(string $table,array $columns,array $rows):void {
        if(!$rows)return;global $wpdb;$format='('.implode(',',array_fill(0,count($columns),'%s')).')';$sql='INSERT IGNORE INTO '.$table.' ('.implode(',',$columns).') VALUES '.implode(',',array_fill(0,count($rows),$format));$args=[];foreach($rows as $row){foreach($row as $v)$args[]=$v;}$wpdb->query($wpdb->prepare($sql,$args));
    }
    public static function search(string $query,string $lang='de',int $page=1,int $perPage=10,string $locality='all',string $type=''):array {
        global $wpdb;$p=$wpdb->prefix.'oh_';$q=self::normalize($query);$words=array_slice(self::tokens($q),0,20);$intentWords=array_values(array_diff($words,['oberriet','montlingen','kriessern','eichenwies','kobelwald','altstatten','rheintal','heerbrugg','widnau','buchs','rebstein','marbach','ruthi','balgach','grabs','st','gallen','sankt','stgallen']));if($intentWords)$words=$intentWords;$page=max(1,min(1000,$page));$perPage=max(1,min(50,$perPage));$empty=['results'=>[],'total'=>0,'page'=>$page,'per_page'=>$perPage,'pages'=>0,'engine'=>'local-index'];if(self::length($q)<2||!$words)return $empty;$scores=[];$hits=[];
        foreach($words as $word){$candidates=[$word=>1.0];if(self::length($word)>=4){$sigs=self::signatures($word);$slots=implode(',',array_fill(0,count($sigs),'%s'));$matches=$wpdb->get_col($wpdb->prepare("SELECT DISTINCT term FROM {$p}deletions WHERE signature IN ($slots)",$sigs));foreach($matches as $term){if(self::distance($word,$term)<=1)$candidates[$term]=$term===$word?1.0:.65;}}
            $slots=implode(',',array_fill(0,count($candidates),'%s'));$rows=$wpdb->get_results($wpdb->prepare("SELECT record_key,term,weight FROM {$p}terms WHERE term IN ($slots)",array_keys($candidates)),ARRAY_A);
            if(!$rows&&self::length($word)>=3){$rows=$wpdb->get_results($wpdb->prepare("SELECT record_key,term,weight FROM {$p}terms WHERE term LIKE %s LIMIT 2000",$wpdb->esc_like($word).'%'),ARRAY_A);foreach($rows as $row)$candidates[$row['term']]=.7;}
            $best=[];foreach($rows as $row){$key=$row['record_key'];$best[$key]=max($best[$key]??0,(int)$row['weight']*($candidates[$row['term']]??.7));}foreach($best as $key=>$score){$scores[$key]=($scores[$key]??0)+$score;$hits[$key]=($hits[$key]??0)+1;}
        }
        if(!$scores)return $empty;$slots=implode(',',array_fill(0,count($scores),'%s'));$rows=$wpdb->get_results($wpdb->prepare("SELECT * FROM {$p}knowledge WHERE record_key IN ($slots)",array_keys($scores)),ARRAY_A);$results=[];
        foreach($rows as $row){$key=$row['record_key'];if($type!==''&&$row['record_type']!==$type)continue;$record=json_decode($row['record_json'],true);if(!QueryUnderstanding::relevant($record,$words))continue;$coverage=$hits[$key]/count($words);if($coverage<.5)continue;$score=$scores[$key]*$coverage+40*$coverage*$coverage;$phraseBonus=0;foreach(json_decode($row['phrases_json'],true)??[] as $phrase){if($phrase['text']===$q)$phraseBonus=max($phraseBonus,100+$phrase['weight']+($phrase['weight']>=10?30:0));elseif(self::length($phrase['text'])>=4&&str_contains($q,$phrase['text']))$phraseBonus=max($phraseBonus,30+$phrase['weight']);}$titleTokens=self::tokens(self::localized($record['title']??$record['name']??'', $lang));$score+=30*count(array_intersect($words,$titleTokens))/count($words);$score+=$phraseBonus;if(self::normalize(self::localized($record['title']??$record['name']??'', $lang))===$q)$score+=15;$score+=self::quality($record,$q,$locality);$results[]=['record'=>$record,'type'=>$row['record_type'],'score'=>round($score,2)];}
        usort($results,fn($a,$b)=>($b['score']<=>$a['score'])?:strcmp((string)$a['record']['id'],(string)$b['record']['id']));$unique=[];$seen=[];foreach($results as $hit){$record=$hit['record'];$family=(string)($record['service_id']??$record['id']);if(isset($seen[$family]))continue;$seen[$family]=true;$unique[]=$hit;}$results=$unique;$total=count($results);return ['results'=>array_slice($results,($page-1)*$perPage,$perPage),'total'=>$total,'page'=>$page,'per_page'=>$perPage,'pages'=>(int)ceil($total/$perPage),'engine'=>'local-index'];
    }
}
