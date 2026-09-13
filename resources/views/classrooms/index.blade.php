@extends('layouts.app')

@section('title', 'Classrooms')

@section('content')
<div class="flex flex-col gap-space-lg pb-space-xl">
    <header class="flex flex-col sm:flex-row sm:items-end justify-between gap-space-md">
        <div>
            <span class="inline-flex items-center px-space-xs py-0.5 rounded-full bg-secondary/10 text-secondary font-label-code text-label-code mb-2">CLASSTWIN</span>
            <h1 class="font-headline text-headline-xl text-on-surface">Classrooms</h1>
            <p class="font-body-lg text-body-lg text-on-surface-variant mt-1 max-w-2xl">
                Digital twins of physical rooms — create, join, and run live ClassTwin sessions.
            </p>
        </div>
        <div class="flex flex-col sm:flex-row gap-2">
            @can('join', App\Models\Classroom::class)
                <a href="{{ route('classrooms.join') }}" class="pb-btn-secondary min-h-11 px-4 inline-flex items-center justify-center">Join with code</a>
            @endcan
            @can('create', App\Models\Classroom::class)
                <a href="{{ route('classrooms.create') }}" class="pb-btn-primary min-h-11 px-4 inline-flex items-center justify-center">Create classroom</a>
            @endcan
        </div>
    </header>

    @if(session('status'))
        <div class="rounded-xl bg-secondary-container/15 border border-secondary/20 px-space-md py-space-sm text-secondary" role="status">{{ session('status') }}</div>
    @endif

    @if(auth()->user()->hasRole('teacher', 'admin'))
        <section>
            <h2 class="font-headline text-headline-md text-on-surface mb-4">Your classrooms</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-space-md">
                @forelse($owned as $classroom)
                    <a href="{{ route('classrooms.show', $classroom) }}" class="pb-card p-space-lg hover:-translate-y-0.5 transition-transform block">
                        <div class="flex items-center justify-between gap-2 mb-2">
                            <h3 class="font-headline text-headline-sm text-on-surface">{{ $classroom->name }}</h3>
                            @if($classroom->sessions->isNotEmpty())
                                <span class="font-label-code text-label-code text-error uppercase flex items-center gap-1">
                                    <span class="w-1.5 h-1.5 rounded-full bg-error animate-pulse"></span> Live
                                </span>
                            @endif
                        </div>
                        <p class="font-body-sm text-body-sm text-on-surface-variant">{{ $classroom->subject ?: $classroom->course?->title }}</p>
                        <p class="font-label-code text-label-code text-primary mt-3">Join code · {{ $classroom->join_code }}</p>
                        <p class="font-label-code text-label-code text-on-surface-variant mt-1">{{ $classroom->members_count }} students</p>
                    </a>
                @empty
                    <div class="md:col-span-2 xl:col-span-3 rounded-xl border border-dashed border-white/10 p-10 text-center text-on-surface-variant">
                        <p>No classrooms yet. Create your first ClassTwin room.</p>
                        <a href="{{ route('classrooms.create') }}" class="inline-flex mt-4 pb-btn-primary min-h-11 px-5">Create classroom</a>
                    </div>
                @endforelse
            </div>
        </section>
    @endif

    <section>
        <h2 class="font-headline text-headline-md text-on-surface mb-4">{{ auth()->user()->hasRole('teacher', 'admin') ? 'Joined as member' : 'Your classrooms' }}</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-space-md">
            @forelse($joined->reject(fn ($c) => $owned->contains('id', $c->id)) as $classroom)
                <a href="{{ route('classrooms.show', $classroom) }}" class="pb-card p-space-lg hover:-translate-y-0.5 transition-transform block">
                    <h3 class="font-headline text-headline-sm text-on-surface mb-1">{{ $classroom->name }}</h3>
                    <p class="font-body-sm text-body-sm text-on-surface-variant">{{ $classroom->teacher->preferredName() }} · {{ $classroom->subject ?: $classroom->course?->code }}</p>
                    @if($classroom->sessions->isNotEmpty())
                        <p class="font-label-code text-label-code text-secondary mt-3 uppercase">Live session available</p>
                    @endif
                </a>
            @empty
                <div class="md:col-span-2 xl:col-span-3 rounded-xl border border-dashed border-white/10 p-10 text-center text-on-surface-variant">
                    <p>You have not joined a classroom yet.</p>
                    @can('join', App\Models\Classroom::class)
                        <a href="{{ route('classrooms.join') }}" class="inline-flex mt-4 pb-btn-secondary min-h-11 px-5">Join with code</a>
                    @endcan
                </div>
            @endforelse
        </div>
    </section>
</div>
@endsection
