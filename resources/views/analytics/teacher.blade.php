@extends('layouts.app')

@section('title', 'Teacher Analytics')

@section('content')
<div class="flex flex-col gap-space-lg">
    <header>
        <span class="inline-flex items-center px-space-xs py-0.5 rounded-full bg-secondary/10 text-secondary font-label-code text-label-code mb-2">FACULTY COMMAND</span>
        <h1 class="font-headline text-headline-xl text-on-surface tracking-tight">Classroom Intelligence</h1>
        <p class="font-body-lg text-body-lg text-on-surface-variant">Insights calculated from stored ParbaTo data — never fabricated.</p>
    </header>

    @if(!$classroom)
        <div class="pb-card p-10 text-center text-on-surface-variant">
            No classroom assigned to your teacher account yet. Seed data includes a demo classroom for teacher@parbato.test.
        </div>
    @else
        <div class="flex items-center gap-2 flex-wrap">
            <span class="font-headline text-headline-sm text-on-surface">{{ $classroom->course->title ?? $classroom->name }}</span>
            <span class="px-2 py-0.5 rounded bg-surface-container-highest font-label-code text-label-code text-primary">{{ $classroom->course->code ?? '' }}</span>
        </div>

        <section class="grid grid-cols-2 lg:grid-cols-4 gap-space-md">
            <div class="pb-card p-space-md">
                <div class="font-label-meta text-label-meta uppercase text-on-surface-variant">Members</div>
                <div class="font-headline text-headline-lg text-on-surface">{{ $memberCount }}</div>
            </div>
            <div class="pb-card p-space-md">
                <div class="font-label-meta text-label-meta uppercase text-on-surface-variant">Present (live)</div>
                <div class="font-headline text-headline-lg text-secondary">{{ $presentCount }}</div>
            </div>
            <div class="pb-card p-space-md">
                <div class="font-label-meta text-label-meta uppercase text-on-surface-variant">Avg mastery</div>
                <div class="font-headline text-headline-lg text-on-surface">{{ $avgMastery }}%</div>
            </div>
            <div class="pb-card p-space-md">
                <div class="font-label-meta text-label-meta uppercase text-on-surface-variant">Mission completion</div>
                <div class="font-headline text-headline-lg text-on-surface">{{ $missionCompletion }}%</div>
            </div>
        </section>

        <section class="pb-card p-space-lg">
            <h2 class="font-headline text-headline-md text-on-surface mb-3">Live insights</h2>
            <ul class="space-y-2">
                @foreach($insights as $insight)
                    <li class="flex gap-2 text-body-md text-on-surface-variant">
                        <span class="text-secondary">▸</span>
                        <span>{{ $insight }}</span>
                    </li>
                @endforeach
            </ul>
        </section>

        <section class="grid grid-cols-1 xl:grid-cols-2 gap-space-lg">
            <div class="pb-card p-space-lg overflow-x-auto">
                <h3 class="font-headline text-headline-md text-on-surface mb-4">Topic difficulty</h3>
                <div class="min-w-[280px] space-y-3">
                    @forelse($topicDifficulty as $topic)
                        <div>
                            <div class="flex justify-between mb-1">
                                <span class="font-body-md text-body-md">{{ $topic->skill->name }}</span>
                                <span class="font-label-code text-label-code {{ $topic->avg_mastery < 55 ? 'text-error' : 'text-secondary' }}">{{ (int) round($topic->avg_mastery) }}%</span>
                            </div>
                            <div class="h-2 rounded-full bg-surface-container-highest">
                                <div class="h-full rounded-full bg-primary-container" style="width: {{ (int) round($topic->avg_mastery) }}%"></div>
                            </div>
                        </div>
                    @empty
                        <p class="text-on-surface-variant text-body-sm">No skill data yet.</p>
                    @endforelse
                </div>
            </div>

            <div class="pb-card p-space-lg">
                <h3 class="font-headline text-headline-md text-on-surface mb-4">Students needing attention</h3>
                @forelse($studentsNeedingSupport as $row)
                    <div class="flex items-center justify-between py-3 border-b border-white/5 last:border-0">
                        <div>
                            <div class="font-headline text-headline-sm text-on-surface">{{ $row->user->preferredName() }}</div>
                            <div class="font-body-sm text-body-sm text-on-surface-variant">Low mastery: {{ $row->skill->name }}</div>
                        </div>
                        <span class="font-label-code text-label-code text-error">{{ $row->mastery }}%</span>
                    </div>
                @empty
                    <p class="text-on-surface-variant text-body-sm">No students currently below the support threshold.</p>
                @endforelse
            </div>
        </section>

        <section class="pb-card p-space-lg overflow-x-auto">
            <h3 class="font-headline text-headline-md text-on-surface mb-4">Mission performance</h3>
            <table class="w-full min-w-[480px] text-left">
                <thead>
                    <tr class="font-label-meta text-label-meta uppercase text-on-surface-variant">
                        <th class="py-2 pr-4">Mission</th>
                        <th class="py-2 pr-4">Attempts</th>
                        <th class="py-2">Completed</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($missionStats as $stat)
                        <tr class="border-t border-white/5">
                            <td class="py-3 pr-4 font-body-md text-body-md text-on-surface">{{ $stat->mission->title ?? 'Mission' }}</td>
                            <td class="py-3 pr-4 font-label-code text-label-code">{{ $stat->attempts }}</td>
                            <td class="py-3 font-label-code text-label-code text-secondary">{{ $stat->completed }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="py-4 text-on-surface-variant">No mission attempts yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </section>
    @endif
</div>
@endsection
