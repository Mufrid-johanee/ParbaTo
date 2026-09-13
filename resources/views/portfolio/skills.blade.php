@extends('layouts.app')

@section('title', 'Skill profile')

@section('content')
<div class="flex flex-col gap-space-lg max-w-3xl">
    <header>
        <h1 class="font-headline text-headline-xl text-on-surface">Skill profile</h1>
        <p class="text-on-surface-variant">Bands from FlexLearn mastery — Needs Support, Developing, Proficient, Advanced.</p>
    </header>
    @forelse($mastery['skills'] as $row)
        <div class="pb-card p-space-md">
            <div class="flex justify-between mb-2">
                <h2 class="font-headline text-headline-sm">{{ $row->skill->name }}</h2>
                <span class="font-label-code text-label-code">{{ $row->mastery }}%</span>
            </div>
            <div class="h-2 rounded-full bg-surface-container"><div class="h-full rounded-full bg-secondary" style="width:{{ $row->mastery }}%"></div></div>
            <p class="text-sm text-on-surface-variant mt-2">{{ \App\Services\MasteryService::bandLabel((int)$row->mastery) }} · {{ $row->evidence_count }} evidence</p>
        </div>
    @empty
        <div class="pb-card p-10 text-center text-on-surface-variant">No skills tracked yet.</div>
    @endforelse
</div>
@endsection
