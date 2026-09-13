@extends('layouts.app')

@section('title', $classroom->name)

@section('content')
<div class="flex flex-col gap-space-lg pb-space-xl">
    <header class="flex flex-col xl:flex-row xl:items-end justify-between gap-space-md">
        <div>
            <a href="{{ route('classrooms.index') }}" class="font-label-code text-label-code text-primary hover:underline">← Classrooms</a>
            <div class="flex flex-wrap items-center gap-2 mt-2">
                <h1 class="font-headline text-headline-xl text-on-surface">{{ $classroom->name }}</h1>
                @if($live)
                    <span class="px-2 py-0.5 rounded-full bg-error-container/40 font-label-code text-label-code text-error uppercase flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-error animate-pulse"></span> Live
                    </span>
                @endif
            </div>
            <p class="font-body-md text-body-md text-on-surface-variant mt-1">
                {{ $classroom->subject ?: $classroom->course?->title }}
                · {{ $classroom->teacher->preferredName() }}
                @if($classroom->room_label) · {{ $classroom->room_label }} @endif
            </p>
            @if($classroom->description)
                <p class="font-body-sm text-body-sm text-on-surface-variant mt-2 max-w-2xl">{{ $classroom->description }}</p>
            @endif
        </div>
        <div class="flex flex-col sm:flex-row gap-2">
            @if($live)
                <a href="{{ route('classtwin.show', $live) }}" class="pb-btn-primary min-h-11 px-4 inline-flex items-center justify-center">Open live ClassTwin</a>
            @elseif($isTeacher)
                <form method="POST" action="{{ route('classrooms.sessions.start', $classroom) }}">
                    @csrf
                    <input type="hidden" name="title" value="{{ $classroom->name }} — {{ now()->format('M j') }}">
                    <button type="submit" class="pb-btn-primary min-h-11 px-4 w-full">Start session</button>
                </form>
            @endif
            @if($isTeacher)
                <a href="{{ route('classrooms.edit', $classroom) }}" class="pb-btn-secondary min-h-11 px-4 inline-flex items-center justify-center">Edit</a>
            @endif
            <a href="{{ route('learnquest.index') }}" class="pb-btn-secondary min-h-11 px-4 inline-flex items-center justify-center">LearnQuest</a>
            <a href="{{ route('flexlearn.index') }}" class="pb-btn-secondary min-h-11 px-4 inline-flex items-center justify-center">FlexLearn</a>
        </div>
    </header>

    @if(session('status'))
        <div class="rounded-xl bg-secondary-container/15 border border-secondary/20 px-space-md py-space-sm text-secondary" role="status">{{ session('status') }}</div>
    @endif

    <div class="grid grid-cols-1 xl:grid-cols-12 gap-space-lg">
        <section class="xl:col-span-7 flex flex-col gap-space-md">
            @if($isTeacher)
                <div class="pb-card p-space-lg">
                    <h2 class="font-headline text-headline-md text-on-surface mb-2">Classroom join code</h2>
                    <p class="font-display-hero text-3xl sm:text-4xl font-bold tracking-[0.2em] text-primary">{{ $classroom->join_code }}</p>
                    <p class="font-body-sm text-body-sm text-on-surface-variant mt-2">Students use this code to join the classroom (not the live attendance code).</p>
                </div>
            @endif

            <div class="pb-card p-space-lg">
                <h2 class="font-headline text-headline-md text-on-surface mb-4">Students ({{ $classroom->members->count() }})</h2>
                @forelse($classroom->members->sortBy('desk_label') as $member)
                    <div class="flex items-center justify-between gap-3 py-2 border-b border-white/5 last:border-0">
                        <div>
                            <p class="font-body-md text-on-surface">{{ $member->user->preferredName() }}</p>
                            <p class="font-label-code text-label-code text-on-surface-variant">{{ $member->desk_label }}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="font-label-code text-label-code text-secondary uppercase">{{ $member->status }}</span>
                            @if($isTeacher)
                                <form method="POST" action="{{ route('classrooms.members.remove', [$classroom, $member->user]) }}" onsubmit="return confirm('Remove this student from the classroom?')">
                                    @csrf
                                    <button type="submit" class="font-label-code text-label-code text-error min-h-10 px-2">Remove</button>
                                </form>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-on-surface-variant">No students have joined yet.</p>
                @endforelse
            </div>

            <div class="pb-card p-space-lg">
                <h2 class="font-headline text-headline-md text-on-surface mb-4">Related LearnQuest missions</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    @forelse($missions as $mission)
                        <a href="{{ route('learnquest.show', $mission) }}" class="rounded-xl bg-surface-container-low p-4 border border-white/5 hover:border-primary/30 transition-colors">
                            <p class="font-headline text-headline-sm text-on-surface">{{ $mission->title }}</p>
                            <p class="font-label-code text-label-code text-tertiary capitalize mt-1">{{ $mission->difficulty }}</p>
                        </a>
                    @empty
                        <p class="text-on-surface-variant sm:col-span-2">No published missions yet.</p>
                    @endforelse
                </div>
            </div>
        </section>

        <section class="xl:col-span-5 flex flex-col gap-space-md">
            <div class="pb-card p-space-lg">
                <h2 class="font-headline text-headline-md text-on-surface mb-4">Session history</h2>
                @forelse($history as $row)
                    @php $s = $row['session']; $stats = $row['stats']; @endphp
                    <article class="mb-4 last:mb-0 rounded-xl bg-surface-container-low p-4 border border-white/5">
                        <h3 class="font-headline text-headline-sm text-on-surface">{{ $s->title ?: 'Session' }}</h3>
                        <p class="font-label-code text-label-code text-on-surface-variant mt-1">
                            {{ optional($s->started_at)->format('M j, Y · H:i') }}
                            @if($s->ended_at) → {{ $s->ended_at->format('H:i') }} @endif
                        </p>
                        <p class="font-body-sm text-body-sm text-on-surface-variant mt-2">
                            {{ $stats['present_count'] }}/{{ $stats['member_count'] }} present
                            ({{ $stats['attendance_percent'] }}%)
                            @if($stats['duration_minutes'] !== null)
                                · {{ $stats['duration_minutes'] }} min
                            @endif
                        </p>
                    </article>
                @empty
                    <p class="text-on-surface-variant">No ended sessions yet. Start a live ClassTwin session to begin history.</p>
                @endforelse
            </div>

            @if(! $isTeacher && ! $isMember)
                <div class="rounded-xl border border-dashed border-white/10 p-6 text-center text-on-surface-variant">
                    You are not a member of this classroom.
                </div>
            @endif

            @if($isTeacher)
                <div class="pb-card p-space-lg border border-error/20">
                    <h2 class="font-headline text-headline-sm text-on-surface mb-2">Archive classroom</h2>
                    <p class="font-body-sm text-body-sm text-on-surface-variant mb-3">Archived classrooms leave the active list. End any live session first.</p>
                    <form method="POST" action="{{ route('classrooms.archive', $classroom) }}" onsubmit="return confirm('Archive this classroom?')">
                        @csrf
                        <button type="submit" class="pb-btn-secondary min-h-11 px-4 text-error">Archive</button>
                    </form>
                </div>
            @endif
        </section>
    </div>
</div>
@endsection
