<?php

namespace Database\Seeders;

use App\Models\Faq;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $faqs = [
            [
                'question' => 'How do I track my shipment?',
                'answer' => 'Use the tracking link in your shipping confirmation email. Tracking updates can take up to 24 hours after dispatch.',
                'keywords' => ['shipping', 'shipment', 'track', 'tracking', 'delivery', 'order'],
            ],
            [
                'question' => 'What is your return policy?',
                'answer' => 'Unused items can be returned within 30 days of delivery. Start a return from your order confirmation page to receive a prepaid label.',
                'keywords' => ['return', 'returns', 'refund', 'exchange', 'send back', 'policy'],
            ],
            [
                'question' => 'When will my refund arrive?',
                'answer' => 'After we receive your return, refunds are issued to your original payment method within 5–7 business days.',
                'keywords' => ['refund', 'money back', 'reimbursement', 'when', 'arrive', 'arrival'],
            ],
        ];

        foreach ($faqs as $faq) {
            Faq::query()->updateOrCreate(['question' => $faq['question']], $faq);
        }
    }
}
