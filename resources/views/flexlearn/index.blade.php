@extends('layouts.app')

@section('title', 'FlexLearn')

@section('content')
<div class="flex flex-col gap-space-lg">
    <header>
        <span class="inline-flex items-center px-space-xs py-0.5 rounded-full bg-primary/10 text-primary font-label-code text-label-code mb-2">FLEXLEARN</span>
        <h1 class="font-headline text-headline-xl text-on-surface">Your adaptive learning path</h1>
        <p class="font-body-lg text-body-lg text-on-surface-variant max-w-2xl mt-1">Recommendations are rule-based and explainable — every suggestion includes a reason from your learning evidence.</p>
    </header>

    <section class="grid grid-cols-1 xl:grid-cols-12 gap-space-lg">
        <div class="xl:col-span-7 pb-card p-space-lg">
            <h2 class="font-headline text-headline-md text-on-surface mb-4">Skill mastery</h2>
            @forelse($skills as $studentSkill)
                <div class="mb-4 last:mb-0">
                    <div class="flex items-center justify-between mb-1">
                        <span class="font-body-md text-body-md text-on-surface">{{ $studentSkill->skill->name }}</span>
                        <span class="font-label-code text-label-code {{ $studentSkill->mastery < 55 ? 'text-error' : ($studentSkill->mastery >= 80 ? 'text-success' : 'text-secondary') }}">{{ $studentSkill->mastery }}%</span>
                    </div>
                    <div class="w-full h-2 rounded-full bg-surface-container-highest overflow-hidden">
                        <div class="h-full rounded-full {{ $studentSkill->mastery < 55 ? 'bg-error' : ($studentSkill->mastery >= 80 ? 'bg-success' : 'bg-secondary') }}" style="width: {{ $studentSkill->mastery }}%"></div>
                    </div>
                </div>
            @empty
                <p class="text-on-surface-variant text-body-md">No skill evidence yet. Complete assessments and missions to build your path.</p>
            @endforelse
        </div>

        <div class="xl:col-span-5 pb-card p-space-lg">
            <h2 class="font-headline text-headline-md text-on-surface mb-4">Recommended next</h2>
            @forelse($recommendations as $rec)
                <article class="mb-4 last:mb-0 rounded-xl bg-surface-container-low p-4 border border-white/5">
                    <div class="flex items-center justify-between gap-2 mb-1">
                        <h3 class="font-headline text-headline-sm text-on-surface">{{ $rec->title }}</h3>
                        <span class="font-label-code text-label-code uppercase text-tertiary">{{ $rec->priority }}</span>
                    </div>
                    <p class="font-body-sm text-body-sm text-on-surface-variant">{{ $rec->reason }}</p>
                    @if($rec->action_url)
                        <a href="{{ $rec->action_url }}" class="inline-flex mt-3 pb-btn-primary text-xs">{{ $rec->action_label ?? 'Continue' }}</a>
                    @endif
                </article>
            @empty
                <p class="text-on-surface-variant text-body-md">No active recommendations.</p>
            @endforelse
        </div>
    </section>
</div>
@endsection
