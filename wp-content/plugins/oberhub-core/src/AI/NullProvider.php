<?php
namespace OberHub\AI;
final class NullProvider implements AIProvider {
    public function answer(string $question,array $context): array { return ['mode'=>'deterministic','records'=>$context,'sources'=>array_column($context,'source_url')]; }
}
