<?php
namespace OberHub\AI;
use OberHub\Knowledge;
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
    public static function answer(string $question,string $lang): array {
        $identity=hash_hmac('sha256',(string)($_SERVER['REMOTE_ADDR']??''),wp_salt('nonce'));
        $bucket='oh_ai_rate_'.substr($identity,0,24).'_'.(int)floor(time()/60);
        $count=(int)get_transient($bucket);
        if ($count>=12) return ['mode'=>'rate-limited','answer'=>null,'retry_after'=>60];
        set_transient($bucket,$count+1,70);
        $retrieval=Knowledge::search($question,$lang,1,7,'all','services');
        $records=array_column($retrieval['results'],'record');
        $registry=array_column(\OberHub\records('source'),null,'source_id');
        $context=[];$evidence=[];
        foreach ($records as $record) {
            $source=$registry[$record['source_id']??'']??[];
            $e=Sources::evidence($record,false,false);$evidence[]=$e;
            if (($source['review_status']??'')!=='checked' || !in_array($record['source_trust_level']??'',['A','B'],true) || !empty($e['stale'])) continue;
            // Only relevant, compact facts. No full corpus or personal staff contacts in a prompt.
            $context[]=array_intersect_key($record,array_flip(['id','title','description_short','next_action','source_url','source_checked_at','search_concepts']));
        }
        $fallback=['mode'=>'evidence-pack','answer'=>null,'records'=>$records,'sources'=>array_values(array_unique(array_column($records,'source_url'))),'evidence'=>$evidence,'generative_available'=>false,'retrieval'=>'server','language'=>$lang];
        if (!$context) return $fallback;
        $pii=preg_match('/[\w.+-]+@[\w.-]+\.[a-z]{2,}|\+?\d[\d\s().-]{8,}\d|\b756[.\s-]?\d{4}/iu',$question);
        $personal=preg_match('/(?<![\p{L}\p{N}])(?:i|my|me|mine|ich|mir|mich|mein|meine|meiner|мне|меня|моя|мой|моего|я|моє|мій|мої|мене|мені)(?![\p{L}\p{N}])/iu',$question);
        $sensitive=preg_match('/diagnos|symptom|medicin|medikament|hepat|cancer|болез|болит|диагноз|гепат|ліки|симптом|захвор|боль|debt|income|salary|steuer|schulden|einkommen|долг|доход|зарплат|борг|дохід|asylum|divorce|custody|arrest|scheidung|sorgerecht|развод|розлуч|опек|алименты|аліменти|убежищ|притул|abuse|violence|stalking|rape|gewalt|насил|угрож|погрож/iu',$question);
        if($pii||($personal&&$sensitive))return array_merge($fallback,['privacy'=>'sensitive-question-not-sent']);
        // Cache only anonymous activity intents. Arbitrary legal/medical/person-specific text is never cached.
        $tokens=Knowledge::tokens($question);$safeTokens=['sport','dance','children','women','adults','indoor','centres','centers','центры','центри','центров'];
        $cacheable=$tokens && !array_diff($tokens,$safeTokens);
        $fingerprint=hash('sha256',wp_json_encode([$context,$lang,get_option('oh_ai_config_version','1')]));
        $cacheKey='oh_ai_'.hash_hmac('sha256',Knowledge::normalize($question).'|'.$fingerprint,wp_salt('nonce'));
        if ($cacheable && ($cached=get_transient($cacheKey)) && is_array($cached)) return array_merge($cached,['cache'=>'hit']);
        $providers=defined('OBERHUB_AI_PROVIDERS') ? OBERHUB_AI_PROVIDERS : [];
        if (!is_array($providers)) $providers=[];
        $attempts=0;
        foreach (array_slice($providers,0,4) as $config) {
            if (empty($config['enabled']) || empty($config['free_tier']) || empty($config['endpoint']) || empty($config['model'])) continue;
            $id=hash('sha256',$config['endpoint'].'|'.$config['model']);
            if (get_transient('oh_ai_cooldown_'.$id)) continue;
            if (++$attempts>2) break;
            $provider=new OpenAICompatibleProvider((string)$config['endpoint'],(string)($config['key']??''),(string)$config['model']);
            $draft=$provider->answer('Answer in '.$lang.'. '.$question,$context);
            if (($draft['mode']??'')==='ai-draft' && self::valid($draft,$context)) {
                $answer=array_merge($draft,['mode'=>'server-ai','generative_available'=>true,'retrieval'=>'server','language'=>$lang,'cache'=>'miss','records'=>$records,'evidence'=>$evidence]);
                if ($cacheable) set_transient($cacheKey,$answer,600);
                return $answer;
            }
            set_transient('oh_ai_cooldown_'.$id,1,120);
        }
        return $fallback;
    }
}
