<?php

namespace App\Services\Llm;

interface LlmClient
{
    public function answer(string $question, string $grounding): ?string;
}
