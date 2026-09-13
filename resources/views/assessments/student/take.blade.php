@extends('layouts.app')

@section('title', 'Take Assessment')

@section('content')
@php
    $saved = $attempt->answerRecords->keyBy('question_id');
@endphp
<div
    class="max-w-3xl mx-auto flex flex-col gap-space-lg"
    x-data="assessmentTake({
        autosaveUrl: @js(route('student.attempts.autosave', $attempt)),
        remaining: @js($remainingSeconds),
        csrf: @js(csrf_token())
    })"
>
    <header class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 sticky top-16 z-20 bg-surface/90 backdrop-blur py-3 border-b border-white/5">
        <div>
            <h1 class="font-headline text-headline-md text-on-surface">{{ $assessment->title }}</h1>
            <p class="text-sm" :class="saveState === 'saved' ? 'text-secondary' : (saveState === 'error' ? 'text-error' : 'text-on-surface-variant')" x-text="saveLabel"></p>
        </div>
        <div class="font-label-code text-label-code text-primary" x-show="remaining !== null" x-text="timerLabel"></div>
    </header>

    <form method="POST" action="{{ route('student.attempts.submit', $attempt) }}" @submit="beforeSubmit" class="flex flex-col gap-space-md">
        @csrf
        @foreach($assessment->questions as $index => $question)
            @php $ans = $saved->get($question->id); @endphp
            <div class="pb-card p-space-md" :class="current === {{ $index }} ? 'ring-1 ring-primary/40' : ''" x-show="current === {{ $index }} || wide" x-cloak>
                <p class="font-label-code text-label-code text-on-surface-variant">Question {{ $index + 1 }} / {{ $assessment->questions->count() }} · {{ $question->points }} pts</p>
                <p class="font-body-lg text-on-surface mt-2 mb-4">{{ $question->prompt }}</p>
                <input type="hidden" name="answers[{{ $index }}][question_id]" value="{{ $question->id }}">

                @if($question->type === 'short_answer')
                    <textarea
                        name="answers[{{ $index }}][answer_text]"
                        rows="4"
                        class="w-full rounded-lg bg-surface-container border border-white/10 px-3 py-2 text-on-surface"
                        x-on:change="queueSave()"
                        x-on:blur="queueSave()"
                    >{{ $ans?->answer_text }}</textarea>
                @else
                    <div class="flex flex-col gap-2">
                        @foreach(($question->options ?? ($question->type === 'true_false' ? ['true','false'] : [])) as $opt)
                            <label class="flex items-center gap-3 rounded-lg border border-white/10 px-3 py-3 cursor-pointer hover:bg-surface-container-high">
                                <input
                                    type="radio"
                                    name="answers[{{ $index }}][selected_option]"
                                    value="{{ $opt }}"
                                    @checked(($ans?->selected_option ?? '') === $opt)
                                    x-on:change="queueSave()"
                                >
                                <span class="text-on-surface">{{ $opt }}</span>
                            </label>
                        @endforeach
                    </div>
                @endif
            </div>
        @endforeach

        <div class="flex flex-wrap gap-2 justify-between sticky bottom-20 lg:bottom-4 bg-surface/90 backdrop-blur py-3">
            <button type="button" class="rounded-lg border border-white/10 px-4 py-2" @click="current = Math.max(0, current-1)" :disabled="current===0">Previous</button>
            <div class="flex gap-2">
                <button type="button" class="rounded-lg border border-white/10 px-4 py-2" @click="current = Math.min({{ $assessment->questions->count()-1 }}, current+1)">Next</button>
                <button type="submit" class="rounded-lg bg-primary px-4 py-2 text-on-primary" onclick="return confirm('Submit assessment?')">Submit</button>
            </div>
        </div>
    </form>
</div>

<script>
function assessmentTake({ autosaveUrl, remaining, csrf }) {
    return {
        current: 0,
        wide: window.matchMedia('(min-width: 1024px)').matches,
        remaining,
        saveState: 'idle',
        saveLabel: 'Answers autosave when you change them',
        timerLabel: '',
        timer: null,
        saveTimer: null,
        init() {
            this.tick();
            if (this.remaining !== null) {
                this.timer = setInterval(() => this.tick(), 1000);
            }
            window.addEventListener('resize', () => {
                this.wide = window.matchMedia('(min-width: 1024px)').matches;
            });
        },
        tick() {
            if (this.remaining === null) return;
            if (this.remaining <= 0) {
                this.timerLabel = 'Time up — submitting…';
                this.$el.querySelector('form')?.requestSubmit();
                return;
            }
            const m = Math.floor(this.remaining / 60);
            const s = this.remaining % 60;
            this.timerLabel = `${m}:${String(s).padStart(2,'0')} left`;
            this.remaining -= 1;
        },
        queueSave() {
            this.saveState = 'pending';
            this.saveLabel = 'Saving…';
            clearTimeout(this.saveTimer);
            this.saveTimer = setTimeout(() => this.save(), 600);
        },
        async save() {
            const form = this.$el.querySelector('form');
            const fd = new FormData(form);
            const answers = [];
            const map = {};
            for (const [key, value] of fd.entries()) {
                const m = key.match(/^answers\[(\d+)\]\[(\w+)\]$/);
                if (!m) continue;
                map[m[1]] = map[m[1]] || {};
                map[m[1]][m[2]] = value;
            }
            Object.values(map).forEach(a => answers.push(a));
            try {
                const res = await fetch(autosaveUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ answers }),
                });
                const data = await res.json();
                if (!res.ok) throw new Error('save failed');
                this.saveState = 'saved';
                this.saveLabel = 'Saved ' + new Date().toLocaleTimeString();
                if (data.remaining_seconds !== null && data.remaining_seconds !== undefined) {
                    this.remaining = data.remaining_seconds;
                }
            } catch (e) {
                this.saveState = 'error';
                this.saveLabel = 'Autosave failed — check connection';
            }
        },
        beforeSubmit() {
            clearInterval(this.timer);
        }
    }
}
</script>
@endsection
