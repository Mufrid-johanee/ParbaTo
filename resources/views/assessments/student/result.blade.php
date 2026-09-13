@extends('layouts.app')

@section('title', 'Assessment Result')

@section('content')
<div class="max-w-2xl flex flex-col gap-space-lg">
    <header>
        <h1 class="font-headline text-headline-xl text-on-surface">{{ $attempt->assessment->title }}</h1>
        <p class="text-on-surface-variant capitalize">{{ $attempt->status }}</p>
    </header>

    <div class="pb-card p-space-lg text-center">
        @if($attempt->status === 'graded')
            <div class="font-headline text-display-hero text-on-surface">{{ number_format($attempt->accuracy, 1) }}%</div>
            <p class="text-on-surface-variant mt-2">{{ $attempt->score }} / {{ $attempt->max_score }} points</p>
            <p class="mt-2 {{ $attempt->accuracy >= $attempt->assessment->pass_score ? 'text-secondary' : 'text-error' }}">
                {{ $attempt->accuracy >= $attempt->assessment->pass_score ? 'Passed' : 'Below pass score' }}
            </p>
        @else
            <div class="font-headline text-headline-lg text-on-surface">Submitted — awaiting short-answer review</div>
            <p class="text-on-surface-variant mt-2">Objective questions scored. Final result after teacher grading.</p>
        @endif
    </div>

    @if($showAnswers)
        <section class="flex flex-col gap-3">
            <h2 class="font-headline text-headline-sm">Breakdown</h2>
            @foreach($attempt->assessment->questions as $question)
                @php $answer = $attempt->answerRecords->firstWhere('question_id', $question->id); @endphp
                <div class="pb-card p-space-md">
                    <p class="text-on-surface">{{ $question->prompt }}</p>
                    <p class="text-sm text-on-surface-variant mt-2">Your answer: {{ $answer?->selected_option ?? $answer?->answer_text ?? '—' }}</p>
                    <p class="text-sm mt-1 {{ $answer?->is_correct ? 'text-secondary' : 'text-error' }}">
                        {{ $answer?->points_awarded ?? 0 }}/{{ $question->points }} pts
                    </p>
                </div>
            @endforeach
        </section>
    @endif

    <a href="{{ route('student.assessments.index') }}" class="text-primary">Back to assessments</a>
</div>
@endsection
