@extends('layouts.app')

@section('title', 'LearnQuest')

@section('content')
<div class="flex flex-col gap-y-space-xl pb-space-xl">
    <header class="flex flex-col lg:flex-row lg:items-end justify-between gap-space-lg">
        <div class="flex flex-col gap-space-xs max-w-2xl">
            <div class="flex items-center gap-space-xs">
                <span class="inline-flex items-center px-space-xs py-0.5 rounded-full bg-secondary/10 text-secondary font-label-code text-label-code">
                    <span class="w-1.5 h-1.5 rounded-full bg-secondary animate-ping mr-1.5"></span>
                    LEARNQUEST
                </span>
            </div>
            <h1 class="font-headline text-headline-xl text-on-surface font-bold tracking-tight">Turn Learning Into Missions.</h1>
            <p class="font-body-lg text-body-lg text-on-surface-variant">Learn concepts, solve real problems, and build something meaningful.</p>
        </div>
        <div class="flex flex-col p-space-md rounded-xl bg-surface-container-low min-w-[280px] border border-white/5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="font-headline text-headline-sm text-on-surface font-bold leading-none">Level {{ auth()->user()->level }}</p>
                    <p class="font-label-meta text-label-meta text-on-surface-variant uppercase tracking-wide">Quest rank</p>
                </div>
                <span class="font-label-code text-label-code text-primary">{{ auth()->user()->xp }} XP</span>
            </div>
        </div>
    </header>

    <form method="GET" action="{{ route('learnquest.index') }}" class="flex flex-col xl:flex-row items-stretch xl:items-center justify-between gap-space-md p-space-sm rounded-xl bg-surface-container-low border border-white/5">
        <div class="flex items-center gap-space-xs overflow-x-auto pb-1 xl:pb-0">
            <span class="px-space-sm py-1.5 rounded-lg bg-primary-container text-on-primary-container font-semibold text-body-md whitespace-nowrap">All {{ $counts['all'] }}</span>
            <span class="px-space-sm py-1.5 rounded-lg text-on-surface-variant text-body-md whitespace-nowrap">Active {{ $counts['active'] }}</span>
            <span class="px-space-sm py-1.5 rounded-lg text-on-surface-variant text-body-md whitespace-nowrap">Completed {{ $counts['completed'] }}</span>
        </div>
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-space-sm">
            <input type="search" name="q" value="{{ request('q') }}" class="pb-input min-w-[220px]" placeholder="Filter quest title...">
            <select name="difficulty" class="pb-input">
                <option value="">All difficulty</option>
                @foreach(['beginner','intermediate','advanced'] as $diff)
                    <option value="{{ $diff }}" @selected(request('difficulty') === $diff)>{{ ucfirst($diff) }}</option>
                @endforeach
            </select>
            <button type="submit" class="pb-btn-primary">Filter</button>
        </div>
    </form>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-space-lg">
        @forelse($missions as $mission)
            @php $enrollment = $enrollments->get($mission->id); @endphp
            <article class="pb-card p-space-lg flex flex-col gap-3 hover:-translate-y-0.5 transition-transform">
                <div class="flex items-center justify-between gap-2">
                    <span class="px-2 py-0.5 rounded font-label-code text-label-code bg-tertiary-container/20 text-tertiary capitalize">{{ $mission->difficulty }}</span>
                    <span class="font-label-code text-label-code text-primary">+{{ $mission->xp_reward }} XP</span>
                </div>
                <h2 class="font-headline text-headline-sm text-on-surface">{{ $mission->title }}</h2>
                <p class="font-body-sm text-body-sm text-on-surface-variant line-clamp-3 flex-1">{{ $mission->description }}</p>
                <div class="flex flex-wrap gap-1">
                    @foreach($mission->skills as $skill)
                        <span class="px-2 py-0.5 rounded bg-surface-container-highest font-label-code text-label-code text-on-surface-variant">{{ $skill->name }}</span>
                    @endforeach
                </div>
                @if($enrollment)
                    <div class="font-label-code text-label-code text-secondary capitalize">Status: {{ str_replace('_', ' ', $enrollment->status) }} · {{ $enrollment->progress_percent }}%</div>
                @endif
                <a href="{{ route('learnquest.show', $mission) }}" class="pb-btn-secondary w-full mt-auto">Open workspace</a>
            </article>
        @empty
            <div class="md:col-span-2 xl:col-span-3 rounded-xl border border-dashed border-white/10 p-10 text-center text-on-surface-variant">
                No published missions match your filters.
            </div>
        @endforelse
    </div>

    <div>{{ $missions->links() }}</div>
</div>
@endsection
