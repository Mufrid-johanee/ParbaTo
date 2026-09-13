@extends('layouts.guest')

@section('title', 'Register')

@section('content')
<div class="min-h-screen flex items-center justify-center px-4 py-12 relative overflow-hidden">
    <div class="absolute bottom-10 right-1/4 w-72 h-72 bg-secondary-container/10 rounded-full blur-3xl"></div>
    <div class="w-full max-w-md pb-card p-space-lg relative z-10">
        <div class="mb-space-lg">
            <a href="{{ route('home') }}" class="inline-flex items-center gap-2 mb-4">
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary-container/20 text-primary font-headline font-bold">P</span>
                <span class="font-headline text-headline-sm">ParbaTo</span>
            </a>
            <h1 class="font-headline text-headline-lg text-on-surface">Join ParbaTo</h1>
            <p class="font-body-md text-body-md text-on-surface-variant mt-1">Create your learning identity.</p>
        </div>

        @if ($errors->any())
            <div class="mb-4 rounded-lg border border-error/30 bg-error-container/20 px-3 py-2 text-body-sm text-error">
                <ul class="list-disc pl-4 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('register') }}" class="space-y-4">
            @csrf
            <div>
                <label class="font-label-meta text-label-meta uppercase text-on-surface-variant tracking-wider" for="name">Full name</label>
                <input id="name" name="name" type="text" value="{{ old('name') }}" required class="pb-input mt-1">
            </div>
            <div>
                <label class="font-label-meta text-label-meta uppercase text-on-surface-variant tracking-wider" for="email">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required class="pb-input mt-1">
            </div>
            <div>
                <label class="font-label-meta text-label-meta uppercase text-on-surface-variant tracking-wider" for="role">Role</label>
                <select id="role" name="role" class="pb-input mt-1" required>
                    <option value="student" @selected(old('role', 'student') === 'student')>Student</option>
                    <option value="teacher" @selected(old('role') === 'teacher')>Teacher</option>
                </select>
            </div>
            <div>
                <label class="font-label-meta text-label-meta uppercase text-on-surface-variant tracking-wider" for="password">Password</label>
                <input id="password" name="password" type="password" required class="pb-input mt-1">
            </div>
            <div>
                <label class="font-label-meta text-label-meta uppercase text-on-surface-variant tracking-wider" for="password_confirmation">Confirm password</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required class="pb-input mt-1">
            </div>
            <button type="submit" class="pb-btn-primary w-full">Create account</button>
        </form>

        <p class="mt-6 text-body-sm text-on-surface-variant text-center">
            Already have an account?
            <a href="{{ route('login') }}" class="text-primary hover:underline">Sign in</a>
        </p>
    </div>
</div>
@endsection
