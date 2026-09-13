@extends('layouts.app')

@section('title', 'Mission evaluations')

@section('content')
<div class="flex flex-col gap-space-lg max-w-5xl">
    <header>
        <span class="inline-flex items-center px-space-xs py-0.5 rounded-full bg-tertiary/10 text-tertiary font-label-code text-label-code mb-2">LEARNQUEST</span>
        <h1 class="font-headline text-headline-xl text-on-surface">Mission evaluations</h1>
        <p class="font-body-md text-body-md text-on-surface-variant">Review student submissions and record scores + feedback.</p>
    </header>

    <div class="pb-card overflow-x-auto">
        <table class="w-full min-w-[640px] text-left">
            <thead>
                <tr class="font-label-meta text-label-meta uppercase text-on-surface-variant border-b border-white/5">
                    <th class="p-4">Student</th>
                    <th class="p-4">Mission</th>
                    <th class="p-4">Status</th>
                    <th class="p-4">Submitted</th>
                    <th class="p-4"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($submissions as $row)
                    <tr class="border-b border-white/5 last:border-0">
                        <td class="p-4 font-body-md text-body-md text-on-surface">{{ $row->user->preferredName() }}</td>
                        <td class="p-4 font-body-md text-body-md text-on-surface-variant">{{ $row->mission->title }}</td>
                        <td class="p-4 font-label-code text-label-code capitalize text-secondary">{{ $row->submission?->status }}</td>
                        <td class="p-4 font-label-code text-label-code text-on-surface-variant">{{ optional($row->submitted_at)->format('Y-m-d H:i') }}</td>
                        <td class="p-4 text-right">
                            <a href="{{ route('learnquest.evaluations.show', $row) }}" class="pb-btn-secondary text-xs">Review</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="p-8 text-center text-on-surface-variant">No submissions waiting for review.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $submissions->links() }}</div>
</div>
@endsection
