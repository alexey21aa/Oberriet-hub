<?php
namespace OberHubV6\AI;
use OberHubV6\Knowledge;
use OberHub\Sources;
/** All visitors use the same server retrieval and provider policy. No client capability input. */
final class Gateway {
    public static function valid(array $payload,array $context): bool {
        if (!is_string($payload['answer']??null) || strlen($payload['answer'])<20 || strlen($payload['answer'])>12000) return false;
        $allowed=array_column($context,'source_url');
        $cited=$payload['sources']??[];
        if (!is_array($cited) || !$cited || array_diff($cited,$allowed)) return false;
        preg_match_all('~https?://[^\s<>"\)]+~u',$payload['answer'],$urls);
        foreach ($urls[0] as $url) if (!in_array(rtrim($url,'.;,'),$allowed,true)) return false;
        // Reject new money amounts or article numbers absent from supplied facts.
        preg_match_all('/(?:CHF|EUR|€)\s*\d+(?:[.,]\d+)?|\bArt\.?\s*\d+[a-z]*/iu',$payload['answer'],$claims);
        $facts=wp_json_encode($context);
        foreach($claims[0] as $claim)if(!str_contains($facts,$claim))return false;
        return true;
    }
    public static function answer(string $question,string $lang,string $locality='all',string $type=''): array {
        $identity=hash_hmac('sha256',(string)($_SERVER['REMOTE_ADDR']??''),wp_salt('nonce'));
        $bucket='oh_ai_rate_'.substr($identity,0,24).'_'.(int)floor(time()/60);
        $count=(int)get_transient($bucket);
        if ($count>=12) return ['mode'=>'rate-limited','answer'=>null,'retry_after'=>60];
        set_transient($bucket,$count+1,70);
        $retrieval=class_exists(\OberHubV6\UniversalSearch::class)?\OberHubV6\UniversalSearch::search($question,$lang,1,7,$locality,$type):Knowledge::search($question,$lang,1,7,$locality,$type);
        $records=array_column($retrieval['results'],'record');
        $registry=array_column(\OberHub\records('source'),null,'source_id');
        $context=[];$evidence=[];
        foreach ($records as $record) {
            $source=$registry[$record['source_id']??'']??[];
            $e=!empty($record['discovery_level'])?\OberHubV6\LocalEntities::evidence($record):Sources::evidence($record,false,false);$evidence[]=$e;
            if(!empty($record['discovery_level'])){
                if(!\OberHubV6\LocalEntities::highRisk($question)&&($record['discovery_level']==='open-data')&&empty($e['stale'])&&!empty($record['website']))$context[]=array_intersect_key($record,array_flip(['id','title','description_short','source_url','official_url','website','location_label','distance_km','distance_origin','discovery_level','attribution','trust','result_kind']));
                continue;
            }
            if (($source['review_status']??'')!=='checked' || !in_array($record['source_trust_level']??'',['A','B'],true) || !empty($e['stale'])) continue;
            // Routing/header metadata and unknown fetch state are never model facts.
            if (!in_array($record['verification_scope']??'', ['page','page-subscenario','primary-provider-page','structured-fact'],true)) continue;
            if (!in_array($source['verification_scope']??'', ['page','page-subscenario','primary-provider-page','structured-fact'],true)) continue;
            if (!in_array($source['fetch_status']??'', ['ok','current'],true)) continue;
            if (empty($record['source_url']) || ($source['source_url']??'')!==$record['source_url']) continue;
            // Only relevant, compact facts. No full corpus or personal staff contacts in a prompt.
            $context[]=array_intersect_key($record,array_flip(['id','title','description_short','next_action','source_url','source_checked_at','search_concepts','trust','result_kind','target']));
        }
        $fallback=['mode'=>'evidence-pack','answer'=>self::summary($records,$lang),'records'=>$records,'sources'=>array_values(array_unique(array_column($records,'source_url'))),'evidence'=>$evidence,'generative_available'=>false,'retrieval'=>'server','language'=>$lang,'live_fallback'=>$retrieval['live_fallback']??[]];
        if (!$context) return $fallback;
        $pii=preg_match('/[\w.+-]+@[\w.-]+\.[a-z]{2,}|\+?\d[\d\s().-]{8,}\d|\b756[.\s-]?\d{4}/iu',$question);
        $personal=preg_match('/(?<![\p{L}\p{N}])(?:i|my|me|mine|ich|mir|mich|mein|meine|meiner|мне|меня|моя|мой|моего|я|моє|мій|мої|мене|мені)(?![\p{L}\p{N}])/iu',$question);
        $sensitive=preg_match('/oncolog|onkolog|krebs|stoma|palliativ|nursing|pflege|онкол|паллиатив|паліатив|стом[а-яіїє]|diagnos|symptom|medicin|medikament|hepat|cancer|болез|болит|диагноз|гепат|ліки|симптом|захвор|боль|debt|income|salary|steuer|schulden|einkommen|долг|доход|зарплат|борг|дохід|asylum|divorce|custody|arrest|scheidung|sorgerecht|развод|розлуч|опек|алименты|аліменти|убежищ|притул|abuse|violence|stalking|rape|gewalt|насил|угрож|погрож/iu',$question);
        if($pii||($personal&&($sensitive||\OberHubV6\LocalEntities::highRisk($question))))return array_merge($fallback,['privacy'=>'sensitive-question-not-sent']);
        $pack=AnswerPacks::select($question,$lang,$context);
        $curated=($pack && self::valid($pack,$context))?array_merge($pack,['records'=>$records,'evidence'=>$evidence]):null;
        // Cache only anonymous activity intents. Arbitrary legal/medical/person-specific text is never cached.
        $tokens=Knowledge::tokens($question);$safeTokens=['sport','dance','children','women','adults','indoor','centres','centers','центры','центри','центров'];
        $cacheable=$tokens && !array_diff($tokens,$safeTokens);
        if(class_exists(\OberHubV6\LocalEntities::class)&&!\OberHubV6\LocalEntities::highRisk($question)&&!\OberHubV6\LocalEntities::privateQuery($question)){$concept=\OberHubV6\UniversalSearch::resolve($question,false);$cacheable=$cacheable||!empty($concept['concept_ids']);}
        $fingerprint=hash('sha256',wp_json_encode([$context,$lang,get_option('oh_ai_config_version','1')]));
        $cacheKey='oh025_ai_'.hash_hmac('sha256',Knowledge::normalize($question).'|'.$fingerprint,wp_salt('nonce'));
        if ($cacheable && ($cached=get_transient($cacheKey)) && is_array($cached)) return array_merge($cached,['cache'=>'hit']);
        $providers=ProviderSettings::providers();
        if (!is_array($providers)) $providers=[];
        $attempts=0;
        foreach (array_slice($providers,0,4) as $config) {
            if (empty($config['enabled']) || empty($config['free_tier']) || empty($config['endpoint']) || empty($config['model'])) continue;
            $id=hash('sha256',$config['endpoint'].'|'.$config['model']);
            if (get_transient('oh_ai_cooldown_'.$id)) continue;
            if (++$attempts>2) break;
            if(!ProviderSettings::reserve($config['id']??substr($id,0,16),15,200))continue;
            $provider=new OpenAICompatibleProvider((string)$config['endpoint'],(string)($config['key']??''),(string)$config['model']);
            $draft=$provider->answer('Answer in '.$lang.'. Use only the supplied facts; distinguish reviewed records from discovery. Do not invent offerings or treat raw search snippets as verified. '.$question,$context);
            if (($draft['mode']??'')==='ai-draft' && self::valid($draft,$context)) {
                update_option('oh_v8_ai_success_at',gmdate('c'),false);
                $answer=array_merge($draft,['mode'=>'server-ai','generative_available'=>true,'retrieval'=>'server','language'=>$lang,'cache'=>'miss','records'=>$records,'evidence'=>$evidence]);
                if ($cacheable) set_transient($cacheKey,$answer,600);
                return $answer;
            }
            set_transient('oh_ai_cooldown_'.$id,1,120);
        }
        return $curated??$fallback;
    }
    private static function summary(array $records,string $lang):string {
        $intro=['de'=>'Gefundene Optionen mit Quellen:','en'=>'Matching options with sources:','ru'=>'Найденные варианты по источникам:','uk'=>'Знайдені варіанти за джерелами:'][$lang]??'Matching options:';
        if(!$records)return ['de'=>'In den verfügbaren Quellen wurde keine passende Option gefunden. Bitte präzisieren Sie die Anfrage oder erweitern Sie den Ort.','en'=>'No matching option was found in the available sources. Try a more specific question or a wider area.','ru'=>'В доступных источниках подходящий вариант не найден. Уточните запрос или расширьте район поиска.','uk'=>'У доступних джерелах відповідний варіант не знайдено. Уточніть запит або розширте район пошуку.'][$lang];
        $lines=[$intro];foreach(array_slice($records,0,7) as $r){$title=$r['title']??$r['name']??'';$text=$r['description_short']??'';$title=is_array($title)?($title[$lang]??$title['de']??''):$title;$text=is_array($text)?($text[$lang]??$text['de']??''):$text;
          $label=(!empty($r['discovery_level'])&&!in_array($r['trust']??'', ['verified','reviewed'],true))?(['de'=>'[Karten-/Webeintrag — Aktualität prüfen]','en'=>'[Map/web listing — current status needs confirmation]','ru'=>'[Запись карты/поиска — актуальность уточняется]','uk'=>'[Запис карти/пошуку — актуальність уточнюється]'][$lang]):'';
          $lines[]=$title.($label?' '.$label:'').($text?' — '.$text:'').(isset($r['distance_km'])?' · '.$r['distance_km'].' km (Oberriet)':'');
        }return implode("\n\n",$lines);
    }
}
