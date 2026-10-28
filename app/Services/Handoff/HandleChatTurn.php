<?php

namespace App\Services\Handoff;

use App\Models\ChatTurn;
use App\Models\Faq;
use App\Services\Llm\LlmClient;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class HandleChatTurn
{
    public function __construct(
        private readonly HandoffDetector $detector,
        private readonly LlmClient $llm,
    ) {}

    public function handle(string $question): ChatTurn
    {
        $handoff = $this->detector->detect($question);

        if ($handoff !== null) {
            return DB::transaction(function () use ($question, $handoff): ChatTurn {
                $turn = ChatTurn::create([
                    'question' => $question,
                    'decision' => 'handoff',
                    'reason' => $handoff['reason'],
                    'answer' => null,
                ]);

                $turn->handoff()->create([
                    'queue' => $handoff['queue'],
                    'summary' => mb_substr($question, 0, 500),
                ]);

                return $turn->load('handoff');
            });
        }

        $faq = $this->findFaq($question);

        if ($faq === null) {
            return DB::transaction(function () use ($question): ChatTurn {
                $turn = ChatTurn::create([
                    'question' => $question,
                    'decision' => 'handoff',
                    'reason' => 'unresolved',
                    'answer' => null,
                ]);

                $turn->handoff()->create([
                    'queue' => 'general-support',
                    'summary' => mb_substr($question, 0, 500),
                ]);

                return $turn->load('handoff');
            });
        }

        try {
            $answer = $this->llm->answer($question, $faq->answer);
        } catch (Throwable $exception) {
            Log::warning('Harbor Desk LLM failed; using the verified FAQ answer.', [
                'exception' => $exception::class,
            ]);
            $answer = null;
        }

        return ChatTurn::create([
            'question' => $question,
            'decision' => 'answer',
            'reason' => null,
            'answer' => is_string($answer) && trim($answer) !== '' ? $answer : $faq->answer,
        ]);
    }

    private function findFaq(string $question): ?Faq
    {
        $normalized = mb_strtolower($question);
        $bestMatch = null;
        $bestScore = 0;

        foreach (Faq::query()->get() as $faq) {
            $score = 0;
            foreach ($faq->keywords as $keyword) {
                if (str_contains($normalized, mb_strtolower($keyword))) {
                    $score++;
                }
            }

            if ($score > $bestScore) {
                $bestMatch = $faq;
                $bestScore = $score;
            }
        }

        return $bestMatch;
    }
}
