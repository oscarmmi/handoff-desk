<?php

namespace App\Services\Llm;

class FakeLlmClient implements LlmClient
{
    public int $calls = 0;

    public function answer(string $question, string $grounding): ?string
    {
        $this->calls++;

        return $grounding;
    }
}
