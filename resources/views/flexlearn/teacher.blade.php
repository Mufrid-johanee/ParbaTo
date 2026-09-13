@extends('layouts.app')

@section('title', 'FlexLearn · Students')

@section('content')
<div class="flex flex-col gap-space-lg pb-space-xl">
    <header>
        <span class="inline-flex items-center px-space-xs py-0.5 rounded-full bg-primary/10 text-primary font-label-code text-label-code mb-2">TEACHER · FLEXLEARN</span>
        <h1 class="font-headline text-headline-xl text-on-surface">Student skill mastery</h1>
        <p class="font-body-lg text-body-lg text-on-surface-variant max-w-2xl mt-1">
            Basic visibility into mastery, strengths, support needs, and active recommendations. Full analytics remains separate.
        </p>
    </header>

    <div class="overflow-x-auto rounded-xl border border-white/5">
        <table class="w-full min-w-[640px] text-left">
            <thead class="bg-surface-container-low">
                <tr class="font-label-meta text-label-meta uppercase tracking-wider text-on-surface-variant">
                    <th class="px-space-md py-3">Student</th>
                    <th class="px-space-md py-3">Avg mastery</th>
                    <th class="px-space-md py-3">Needs support</th>
                    <th class="px-space-md py-3">Strengths</th>
                    <th class="px-space-md py-3">Active recs</th>
                    <th class="px-space-md py-3"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($students as $row)
                    <tr class="border-t border-white/5 hover:bg-surface-container/40">
                        <td class="px-space-md py-3 font-body-md text-on-surface">{{ $row['user']->name }}</td>
                        <td class="px-space-md py-3 font-label-code text-label-code text-primary">
                            {{ $row['avg_mastery'] !== null ? $row['avg_mastery'].'%' : '—' }}
                        </td>
                        <td class="px-space-md py-3 font-body-sm text-body-sm text-error">
                            {{ $row['needs']->pluck('skill.name')->take(3)->join(', ') ?: '—' }}
                        </td>
                        <td class="px-space-md py-3 font-body-sm text-body-sm text-secondary">
                            {{ $row['strengths']->pluck('skill.name')->take(3)->join(', ') ?: '—' }}
                        </td>
                        <td class="px-space-md py-3 font-label-code text-label-code">{{ $row['active_recs'] }}</td>
                        <td class="px-space-md py-3 text-right">
                            <a href="{{ route('flexlearn.teacher.show', $row['user']) }}" class="pb-btn-secondary text-xs min-h-10 px-3 inline-flex items-center">View</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
