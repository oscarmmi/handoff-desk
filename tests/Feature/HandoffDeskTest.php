<?php

use App\Models\ChatTurn;
use App\Models\Faq;
use App\Services\Llm\FakeLlmClient;
use App\Services\Llm\LlmClient;

function seedHarborFaqs(): void
{
    Faq::query()->create([
        'question' => 'How do I track my shipment?',
        'answer' => 'Use the tracking link in your shipping confirmation email. Tracking updates can take up to 24 hours after dispatch.',
        'keywords' => ['shipping', 'shipment', 'track', 'tracking', 'delivery', 'order'],
    ]);

    Faq::query()->create([
        'question' => 'What is your return policy?',
        'answer' => 'Unused items can be returned within 30 days of delivery.',
        'keywords' => ['return', 'returns', 'refund', 'exchange', 'policy'],
    ]);

    Faq::query()->create([
        'question' => 'When will my refund arrive?',
        'answer' => 'Refunds are issued within 5–7 business days after we receive your return.',
        'keywords' => ['refund', 'money back', 'reimbursement', 'when', 'arrive', 'arrival'],
    ]);
}

it('answers a shipping FAQ with a grounded answer and accepts a guest post', function () {
    seedHarborFaqs();
    $fake = new FakeLlmClient;
    app()->instance(LlmClient::class, $fake);

    $this->postJson(route('chat.store'), ['question' => 'How do I track my shipment?'])
        ->assertOk()
        ->assertJsonPath('turn.decision', 'answer')
        ->assertJsonPath('turn.answer', 'Use the tracking link in your shipping confirmation email. Tracking updates can take up to 24 hours after dispatch.')
        ->assertJsonPath('turn.reason', null)
        ->assertJsonPath('turn.handoff', null);

    expect($fake->calls)->toBe(1)
        ->and(ChatTurn::query()->count())->toBe(1);
});

it('sends an angry request to a human before any LLM call', function () {
    seedHarborFaqs();
    $fake = new FakeLlmClient;
    app()->instance(LlmClient::class, $fake);

    $this->postJson(route('chat.store'), [
        'question' => 'I am furious about my order. I want to speak to a person.',
    ])->assertOk()
        ->assertJsonPath('turn.decision', 'handoff')
        ->assertJsonPath('turn.reason', 'anger')
        ->assertJsonPath('turn.answer', null)
        ->assertJsonPath('turn.handoff.queue', 'priority-support');

    expect($fake->calls)->toBe(0)
        ->and(ChatTurn::query()->firstOrFail()->handoff)->not->toBeNull();
});

it('hands unknown questions to a human instead of asking the LLM to make something up', function () {
    seedHarborFaqs();
    $fake = new FakeLlmClient;
    app()->instance(LlmClient::class, $fake);

    $this->postJson(route('chat.store'), ['question' => 'Can you change the color of the moon?'])
        ->assertOk()
        ->assertJsonPath('turn.decision', 'handoff')
        ->assertJsonPath('turn.reason', 'unresolved')
        ->assertJsonPath('turn.answer', null)
        ->assertJsonPath('turn.handoff.queue', 'general-support');

    expect($fake->calls)->toBe(0);
});

it('binds the fake LLM client in the test environment', function () {
    expect(app(LlmClient::class))->toBeInstanceOf(FakeLlmClient::class);
});

it('falls back to the verified FAQ when the LLM client fails', function () {
    seedHarborFaqs();
    $client = Mockery::mock(LlmClient::class);
    $client->shouldReceive('answer')->once()->andThrow(new RuntimeException('Provider unavailable'));
    app()->instance(LlmClient::class, $client);

    $this->postJson(route('chat.store'), ['question' => 'How do I track my shipment?'])
        ->assertOk()
        ->assertJsonPath('turn.decision', 'answer')
        ->assertJsonPath('turn.answer', 'Use the tracking link in your shipping confirmation email. Tracking updates can take up to 24 hours after dispatch.');
});

it('renders the chat and FAQ suggestions for a guest', function () {
    seedHarborFaqs();

    $this->get(route('home'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Welcome')
            ->has('faqs', 3)
            ->where('llmEnabled', false));
});
