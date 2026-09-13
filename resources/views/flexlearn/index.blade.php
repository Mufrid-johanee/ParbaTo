@extends('layouts.app')

@section('title', 'FlexLearn')

@section('content')
<div class="flex flex-col w-full gap-space-xl pb-space-xl">
    {{-- Hero --}}
    <div class="relative w-full rounded-2xl bg-surface-container-low p-space-md sm:p-space-lg overflow-hidden border border-white/5">
        <div class="absolute -right-24 -top-24 w-72 h-72 rounded-full bg-primary-container/10 blur-3xl pointer-events-none"></div>
        <div class="absolute left-1/3 -bottom-20 w-64 h-64 rounded-full bg-secondary-container/10 blur-3xl pointer-events-none"></div>
        <div class="relative z-10 flex flex-col xl:flex-row xl:items-end justify-between gap-space-lg">
            <div class="flex flex-col gap-space-xs max-w-3xl">
                <div class="flex flex-wrap items-center gap-space-xs mb-1">
                    <span class="px-space-xs py-0.5 rounded font-label-code text-label-code bg-secondary-container/20 text-secondary uppercase tracking-wider flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-secondary animate-pulse"></span>
                        FlexLearn
                    </span>
                    <span class="font-label-meta text-label-meta text-on-surface-variant uppercase tracking-wider">Personalized Learning Intelligence</span>
                </div>
                <h1 class="font-headline text-headline-xl sm:text-display-hero text-on-surface tracking-tight">Your Learning Path</h1>
                <p class="font-body-lg text-body-lg text-on-surface-variant">
                    Not everyone learns the same way. Your path adapts from real LearnQuest evidence — explainable, never opaque.
                </p>
            </div>
            <div class="flex flex-wrap items-stretch gap-space-sm p-space-sm rounded-xl bg-surface-container-lowest/80 self-start">
                <div class="flex flex-col px-space-sm min-w-[96px]">
                    <span class="font-label-meta text-label-meta text-on-surface-variant uppercase tracking-wider">Skills tracked</span>
                    <span class="font-headline text-headline-sm text-on-surface">{{ $skills->count() }}</span>
                </div>
                <div class="hidden sm:block w-px bg-surface-container-highest"></div>
                <div class="flex flex-col px-space-sm min-w-[96px]">
                    <span class="font-label-meta text-label-meta text-on-surface-variant uppercase tracking-wider">Needs support</span>
                    <span class="font-headline text-headline-sm text-error">{{ $bands['needs_support']->count() }}</span>
                </div>
                <div class="hidden sm:block w-px bg-surface-container-highest"></div>
                <div class="flex flex-col px-space-sm min-w-[96px]">
                    <span class="font-label-meta text-label-meta text-on-surface-variant uppercase tracking-wider">Strengths</span>
                    <span class="font-headline text-headline-sm text-secondary">{{ $bands['strengths']->count() }}</span>
                </div>
            </div>
        </div>
    </div>

    @if(session('status'))
        <div class="rounded-xl bg-secondary-container/15 border border-secondary/20 px-space-md py-space-sm text-secondary font-body-md" role="status">
            {{ session('status') }}
        </div>
    @endif

    @if($error)
        <div class="rounded-xl bg-error-container/20 border border-error/30 px-space-md py-space-sm text-error font-body-md" role="alert">
            {{ $error }}
        </div>
    @endif

    {{-- Learning path --}}
    <section class="relative w-full rounded-2xl bg-surface-container-lowest p-space-md sm:p-space-lg overflow-hidden border border-white/5">
        <div class="absolute inset-0 bg-[radial-gradient(circle,#313540_1px,transparent_1px)] [background-size:24px_24px] opacity-20 pointer-events-none"></div>
        <div class="relative z-10 flex flex-wrap items-center justify-between gap-space-md mb-space-lg">
            <div class="flex items-center gap-space-sm">
                <span class="material-symbols-outlined text-secondary text-2xl">account_tree</span>
                <div>
                    <span class="font-label-meta text-label-meta uppercase tracking-wider text-secondary">Adaptive path</span>
                    <h2 class="font-headline text-headline-md text-on-surface">Personalized Learning Path</h2>
                </div>
            </div>
            <div class="flex flex-wrap items-center gap-space-sm text-on-surface-variant">
                <span class="flex items-center gap-1.5 font-label-code text-label-code"><span class="w-2.5 h-2.5 rounded-full bg-secondary-container"></span> Done</span>
                <span class="flex items-center gap-1.5 font-label-code text-label-code"><span class="w-2.5 h-2.5 rounded-full bg-primary animate-pulse"></span> Current</span>
                <span class="flex items-center gap-1.5 font-label-code text-label-code"><span class="w-2.5 h-2.5 rounded-full bg-surface-container-highest"></span> Planned</span>
            </div>
        </div>

        <ol class="relative z-10 flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-stretch">
            @foreach($pathNodes as $i => $node)
                <li class="flex-1 min-w-0 sm:min-w-[180px] max-w-full rounded-xl border p-space-md
                    @if($node['state'] === 'done') bg-secondary-container/10 border-secondary/30
                    @elseif($node['state'] === 'current') bg-primary-container/15 border-primary/40 shadow-[0_0_16px_rgba(128,131,255,0.2)]
                    @elseif($node['state'] === 'empty') bg-surface-container border-dashed border-white/15
                    @else bg-surface-container/60 border-white/5 opacity-80
                    @endif">
                    <div class="flex items-start gap-2">
                        <span class="material-symbols-outlined text-lg shrink-0 mt-0.5
                            @if($node['state'] === 'done') text-secondary
                            @elseif($node['state'] === 'current') text-primary
                            @else text-on-surface-variant
                            @endif">
                            @if($node['state'] === 'done') check_circle
                            @elseif($node['state'] === 'current') play_circle
                            @elseif($node['state'] === 'empty') lock
                            @else radio_button_unchecked
                            @endif
                        </span>
                        <div class="min-w-0">
                            <p class="font-headline text-headline-sm text-on-surface truncate" title="{{ $node['label'] }}">{{ $node['label'] }}</p>
                            @if($node['detail'])
                                <p class="font-body-sm text-body-sm text-on-surface-variant mt-1 line-clamp-3">{{ $node['detail'] }}</p>
                            @endif
                        </div>
                    </div>
                </li>
                @if(! $loop->last)
                    <li class="hidden sm:flex items-center text-outline px-1" aria-hidden="true">
                        <span class="material-symbols-outlined">arrow_forward</span>
                    </li>
                @endif
            @endforeach
        </ol>
    </section>

    <div class="grid grid-cols-1 xl:grid-cols-12 gap-space-lg">
        {{-- Mastery --}}
        <section class="xl:col-span-7 flex flex-col gap-space-lg">
            <div class="pb-card p-space-md sm:p-space-lg">
                <h2 class="font-headline text-headline-md text-on-surface mb-1">Mastery overview</h2>
                <p class="font-body-sm text-body-sm text-on-surface-variant mb-4">0–39 Needs Support · 40–59 Developing · 60–79 Proficient · 80–100 Advanced</p>

                @forelse($skills as $studentSkill)
                    @php
                        $band = $studentSkill->bandKey();
                        $bar = match ($band) {
                            'needs_support' => 'bg-error',
                            'developing' => 'bg-tertiary',
                            'proficient' => 'bg-primary',
                            default => 'bg-secondary',
                        };
                        $labelColor = match ($band) {
                            'needs_support' => 'text-error',
                            'developing' => 'text-tertiary',
                            'proficient' => 'text-primary',
                            default => 'text-secondary',
                        };
                    @endphp
                    <div class="mb-4 last:mb-0">
                        <div class="flex flex-wrap items-center justify-between gap-2 mb-1">
                            <span class="font-body-md text-body-md text-on-surface">{{ $studentSkill->skill->name }}</span>
                            <div class="flex items-center gap-2">
                                <span class="font-label-code text-label-code {{ $labelColor }}">{{ $studentSkill->bandLabel() }}</span>
                                <span class="font-label-code text-label-code text-on-surface">{{ $studentSkill->mastery }}%</span>
                            </div>
                        </div>
                        <div class="w-full h-2 rounded-full bg-surface-container-highest overflow-hidden">
                            <div class="h-full rounded-full {{ $bar }} transition-all duration-500" style="width: {{ max(2, $studentSkill->mastery) }}%"></div>
                        </div>
                        <p class="font-label-code text-label-code text-on-surface-variant/70 mt-1">
                            {{ $studentSkill->evidence_count }} evidence
                            @if($studentSkill->last_assessed_at)
                                · updated {{ $studentSkill->last_assessed_at->diffForHumans() }}
                            @endif
                        </p>
                    </div>
                @empty
                    <div class="rounded-xl border border-dashed border-white/10 p-8 text-center">
                        <span class="material-symbols-outlined text-4xl text-on-surface-variant mb-2">psychology</span>
                        <p class="font-body-md text-body-md text-on-surface-variant">
                            Complete your first mission to unlock your personalized learning path.
                        </p>
                        <a href="{{ route('learnquest.index') }}" class="inline-flex mt-4 pb-btn-primary min-h-11 px-5">Open LearnQuest</a>
                    </div>
                @endforelse
            </div>

            @if($skills->isNotEmpty())
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-space-md">
                    <div class="rounded-xl bg-secondary-container/10 border border-secondary/20 p-space-md">
                        <h3 class="font-label-meta text-label-meta uppercase tracking-wider text-secondary mb-2">Strengths</h3>
                        @forelse($bands['strengths'] as $s)
                            <p class="font-body-sm text-body-sm text-on-surface mb-1">{{ $s->skill->name }} <span class="text-secondary">{{ $s->mastery }}%</span></p>
                        @empty
                            <p class="font-body-sm text-body-sm text-on-surface-variant">None yet at Advanced.</p>
                        @endforelse
                    </div>
                    <div class="rounded-xl bg-tertiary-container/10 border border-tertiary/20 p-space-md">
                        <h3 class="font-label-meta text-label-meta uppercase tracking-wider text-tertiary mb-2">Developing</h3>
                        @forelse($bands['developing'] as $s)
                            <p class="font-body-sm text-body-sm text-on-surface mb-1">{{ $s->skill->name }} <span class="text-tertiary">{{ $s->mastery }}%</span></p>
                        @empty
                            <p class="font-body-sm text-body-sm text-on-surface-variant">No mid-range skills.</p>
                        @endforelse
                    </div>
                    <div class="rounded-xl bg-error-container/15 border border-error/25 p-space-md">
                        <h3 class="font-label-meta text-label-meta uppercase tracking-wider text-error mb-2">Needs support</h3>
                        @forelse($bands['needs_support'] as $s)
                            <p class="font-body-sm text-body-sm text-on-surface mb-1">{{ $s->skill->name }} <span class="text-error">{{ $s->mastery }}%</span></p>
                        @empty
                            <p class="font-body-sm text-body-sm text-on-surface-variant">No critical gaps right now.</p>
                        @endforelse
                    </div>
                </div>
            @endif
        </section>

        {{-- Recommendations --}}
        <section class="xl:col-span-5">
            <div class="pb-card p-space-md sm:p-space-lg h-full">
                <h2 class="font-headline text-headline-md text-on-surface mb-1">Recommended next steps</h2>
                <p class="font-body-sm text-body-sm text-on-surface-variant mb-4">Every suggestion includes a reason from your learning evidence.</p>

                @forelse($recommendations as $rec)
                    <article class="mb-4 last:mb-0 rounded-xl bg-surface-container-low p-4 border border-white/5">
                        <div class="flex flex-wrap items-center justify-between gap-2 mb-2">
                            <h3 class="font-headline text-headline-sm text-on-surface">{{ $rec->title }}</h3>
                            <span class="font-label-code text-label-code uppercase
                                {{ $rec->priority === 'high' ? 'text-error' : ($rec->priority === 'medium' ? 'text-tertiary' : 'text-on-surface-variant') }}">
                                {{ $rec->priority }}
                            </span>
                        </div>
                        @if($rec->skill)
                            <p class="font-label-code text-label-code text-primary mb-2">Skill: {{ $rec->skill->name }}</p>
                        @endif
                        @if($rec->mission)
                            <p class="font-label-code text-label-code text-on-surface-variant mb-2 capitalize">
                                Mission · {{ $rec->mission->difficulty }}
                                @if($rec->status === 'started') · Started @endif
                            </p>
                        @endif
                        <p class="font-body-sm text-body-sm text-on-surface-variant">
                            <span class="text-on-surface font-medium">Why this is recommended:</span>
                            {{ $rec->reason }}
                        </p>
                        <div class="flex flex-col sm:flex-row gap-2 mt-4">
                            <form method="POST" action="{{ route('flexlearn.recommendations.start', $rec) }}" class="flex-1">
                                @csrf
                                <button type="submit" class="pb-btn-primary w-full min-h-11 text-sm">
                                    {{ $rec->action_label ?? 'Start' }}
                                </button>
                            </form>
                            <form method="POST" action="{{ route('flexlearn.recommendations.dismiss', $rec) }}">
                                @csrf
                                <button type="submit" class="pb-btn-secondary w-full sm:w-auto min-h-11 text-sm px-4">Dismiss</button>
                            </form>
                        </div>
                    </article>
                @empty
                    <div class="rounded-xl border border-dashed border-white/10 p-8 text-center text-on-surface-variant">
                        <p class="font-body-md text-body-md">No active recommendations yet.</p>
                        <p class="font-body-sm text-body-sm mt-2">Complete a LearnQuest mission and get evaluated to generate your next steps.</p>
                        <a href="{{ route('learnquest.index') }}" class="inline-flex mt-4 pb-btn-secondary min-h-11 px-5">Browse missions</a>
                    </div>
                @endforelse
            </div>
        </section>
    </div>
</div>
@endsection
