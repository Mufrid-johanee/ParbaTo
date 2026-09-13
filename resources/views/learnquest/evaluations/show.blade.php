@extends('layouts.app')

@section('title', 'Evaluate submission')

@section('content')
<div class="flex flex-col gap-space-lg max-w-3xl">
    <div>
        <a href="{{ route('learnquest.evaluations.index') }}" class="font-label-code text-label-code text-secondary hover:underline">← Evaluations</a>
        <h1 class="font-headline text-headline-xl text-on-surface mt-2">{{ $enrollment->mission->title }}</h1>
        <p class="font-body-md text-body-md text-on-surface-variant">Student: {{ $enrollment->user->preferredName() }} · Progress {{ $enrollment->progress_percent }}%</p>
    </div>

    <section class="pb-card p-space-lg">
        <h2 class="font-headline text-headline-sm text-on-surface mb-2">Submission</h2>
        <p class="font-label-code text-label-code text-secondary uppercase mb-3">{{ $enrollment->submission?->status }}</p>
        <p class="font-body-md text-body-md text-on-surface-variant whitespace-pre-line">{{ $enrollment->submission?->summary }}</p>
        @if($enrollment->submission?->repo_url)
            <a class="block mt-3 text-secondary hover:underline break-all" href="{{ $enrollment->submission->repo_url }}" target="_blank" rel="noopener">{{ $enrollment->submission->repo_url }}</a>
        @endif
        @if($enrollment->submission?->demo_url)
            <a class="block mt-1 text-secondary hover:underline break-all" href="{{ $enrollment->submission->demo_url }}" target="_blank" rel="noopener">{{ $enrollment->submission->demo_url }}</a>
        @endif
    </section>

    @if($enrollment->submission?->status === 'accepted')
        <section class="pb-card p-space-lg border border-success/30">
            <h2 class="font-headline text-headline-sm text-success mb-2">Already evaluated</h2>
            <p class="font-label-code text-label-code text-on-surface mb-2">Score: {{ $enrollment->submission->score }}</p>
            <p class="font-body-md text-body-md text-on-surface-variant whitespace-pre-line">{{ $enrollment->submission->feedback }}</p>
        </section>
    @else
        <section class="pb-card p-space-lg">
            <h2 class="font-headline text-headline-sm text-on-surface mb-4">Evaluate</h2>
            <form method="POST" action="{{ route('learnquest.evaluations.store', $enrollment) }}" class="space-y-4">
                @csrf
                <div>
                    <label class="font-label-meta text-label-meta uppercase text-on-surface-variant" for="score">Score (0–100)</label>
                    <input id="score" type="number" name="score" min="0" max="100" step="0.01" required class="pb-input mt-1" value="{{ old('score', 80) }}">
                </div>
                <div>
                    <label class="font-label-meta text-label-meta uppercase text-on-surface-variant" for="feedback">Feedback</label>
                    <textarea id="feedback" name="feedback" rows="6" required minlength="10" class="pb-input mt-1" placeholder="What worked well? What should improve?">{{ old('feedback') }}</textarea>
                </div>
                <button type="submit" class="pb-btn-primary w-full sm:w-auto min-h-11">Save evaluation</button>
            </form>
        </section>
    @endif
</div>
@endsection
