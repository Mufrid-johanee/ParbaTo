@extends('layouts.app')

@section('title', 'Leaderboard')

@section('content')
<div class="flex flex-col gap-space-lg max-w-3xl">
    <header>
        <span class="inline-flex items-center px-space-xs py-0.5 rounded-full bg-secondary/10 text-secondary font-label-code text-label-code mb-2">GAMIFICATION</span>
        <h1 class="font-headline text-headline-xl text-on-surface tracking-tight">Leaderboard</h1>
        <p class="text-on-surface-variant">Classroom-scoped ranks by XP. Private details stay private — initials only beyond your own row.</p>
    </header>

    @if($user->isStudent())
        <x-card>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <p class="font-label-code text-label-code text-primary">Your rank #{{ $myRank }}</p>
                    <p class="font-headline text-headline-sm text-on-surface mt-1">{{ $level['name'] }} · Level {{ $level['level'] }}</p>
                </div>
                <div class="text-sm text-on-surface-variant">{{ number_format($level['current_xp']) }} XP · {{ $badgeCount }} badges</div>
            </div>
            <div class="mt-3">
                <x-progress-bar :percent="$level['progress_percent']" label="Progress to next level" />
            </div>
        </x-card>
    @endif

    @if($classrooms->isNotEmpty())
        <form method="GET" class="flex flex-wrap gap-2 items-center">
            <label for="classroom" class="font-label-meta text-label-meta uppercase text-on-surface-variant">Classroom</label>
            <select id="classroom" name="classroom" class="rounded-lg bg-surface-container border border-white/10 px-3 py-2 text-on-surface" onchange="this.form.submit()">
                <option value="">All my classrooms</option>
                @foreach($classrooms as $c)
                    <option value="{{ $c->id }}" @selected($classroomId == $c->id)>{{ $c->name }}</option>
                @endforeach
            </select>
        </form>
    @endif

    <div class="overflow-x-auto pb-card">
        <table class="min-w-full text-sm text-left">
            <thead class="border-b border-white/10 text-on-surface-variant">
                <tr>
                    <th class="p-3" scope="col">Rank</th>
                    <th class="p-3" scope="col">Learner</th>
                    <th class="p-3" scope="col">Level</th>
                    <th class="p-3" scope="col">XP</th>
                    <th class="p-3" scope="col">Badges</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $index => $row)
                    @php $rank = ($rows->currentPage() - 1) * $rows->perPage() + $index + 1; @endphp
                    <tr @class(['border-b border-white/5', 'bg-primary/5' => $row->id === $user->id])>
                        <td class="p-3 font-label-code">#{{ $rank }}</td>
                        <td class="p-3">
                            <div class="flex items-center gap-2">
                                <x-avatar :name="$row->preferredName()" size="sm" />
                                <span class="text-on-surface">
                                    {{ $row->id === $user->id ? $row->preferredName() : strtoupper(substr($row->preferredName(), 0, 1)).'.' }}
                                </span>
                            </div>
                        </td>
                        <td class="p-3">{{ $row->level }}</td>
                        <td class="p-3">{{ number_format($row->xp) }}</td>
                        <td class="p-3">{{ $row->achievements_count }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="p-8 text-center text-on-surface-variant">No leaderboard data yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    {{ $rows->withQueryString()->links() }}
</div>
@endsection
