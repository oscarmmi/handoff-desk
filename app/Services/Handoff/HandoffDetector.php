<?php

namespace App\Services\Handoff;

class HandoffDetector
{
    /** @return array{reason: string, queue: string}|null */
    public function detect(string $question): ?array
    {
        $normalized = mb_strtolower($question);

        if (preg_match('/\b(angry|furious|unacceptable|ridiculous|scam|terrible|awful|disgusted|horrible|frustrated|upset|pissed|complaint)\b/u', $normalized) === 1) {
            return ['reason' => 'anger', 'queue' => 'priority-support'];
        }

        if (preg_match('/\b(vip|priority customer|premium member)\b/u', $normalized) === 1) {
            return ['reason' => 'vip', 'queue' => 'vip-support'];
        }

        if (preg_match('/\b(speak to|talk to|connect me to|human|real person|agent|representative)\b/u', $normalized) === 1) {
            return ['reason' => 'customer_request', 'queue' => 'customer-care'];
        }

        return null;
    }
}
