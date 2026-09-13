@extends('layouts.app')

@section('title', 'Edit Assessment')

@section('content')
<div class="flex flex-col gap-space-lg" x-data="{ qType: 'mcq' }">
    <header class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-4">
        <div>
            <span class="font-label-code text-label-code text-primary uppercase">{{ $assessment->status }}</span>
            <h1 class="font-headline text-headline-xl text-on-surface">{{ $assessment->title }}</h1>
            <p class="text-on-surface-variant">{{ $attemptStats['total'] }} attempts · {{ $attemptStats['submitted'] }} pending review · {{ $attemptStats['graded'] }} graded</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('teacher.assessments.attempts', $assessment) }}" class="rounded-lg border border-white/10 px-3 py-2 text-on-surface">Attempts</a>
            @if($assessment->status === 'draft')
                <form method="POST" action="{{ route('teacher.assessments.publish', $assessment) }}">@csrf<button class="rounded-lg bg-primary px-3 py-2 text-on-primary">Publish</button></form>
            @elseif($assessment->status === 'published')
                <form method="POST" action="{{ route('teacher.assessments.unpublish', $assessment) }}">@csrf<button class="rounded-lg border border-white/10 px-3 py-2">Unpublish</button></form>
            @endif
            <form method="POST" action="{{ route('teacher.assessments.archive', $assessment) }}" onsubmit="return confirm('Archive this assessment?')">@csrf<button class="rounded-lg border border-error/40 px-3 py-2 text-error">Archive</button></form>
        </div>
    </header>

    <form method="POST" action="{{ route('teacher.assessments.update', $assessment) }}" class="pb-card p-space-md grid gap-space-md sm:grid-cols-2">
        @csrf @method('PUT')
        <label class="flex flex-col gap-1 sm:col-span-2">
            <span class="font-label-meta text-label-meta uppercase text-on-surface-variant">Title</span>
            <input name="title" value="{{ old('title', $assessment->title) }}" class="rounded-lg bg-surface-container border border-white/10 px-3 py-2 text-on-surface">
        </label>
        <label class="flex flex-col gap-1 sm:col-span-2">
            <span class="font-label-meta text-label-meta uppercase text-on-surface-variant">Description</span>
            <textarea name="description" rows="2" class="rounded-lg bg-surface-container border border-white/10 px-3 py-2 text-on-surface">{{ old('description', $assessment->description) }}</textarea>
        </label>
        <label class="flex flex-col gap-1">
            <span class="font-label-meta text-label-meta uppercase text-on-surface-variant">Type</span>
            <select name="type" class="rounded-lg bg-surface-container border border-white/10 px-3 py-2 text-on-surface">
                @foreach(['quiz','assignment','pre_assessment','post_assessment','practical','project'] as $type)
                    <option value="{{ $type }}" @selected(old('type', $assessment->type)===$type)>{{ $type }}</option>
                @endforeach
            </select>
        </label>
        <label class="flex flex-col gap-1">
            <span class="font-label-meta text-label-meta uppercase text-on-surface-variant">Classroom</span>
            <select name="classroom_id" class="rounded-lg bg-surface-container border border-white/10 px-3 py-2 text-on-surface">
                <option value="">—</option>
                @foreach($classrooms as $c)
                    <option value="{{ $c->id }}" @selected(old('classroom_id', $assessment->classroom_id)==$c->id)>{{ $c->name }}</option>
                @endforeach
            </select>
        </label>
        <label class="flex flex-col gap-1">
            <span class="font-label-meta text-label-meta uppercase text-on-surface-variant">Time limit</span>
            <input type="number" name="time_limit_minutes" value="{{ old('time_limit_minutes', $assessment->time_limit_minutes) }}" class="rounded-lg bg-surface-container border border-white/10 px-3 py-2 text-on-surface">
        </label>
        <label class="flex flex-col gap-1">
            <span class="font-label-meta text-label-meta uppercase text-on-surface-variant">Pass / Max attempts</span>
            <div class="flex gap-2">
                <input type="number" name="pass_score" value="{{ old('pass_score', $assessment->pass_score) }}" class="w-1/2 rounded-lg bg-surface-container border border-white/10 px-3 py-2 text-on-surface" placeholder="Pass %">
                <input type="number" name="max_attempts" value="{{ old('max_attempts', $assessment->max_attempts) }}" class="w-1/2 rounded-lg bg-surface-container border border-white/10 px-3 py-2 text-on-surface" placeholder="Attempts">
            </div>
        </label>
        <div class="sm:col-span-2"><button class="rounded-lg bg-surface-container-highest px-4 py-2 text-on-surface">Save settings</button></div>
    </form>

    <section class="flex flex-col gap-space-md">
        <h2 class="font-headline text-headline-md text-on-surface">Questions</h2>
        @forelse($assessment->questions as $question)
            <div class="pb-card p-space-md flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                <div>
                    <p class="font-label-code text-label-code text-primary uppercase">Q{{ $question->position }} · {{ $question->type }} · {{ $question->points }} pts @if($question->skill)· {{ $question->skill->name }}@endif</p>
                    <p class="font-body-md text-on-surface mt-1">{{ $question->prompt }}</p>
                    @if($question->options)
                        <ul class="mt-2 text-on-surface-variant font-body-sm list-disc pl-5">
                            @foreach($question->options as $opt)
                                <li @class(['text-secondary' => in_array($opt, $question->correct_answer ?? [], true)])>{{ $opt }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>
                <form method="POST" action="{{ route('teacher.assessments.questions.destroy', [$assessment, $question]) }}">@csrf @method('DELETE')
                    <button class="text-error text-sm">Remove</button>
                </form>
            </div>
        @empty
            <div class="pb-card p-8 text-center text-on-surface-variant">No questions yet.</div>
        @endforelse
    </section>

    <form method="POST" action="{{ route('teacher.assessments.questions.store', $assessment) }}" class="pb-card p-space-md flex flex-col gap-space-md">
        @csrf
        <h3 class="font-headline text-headline-sm">Add question</h3>
        <label class="flex flex-col gap-1">
            <span class="font-label-meta text-label-meta uppercase text-on-surface-variant">Type</span>
            <select name="type" x-model="qType" class="rounded-lg bg-surface-container border border-white/10 px-3 py-2 text-on-surface">
                <option value="mcq">MCQ</option>
                <option value="true_false">True / False</option>
                <option value="short_answer">Short answer</option>
            </select>
        </label>
        <label class="flex flex-col gap-1">
            <span class="font-label-meta text-label-meta uppercase text-on-surface-variant">Prompt</span>
            <textarea name="prompt" required rows="2" class="rounded-lg bg-surface-container border border-white/10 px-3 py-2 text-on-surface"></textarea>
        </label>
        <div class="grid sm:grid-cols-2 gap-space-md">
            <label class="flex flex-col gap-1">
                <span class="font-label-meta text-label-meta uppercase text-on-surface-variant">Skill mapping</span>
                <select name="skill_id" class="rounded-lg bg-surface-container border border-white/10 px-3 py-2 text-on-surface">
                    <option value="">— Optional —</option>
                    @foreach($skills as $skill)
                        <option value="{{ $skill->id }}">{{ $skill->name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="flex flex-col gap-1">
                <span class="font-label-meta text-label-meta uppercase text-on-surface-variant">Points</span>
                <input type="number" name="points" value="1" min="1" class="rounded-lg bg-surface-container border border-white/10 px-3 py-2 text-on-surface">
            </label>
        </div>
        <div x-show="qType === 'mcq'" class="grid gap-2">
            @foreach(range(0,3) as $i)
                <input name="options[]" placeholder="Option {{ $i+1 }}" class="rounded-lg bg-surface-container border border-white/10 px-3 py-2 text-on-surface">
            @endforeach
            <input name="correct_option" placeholder="Exact correct option text" class="rounded-lg bg-surface-container border border-secondary/30 px-3 py-2 text-on-surface">
        </div>
        <div x-show="qType === 'true_false'">
            <select name="correct_true_false" class="rounded-lg bg-surface-container border border-white/10 px-3 py-2 text-on-surface">
                <option value="true">True</option>
                <option value="false">False</option>
            </select>
        </div>
        <button class="rounded-lg bg-primary px-4 py-2.5 text-on-primary self-start">Add question</button>
    </form>
</div>
@endsection
