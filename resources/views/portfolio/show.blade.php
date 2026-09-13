@extends('layouts.app')

@section('title', ($teacherView ? 'Student portfolio' : 'My portfolio'))

@section('content')
<div class="flex flex-col gap-space-xl">
    <header class="relative rounded-2xl bg-surface-container-low p-space-md sm:p-space-lg border border-white/5 overflow-hidden">
        <div class="absolute -right-16 -top-16 w-56 h-56 rounded-full bg-primary-container/10 blur-3xl"></div>
        <div class="relative flex flex-col sm:flex-row sm:items-center gap-space-md">
            <div class="flex h-16 w-16 items-center justify-center rounded-full bg-primary-container/30 text-primary font-headline text-2xl">
                {{ strtoupper(substr($student->preferredName(), 0, 1)) }}
            </div>
            <div class="flex-1">
                <h1 class="font-headline text-headline-xl text-on-surface">{{ $student->preferredName() }}</h1>
                <p class="text-on-surface-variant capitalize">{{ $student->role }} · Member since {{ $stats['member_since']?->format('M Y') }}</p>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-center">
                <div class="pb-card p-3"><div class="font-label-code text-label-code text-primary">{{ $stats['xp'] }}</div><div class="text-xs text-on-surface-variant">XP</div></div>
                <div class="pb-card p-3"><div class="font-label-code text-label-code text-on-surface">{{ $level['name'] ?? '—' }}</div><div class="text-xs text-on-surface-variant">Level {{ $level['level'] ?? $student->level }}</div></div>
                <div class="pb-card p-3"><div class="font-label-code text-label-code text-on-surface">{{ $stats['missions_completed'] }}</div><div class="text-xs text-on-surface-variant">Missions</div></div>
                <div class="pb-card p-3"><div class="font-label-code text-label-code text-on-surface">{{ $badgeCatalog['count'] ?? 0 }}/{{ $badgeCatalog['total'] ?? 0 }}</div><div class="text-xs text-on-surface-variant">Badges</div></div>
            </div>
        </div>
    </header>

    @isset($level)
        <section class="grid lg:grid-cols-2 gap-space-lg">
            <x-card title="Level progress">
                <p class="text-on-surface">{{ $level['name'] }} · {{ number_format($level['current_xp']) }} XP</p>
                <div class="mt-3"><x-progress-bar :percent="$level['progress_percent']" label="Progress to next level" /></div>
                @if(!($level['is_max'] ?? false))
                    <p class="text-xs text-on-surface-variant mt-2">Next at {{ number_format($level['next_level_xp']) }} XP</p>
                @endif
                <h3 class="font-headline text-headline-sm mt-4 mb-2">Recent XP</h3>
                @forelse($recentXp as $entry)
                    <p class="text-sm text-on-surface-variant">+{{ $entry->amount }} · {{ $entry->description }} · {{ $entry->awarded_at->diffForHumans() }}</p>
                @empty
                    <p class="text-sm text-on-surface-variant">No XP history yet.</p>
                @endforelse
            </x-card>
            <x-card title="Badges">
                <h3 class="font-label-meta text-label-meta uppercase text-secondary mb-2">Earned ({{ $badgeCatalog['count'] }})</h3>
                <div class="flex flex-wrap gap-2 mb-4">
                    @forelse($badgeCatalog['earned'] as $badge)
                        <x-badge tone="secondary" :title="$badge->description">{{ $badge->name }}</x-badge>
                    @empty
                        <p class="text-sm text-on-surface-variant">No badges yet.</p>
                    @endforelse
                </div>
                <h3 class="font-label-meta text-label-meta uppercase text-on-surface-variant mb-2">Locked</h3>
                <div class="flex flex-wrap gap-2 opacity-60">
                    @foreach($badgeCatalog['locked'] as $badge)
                        <x-badge tone="neutral">{{ $badge->name }}</x-badge>
                    @endforeach
                </div>
            </x-card>
        </section>
    @endisset

    <section class="grid lg:grid-cols-2 gap-space-lg">
        <div>
            <h2 class="font-headline text-headline-md mb-3">Skill mastery</h2>
            @if($mastery['skills']->isEmpty())
                <div class="pb-card p-8 text-center text-on-surface-variant">No mastery evidence yet.</div>
            @else
                <div class="flex flex-col gap-2">
                    @foreach($mastery['skills'] as $row)
                        <div class="pb-card p-3">
                            <div class="flex justify-between text-sm mb-1">
                                <span class="text-on-surface">{{ $row->skill->name }}</span>
                                <span class="font-label-code text-label-code">{{ $row->mastery }}% · {{ \App\Services\MasteryService::bandLabel((int)$row->mastery) }}</span>
                            </div>
                            <div class="h-1.5 rounded-full bg-surface-container"><div class="h-full rounded-full bg-primary" style="width: {{ $row->mastery }}%"></div></div>
                            <p class="text-xs text-on-surface-variant mt-1">{{ $row->evidence_count }} evidence</p>
                        </div>
                    @endforeach
                </div>
            @endif
            <div class="mt-4 grid sm:grid-cols-2 gap-3">
                <div class="pb-card p-3">
                    <h3 class="font-label-meta text-label-meta uppercase text-secondary mb-2">Top skills</h3>
                    @forelse($mastery['top'] as $row)
                        <p class="text-sm text-on-surface">{{ $row->skill->name }}</p>
                    @empty
                        <p class="text-sm text-on-surface-variant">—</p>
                    @endforelse
                </div>
                <div class="pb-card p-3">
                    <h3 class="font-label-meta text-label-meta uppercase text-error mb-2">Needs support</h3>
                    @forelse($mastery['needs_support'] as $row)
                        <p class="text-sm text-on-surface">{{ $row->skill->name }}</p>
                    @empty
                        <p class="text-sm text-on-surface-variant">None</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div>
            <h2 class="font-headline text-headline-md mb-3">FlexLearn recommendations</h2>
            @forelse($recommendations as $rec)
                <div class="pb-card p-space-md mb-2">
                    <h3 class="font-headline text-headline-sm text-on-surface">{{ $rec->title }}</h3>
                    <p class="text-sm text-on-surface-variant mt-1">{{ $rec->reason }}</p>
                </div>
            @empty
                <div class="pb-card p-8 text-center text-on-surface-variant">No active recommendations.</div>
            @endforelse
        </div>
    </section>

    <section>
        <h2 class="font-headline text-headline-md mb-3">Portfolio timeline</h2>
        @forelse($timeline as $item)
            <div class="pb-card p-space-md mb-2">
                <div class="flex flex-col sm:flex-row sm:justify-between gap-1">
                    <div>
                        <p class="font-label-code text-label-code text-primary uppercase">{{ class_basename($item->source_type) }}</p>
                        <h3 class="font-headline text-headline-sm text-on-surface">{{ $item->title }}</h3>
                        <p class="text-sm text-on-surface-variant">{{ $item->summary }}</p>
                    </div>
                    <span class="text-xs text-on-surface-variant">{{ $item->created_at->diffForHumans() }}</span>
                </div>
            </div>
        @empty
            <div class="pb-card p-8 text-center text-on-surface-variant">No portfolio evidence yet.</div>
        @endforelse
        <div class="mt-3">{{ $timeline->links() }}</div>
    </section>

    <section>
        <h2 class="font-headline text-headline-md mb-3">Learning evidence</h2>
        @forelse($evidence as $ev)
            <div class="pb-card p-3 mb-2 flex flex-col sm:flex-row sm:justify-between gap-1 text-sm">
                <div>
                    <span class="text-on-surface">{{ $ev->skill?->name ?? 'Skill' }}</span>
                    <span class="text-on-surface-variant"> · {{ $ev->evidence_type }}</span>
                </div>
                <div class="text-on-surface-variant">{{ number_format($ev->score, 1) }} · {{ $ev->created_at->format('M j, Y') }}</div>
            </div>
        @empty
            <div class="pb-card p-8 text-center text-on-surface-variant">No evidence recorded yet.</div>
        @endforelse
        <div class="mt-3">{{ $evidence->links() }}</div>
    </section>
</div>
@endsection
