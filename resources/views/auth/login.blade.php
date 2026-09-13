@extends('layouts.guest')

@section('title', 'Sign in')

@section('content')
<div class="min-h-screen flex items-center justify-center px-4 py-12 relative overflow-hidden">
    <div class="absolute top-20 left-1/4 w-72 h-72 bg-primary-container/10 rounded-full blur-3xl"></div>
    <div class="w-full max-w-md pb-card p-space-lg relative z-10">
        <div class="mb-space-lg">
            <a href="{{ route('home') }}" class="inline-flex items-center gap-2 mb-4">
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary-container/20 text-primary font-headline font-bold">P</span>
                <span class="font-headline text-headline-sm">ParbaTo</span>
            </a>
            <h1 class="font-headline text-headline-lg text-on-surface">Welcome back</h1>
            <p class="font-body-md text-body-md text-on-surface-variant mt-1">Sign in to ClassTwin, LearnQuest, and FlexLearn.</p>
        </div>

        @if ($errors->any())
            <div class="mb-4 rounded-lg border border-error/30 bg-error-container/20 px-3 py-2 text-body-sm text-error">
                {{ $errors->first() }}
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="space-y-4">
            @csrf
            <div>
                <label class="font-label-meta text-label-meta uppercase text-on-surface-variant tracking-wider" for="email">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus class="pb-input mt-1">
            </div>
            <div>
                <label class="font-label-meta text-label-meta uppercase text-on-surface-variant tracking-wider" for="password">Password</label>
                <input id="password" name="password" type="password" required class="pb-input mt-1">
            </div>
            <label class="flex items-center gap-2 text-body-sm text-on-surface-variant">
                <input type="checkbox" name="remember" class="rounded border-white/20 bg-surface-container">
                Remember me
            </label>
            <button type="submit" class="pb-btn-primary w-full">Sign in</button>
        </form>

        <p class="mt-6 text-body-sm text-on-surface-variant text-center">
            New to ParbaTo?
            <a href="{{ route('register') }}" class="text-primary hover:underline">Create an account</a>
        </p>
        <p class="mt-3 text-center font-label-code text-label-code text-outline">Demo: student@parbato.test / password</p>
    </div>
</div>
@endsection
