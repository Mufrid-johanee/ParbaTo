@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="flex flex-col w-full gap-space-lg">
    <section class="flex flex-col xl:flex-row xl:items-end justify-between gap-space-md">
        <div class="flex flex-col gap-space-xs">
            <div class="flex items-center gap-space-xs flex-wrap">
                <span class="px-2 py-0.5 rounded-full font-label-code text-label-code bg-secondary/15 text-secondary uppercase">{{ $user->role }}</span>
                <span class="font-label-meta text-label-meta text-on-surface-variant uppercase tracking-widest">Learning hub</span>
            </div>
            <h1 class="font-headline text-headline-xl lg:text-display-hero text-on-surface tracking-tight">
                Good {{ now()->format('A') === 'AM' ? 'morning' : 'afternoon' }}, {{ $user->preferredName() }}
            </h1>
            <p class="font-body-lg text-body-lg text-on-surface-variant max-w-2xl">
                You have
                <span class="text-secondary font-medium">{{ $activeMissions->count() }} active mission(s)</span>
                @if($liveSession)
                    and a <span class="text-tertiary font-medium">live ClassTwin session</span>
                @endif
                waiting for you.
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-space-xs">
            <div class="flex items-center gap-1.5 px-space-sm py-1.5 rounded-full bg-surface-container-high text-on-surface">
                <span class="material-symbols-outlined text-sm text-primary">military_tech</span>
                <span class="font-label-meta text-label-meta uppercase">Level {{ $user->level }} · {{ $user->xp }} XP</span>
            </div>
            @if($user->major)
                <div class="flex items-center gap-1.5 px-space-sm py-1.5 rounded-full bg-surface-container-high text-on-surface">
                    <span class="material-symbols-outlined text-sm text-secondary">terminal</span>
                    <span class="font-label-meta text-label-meta uppercase">{{ $user->major }}</span>
                </div>
            @endif
        </div>
    </section>

    <section class="grid grid-cols-1 xl:grid-cols-12 gap-space-lg">
        <div class="xl:col-span-8 pb-card p-space-lg relative overflow-hidden">
            <div class="absolute -top-24 -right-24 w-80 h-80 rounded-full bg-primary-container/10 blur-3xl pointer-events-none"></div>
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-space-lg">
                <div>
                    <span class="font-label-code text-label-code uppercase tracking-wider text-secondary">Sprint overview</span>
                    <h2 class="font-headline text-headline-md text-on-surface mt-1">Learning progress</h2>
                </div>
                <div class="flex items-center gap-4">
                    <div class="text-center">
                        <div class="font-headline text-headline-lg text-on-surface">{{ $missionCount }}</div>
                        <div class="font-label-meta text-label-meta text-on-surface-variant uppercase">Completed</div>
                    </div>
                    <div class="text-center">
                        <div class="font-headline text-headline-lg text-secondary">{{ $recommendations->count() }}</div>
                        <div class="font-label-meta text-label-meta text-on-surface-variant uppercase">FlexLearn</div>
                    </div>
                </div>
            </div>

            @if($liveSession)
                <a href="{{ route('classtwin.show', $liveSession) }}" class="block rounded-xl bg-surface-container-low border border-secondary/20 p-space-md mb-space-md hover:border-secondary/50 transition-colors">
                    <div class="flex items-center justify-between gap-3 flex-wrap">
                        <div>
                            <div class="flex items-center gap-2 mb-1">
                                <span class="w-2 h-2 rounded-full bg-error animate-ping"></span>
                                <span class="font-label-code text-label-code text-error uppercase">Live ClassTwin</span>
                            </div>
                            <h3 class="font-headline text-headline-sm text-on-surface">{{ $liveSession->title ?? $liveSession->classroom->name }}</h3>
                            <p class="font-body-sm text-body-sm text-on-surface-variant">{{ $liveSession->classroom->course->code ?? '' }} · {{ $liveSession->classroom->room_label }}</p>
                        </div>
                        <span class="pb-btn-secondary text-xs">Enter twin stage</span>
                    </div>
                </a>
            @else
                <div class="rounded-xl bg-surface-container-low p-space-md mb-space-md text-body-md text-on-surface-variant">
                    No live ClassTwin session right now. Check back when your teacher starts class.
                </div>
            @endif

            <div class="space-y-3">
                <h3 class="font-label-meta text-label-meta uppercase tracking-wider text-on-surface-variant">Active missions</h3>
                @forelse($activeMissions as $enrollment)
                    <a href="{{ route('learnquest.show', $enrollment->mission) }}" class="flex items-center justify-between gap-3 rounded-xl bg-surface-container-low px-4 py-3 hover:bg-surface-container-high transition-colors">
                        <div>
                            <div class="font-headline text-headline-sm text-on-surface">{{ $enrollment->mission->title }}</div>
                            <div class="font-label-code text-label-code text-on-surface-variant capitalize">{{ $enrollment->lifecycle_phase }} · {{ $enrollment->progress_percent }}%</div>
                        </div>
                        <span class="material-symbols-outlined text-primary">arrow_forward</span>
                    </a>
                @empty
                    <div class="rounded-xl border border-dashed border-white/10 p-6 text-center text-on-surface-variant">
                        <p class="mb-3">No active missions yet.</p>
                        <a href="{{ route('learnquest.index') }}" class="pb-btn-primary text-sm">Browse LearnQuest</a>
                    </div>
                @endforelse
            </div>
        </div>

        <div class="xl:col-span-4 flex flex-col gap-space-md">
            <div class="pb-card p-space-md">
                <h3 class="font-headline text-headline-sm text-on-surface mb-3">FlexLearn recommendations</h3>
                @forelse($recommendations as $rec)
                    <div class="mb-3 last:mb-0 rounded-lg bg-surface-container-low p-3">
                        <div class="font-body-md text-body-md text-on-surface font-medium">{{ $rec->title }}</div>
                        <p class="font-body-sm text-body-sm text-on-surface-variant mt-1">{{ $rec->reason }}</p>
                        @if($rec->action_url)
                            <a href="{{ $rec->action_url }}" class="inline-block mt-2 font-label-code text-label-code text-secondary hover:underline">{{ $rec->action_label ?? 'Open' }} →</a>
                        @endif
                    </div>
                @empty
                    <p class="text-body-sm text-on-surface-variant">Recommendations will appear as learning evidence accumulates.</p>
                @endforelse
            </div>

            <div class="pb-card p-space-md">
                <h3 class="font-headline text-headline-sm text-on-surface mb-3">Suggested missions</h3>
                @foreach($availableMissions as $mission)
                    <a href="{{ route('learnquest.show', $mission) }}" class="block py-2 border-b border-white/5 last:border-0 hover:text-primary transition-colors">
                        <div class="font-body-md text-body-md">{{ $mission->title }}</div>
                        <div class="font-label-code text-label-code text-on-surface-variant capitalize">{{ $mission->difficulty }} · {{ $mission->xp_reward }} XP</div>
                    </a>
                @endforeach
            </div>
        </div>
    </section>
</div>
@endsection
