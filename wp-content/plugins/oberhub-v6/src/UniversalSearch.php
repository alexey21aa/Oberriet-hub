<?php
namespace OberHubV6;
/** V7 extends V6 using server-owned ontology labels. Never exposes raw POI discoveries. */
final class UniversalSearch {
    private static ?array $pack=null;
    private static function pack():array {
        if(self::$pack===null){
            $file=dirname(__DIR__).'/ontology.json';
            self::$pack=is_file($file)?(json_decode(file_get_contents($file),true)?:[]):[];
        }
        return self::$pack;
    }
    public static function normalize(string $text):string {
        if(class_exists('Normalizer'))$text=\Normalizer::normalize($text,\Normalizer::FORM_KC);
        if(function_exists('mb_strtolower'))$text=mb_strtolower($text,'UTF-8');
        else $text=strtolower(strtr($text,array_combine(preg_split('//u','АБВГДЕЖЗИЙКЛМНОПРСТУФХЦЧШЩЪЫЬЭЮЯІЇЄÄÖÜẞ',-1,PREG_SPLIT_NO_EMPTY),preg_split('//u','абвгдежзийклмнопрстуфхцчшщъыьэюяіїєäöüß',-1,PREG_SPLIT_NO_EMPTY))));
        $text=str_replace('ß','ss',$text); // Match Unicode casefold used by the canonical registry.
        return trim(preg_replace('/\s+/u',' ',preg_replace('/[^\p{L}\p{N}]/u',' ',$text)));
    }
    public static function resolve(string $query,bool $allowFuzzy=true):array {
        $q=self::normalize($query);$pack=self::pack();$terms=$pack['terms']??[];
        // Only complete phrases: a generic fragment must not redirect an unknown need.
        $ids=$terms[$q]??[];$method=$ids?'exact':'none';
        $length=count(preg_split('//u',$q,-1,PREG_SPLIT_NO_EMPTY));
        if(!$ids&&$allowFuzzy&&$length>=4){
            foreach($terms as $term=>$cids){
                if(abs(count(preg_split('//u',$term,-1,PREG_SPLIT_NO_EMPTY))-$length)>1)continue;
                if(Knowledge::distance($q,(string)$term)<=1){$ids=array_merge($ids,$cids);$method='one-edit';}
            }
        }
        $ids=array_values(array_unique($ids));sort($ids);
        return ['concept_ids'=>$ids,'ambiguous'=>count($ids)>1,'method'=>$method,'device_independent'=>true];
    }
    public static function search(string $query,string $lang='de',int $page=1,int $perPage=10,string $locality='all',string $type=''):array {
        $original=Knowledge::search($query,$lang,$page,$perPage,$locality,$type);
        $resolved=self::resolve($query,$original['total']===0);
        $original['ontology']=$resolved;
        // Preserve successful V6 results and pagination. Ambiguous aliases are not expanded.
        if($original['total']>0||count($resolved['concept_ids'])!==1)return $original;
        $cid=$resolved['concept_ids'][0];$labels=self::pack()['concepts'][$cid]['labels']??[];
        $choices=[];
        foreach(array_unique(array_values($labels)) as $label){
            if(self::normalize($label)===self::normalize($query))continue;
            $result=Knowledge::search($label,$lang,$page,$perPage,$locality,$type);
            // Single language candidate preserves server pagination; never combine partial pages.
            if($result['total']>0)$choices[]=['label'=>$label,'result'=>$result];
        }
        if(!$choices)return $original;
        // Prefer the German primary-provider label; otherwise stable first available translation.
        usort($choices,fn($a,$b)=>(($b['label']===($labels['de']??''))<=>($a['label']===($labels['de']??'')))?:strcmp($a['label'],$b['label']));
        $result=$choices[0]['result'];$result['ontology']=$resolved;
        $result['ontology']['expanded_query']=$choices[0]['label'];
        $result['ontology']['mode']='multilingual-label-fallback';
        return $result;
    }
    public static function status():array {return self::pack()['counts']??['concepts'=>0,'terms'=>0];}
}
