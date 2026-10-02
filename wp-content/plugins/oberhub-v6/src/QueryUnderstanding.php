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
    public static function concept(string $token): string {
        static $cache=[];if(isset($cache[$token]))return $cache[$token];
        $map=self::map();if(isset($map[$token]))return $map[$token];
        if(count(preg_split('//u',$token,-1,PREG_SPLIT_NO_EMPTY))<4)return $token;
        $matches=[];
        foreach($map as $alias=>$concept)if(Knowledge::distance($token,$alias)<=1)$matches[$concept]=true;
        $value=count($matches)===1 ? array_key_first($matches) : $token;
        if(count($cache)<10000)$cache[$token]=$value;return $value;
    }
    public static function needsActivity(array $tokens): bool {
        return (bool)array_intersect($tokens,['sport','dance','indoor']);
    }
    public static function relevant(array $record,array $tokens): bool {
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
