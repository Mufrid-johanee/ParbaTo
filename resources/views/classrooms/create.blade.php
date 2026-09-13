@extends('layouts.app')

@section('title', 'Create Classroom')

@section('content')
<div class="max-w-xl mx-auto flex flex-col gap-space-lg pb-space-xl">
    <header>
        <a href="{{ route('classrooms.index') }}" class="font-label-code text-label-code text-primary hover:underline">← Classrooms</a>
        <h1 class="font-headline text-headline-xl text-on-surface mt-2">Create classroom</h1>
        <p class="font-body-md text-body-md text-on-surface-variant mt-1">Students will join with a secure classroom code — not a sequential ID.</p>
    </header>

    <form method="POST" action="{{ route('classrooms.store') }}" class="pb-card p-space-lg space-y-4">
        @csrf
        <div>
            <label for="name" class="font-label-meta text-label-meta uppercase text-on-surface-variant">Name</label>
            <input id="name" name="name" value="{{ old('name') }}" class="pb-input mt-1" required maxlength="120" placeholder="CS-201 Lab Twin">
            @error('name')<p class="text-error text-sm mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="subject" class="font-label-meta text-label-meta uppercase text-on-surface-variant">Subject</label>
            <input id="subject" name="subject" value="{{ old('subject') }}" class="pb-input mt-1" maxlength="120" placeholder="Web Programming">
        </div>
        <div>
            <label for="description" class="font-label-meta text-label-meta uppercase text-on-surface-variant">Description</label>
            <textarea id="description" name="description" rows="3" class="pb-input mt-1" maxlength="2000">{{ old('description') }}</textarea>
        </div>
        <div>
            <label for="room_label" class="font-label-meta text-label-meta uppercase text-on-surface-variant">Room label</label>
            <input id="room_label" name="room_label" value="{{ old('room_label') }}" class="pb-input mt-1" maxlength="80" placeholder="Turing Hall 302">
        </div>
        @if($courses->isNotEmpty())
            <div>
                <label for="course_id" class="font-label-meta text-label-meta uppercase text-on-surface-variant">Link course (optional)</label>
                <select id="course_id" name="course_id" class="pb-input mt-1">
                    <option value="">Create linked course automatically</option>
                    @foreach($courses as $course)
                        <option value="{{ $course->id }}" @selected(old('course_id') == $course->id)>{{ $course->code }} — {{ $course->title }}</option>
                    @endforeach
                </select>
            </div>
        @endif
        <button type="submit" class="pb-btn-primary w-full min-h-11">Create classroom</button>
    </form>
</div>
@endsection
