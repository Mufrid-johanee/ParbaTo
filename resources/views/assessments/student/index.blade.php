@extends('layouts.app')

@section('title', 'Assessments')

@section('content')
<div class="flex flex-col gap-space-lg">
    <header>
        <span class="inline-flex items-center px-space-xs py-0.5 rounded-full bg-primary/10 text-primary font-label-code text-label-code mb-2">ASSESSMENT</span>
        <h1 class="font-headline text-headline-xl text-on-surface tracking-tight">Assessments</h1>
        <p class="text-on-surface-variant">Published quizzes and assignments for your classrooms.</p>
    </header>

    @if($items->isEmpty())
        <div class="pb-card p-10 text-center text-on-surface-variant">No assessments available.</div>
    @else
        <div class="grid gap-space-md">
            @foreach($items as $assessment)
                @php
                    $mine = $attempts->get($assessment->id) ?? collect();
                    $active = $mine->firstWhere('status', 'in_progress');
                    $best = $mine->where('status', 'graded')->sortByDesc('accuracy')->first();
                    $used = $mine->whereIn('status', ['submitted','graded'])->count();
                @endphp
                <a href="{{ route('student.assessments.show', $assessment) }}" class="pb-card p-space-md block">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                        <div>
                            <h2 class="font-headline text-headline-sm text-on-surface">{{ $assessment->title }}</h2>
                            <p class="font-label-code text-label-code text-on-surface-variant mt-1 uppercase">
                                {{ $assessment->type }} · {{ $assessment->questions_count }} questions
                                @if($assessment->time_limit_minutes) · {{ $assessment->time_limit_minutes }} min @endif
                            </p>
                            <p class="text-sm text-on-surface-variant mt-1">{{ $assessment->classroom?->name ?? $assessment->course?->title ?? 'General' }}</p>
                        </div>
                        <div class="text-sm text-on-surface-variant">
                            @if($active)
                                <span class="text-secondary">Resume</span>
                            @elseif($best)
                                Result {{ number_format($best->accuracy, 1) }}%
                            @else
                                {{ max(0, $assessment->max_attempts - $used) }} attempts left
                            @endif
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
        {{ $items->links() }}
    @endif
</div>
@endsection
