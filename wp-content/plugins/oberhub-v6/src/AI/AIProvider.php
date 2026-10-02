<?php
namespace OberHubV6\AI;
interface AIProvider { public function answer(string $question,array $context): array; }
