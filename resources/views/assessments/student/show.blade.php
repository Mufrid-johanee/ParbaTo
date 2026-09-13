@extends('layouts.app')

@section('title', $assessment->title)

@section('content')
<div class="max-w-2xl flex flex-col gap-space-lg">
    <header>
        <a href="{{ route('student.assessments.index') }}" class="text-primary text-sm">← Assessments</a>
        <h1 class="font-headline text-headline-xl text-on-surface mt-2">{{ $assessment->title }}</h1>
        <p class="text-on-surface-variant">{{ $assessment->description ?? $assessment->instructions }}</p>
    </header>

    <div class="pb-card p-space-md grid grid-cols-2 gap-4 text-sm">
        <div><span class="text-on-surface-variant">Questions</span><div class="text-on-surface font-headline">{{ $assessment->questions_count }}</div></div>
        <div><span class="text-on-surface-variant">Time limit</span><div class="text-on-surface font-headline">{{ $assessment->time_limit_minutes ? $assessment->time_limit_minutes.' min' : 'None' }}</div></div>
        <div><span class="text-on-surface-variant">Max attempts</span><div class="text-on-surface font-headline">{{ $assessment->max_attempts }}</div></div>
        <div><span class="text-on-surface-variant">Pass score</span><div class="text-on-surface font-headline">{{ $assessment->pass_score }}%</div></div>
    </div>

    @php $active = $myAttempts->firstWhere('status', 'in_progress'); @endphp
    @if($active)
        <a href="{{ route('student.attempts.take', $active) }}" class="rounded-lg bg-primary px-4 py-3 text-center text-on-primary">Resume attempt</a>
    @elseif($assessment->isAvailableNow() && $myAttempts->whereIn('status',['submitted','graded'])->count() < $assessment->max_attempts)
        <form method="POST" action="{{ route('student.assessments.start', $assessment) }}">@csrf
            <button class="w-full rounded-lg bg-primary px-4 py-3 text-on-primary">Start assessment</button>
        </form>
    @else
        <div class="pb-card p-4 text-on-surface-variant text-center">No attempts remaining or assessment closed.</div>
    @endif

    @if($myAttempts->isNotEmpty())
        <section>
            <h2 class="font-headline text-headline-sm mb-3">Your attempts</h2>
            <div class="flex flex-col gap-2">
                @foreach($myAttempts as $attempt)
                    <div class="pb-card p-3 flex justify-between items-center text-sm">
                        <span class="uppercase font-label-code text-label-code">{{ $attempt->status }}</span>
                        <span>{{ $attempt->accuracy !== null ? number_format($attempt->accuracy,1).'%' : '—' }}</span>
                        @if(in_array($attempt->status, ['submitted','graded']))
                            <a class="text-primary" href="{{ route('student.attempts.result', $attempt) }}">Result</a>
                        @elseif($attempt->status === 'in_progress')
                            <a class="text-secondary" href="{{ route('student.attempts.take', $attempt) }}">Continue</a>
                        @endif
                    </div>
                @endforeach
            </div>
        </section>
    @endif
</div>
@endsection
