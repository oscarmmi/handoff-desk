<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, nextTick, ref } from 'vue';

interface ChatTurn {
    id: number;
    question: string;
    decision: 'answer' | 'handoff';
    reason: string | null;
    answer: string | null;
    created_at: string;
    handoff?: {
        id: number;
        queue: string;
        summary: string;
        created_at: string;
    } | null;
}

interface Faq {
    id: number;
    question: string;
}

const props = defineProps<{
    turns: ChatTurn[];
    handoffs: ChatTurn[];
    faqs: Faq[];
    llmEnabled: boolean;
}>();

const question = ref('');
const turns = ref([...props.turns]);
const handoffs = ref([...props.handoffs]);
const sending = ref(false);
const errorMessage = ref('');
const conversation = ref<HTMLElement | null>(null);
const openCount = computed(() => handoffs.value.length);

function formatTime(value: string): string {
    return new Intl.DateTimeFormat('en', {
        hour: 'numeric',
        minute: '2-digit',
    }).format(new Date(value));
}

function reasonLabel(reason: string | null): string {
    const labels: Record<string, string> = {
        anger: 'Customer is upset',
        vip: 'VIP customer',
        customer_request: 'Requested a person',
        unresolved: 'Needs a specialist',
    };

    return reason ? (labels[reason] ?? 'Needs a person') : 'Needs a person';
}

async function sendMessage(): Promise<void> {
    const message = question.value.trim();
    if (!message || sending.value) return;

    sending.value = true;
    errorMessage.value = '';

    try {
        const response = await fetch('/chat', {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN':
                    document.querySelector<HTMLMetaElement>(
                        'meta[name="csrf-token"]',
                    )?.content ?? '',
            },
            body: JSON.stringify({ question: message }),
        });

        const payload = await response.json();
        if (!response.ok) {
            throw new Error(
                payload.message ?? 'That message could not be sent. Try again.',
            );
        }

        const turn = payload.turn as ChatTurn;
        turns.value.push(turn);
        if (turn.decision === 'handoff') handoffs.value.unshift(turn);
        question.value = '';
        await nextTick();
        conversation.value?.scrollTo({
            top: conversation.value.scrollHeight,
            behavior: 'smooth',
        });
    } catch (error) {
        errorMessage.value =
            error instanceof Error
                ? error.message
                : 'Something went wrong. Please try again.';
    } finally {
        sending.value = false;
    }
}

function useSuggestion(value: string): void {
    question.value = value;
}
</script>

