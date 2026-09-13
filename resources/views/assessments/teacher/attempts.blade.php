@extends('layouts.app')

@section('title', 'Attempts')

@section('content')
<div class="flex flex-col gap-space-lg">
    <header>
        <a href="{{ route('teacher.assessments.edit', $assessment) }}" class="text-primary text-sm">← Back</a>
        <h1 class="font-headline text-headline-xl text-on-surface mt-2">{{ $assessment->title }} — Attempts</h1>
    </header>
    @if($attempts->isEmpty())
        <div class="pb-card p-10 text-center text-on-surface-variant">No attempts yet.</div>
    @else
        <div class="overflow-x-auto pb-card">
            <table class="min-w-full text-left text-sm">
                <thead class="border-b border-white/10 text-on-surface-variant">
                    <tr>
                        <th class="p-3">Student</th>
                        <th class="p-3">Status</th>
                        <th class="p-3">Score</th>
                        <th class="p-3">Submitted</th>
                        <th class="p-3"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($attempts as $attempt)
                        <tr class="border-b border-white/5">
                            <td class="p-3 text-on-surface">{{ $attempt->user->preferredName() }}</td>
                            <td class="p-3 uppercase font-label-code text-label-code">{{ $attempt->status }}</td>
                            <td class="p-3">{{ $attempt->accuracy !== null ? number_format($attempt->accuracy, 1).'%' : '—' }}</td>
                            <td class="p-3 text-on-surface-variant">{{ optional($attempt->submitted_at)->diffForHumans() ?? '—' }}</td>
                            <td class="p-3"><a class="text-primary" href="{{ route('teacher.assessments.attempts.show', [$assessment, $attempt]) }}">Open</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $attempts->links() }}
    @endif
</div>
@endsection
