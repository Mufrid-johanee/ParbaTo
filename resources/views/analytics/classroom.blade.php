@extends('layouts.app')

@section('title', 'Classroom Analytics')

@section('content')
@php $classroom = $detail['classroom']; @endphp
<div class="flex flex-col gap-space-lg">
    <header>
        <a href="{{ route('analytics.teacher') }}" class="text-primary text-sm">← Analytics</a>
        <h1 class="font-headline text-headline-xl text-on-surface mt-2">{{ $classroom->name }}</h1>
        <p class="text-on-surface-variant">Attendance threshold {{ $detail['lowThreshold'] }}%</p>
    </header>

    <div class="overflow-x-auto pb-card">
        <table class="min-w-full text-sm">
            <thead class="border-b border-white/10 text-on-surface-variant">
                <tr>
                    <th class="p-2 text-left">Student</th>
                    @foreach($detail['sessions'] as $session)
                        <th class="p-2">{{ optional($session->started_at)->format('M j') }}</th>
                    @endforeach
                    <th class="p-2">%</th>
                </tr>
            </thead>
            <tbody>
                @foreach($detail['grid'] as $row)
                    <tr class="border-b border-white/5 {{ $row['low'] ? 'bg-error/5' : '' }}">
                        <td class="p-2 text-on-surface">{{ $row['student']->preferredName() }}</td>
                        @foreach($detail['sessions'] as $session)
                            <td class="p-2 text-center font-label-code text-label-code">{{ strtoupper(substr($row['sessions'][$session->id] ?? 'a', 0, 1)) }}</td>
                        @endforeach
                        <td class="p-2 {{ $row['low'] ? 'text-error' : '' }}">{{ $row['percentage'] }}%</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="md:hidden flex flex-col gap-2">
        @foreach($matrix as $row)
            <div class="pb-card p-3">
                <div class="font-headline text-sm">{{ $row['student']->preferredName() }}</div>
                <p class="text-xs text-on-surface-variant">Att {{ $row['attendance'] }}% · Missions {{ $row['missions'] }} · Mastery {{ $row['avg_mastery'] }}%</p>
            </div>
        @endforeach
    </div>
</div>
@endsection
