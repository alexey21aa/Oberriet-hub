<?php
namespace OberHubV6\AI;
use OberHubV6\Knowledge;
/** Prebuilt editorial answers. Exact evidence and freshness must still match at runtime. */
final class AnswerPacks {
    public static function select(string $question,string $lang,array $context): ?array {
        $tokens=Knowledge::tokens($question);
        // Unknown constraints, places, ages and personal details require normal retrieval.
        $allowed=['sport','dance','children','women','adults','indoor','centres','centers','центры','центри','центров'];
        if (!$tokens || array_diff($tokens,$allowed)) return null;
        $concepts=array_values(array_intersect($tokens,['sport','dance','children','women','adults','indoor']));
        sort($concepts);$key=implode('|',$concepts);
        static $packs;
        if ($packs===null) $packs=json_decode(file_get_contents(__DIR__.'/../../answer-packs.json'),true) ?: [];
        $pack=$packs[$key][$lang]??null;
        if (!$pack || empty($pack['answer']) || empty($pack['facts'])) return null;
        $available=array_column($context,null,'id');
        foreach($pack['facts'] as $fact) {
            $actual=$available[$fact['id']]??null;
            if (!$actual) return null;
            foreach($fact as $field=>$value) if (($actual[$field]??null)!=$value) return null;
        }
        return ['mode'=>'curated-answer-pack','answer'=>$pack['answer'],'sources'=>$pack['sources'],
            'generative_available'=>false,'retrieval'=>'server','language'=>$lang,'pack_id'=>$pack['id'],
            'generated_at'=>$pack['generated_at'],'requires_review'=>false];
    }
}
