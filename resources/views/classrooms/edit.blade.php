@extends('layouts.app')

@section('title', 'Edit '.$classroom->name)

@section('content')
<div class="max-w-xl mx-auto flex flex-col gap-space-lg pb-space-xl">
    <header>
        <a href="{{ route('classrooms.show', $classroom) }}" class="font-label-code text-label-code text-primary hover:underline">← {{ $classroom->name }}</a>
        <h1 class="font-headline text-headline-xl text-on-surface mt-2">Edit classroom</h1>
    </header>

    <form method="POST" action="{{ route('classrooms.update', $classroom) }}" class="pb-card p-space-lg space-y-4">
        @csrf
        @method('PUT')
        <div>
            <label for="name" class="font-label-meta text-label-meta uppercase text-on-surface-variant">Name</label>
            <input id="name" name="name" value="{{ old('name', $classroom->name) }}" class="pb-input mt-1" required maxlength="120">
            @error('name')<p class="text-error text-sm mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label for="subject" class="font-label-meta text-label-meta uppercase text-on-surface-variant">Subject</label>
            <input id="subject" name="subject" value="{{ old('subject', $classroom->subject) }}" class="pb-input mt-1" maxlength="120">
        </div>
        <div>
            <label for="description" class="font-label-meta text-label-meta uppercase text-on-surface-variant">Description</label>
            <textarea id="description" name="description" rows="3" class="pb-input mt-1" maxlength="2000">{{ old('description', $classroom->description) }}</textarea>
        </div>
        <div>
            <label for="room_label" class="font-label-meta text-label-meta uppercase text-on-surface-variant">Room label</label>
            <input id="room_label" name="room_label" value="{{ old('room_label', $classroom->room_label) }}" class="pb-input mt-1" maxlength="80">
        </div>
        <div>
            <label for="capacity" class="font-label-meta text-label-meta uppercase text-on-surface-variant">Capacity</label>
            <input id="capacity" type="number" name="capacity" value="{{ old('capacity', $classroom->capacity) }}" class="pb-input mt-1" min="1" max="500">
        </div>
        <p class="font-body-sm text-body-sm text-on-surface-variant">Join code stays <span class="font-label-code text-primary">{{ $classroom->join_code }}</span> (not changed on edit).</p>
        <button type="submit" class="pb-btn-primary w-full min-h-11">Save changes</button>
    </form>
</div>
@endsection
