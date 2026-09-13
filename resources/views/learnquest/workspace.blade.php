@extends('layouts.app')

@section('title', $mission->title)

@section('content')
@php
    $phases = ['discover','learn','practice','build','submit','present','evaluate'];
    $canAct = $enrollment && !in_array($enrollment->status, ['completed'], true);
    $studentCompletableDone = $mission->tasks
        ->where('is_required', true)
        ->where('phase', '!=', 'evaluate')
        ->every(fn ($t) => $completedTaskIds->contains($t->id));
@endphp

<div class="flex flex-col gap-space-lg w-full max-w-6xl">
    {{-- Top banner --}}
    <div class="relative bg-surface-container-low rounded-xl p-4 sm:p-space-lg shadow-xl overflow-hidden border border-white/5">
        <div class="absolute -right-16 -top-16 w-80 h-80 rounded-full bg-primary-container/10 blur-3xl pointer-events-none"></div>
        <div class="relative flex flex-col gap-space-md">
            <div class="flex flex-wrap items-center gap-2 font-label-code text-label-code text-on-surface-variant">
                <a href="{{ route('learnquest.index') }}" class="hover:text-primary transition-colors">← Mission Hub</a>
                <span class="text-outline-variant">/</span>
                <span class="px-space-xs py-0.5 rounded bg-surface-container-high text-secondary capitalize">{{ $mission->difficulty }}</span>
                <span class="text-tertiary font-semibold">+{{ $mission->xp_reward }} XP</span>
            </div>
            <h1 class="font-headline text-headline-lg sm:text-headline-xl text-on-surface tracking-tight">{{ $mission->title }}</h1>
            <p class="font-body-md text-body-md text-on-surface-variant max-w-3xl">{{ $mission->description }}</p>

            <div class="flex flex-wrap items-center gap-3">
                @if($enrollment)
                    <div class="flex items-center gap-2 bg-surface-container px-3 py-1.5 rounded-lg">
                        <span class="w-2 h-2 rounded-full bg-secondary animate-pulse"></span>
                        <span class="font-label-code text-label-code text-on-surface">
                            Stage: <span class="text-secondary font-semibold uppercase">{{ $enrollment->lifecycle_phase }}</span>
                        </span>
                    </div>
                    <div class="flex items-center gap-2 min-w-[160px] flex-1 sm:flex-none sm:min-w-[220px]">
                        <div class="flex-1 h-1.5 rounded-full bg-surface-container-highest overflow-hidden">
                            <div class="h-full bg-secondary rounded-full transition-all" style="width: {{ $enrollment->progress_percent }}%"></div>
                        </div>
                        <span class="font-label-code text-label-code text-secondary font-semibold whitespace-nowrap">{{ $enrollment->progress_percent }}%</span>
                    </div>
                    <span class="font-label-code text-label-code text-on-surface-variant capitalize">{{ str_replace('_', ' ', $enrollment->status) }}</span>
                @else
                    <form method="POST" action="{{ route('learnquest.start', $mission) }}">
                        @csrf
                        <button type="submit" class="pb-btn-primary">
                            <span class="material-symbols-outlined text-lg">play_arrow</span>
                            Start Mission
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-12 gap-space-lg items-start">
        {{-- Stage pipeline --}}
        <aside class="xl:col-span-3 flex flex-col gap-space-md order-2 xl:order-1">
            <div class="pb-card p-space-md">
                <div class="flex items-center gap-2 mb-space-md">
                    <span class="material-symbols-outlined text-primary text-base">alt_route</span>
                    <h2 class="font-headline text-headline-sm text-on-surface">Stage Pipeline</h2>
                </div>
                <ol class="flex flex-col gap-3">
                    @foreach($phases as $i => $phase)
                        @php
                            $phaseTasks = $tasksByPhase->get($phase, collect());
                            $phaseDone = $phaseTasks->isNotEmpty() && $phaseTasks->every(fn ($t) => $completedTaskIds->contains($t->id));
                            $isCurrent = $enrollment && $enrollment->lifecycle_phase === $phase;
                        @endphp
                        <li class="flex items-start gap-3 rounded-lg p-2 {{ $isCurrent ? 'bg-surface-container shadow-[0_0_15px_rgba(76,215,246,0.12)]' : '' }} {{ ! $enrollment ? 'opacity-50' : '' }}">
                            <div class="w-6 h-6 rounded-full flex items-center justify-center shrink-0 text-xs font-label-code
                                {{ $phaseDone ? 'bg-surface-container-high text-secondary' : ($isCurrent ? 'bg-secondary text-on-secondary' : 'bg-surface-container-highest text-on-surface-variant') }}">
                                @if($phaseDone)
                                    <span class="material-symbols-outlined text-sm">check</span>
                                @else
                                    {{ str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) }}
                                @endif
                            </div>
                            <div>
                                <div class="font-label-code text-label-code {{ $phaseDone ? 'line-through opacity-70' : ($isCurrent ? 'text-secondary font-bold' : 'text-on-surface-variant') }} uppercase">
                                    {{ $phase }}
                                </div>
                                <div class="font-body-sm text-body-sm text-on-surface-variant">
                                    {{ $phaseTasks->count() }} task{{ $phaseTasks->count() === 1 ? '' : 's' }}
                                </div>
                            </div>
                        </li>
                    @endforeach
                </ol>
            </div>

            <div class="pb-card p-space-md">
                <div class="flex items-center gap-2 mb-3">
                    <span class="material-symbols-outlined text-primary text-base">menu_book</span>
                    <h3 class="font-headline text-headline-sm text-on-surface">Resources</h3>
                </div>
                @forelse($mission->resources as $resource)
                    <a href="{{ $resource->url }}" target="_blank" rel="noopener" class="group block p-3 rounded-lg bg-surface-container hover:bg-surface-container-high transition-all mb-2 last:mb-0">
                        <div class="font-body-sm text-body-sm text-on-surface group-hover:text-primary">{{ $resource->title }}</div>
                        <div class="font-label-meta text-label-meta text-on-surface-variant uppercase">{{ $resource->type }}</div>
                    </a>
                @empty
                    <p class="text-body-sm text-on-surface-variant">No resources attached yet.</p>
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

        {{-- Main column --}}
        <div class="xl:col-span-6 flex flex-col gap-space-md order-1 xl:order-2">
            <section class="pb-card p-space-md sm:p-space-lg">
                <h2 class="font-headline text-headline-sm text-on-surface mb-2">Problem</h2>
                <p class="font-body-md text-body-md text-on-surface-variant whitespace-pre-line">{{ $mission->problem_statement }}</p>
            </section>
            <section class="pb-card p-space-md sm:p-space-lg">
                <h2 class="font-headline text-headline-sm text-on-surface mb-2">Objective</h2>
                <p class="font-body-md text-body-md text-on-surface-variant whitespace-pre-line">{{ $mission->objective }}</p>
            </section>

            <section class="pb-card p-space-md sm:p-space-lg">
                <h2 class="font-headline text-headline-sm text-on-surface mb-4">Lifecycle tasks</h2>

                @if($mission->tasks->isEmpty())
                    <div class="rounded-xl border border-dashed border-white/10 p-6 text-center text-on-surface-variant text-body-md">
                        No tasks have been defined for this mission yet.
                    </div>
                @else
                    @foreach($phases as $phase)
                        @php $phaseTasks = $tasksByPhase->get($phase, collect()); @endphp
                        @continue($phaseTasks->isEmpty())
                        <div class="mb-space-lg last:mb-0">
                            <div class="flex items-center gap-2 mb-3">
                                <span class="font-label-code text-label-code uppercase text-secondary">{{ $phase }}</span>
                                @php
                                    $phaseComplete = $phaseTasks->every(fn ($t) => $completedTaskIds->contains($t->id));
                                @endphp
                                @if($phaseComplete)
                                    <span class="font-label-meta text-label-meta text-success uppercase">Complete</span>
                                @endif
                            </div>
                            <ul class="space-y-3">
                                @foreach($phaseTasks as $task)
                                    @php $done = $completedTaskIds->contains($task->id); @endphp
                                    <li class="flex flex-col sm:flex-row sm:items-center gap-3 rounded-xl bg-surface-container-low p-3 sm:p-4 border border-white/5">
                                        <div class="flex items-start gap-3 flex-1 min-w-0">
                                            <span class="mt-0.5 w-5 h-5 rounded-full flex items-center justify-center shrink-0 {{ $done ? 'bg-success/20 text-success' : 'bg-surface-container-highest text-outline' }}">
                                                <span class="material-symbols-outlined text-sm">{{ $done ? 'check' : 'radio_button_unchecked' }}</span>
                                            </span>
                                            <div class="min-w-0">
                                                <div class="font-body-md text-body-md text-on-surface font-medium {{ $done ? 'line-through opacity-70' : '' }}">{{ $task->title }}</div>
                                                @if($task->description)
                                                    <div class="font-body-sm text-body-sm text-on-surface-variant mt-0.5">{{ $task->description }}</div>
                                                @endif
                                                <div class="font-label-code text-label-code text-on-surface-variant mt-1">
                                                    {{ $done ? 'Completed' : 'Not Started' }}
                                                    @if($task->phase === 'evaluate') · Teacher step @endif
                                                </div>
                                            </div>
                                        </div>
                                        @if($enrollment && $canAct && ! $done && $task->phase !== 'evaluate')
                                            <form method="POST" action="{{ route('learnquest.tasks.complete', [$mission, $task]) }}" class="sm:shrink-0">
                                                @csrf
                                                <button type="submit" class="pb-btn-secondary w-full sm:w-auto text-xs min-h-11 px-4">
                                                    Complete Task
                                                </button>
                                            </form>
                                        @elseif($done)
                                            <span class="font-label-code text-label-code text-success uppercase sm:shrink-0">Done</span>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                @endif
            </section>
        </div>

        {{-- Right: submission / feedback --}}
        <aside class="xl:col-span-3 flex flex-col gap-space-md order-3">
            @if(! $enrollment)
                <div class="pb-card p-space-md text-body-sm text-on-surface-variant">
                    Start the mission to track tasks, progress, and submission.
                </div>
            @else
                <div class="pb-card p-space-md">
                    <h3 class="font-headline text-headline-sm text-on-surface mb-2">Your progress</h3>
                    <p class="font-label-code text-label-code text-secondary uppercase mb-1">{{ $enrollment->lifecycle_phase }}</p>
                    <p class="font-body-sm text-body-sm text-on-surface-variant mb-3">{{ $enrollment->progress_percent }}% · {{ str_replace('_', ' ', $enrollment->status) }}</p>
                    <div class="w-full h-2 rounded-full bg-surface-container mb-1">
                        <div class="h-full rounded-full bg-primary-container" style="width: {{ $enrollment->progress_percent }}%"></div>
                    </div>
                </div>

                @if($enrollment->submission && in_array($enrollment->submission->status, ['submitted', 'accepted'], true))
                    <div class="pb-card p-space-md">
                        <h3 class="font-headline text-headline-sm text-on-surface mb-2">Submission</h3>
                        <p class="font-label-code text-label-code text-secondary uppercase mb-2">{{ $enrollment->submission->status }}</p>
                        <p class="font-body-sm text-body-sm text-on-surface-variant whitespace-pre-line">{{ $enrollment->submission->summary }}</p>
                        @if($enrollment->submission->repo_url)
                            <a href="{{ $enrollment->submission->repo_url }}" class="block mt-2 text-secondary text-body-sm hover:underline break-all" target="_blank" rel="noopener">Repo link</a>
                        @endif
                        @if($enrollment->submission->demo_url)
                            <a href="{{ $enrollment->submission->demo_url }}" class="block mt-1 text-secondary text-body-sm hover:underline break-all" target="_blank" rel="noopener">Demo link</a>
                        @endif
                        @if($enrollment->submission->status === 'accepted')
                            <div class="mt-4 rounded-lg bg-success/10 border border-success/30 p-3">
                                <div class="font-label-code text-label-code text-success mb-1">Score: {{ $enrollment->submission->score }}</div>
                                <p class="font-body-sm text-body-sm text-on-surface-variant whitespace-pre-line">{{ $enrollment->submission->feedback }}</p>
                            </div>
                        @endif
                    </div>
                @elseif($enrollment && $studentCompletableDone && $enrollment->status !== 'completed')
                    <div class="pb-card p-space-md" x-data="{ submitting: false }">
                        <h3 class="font-headline text-headline-sm text-on-surface mb-2">Submit project</h3>
                        <p class="font-body-sm text-body-sm text-on-surface-variant mb-3">All student tasks are done. Submit for teacher evaluation.</p>
                        <form method="POST" action="{{ route('learnquest.submit', $mission) }}" @submit="submitting = true" class="space-y-3">
                            @csrf
                            <div>
                                <label class="font-label-meta text-label-meta uppercase text-on-surface-variant" for="summary">Project explanation</label>
                                <textarea id="summary" name="summary" rows="5" required minlength="20" class="pb-input mt-1" placeholder="Describe what you built and how it solves the problem...">{{ old('summary') }}</textarea>
                            </div>
                            <div>
                                <label class="font-label-meta text-label-meta uppercase text-on-surface-variant" for="repo_url">Project URL (optional)</label>
                                <input id="repo_url" type="url" name="repo_url" value="{{ old('repo_url') }}" class="pb-input mt-1" placeholder="https://...">
                            </div>
                            <div>
                                <label class="font-label-meta text-label-meta uppercase text-on-surface-variant" for="demo_url">Demo URL (optional)</label>
                                <input id="demo_url" type="url" name="demo_url" value="{{ old('demo_url') }}" class="pb-input mt-1" placeholder="https://...">
                            </div>
                            <button type="submit" class="pb-btn-primary w-full min-h-11" :disabled="submitting" :class="{ 'opacity-60 pointer-events-none': submitting }">
                                <span class="material-symbols-outlined text-lg">rocket_launch</span>
                                <span x-text="submitting ? 'Submitting…' : 'Submit Project'"></span>
                            </button>
                        </form>
                    </div>
                @elseif($enrollment && $enrollment->status === 'in_progress')
                    <div class="pb-card p-space-md text-body-sm text-on-surface-variant">
                        Complete required tasks through Present to unlock submission.
                    </div>
                @endif
            @endif
        </aside>
    </div>
</div>
@endsection
