@extends('layouts.app')

@section('title', 'FlexLearn · '.$student->name)

@section('content')
<div class="flex flex-col gap-space-lg pb-space-xl">
    <header class="flex flex-col sm:flex-row sm:items-end justify-between gap-space-md">
        <div>
            <a href="{{ route('flexlearn.teacher.index') }}" class="font-label-code text-label-code text-primary hover:underline">← All students</a>
            <h1 class="font-headline text-headline-xl text-on-surface mt-2">{{ $student->name }}</h1>
            <p class="font-body-md text-body-md text-on-surface-variant">Skill mastery and recommended next activities from FlexLearn.</p>
        </div>
    </header>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-space-lg">
        <section class="pb-card p-space-lg">
            <h2 class="font-headline text-headline-md text-on-surface mb-4">Skill mastery</h2>
            @forelse($skills as $studentSkill)
                <div class="mb-3 last:mb-0">
                    <div class="flex justify-between gap-2 mb-1">
                        <span class="font-body-md text-on-surface">{{ $studentSkill->skill->name }}</span>
                        <span class="font-label-code text-label-code">{{ $studentSkill->mastery }}% · {{ $studentSkill->bandLabel() }}</span>
                    </div>
                    <div class="w-full h-2 rounded-full bg-surface-container-highest overflow-hidden">
                        <div class="h-full rounded-full bg-primary" style="width: {{ max(2, $studentSkill->mastery) }}%"></div>
                    </div>
                </div>
            @empty
                <p class="text-on-surface-variant">No skill evidence for this student yet.</p>
            @endforelse

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mt-6">
                <div>
                    <h3 class="font-label-meta text-label-meta text-secondary uppercase mb-1">Strengths</h3>
                    @forelse($bands['strengths'] as $s)
                        <p class="text-body-sm text-on-surface">{{ $s->skill->name }}</p>
                    @empty
                        <p class="text-body-sm text-on-surface-variant">—</p>
                    @endforelse
                </div>
                <div>
                    <h3 class="font-label-meta text-label-meta text-tertiary uppercase mb-1">Developing</h3>
                    @forelse($bands['developing'] as $s)
                        <p class="text-body-sm text-on-surface">{{ $s->skill->name }}</p>
                    @empty
                        <p class="text-body-sm text-on-surface-variant">—</p>
                    @endforelse
                </div>
                <div>
                    <h3 class="font-label-meta text-label-meta text-error uppercase mb-1">Needs support</h3>
                    @forelse($bands['needs_support'] as $s)
                        <p class="text-body-sm text-on-surface">{{ $s->skill->name }}</p>
                    @empty
                        <p class="text-body-sm text-on-surface-variant">—</p>
                    @endforelse
                </div>
            </div>
        </section>

        <section class="pb-card p-space-lg">
            <h2 class="font-headline text-headline-md text-on-surface mb-4">Recommended activities</h2>
            @forelse($recommendations as $rec)
                <article class="mb-4 last:mb-0 rounded-xl bg-surface-container-low p-4 border border-white/5">
                    <h3 class="font-headline text-headline-sm text-on-surface">{{ $rec->title }}</h3>
                    @if($rec->skill)
                        <p class="font-label-code text-label-code text-primary mt-1">{{ $rec->skill->name }}</p>
                    @endif
                    <p class="font-body-sm text-body-sm text-on-surface-variant mt-2">{{ $rec->reason }}</p>
                    <p class="font-label-code text-label-code text-on-surface-variant mt-2 uppercase">{{ $rec->priority }} · {{ $rec->status }}</p>
                </article>
            @empty
                <p class="text-on-surface-variant">No active recommendations.</p>
            @endforelse
        </section>
    </div>
</div>
@endsection
