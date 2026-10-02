<?php
namespace OberHubV6\AI;
final class NullProvider implements AIProvider {
    public function answer(string $question,array $context): array { return ['mode'=>'deterministic','records'=>$context,'sources'=>array_column($context,'source_url')]; }
}
