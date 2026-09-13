@extends('layouts.base')

@section('title', 'Not Found')

@section('body')
<main id="main-content" class="min-h-screen flex items-center justify-center px-4 py-16">
    <div class="max-w-md text-center space-y-4">
        <p class="font-display text-6xl font-extrabold text-primary">404</p>
        <h1 class="font-display text-2xl font-bold text-on-surface">Page not found</h1>
        <p class="text-on-surface-variant">The page you requested does not exist or was moved.</p>
        <a href="{{ url('/') }}" class="inline-flex min-h-11 items-center justify-center rounded-lg bg-primary px-5 py-2.5 text-sm font-semibold text-on-primary hover:opacity-90 focus:outline-none focus-visible:ring-2 focus-visible:ring-primary">Back home</a>
    </div>
</main>
@endsection
