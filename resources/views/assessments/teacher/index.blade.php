@extends('layouts.app')

@section('title', 'Assessments')

@section('content')
<div class="flex flex-col gap-space-lg">
    <header class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
        <div>
            <span class="inline-flex items-center px-space-xs py-0.5 rounded-full bg-primary/10 text-primary font-label-code text-label-code mb-2">ASSESSMENT</span>
            <h1 class="font-headline text-headline-xl text-on-surface tracking-tight">Your assessments</h1>
            <p class="font-body-md text-body-md text-on-surface-variant">Create, publish, and grade — server-side scoring only.</p>
        </div>
        <a href="{{ route('teacher.assessments.create') }}" class="inline-flex items-center justify-center gap-2 rounded-lg bg-primary px-4 py-2.5 text-on-primary font-label-md">
            <span class="material-symbols-outlined text-lg">add</span> New assessment
        </a>
    </header>

    @if($items->isEmpty())
        <div class="pb-card p-10 text-center text-on-surface-variant">No assessments yet. Create your first draft.</div>
    @else
        <div class="grid gap-space-md md:grid-cols-2">
            @foreach($items as $assessment)
                <a href="{{ route('teacher.assessments.edit', $assessment) }}" class="pb-card p-space-md block hover:border-primary/40 transition-colors">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h2 class="font-headline text-headline-sm text-on-surface">{{ $assessment->title }}</h2>
                            <p class="font-label-code text-label-code text-on-surface-variant mt-1 uppercase">{{ $assessment->type }} · {{ $assessment->status }}</p>
                        </div>
                        <span class="px-2 py-0.5 rounded bg-surface-container-highest font-label-code text-label-code">{{ $assessment->questions_count }} Q</span>
                    </div>
                    <p class="mt-3 font-body-sm text-body-sm text-on-surface-variant">{{ $assessment->classroom?->name ?? $assessment->course?->title ?? 'Unscoped' }} · {{ $assessment->attempts_count }} attempts</p>
                </a>
            @endforeach
        </div>
        <div>{{ $items->links() }}</div>
    @endif
</div>
@endsection
