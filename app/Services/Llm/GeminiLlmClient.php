<?php

namespace App\Services\Llm;

use Illuminate\Support\Facades\Http;
use Throwable;

class GeminiLlmClient implements LlmClient
{
    public function answer(string $question, string $grounding): ?string
    {
        try {
            $model = config('services.gemini.model', 'gemini-2.5-flash');
            $key = config('services.gemini.key');
            $response = Http::connectTimeout(4)
                ->timeout(12)
                ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$key}", [
                    'systemInstruction' => [
                        'parts' => [[
                            'text' => 'Answer the customer using only the supplied Harbor Desk FAQ. If it does not answer the question, say you do not know.',
                        ]],
                    ],
                    'contents' => [[
                        'role' => 'user',
                        'parts' => [[
                            'text' => "FAQ answer: {$grounding}\nCustomer question: {$question}",
                        ]],
                    ]],
                    'generationConfig' => ['temperature' => 0.2, 'maxOutputTokens' => 180],
                ]);

            if (! $response->successful()) {
                return null;
            }

            $answer = $response->json('candidates.0.content.parts.0.text');

            return is_string($answer) && trim($answer) !== '' ? trim($answer) : null;
        } catch (Throwable) {
            return null;
        }
    }
}
