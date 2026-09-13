@extends('layouts.app')

@section('title', 'Create Assessment')

@section('content')
<div class="max-w-2xl flex flex-col gap-space-lg">
    <header>
        <h1 class="font-headline text-headline-xl text-on-surface">Create assessment</h1>
        <p class="text-on-surface-variant">Draft first — add questions before publishing.</p>
    </header>

    <form method="POST" action="{{ route('teacher.assessments.store') }}" class="pb-card p-space-md flex flex-col gap-space-md">
        @csrf
        <label class="flex flex-col gap-1">
            <span class="font-label-meta text-label-meta uppercase text-on-surface-variant">Title</span>
            <input name="title" value="{{ old('title') }}" required class="rounded-lg bg-surface-container border border-white/10 px-3 py-2 text-on-surface">
        </label>
        <label class="flex flex-col gap-1">
            <span class="font-label-meta text-label-meta uppercase text-on-surface-variant">Description</span>
            <textarea name="description" rows="3" class="rounded-lg bg-surface-container border border-white/10 px-3 py-2 text-on-surface">{{ old('description') }}</textarea>
        </label>
        <div class="grid sm:grid-cols-2 gap-space-md">
            <label class="flex flex-col gap-1">
                <span class="font-label-meta text-label-meta uppercase text-on-surface-variant">Type</span>
                <select name="type" class="rounded-lg bg-surface-container border border-white/10 px-3 py-2 text-on-surface">
                    @foreach(['quiz','assignment','pre_assessment','post_assessment'] as $type)
                        <option value="{{ $type }}" @selected(old('type')===$type)>{{ $type }}</option>
                    @endforeach
                </select>
            </label>
            <label class="flex flex-col gap-1">
                <span class="font-label-meta text-label-meta uppercase text-on-surface-variant">Classroom</span>
                <select name="classroom_id" class="rounded-lg bg-surface-container border border-white/10 px-3 py-2 text-on-surface">
                    <option value="">— Optional —</option>
                    @foreach($classrooms as $c)
                        <option value="{{ $c->id }}" @selected(old('classroom_id')==$c->id)>{{ $c->name }}</option>
                    @endforeach
                </select>
            </label>
        </div>
        <div class="grid sm:grid-cols-3 gap-space-md">
            <label class="flex flex-col gap-1">
                <span class="font-label-meta text-label-meta uppercase text-on-surface-variant">Time limit (min)</span>
                <input type="number" name="time_limit_minutes" value="{{ old('time_limit_minutes') }}" class="rounded-lg bg-surface-container border border-white/10 px-3 py-2 text-on-surface">
            </label>
            <label class="flex flex-col gap-1">
                <span class="font-label-meta text-label-meta uppercase text-on-surface-variant">Pass score %</span>
                <input type="number" name="pass_score" value="{{ old('pass_score', 60) }}" class="rounded-lg bg-surface-container border border-white/10 px-3 py-2 text-on-surface">
            </label>
            <label class="flex flex-col gap-1">
                <span class="font-label-meta text-label-meta uppercase text-on-surface-variant">Max attempts</span>
                <input type="number" name="max_attempts" value="{{ old('max_attempts', 1) }}" class="rounded-lg bg-surface-container border border-white/10 px-3 py-2 text-on-surface">
            </label>
        </div>
        <button class="rounded-lg bg-primary px-4 py-2.5 text-on-primary font-label-md self-start">Create draft</button>
    </form>
</div>
@endsection
