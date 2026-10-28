<?php

namespace App\Http\Controllers;

use App\Models\ChatTurn;
use App\Models\Faq;
use App\Services\Handoff\HandleChatTurn;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ChatController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Welcome', [
            'turns' => ChatTurn::query()->with('handoff')->oldest()->get(),
            'handoffs' => ChatTurn::query()->with('handoff')->where('decision', 'handoff')->latest()->get(),
            'faqs' => Faq::query()->orderBy('id')->get(['id', 'question']),
            'llmEnabled' => (bool) config('services.gemini.key'),
        ]);
    }

    public function store(Request $request, HandleChatTurn $handler): JsonResponse
    {
        $validated = $request->validate([
            'question' => ['required', 'string', 'max:1000'],
        ]);

        $turn = $handler->handle($validated['question']);

        return response()->json(['turn' => $turn->load('handoff')], 200);
    }
}
