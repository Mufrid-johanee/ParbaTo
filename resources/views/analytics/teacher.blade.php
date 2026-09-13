@extends('layouts.app')

@section('title', 'Teacher Analytics')

@section('content')
<div class="flex flex-col gap-space-lg">
    <header>
        <span class="inline-flex items-center px-space-xs py-0.5 rounded-full bg-secondary/10 text-secondary font-label-code text-label-code mb-2">FACULTY COMMAND</span>
        <h1 class="font-headline text-headline-xl text-on-surface tracking-tight">Classroom Intelligence</h1>
        <p class="font-body-lg text-body-lg text-on-surface-variant">Insights calculated from stored ParbaTo data — never fabricated.</p>
    </header>

    <section class="grid grid-cols-2 lg:grid-cols-4 gap-space-md">
        @foreach([
            ['Students', $overview['total_students']],
            ['Classrooms', $overview['total_classrooms']],
            ['Missions', $overview['total_missions']],
            ['Assessments', $overview['total_assessments']],
            ['Sessions', $overview['total_sessions']],
            ['Avg attendance', $overview['average_attendance'].'%'],
            ['Learning activity', $overview['learning_activity']],
        ] as [$label, $value])
            <div class="pb-card p-space-md">
                <div class="font-label-meta text-label-meta uppercase text-on-surface-variant">{{ $label }}</div>
                <div class="font-headline text-headline-lg text-on-surface">{{ $value }}</div>
            </div>
        @endforeach
    </section>

    <section>
        <h2 class="font-headline text-headline-md mb-3">Needs attention</h2>
        @forelse($atRisk as $row)
            <div class="pb-card p-space-md mb-2">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2">
                    <a href="{{ route('teacher.students.portfolio', $row['student']) }}" class="font-headline text-headline-sm text-on-surface hover:text-primary">{{ $row['student']->preferredName() }}</a>
                    <ul class="text-sm text-error space-y-0.5">
                        @foreach($row['reasons'] as $reason)
                            <li>{{ $reason }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @empty
            <div class="pb-card p-6 text-center text-on-surface-variant">No at-risk signals from current rules.</div>
        @endforelse
    </section>

    <section>
        <h2 class="font-headline text-headline-md mb-3">Classrooms</h2>
        <div class="grid md:grid-cols-2 gap-space-md">
            @forelse($classrooms as $row)
                <a href="{{ route('analytics.classroom', $row['classroom']) }}" class="pb-card p-space-md block">
                    <h3 class="font-headline text-headline-sm">{{ $row['classroom']->name }}</h3>
                    <p class="text-sm text-on-surface-variant mt-1">{{ $row['member_count'] }} members · {{ $row['session_count'] }} sessions · {{ $row['average_attendance'] }}% attendance</p>
                </a>
            @empty
                <div class="pb-card p-8 text-center text-on-surface-variant md:col-span-2">No analytics data yet.</div>
            @endforelse
        </div>
    </section>

    <section>
        <h2 class="font-headline text-headline-md mb-3">Skill mastery</h2>
        <p class="text-sm text-on-surface-variant mb-3">Classroom average mastery {{ $skills['average_mastery'] }}%</p>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 mb-4">
            @foreach($skills['distribution'] as $band => $count)
                <div class="pb-card p-3 text-center">
                    <div class="font-headline text-headline-sm">{{ $count }}</div>
                    <div class="text-xs text-on-surface-variant capitalize">{{ str_replace('_',' ', $band) }}</div>
                </div>
            @endforeach
        </div>
        <div class="overflow-x-auto">
            <svg viewBox="0 0 400 120" class="w-full max-w-xl h-28 text-primary">
                @php $bars = $skills['skills']->take(6)->values(); $max = max(1, (float)$bars->max('avg_mastery')); @endphp
                @foreach($bars as $i => $s)
                    @php $h = ((float)$s->avg_mastery / $max) * 90; $x = 20 + $i * 60; @endphp
                    <rect x="{{ $x }}" y="{{ 100 - $h }}" width="36" height="{{ $h }}" fill="currentColor" opacity="0.7" rx="4"/>
                    <text x="{{ $x + 18 }}" y="115" text-anchor="middle" fill="#9aa0ad" font-size="8">{{ \Illuminate\Support\Str::limit($s->skill->name ?? 'Skill', 8) }}</text>
                @endforeach
            </svg>
        </div>
    </section>

    <section>
        <h2 class="font-headline text-headline-md mb-3">Student progress matrix</h2>
        <div class="overflow-x-auto pb-card hidden md:block">
            <table class="min-w-full text-sm text-left">
                <thead class="border-b border-white/10 text-on-surface-variant">
                    <tr>
                        <th class="p-3">Student</th>
                        <th class="p-3">Missions</th>
                        <th class="p-3">Assessments</th>
                        <th class="p-3">Attendance</th>
                        <th class="p-3">Mastery</th>
                        <th class="p-3">Evidence</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($matrix as $row)
                        <tr class="border-b border-white/5">
                            <td class="p-3"><a class="text-primary" href="{{ route('teacher.students.portfolio', $row['student']) }}">{{ $row['student']->preferredName() }}</a></td>
                            <td class="p-3">{{ $row['missions'] }}</td>
                            <td class="p-3">{{ $row['assessments'] }}</td>
                            <td class="p-3">{{ $row['attendance'] }}%</td>
                            <td class="p-3">{{ $row['avg_mastery'] }}%</td>
                            <td class="p-3">{{ $row['evidence'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="md:hidden flex flex-col gap-2">
            @foreach($matrix as $row)
                <div class="pb-card p-3">
                    <a href="{{ route('teacher.students.portfolio', $row['student']) }}" class="font-headline text-headline-sm text-primary">{{ $row['student']->preferredName() }}</a>
                    <p class="text-xs text-on-surface-variant mt-1">Missions {{ $row['missions'] }} · Assessments {{ $row['assessments'] }} · Att {{ $row['attendance'] }}% · Mastery {{ $row['avg_mastery'] }}%</p>
                </div>
            @endforeach
        </div>
    </section>

    <section class="grid lg:grid-cols-2 gap-space-lg">
        <div>
            <h2 class="font-headline text-headline-md mb-3">Missions</h2>
            @forelse($missions as $m)
                <div class="pb-card p-3 mb-2 text-sm">
                    <div class="font-headline text-on-surface">{{ $m['mission']->title }}</div>
                    <p class="text-on-surface-variant">Enrolled {{ $m['enrollments'] }} · Submitted {{ $m['submissions'] }} · Evaluated {{ $m['evaluations'] }} · Avg {{ $m['average_score'] ?? '—' }}</p>
                </div>
            @empty
                <div class="pb-card p-6 text-center text-on-surface-variant">No mission analytics yet.</div>
            @endforelse
        </div>
        <div>
            <h2 class="font-headline text-headline-md mb-3">Assessments</h2>
            @forelse($assessments as $a)
                <div class="pb-card p-3 mb-2 text-sm">
                    <div class="font-headline text-on-surface">{{ $a['assessment']->title }}</div>
                    <p class="text-on-surface-variant">Attempts {{ $a['total_attempts'] }} · Graded {{ $a['graded'] }} · Pending {{ $a['pending_review'] }} · Avg {{ $a['average_score'] ?? '—' }}% · Pass {{ $a['pass_rate'] ?? '—' }}%</p>
                </div>
            @empty
                <div class="pb-card p-6 text-center text-on-surface-variant">No assessment analytics yet.</div>
            @endforelse
        </div>
    </section>

    @if($classroomDetail)
        <section>
            <h2 class="font-headline text-headline-md mb-3">Latest classroom attendance trend</h2>
            <div class="overflow-x-auto">
                <svg viewBox="0 0 400 100" class="w-full max-w-2xl h-24">
                    @php
                        $points = collect($classroomDetail['trend']);
                        $n = max(1, $points->count() - 1);
                        $path = $points->values()->map(function ($p, $i) use ($n) {
                            $x = 20 + ($i / $n) * 360;
                            $y = 90 - (($p['percentage'] / 100) * 70);
                            return ($i === 0 ? 'M' : 'L').$x.' '.$y;
                        })->implode(' ');
                    @endphp
                    <path d="{{ $path }}" fill="none" stroke="#7dd3c7" stroke-width="2"/>
                </svg>
            </div>
        </section>
    @endif
</div>
@endsection