<template>
    <Head title="Harbor Desk" />
    <div class="desk-shell min-h-screen text-slate-900">
        <header class="border-b border-slate-200/80 bg-white/90">
            <div
                class="mx-auto flex h-[72px] max-w-[1440px] items-center justify-between px-5 sm:px-8"
            >
                <a
                    href="/"
                    class="flex items-center gap-3"
                    aria-label="Harbor Desk home"
                >
                    <span
                        class="flex size-10 items-center justify-center rounded-xl bg-slate-900 text-white shadow-sm"
                    >
                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            class="size-5"
                            aria-hidden="true"
                        >
                            <path
                                d="M4 18.5V5.5h3.1l4.9 6.3 4.9-6.3H20v13h-3.2v-8l-4.8 6-4.8-6v8H4Z"
                                fill="currentColor"
                            />
                        </svg>
                    </span>
                    <span>
                        <span class="block text-sm font-semibold tracking-tight"
                            >Harbor Desk</span
                        >
                        <span class="block text-xs text-slate-500"
                            >Customer support workspace</span
                        >
                    </span>
                </a>
                <div class="flex items-center gap-3">
                    <span
                        class="hidden items-center gap-2 rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-medium text-emerald-800 sm:flex"
                    >
                        <span
                            class="size-1.5 rounded-full bg-emerald-500"
                        ></span>
                        All systems operational
                    </span>
                    <div
                        class="flex size-9 items-center justify-center rounded-full bg-amber-100 text-xs font-semibold text-amber-900"
                        aria-label="Harbor support team"
                        title="Harbor support team"
                    >
                        HD
                    </div>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-[1440px] px-4 py-7 sm:px-8 sm:py-10">
            <section
                class="mb-7 flex flex-col justify-between gap-5 sm:flex-row sm:items-end"
            >
                <div>
                    <div
                        class="mb-2 flex items-center gap-2 text-xs font-medium text-slate-500"
                    >
                        <span>WORKSPACE</span>
                        <span class="text-slate-300">/</span>
                        <span class="text-slate-700">INBOX OVERVIEW</span>
                    </div>
                    <h1
                        class="text-2xl font-semibold tracking-tight text-slate-950 sm:text-[30px]"
                    >
                        Handoff desk
                    </h1>
                    <p class="mt-1.5 text-sm text-slate-500">
                        A little help from AI. A human whenever it matters.
                    </p>
                </div>
                <div class="flex items-center gap-2 text-xs text-slate-500">
                    <span
                        class="flex size-7 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-600"
                    >
                        <svg
                            viewBox="0 0 20 20"
                            fill="none"
                            class="size-4"
                            aria-hidden="true"
                        >
                            <path
                                d="M10 3v7l4 2M18 10a8 8 0 1 1-16 0 8 8 0 0 1 16 0Z"
                                stroke="currentColor"
                                stroke-width="1.5"
                                stroke-linecap="round"
                            />
                        </svg>
                    </span>
                    <span>Live workspace</span>
                </div>
            </section>

            <section
                class="mb-6 grid gap-4 sm:grid-cols-3"
                aria-label="Support metrics"
            >
                <article class="metric-card">
                    <div class="flex items-center justify-between">
                        <span class="metric-label">OPEN HANDOFFS</span>
                        <span class="metric-icon bg-amber-50 text-amber-700">
                            <svg
                                viewBox="0 0 20 20"
                                fill="none"
                                class="size-4"
                                aria-hidden="true"
                            >
                                <path
                                    d="M10 6v4l2.5 1.5M17.5 10a7.5 7.5 0 1 1-15 0 7.5 7.5 0 0 1 15 0Z"
                                    stroke="currentColor"
                                    stroke-width="1.5"
                                    stroke-linecap="round"
                                />
                            </svg>
                        </span>
                    </div>
                    <div class="mt-4 flex items-baseline gap-2">
                        <span
                            class="text-[28px] leading-none font-semibold tracking-tight"
                            >{{ openCount }}</span
                        >
                        <span class="text-xs text-slate-500"
                            >waiting for a teammate</span
                        >
                    </div>
                </article>
                <article class="metric-card">
                    <div class="flex items-center justify-between">
                        <span class="metric-label">AI RESOLUTION</span>
                        <span class="metric-icon bg-sky-50 text-sky-700">
                            <svg
                                viewBox="0 0 20 20"
                                fill="none"
                                class="size-4"
                                aria-hidden="true"
                            >
                                <path
                                    d="m10 2 1.6 5.1L17 9l-5.4 1.9L10 16l-1.6-5.1L3 9l5.4-1.9L10 2Z"
                                    stroke="currentColor"
                                    stroke-width="1.4"
                                    stroke-linejoin="round"
                                />
                                <path
                                    d="m16 13 .7 2.3L19 16l-2.3.7L16 19l-.7-2.3L13 16l2.3-.7L16 13Z"
                                    fill="currentColor"
                                />
                            </svg>
                        </span>
                    </div>
                    <div class="mt-4 flex items-baseline gap-2">
                        <span
                            class="text-[28px] leading-none font-semibold tracking-tight"
                            >FAQ</span
                        >
                        <span class="text-xs text-slate-500"
                            >grounded answers only</span
                        >
                    </div>
                </article>
                <article class="metric-card">
                    <div class="flex items-center justify-between">
                        <span class="metric-label">HUMAN SAFEGUARD</span>
                        <span class="metric-icon bg-violet-50 text-violet-700">
                            <svg
                                viewBox="0 0 20 20"
                                fill="none"
                                class="size-4"
                                aria-hidden="true"
                            >
                                <path
                                    d="M10 2.5 16 5v4.8c0 3.5-2.4 6.3-6 7.7-3.6-1.4-6-4.2-6-7.7V5l6-2.5Z"
                                    stroke="currentColor"
                                    stroke-width="1.5"
                                    stroke-linejoin="round"
                                />
                                <path
                                    d="m7.5 9.8 1.7 1.7 3.4-3.6"
                                    stroke="currentColor"
                                    stroke-width="1.5"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                />
                            </svg>
                        </span>
                    </div>
                    <div class="mt-4 flex items-baseline gap-2">
                        <span
                            class="text-[28px] leading-none font-semibold tracking-tight"
                            >Always on</span
                        >
                        <span class="text-xs text-slate-500"
                            >anger · VIP · unsure</span
                        >
                    </div>
                </article>
            </section>

            <section
                class="grid items-start gap-5 xl:grid-cols-[minmax(0,1fr)_350px]"
            >
                <article
                    class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-[0_2px_8px_rgba(15,23,42,0.035)]"
                >
                    <header
                        class="flex items-center justify-between border-b border-slate-100 px-5 py-4 sm:px-6"
                    >
                        <div class="flex items-center gap-3">
                            <div
                                class="flex size-10 items-center justify-center rounded-xl bg-[#e9f2ef] text-sm font-semibold text-[#245a50]"
                            >
                                H
                            </div>
                            <div>
                                <h2
                                    class="text-sm font-semibold text-slate-900"
                                >
                                    Harbor Desk bot
                                </h2>
                                <p
                                    class="mt-0.5 flex items-center gap-1.5 text-xs text-slate-500"
                                >
                                    <span
                                        class="size-1.5 rounded-full bg-emerald-500"
                                    ></span>
                                    Online · Support assistant
                                </p>
                            </div>
                        </div>
                        <span
                            class="rounded-md border border-slate-200 px-2.5 py-1 text-[11px] font-medium text-slate-500"
                            >DEMO CONVERSATION</span
                        >
                    </header>

                    <div
                        ref="conversation"
                        class="conversation-panel space-y-6 px-5 py-6 sm:px-8"
                    >
                        <div class="flex justify-center">
                            <span
                                class="rounded-full bg-white px-3 py-1 text-[11px] font-medium text-slate-400 shadow-sm ring-1 ring-slate-200/70"
                                >Today</span
                            >
                        </div>
                        <div class="flex items-start gap-3">
                            <div
                                class="mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-full bg-[#e9f2ef] text-xs font-semibold text-[#245a50]"
                            >
                                H
                            </div>
                            <div class="max-w-[88%]">
                                <div class="message-bubble assistant-bubble">
                                    <p class="text-sm leading-6 text-slate-700">
                                        Hi there! I’m the Harbor Desk assistant.
                                        I can help with shipping, returns, and
                                        refunds. If something needs a person,
                                        I’ll get the right teammate.
                                    </p>
                                </div>
                                <p
                                    class="mt-1.5 pl-1 text-[11px] text-slate-400"
                                >
                                    Harbor Desk · Just now
                                </p>
                            </div>
                        </div>

                        <div
                            v-for="turn in turns"
                            :key="turn.id"
                            class="space-y-4"
                        >
                            <div class="flex justify-end">
                                <div class="max-w-[88%]">
                                    <div class="message-bubble customer-bubble">
                                        <p class="text-sm leading-6 text-white">
                                            {{ turn.question }}
                                        </p>
                                    </div>
                                    <p
                                        class="mt-1.5 pr-1 text-right text-[11px] text-slate-400"
                                    >
                                        You · {{ formatTime(turn.created_at) }}
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3">
                                <div
                                    class="mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-full bg-[#e9f2ef] text-xs font-semibold text-[#245a50]"
                                >
                                    H
                                </div>
                                <div class="max-w-[88%]">
                                    <div
                                        v-if="turn.decision === 'handoff'"
                                        class="message-bubble handoff-bubble"
                                    >
                                        <span
                                            class="mb-2 inline-flex items-center gap-1.5 rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-semibold tracking-wide text-amber-800 uppercase"
                                        >
                                            <svg
                                                viewBox="0 0 16 16"
                                                fill="none"
                                                class="size-3"
                                                aria-hidden="true"
                                            >
                                                <path
                                                    d="M8 2 14 13H2L8 2Z"
                                                    stroke="currentColor"
                                                    stroke-width="1.3"
                                                    stroke-linejoin="round"
                                                />
                                                <path
                                                    d="M8 6v3M8 11h.01"
                                                    stroke="currentColor"
                                                    stroke-width="1.3"
                                                    stroke-linecap="round"
                                                />
                                            </svg>
                                            Human handoff
                                        </span>
                                        <p
                                            class="text-sm leading-6 text-slate-700"
                                        >
                                            I’m bringing in a teammate who can
                                            help you personally. I’ve added this
                                            conversation to the
                                            {{
                                                turn.handoff?.queue?.replaceAll(
                                                    '-',
                                                    ' ',
                                                )
                                            }}
                                            queue.
                                        </p>
                                        <p
                                            class="mt-2 text-xs font-medium text-amber-800"
                                        >
                                            {{ reasonLabel(turn.reason) }}
                                        </p>
                                    </div>
                                    <div
                                        v-else
                                        class="message-bubble assistant-bubble"
                                    >
                                        <p
                                            class="text-sm leading-6 text-slate-700"
                                        >
                                            {{ turn.answer }}
                                        </p>
                                        <p
                                            class="mt-2 flex items-center gap-1 text-[10px] font-medium text-[#397267]"
                                        >
                                            <svg
                                                viewBox="0 0 16 16"
                                                fill="none"
                                                class="size-3"
                                                aria-hidden="true"
                                            >
                                                <path
                                                    d="m3 8 3.2 3.2L13 4.5"
                                                    stroke="currentColor"
                                                    stroke-width="1.5"
                                                    stroke-linecap="round"
                                                    stroke-linejoin="round"
                                                />
                                            </svg>
                                            Answered from Harbor Desk FAQ
                                        </p>
                                    </div>
                                    <p
                                        class="mt-1.5 pl-1 text-[11px] text-slate-400"
                                    >
                                        Harbor Desk ·
                                        {{ formatTime(turn.created_at) }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div
                            v-if="sending"
                            class="flex items-start gap-3"
                            aria-live="polite"
                        >
                            <div
                                class="flex size-8 items-center justify-center rounded-full bg-[#e9f2ef] text-xs font-semibold text-[#245a50]"
                            >
                                H
                            </div>
                            <div
                                class="message-bubble assistant-bubble flex gap-1.5 py-4"
                            >
                                <span class="typing-dot"></span
                                ><span class="typing-dot delay-100"></span
                                ><span class="typing-dot delay-200"></span>
                            </div>
                        </div>
                    </div>

                    <div
                        class="border-t border-slate-100 bg-white px-5 py-4 sm:px-6"
                    >
                        <div
                            v-if="turns.length === 0"
                            class="mb-3 flex flex-wrap gap-2"
                        >
                            <button
                                v-for="faq in faqs"
                                :key="faq.id"
                                type="button"
                                class="suggestion-chip"
                                @click="useSuggestion(faq.question)"
                            >
                                {{ faq.question }}
                            </button>
                            <button
                                type="button"
                                class="suggestion-chip"
                                @click="
                                    useSuggestion(
                                        'I am furious about my order and need to speak to a person.',
                                    )
                                "
                            >
                                Try a handoff ↗
                            </button>
                        </div>
                        <form
                            class="flex items-end gap-2 rounded-xl border border-slate-200 bg-white p-2 shadow-sm transition focus-within:border-[#86aaa2] focus-within:ring-4 focus-within:ring-[#e5f0ed]"
                            @submit.prevent="sendMessage"
                        >
                            <label for="chat-question" class="sr-only"
                                >Ask Harbor Desk</label
                            >
                            <textarea
                                id="chat-question"
                                v-model="question"
                                rows="1"
                                maxlength="1000"
                                placeholder="Ask a question or try ‘I need to speak to a person’…"
                                class="max-h-32 min-h-10 flex-1 resize-y border-0 bg-transparent px-2 py-2 text-sm text-slate-800 outline-none placeholder:text-slate-400 focus:ring-0"
                                @keydown.enter.exact.prevent="sendMessage"
                            ></textarea>
                            <button
                                type="submit"
                                :disabled="sending || !question.trim()"
                                class="flex h-10 shrink-0 items-center gap-2 rounded-lg bg-slate-900 px-4 text-xs font-semibold text-white transition hover:bg-slate-700 disabled:cursor-not-allowed disabled:bg-slate-300"
                            >
                                <span>{{ sending ? 'Sending' : 'Send' }}</span>
                                <svg
                                    v-if="!sending"
                                    viewBox="0 0 16 16"
                                    fill="none"
                                    class="size-3.5"
                                    aria-hidden="true"
                                >
                                    <path
                                        d="M14 2 7.5 14l-1.2-5.3L2 6.5 14 2Z"
                                        stroke="currentColor"
                                        stroke-width="1.4"
                                        stroke-linejoin="round"
                                    />
                                    <path
                                        d="m6.3 8.7 3.1-3.1"
                                        stroke="currentColor"
                                        stroke-width="1.4"
                                        stroke-linecap="round"
                                    />
                                </svg>
                            </button>
                        </form>
                        <p
                            v-if="errorMessage"
                            role="alert"
                            class="mt-2 text-xs text-rose-600"
                        >
                            {{ errorMessage }}
                        </p>
                        <p class="mt-2 text-center text-[10px] text-slate-400">
                            AI can make mistakes. Sensitive or unresolved
                            requests go straight to a human.
                        </p>
                    </div>
                </article>

                <aside class="space-y-5">
                    <section
                        class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-[0_2px_8px_rgba(15,23,42,0.035)]"
                    >
                        <header
                            class="flex items-center justify-between border-b border-slate-100 px-5 py-4"
                        >
                            <div>
                                <h2
                                    class="text-sm font-semibold text-slate-900"
                                >
                                    Human queue
                                </h2>
                                <p class="mt-0.5 text-xs text-slate-500">
                                    Needs a teammate’s attention
                                </p>
                            </div>
                            <span
                                class="flex min-w-6 items-center justify-center rounded-full bg-amber-100 px-2 py-1 text-[11px] font-semibold text-amber-800"
                                >{{ openCount }}</span
                            >
                        </header>
                        <div
                            v-if="handoffs.length"
                            class="divide-y divide-slate-100"
                        >
                            <article
                                v-for="turn in handoffs"
                                :key="turn.id"
                                class="px-5 py-4"
                            >
                                <div
                                    class="flex items-start justify-between gap-3"
                                >
                                    <span
                                        class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-2 py-1 text-[10px] font-semibold text-amber-800 ring-1 ring-amber-200/70"
                                    >
                                        <span
                                            class="size-1.5 rounded-full bg-amber-500"
                                        ></span
                                        >{{ reasonLabel(turn.reason) }}
                                    </span>
                                    <span
                                        class="shrink-0 text-[10px] text-slate-400"
                                        >{{ formatTime(turn.created_at) }}</span
                                    >
                                </div>
                                <p
                                    class="mt-2.5 line-clamp-2 text-xs leading-5 text-slate-600"
                                >
                                    {{ turn.question }}
                                </p>
                                <div
                                    class="mt-3 flex items-center justify-between border-t border-slate-100 pt-3"
                                >
                                    <span
                                        class="text-[10px] font-medium tracking-wide text-slate-400 uppercase"
                                        >Assigned queue</span
                                    >
                                    <span
                                        class="rounded-md bg-slate-100 px-2 py-1 text-[10px] font-semibold text-slate-700"
                                        >{{
                                            turn.handoff?.queue?.replaceAll(
                                                '-',
                                                ' ',
                                            )
                                        }}</span
                                    >
                                </div>
                            </article>
                        </div>
                        <div v-else class="px-5 py-8 text-center">
                            <span
                                class="mx-auto flex size-10 items-center justify-center rounded-full bg-emerald-50 text-emerald-700"
                            >
                                <svg
                                    viewBox="0 0 20 20"
                                    fill="none"
                                    class="size-5"
                                    aria-hidden="true"
                                >
                                    <path
                                        d="m5 10 3.2 3.2L15.5 6"
                                        stroke="currentColor"
                                        stroke-width="1.7"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    />
                                </svg>
                            </span>
                            <p class="mt-3 text-xs font-medium text-slate-700">
                                You’re all caught up
                            </p>
                            <p class="mt-1 text-[11px] text-slate-400">
                                New handoffs appear here instantly.
                            </p>
                        </div>
                    </section>

                    <section
                        class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-[0_2px_8px_rgba(15,23,42,0.035)]"
                    >
                        <header class="border-b border-slate-100 px-5 py-4">
                            <h2 class="text-sm font-semibold text-slate-900">
                                How routing works
                            </h2>
                            <p class="mt-0.5 text-xs text-slate-500">
                                A safety-first support flow
                            </p>
                        </header>
                        <ol class="space-y-0 px-5 py-4">
                            <li class="flow-step">
                                <span class="flow-number">1</span>
                                <div>
                                    <p
                                        class="text-xs font-semibold text-slate-800"
                                    >
                                        Check for a handoff
                                    </p>
                                    <p
                                        class="mt-1 text-[11px] leading-4 text-slate-500"
                                    >
                                        Anger, VIP tag, or a request for a
                                        person.
                                    </p>
                                </div>
                            </li>
                            <li class="flow-step">
                                <span class="flow-number">2</span>
                                <div>
                                    <p
                                        class="text-xs font-semibold text-slate-800"
                                    >
                                        Answer known FAQs
                                    </p>
                                    <p
                                        class="mt-1 text-[11px] leading-4 text-slate-500"
                                    >
                                        AI is grounded in our shipping, returns,
                                        and refund policies.
                                    </p>
                                </div>
                            </li>
                            <li class="flow-step last:before:hidden">
                                <span class="flow-number">3</span>
                                <div>
                                    <p
                                        class="text-xs font-semibold text-slate-800"
                                    >
                                        Escalate the unknown
                                    </p>
                                    <p
                                        class="mt-1 text-[11px] leading-4 text-slate-500"
                                    >
                                        No matching FAQ? A human takes it from
                                        here.
                                    </p>
                                </div>
                            </li>
                        </ol>
                    </section>

                    <div
                        class="flex items-start gap-3 rounded-xl border border-slate-200/80 bg-white/70 px-4 py-3.5"
                    >
                        <span class="mt-0.5 text-[#397267]">
                            <svg
                                viewBox="0 0 20 20"
                                fill="none"
                                class="size-4"
                                aria-hidden="true"
                            >
                                <path
                                    d="M10 2.5 16 5v4.8c0 3.5-2.4 6.3-6 7.7-3.6-1.4-6-4.2-6-7.7V5l6-2.5Z"
                                    stroke="currentColor"
                                    stroke-width="1.4"
                                    stroke-linejoin="round"
                                />
                                <path
                                    d="m7.5 9.8 1.7 1.7 3.4-3.6"
                                    stroke="currentColor"
                                    stroke-width="1.4"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                />
                            </svg>
                        </span>
                        <p class="text-[11px] leading-4 text-slate-500">
                            {{
                                llmEnabled
                                    ? 'Gemini is enabled for FAQ-grounded replies.'
                                    : 'Demo mode is on. No API key needed—FAQ answers use the local grounded client.'
                            }}
                        </p>
                    </div>
                </aside>
            </section>
            <footer
                class="mt-8 flex flex-wrap items-center justify-between gap-2 border-t border-slate-200/80 pt-4 text-[10px] text-slate-400"
            >
                <span>HARBOR DESK · SAFE BY DESIGN</span>
                <span>Demo workspace · No customer data</span>
            </footer>
        </main>
    </div>
</template>
