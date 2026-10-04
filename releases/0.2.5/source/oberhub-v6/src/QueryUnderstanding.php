<?php
namespace OberHubV6;
/** Small audited cross-language concept dictionary, shared with the offline adapter. */
final class QueryUnderstanding {
    public static function map(): array {
        static $map;
        if ($map === null) {
            $map=[];
            foreach (json_decode(file_get_contents(__DIR__.'/../query-concepts.json'),true) ?: [] as $concept=>$aliases) {
                foreach ($aliases as $alias) $map[Knowledge::normalize($alias)]=$concept;
            }
        }
        return $map;
    }
    public static function concept(string $token,bool $allowFuzzy=false): string {
        static $cache=[];$cacheKey=$token.'|'.(int)$allowFuzzy;if(isset($cache[$cacheKey]))return $cache[$cacheKey];
        $map=self::map();if(isset($map[$token]))return $map[$token];
        if(!$allowFuzzy||count(preg_split('//u',$token,-1,PREG_SPLIT_NO_EMPTY))<4)return $token;
        $matches=[];
        foreach($map as $alias=>$concept)if(Knowledge::distance($token,$alias)<=1)$matches[$concept]=true;
        $value=count($matches)===1 ? array_key_first($matches) : $token;
        if(count($cache)<10000)$cache[$cacheKey]=$value;return $value;
    }
    public static function needsActivity(array $tokens): bool {
        return (bool)array_intersect($tokens,['sport','dance','indoor']);
    }
    private static function texts($value):array {
        if(is_string($value))return [$value];
        $out=[];if(is_array($value))foreach($value as $item)$out=array_merge($out,self::texts($item));return $out;
    }
    public static function relevant(array $record,array $tokens): bool {
        // Specific clinical/safety intents must not match only a generic audience word.
        $anchors=array_intersect($tokens,['cancer','stoma','palliative','disability','violence','shelter']);
        if($anchors){
            $fields=[];foreach(['title','name','description_short','synonyms','aliases','question','search_concepts'] as $key)$fields=array_merge($fields,self::texts($record[$key]??[]));
            $recordTokens=Knowledge::tokens(implode(' ',$fields));
            if(array_diff($anchors,$recordTokens))return false;
            if(in_array('shelter',$anchors,true)&&in_array('women',$tokens,true)&&!in_array('women',$recordTokens,true))return false;
        }
        if (!self::needsActivity($tokens)) return true;
        $labels=$record['search_concepts'] ?? [];
        if (!$labels) return false;
        foreach (array_intersect($tokens,['sport','dance','indoor','children']) as $concept) {
            if (!in_array($concept,$labels,true)) return false;
        }
        if (in_array('women',$tokens,true) && !array_intersect($labels,['women','adults'])) return false;
        return true;
    }
}
