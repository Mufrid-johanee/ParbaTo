@extends('layouts.app')

@section('title', 'Join Classroom')

@section('content')
<div class="max-w-md mx-auto flex flex-col gap-space-lg pb-space-xl">
    <header>
        <a href="{{ route('classrooms.index') }}" class="font-label-code text-label-code text-primary hover:underline">← Classrooms</a>
        <h1 class="font-headline text-headline-xl text-on-surface mt-2">Join classroom</h1>
        <p class="font-body-md text-body-md text-on-surface-variant mt-1">Enter the classroom join code from your teacher.</p>
    </header>

    <form method="POST" action="{{ route('classrooms.join.store') }}" class="pb-card p-space-lg space-y-4">
        @csrf
        <div>
            <label for="code" class="font-label-meta text-label-meta uppercase text-on-surface-variant">Join code</label>
            <input id="code" name="code" value="{{ old('code') }}" class="pb-input mt-1 uppercase tracking-widest" required maxlength="16" autocomplete="off" placeholder="ABCD1234">
            @error('code')<p class="text-error text-sm mt-1">{{ $message }}</p>@enderror
        </div>
        <button type="submit" class="pb-btn-primary w-full min-h-11">Join classroom</button>
    </form>
</div>
@endsection
