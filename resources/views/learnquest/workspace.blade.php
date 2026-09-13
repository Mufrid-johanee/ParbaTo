@extends('layouts.app')

@section('title', $mission->title)

@section('content')
<div class="flex flex-col gap-space-lg max-w-5xl">
    <div class="flex flex-col gap-2">
        <div class="flex items-center gap-2 flex-wrap">
            <a href="{{ route('learnquest.index') }}" class="font-label-code text-label-code text-secondary hover:underline">← Mission Hub</a>
            <span class="px-2 py-0.5 rounded font-label-code text-label-code bg-tertiary-container/20 text-tertiary capitalize">{{ $mission->difficulty }}</span>
            <span class="font-label-code text-label-code text-primary">+{{ $mission->xp_reward }} XP</span>
        </div>
        <h1 class="font-headline text-headline-xl text-on-surface">{{ $mission->title }}</h1>
        <p class="font-body-lg text-body-lg text-on-surface-variant">{{ $mission->description }}</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-space-lg">
        <div class="lg:col-span-2 space-y-space-md">
            <section class="pb-card p-space-lg">
                <h2 class="font-headline text-headline-sm text-on-surface mb-2">Problem</h2>
                <p class="font-body-md text-body-md text-on-surface-variant whitespace-pre-line">{{ $mission->problem_statement }}</p>
            </section>
            <section class="pb-card p-space-lg">
                <h2 class="font-headline text-headline-sm text-on-surface mb-2">Objective</h2>
                <p class="font-body-md text-body-md text-on-surface-variant whitespace-pre-line">{{ $mission->objective }}</p>
            </section>
            <section class="pb-card p-space-lg">
                <h2 class="font-headline text-headline-sm text-on-surface mb-4">Lifecycle tasks</h2>
                <ol class="space-y-3">
                    @foreach($mission->tasks as $task)
                        <li class="flex gap-3 rounded-lg bg-surface-container-low p-3">
                            <span class="font-label-code text-label-code text-secondary uppercase w-20 shrink-0">{{ $task->phase }}</span>
                            <div>
                                <div class="font-body-md text-body-md text-on-surface font-medium">{{ $task->title }}</div>
                                <div class="font-body-sm text-body-sm text-on-surface-variant">{{ $task->description }}</div>
                            </div>
                        </li>
                    @endforeach
                </ol>
            </section>
        </div>

        <aside class="space-y-space-md">
            <div class="pb-card p-space-md">
                @if($enrollment)
                    <div class="font-label-code text-label-code text-secondary uppercase mb-2">In progress</div>
                    <div class="font-headline text-headline-sm text-on-surface capitalize mb-1">{{ $enrollment->lifecycle_phase }}</div>
                    <div class="w-full h-2 rounded-full bg-surface-container mb-2">
                        <div class="h-full rounded-full bg-primary-container" style="width: {{ $enrollment->progress_percent }}%"></div>
                    </div>
                    <p class="font-body-sm text-body-sm text-on-surface-variant">{{ $enrollment->progress_percent }}% complete · {{ str_replace('_', ' ', $enrollment->status) }}</p>
                @else
                    <form method="POST" action="{{ route('learnquest.start', $mission) }}">
                        @csrf
                        <button type="submit" class="pb-btn-primary w-full">Start mission</button>
                    </form>
                @endif
            </div>

            <div class="pb-card p-space-md">
                <h3 class="font-headline text-headline-sm text-on-surface mb-3">Resources</h3>
                @forelse($mission->resources as $resource)
                    <a href="{{ $resource->url ?? '#' }}" target="_blank" rel="noopener" class="block py-2 border-b border-white/5 last:border-0 text-secondary hover:underline font-body-sm text-body-sm">
                        {{ $resource->title }}
                    </a>
                @empty
                    <p class="text-body-sm text-on-surface-variant">No resources attached.</p>
                @endforelse
            </div>

            <div class="pb-card p-space-md">
                <h3 class="font-headline text-headline-sm text-on-surface mb-2">Skills</h3>
                <div class="flex flex-wrap gap-1">
                    @foreach($mission->skills as $skill)
                        <span class="px-2 py-0.5 rounded bg-surface-container-highest font-label-code text-label-code">{{ $skill->name }}</span>
                    @endforeach
                </div>
            </div>
        </aside>
    </div>
</div>
@endsection
