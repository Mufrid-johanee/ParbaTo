@extends('layouts.app')

@section('title', 'Grade Attempt')

@section('content')
<div class="flex flex-col gap-space-lg max-w-3xl">
    <header>
        <a href="{{ route('teacher.assessments.attempts', $assessment) }}" class="text-primary text-sm">← Attempts</a>
        <h1 class="font-headline text-headline-xl text-on-surface mt-2">{{ $attempt->user->preferredName() }}</h1>
        <p class="text-on-surface-variant">{{ $assessment->title }} · {{ $attempt->status }} · {{ $attempt->accuracy !== null ? number_format($attempt->accuracy,1).'%' : 'Pending' }}</p>
    </header>

    <div class="flex flex-col gap-space-md">
        @foreach($assessment->questions as $question)
            @php $answer = $attempt->answerRecords->firstWhere('question_id', $question->id); @endphp
            <div class="pb-card p-space-md">
                <p class="font-label-code text-label-code text-primary uppercase">Q{{ $question->position }} · {{ $question->type }} · {{ $question->points }} pts</p>
                <p class="font-body-md text-on-surface mt-1">{{ $question->prompt }}</p>
                <p class="mt-2 text-on-surface-variant text-sm">Answer: {{ $answer?->selected_option ?? $answer?->answer_text ?? '—' }}</p>
                @if($question->type !== 'short_answer')
                    <p class="mt-1 text-sm {{ $answer?->is_correct ? 'text-secondary' : 'text-error' }}">
                        {{ $answer?->is_correct === null ? 'Not scored' : ($answer->is_correct ? 'Correct' : 'Incorrect') }}
                        · {{ $answer?->points_awarded ?? 0 }}/{{ $question->points }}
                    </p>
                @endif
            </div>
        @endforeach
    </div>

    @if($attempt->status === 'submitted' || $assessment->questions->where('type','short_answer')->isNotEmpty())
        <form method="POST" action="{{ route('teacher.assessments.attempts.grade', [$assessment, $attempt]) }}" class="pb-card p-space-md flex flex-col gap-space-md">
            @csrf
            <h2 class="font-headline text-headline-sm">Grade short answers</h2>
            @foreach($assessment->questions->where('type','short_answer') as $i => $question)
                @php $answer = $attempt->answerRecords->firstWhere('question_id', $question->id); @endphp
                <div class="border-t border-white/5 pt-space-md">
                    <p class="text-on-surface mb-2">{{ $question->prompt }}</p>
                    <p class="text-sm text-on-surface-variant mb-2">Student: {{ $answer?->answer_text ?? '—' }}</p>
                    <input type="hidden" name="grades[{{ $i }}][question_id]" value="{{ $question->id }}">
                    <label class="flex flex-col gap-1 max-w-xs">
                        <span class="font-label-meta text-label-meta uppercase text-on-surface-variant">Points (max {{ $question->points }})</span>
                        <input type="number" step="0.01" name="grades[{{ $i }}][points_awarded]" value="{{ old('grades.'.$i.'.points_awarded', $answer?->points_awarded ?? 0) }}" class="rounded-lg bg-surface-container border border-white/10 px-3 py-2">
                    </label>
                    <label class="flex flex-col gap-1 mt-2">
                        <span class="font-label-meta text-label-meta uppercase text-on-surface-variant">Feedback</span>
                        <textarea name="grades[{{ $i }}][teacher_feedback]" rows="2" class="rounded-lg bg-surface-container border border-white/10 px-3 py-2">{{ $answer?->teacher_feedback }}</textarea>
                    </label>
                </div>
            @endforeach
            <button class="rounded-lg bg-primary px-4 py-2.5 text-on-primary self-start">Save grading</button>
        </form>
    @endif
</div>
@endsection
