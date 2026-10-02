<?php
namespace OberHub\AI;
interface AIProvider { public function answer(string $question,array $context): array; }
