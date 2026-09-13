@extends('layouts.guest')

@section('title', 'ParbaTo')

@section('content')
<header class="fixed top-0 left-0 right-0 w-full z-50 bg-surface-container-lowest/80 backdrop-blur-xl border-b border-white/5">
    <div class="h-16 max-w-7xl mx-auto px-4 sm:px-margin flex items-center justify-between gap-space-md">
        <a href="{{ route('home') }}" class="flex items-center gap-space-sm">
            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary-container/20 text-primary font-headline font-bold">P</span>
            <span class="font-headline text-headline-sm text-on-surface tracking-tight">ParbaTo</span>
        </a>
        <nav class="hidden md:flex items-center gap-space-lg">
            <a href="#engines" class="font-body-md text-body-md text-on-surface-variant hover:text-on-surface transition-colors">Platform</a>
            <a href="#engines" class="font-body-md text-body-md text-on-surface-variant hover:text-on-surface transition-colors">ClassTwin</a>
            <a href="#engines" class="font-body-md text-body-md text-on-surface-variant hover:text-on-surface transition-colors">LearnQuest</a>
            <a href="#engines" class="font-body-md text-body-md text-on-surface-variant hover:text-on-surface transition-colors">FlexLearn</a>
        </nav>
        <div class="flex items-center gap-space-sm">
            @auth
                <a href="{{ route('dashboard') }}" class="pb-btn-primary text-sm">Launch Portal</a>
            @else
                <a href="{{ route('login') }}" class="hidden sm:inline-flex font-body-sm text-body-sm text-on-surface-variant hover:text-on-surface px-3 py-2">Sign in</a>
                <a href="{{ route('register') }}" class="pb-btn-primary text-sm">Launch Portal</a>
            @endauth
        </div>
    </div>
</header>

<main class="w-full pt-16 bg-surface min-h-screen">
    <section class="relative w-full pt-8 pb-20 overflow-hidden">
        <div class="absolute top-12 left-1/4 w-96 h-96 bg-primary-container/15 rounded-full blur-3xl pointer-events-none -z-10"></div>
        <div class="absolute top-40 right-10 w-[30rem] h-[30rem] bg-secondary-container/10 rounded-full blur-3xl pointer-events-none -z-10"></div>
        <div class="max-w-7xl mx-auto px-4 sm:px-margin">
            <div class="inline-flex items-center gap-space-sm px-space-md py-space-xs rounded-full bg-surface-container-high mb-space-lg">
                <span class="relative flex h-2.5 w-2.5">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-secondary opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-secondary"></span>
                </span>
                <span class="font-label-code text-label-code text-secondary tracking-wide uppercase">Blended Learning Platform</span>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-12 gap-space-xl items-center">
                <div class="lg:col-span-7 flex flex-col items-start">
                    <div class="inline-flex items-center gap-space-xs mb-space-sm px-space-sm py-space-xs rounded bg-surface-container text-tertiary font-label-code text-label-code uppercase tracking-wider">
                        <span class="material-symbols-outlined text-tertiary" style="font-size: 15px;">hub</span>
                        Physical-Digital Twin Architecture
                    </div>
                    <h1 class="font-display-hero text-display-hero-mobile md:text-display-hero text-on-surface tracking-tight max-w-2xl text-balance">
                        One Classroom. Many Paths.
                        <span class="bg-clip-text text-transparent bg-gradient-to-r from-primary via-secondary to-primary-fixed">Real-World Learning.</span>
                    </h1>
                    <p class="mt-space-md font-body-lg text-body-lg text-on-surface-variant max-w-xl">
                        ParbaTo connects physical classrooms, mission-based learning, and personalized paths into one intelligent learning environment.
                    </p>
                    <div class="mt-space-xl flex flex-wrap items-center gap-space-md">
                        @auth
                            <a href="{{ route('dashboard') }}" class="pb-btn-primary">
                                <span>Open Dashboard</span>
                                <span class="material-symbols-outlined" style="font-size: 20px;">arrow_forward</span>
                            </a>
                        @else
                            <a href="{{ route('register') }}" class="pb-btn-primary">
                                <span>Explore the Platform</span>
                                <span class="material-symbols-outlined" style="font-size: 20px;">arrow_forward</span>
                            </a>
                        @endauth
                        <a href="#engines" class="pb-btn-secondary">
                            <span class="material-symbols-outlined text-secondary" style="font-variation-settings: 'FILL' 1;">play_circle</span>
                            <span>See How It Works</span>
                        </a>
                    </div>
                </div>

                <div class="lg:col-span-5 relative">
                    <div class="relative w-full rounded-xl bg-surface-container-low shadow-xl p-space-lg overflow-hidden border border-white/5">
                        <div class="absolute inset-0 opacity-15 bg-[radial-gradient(#c0c1ff_1px,transparent_1px)] [background-size:16px_16px]"></div>
                        <div class="relative z-10 flex items-center justify-between pb-space-sm">
                            <div class="flex items-center gap-space-sm">
                                <span class="w-3 h-3 rounded-full bg-secondary animate-pulse"></span>
                                <span class="font-label-code text-label-code text-on-surface">CLASS TWIN PREVIEW</span>
                            </div>
                            <span class="px-space-xs py-0.5 rounded bg-surface-container-highest text-secondary font-label-code text-label-code">LIVE READY</span>
                        </div>
                        <div class="relative z-10 my-space-md p-space-md rounded-lg bg-surface-container-lowest">
                            <div class="text-center py-space-xs mb-space-md rounded bg-surface-container-high font-label-code text-label-code text-on-surface-variant">
                                Physical ↔ Digital Classroom
                            </div>
                            <div class="grid grid-cols-6 gap-2">
                                @foreach(range(1, 18) as $i)
                                    <div class="p-2 rounded bg-surface-container text-center">
                                        <div class="w-2 h-2 mx-auto rounded-full {{ $i % 7 === 0 ? 'bg-tertiary' : 'bg-secondary' }}"></div>
                                        <span class="text-[9px] font-label-code text-on-surface-variant mt-1 block">D{{ str_pad($i, 2, '0', STR_PAD_LEFT) }}</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="relative z-20 -mt-2 p-space-md rounded-xl bg-surface-container-high border border-white/5">
                            <div class="flex items-center justify-between">
                                <span class="font-label-meta text-label-meta uppercase tracking-wider text-tertiary">Active Quest</span>
                                <span class="px-space-xs py-0.5 rounded-full bg-tertiary-container/30 text-tertiary font-label-code text-label-code">+250 XP</span>
                            </div>
                            <h2 class="font-headline text-headline-sm text-on-surface mt-1">Build Campus Event Validator</h2>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section id="engines" class="py-16 border-t border-white/5">
        <div class="max-w-7xl mx-auto px-4 sm:px-margin">
            <h2 class="font-headline text-headline-xl text-on-surface mb-2">Three engines. One platform.</h2>
            <p class="font-body-lg text-body-lg text-on-surface-variant mb-space-xl max-w-2xl">ClassTwin, LearnQuest, and FlexLearn work together so classroom presence becomes learning evidence.</p>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-space-lg">
                <article class="pb-card p-space-lg">
                    <span class="material-symbols-outlined text-secondary text-3xl mb-3">sensors</span>
                    <h3 class="font-headline text-headline-md text-on-surface mb-2">ClassTwin</h3>
                    <p class="font-body-md text-body-md text-on-surface-variant">Live digital twin of the physical classroom — attendance, presence, help requests, and session activity.</p>
                </article>
                <article class="pb-card p-space-lg">
                    <span class="material-symbols-outlined text-tertiary text-3xl mb-3">military_tech</span>
                    <h3 class="font-headline text-headline-md text-on-surface mb-2">LearnQuest</h3>
                    <p class="font-body-md text-body-md text-on-surface-variant">Professional mission-based learning with controlled gamification — discover, build, submit, evaluate.</p>
                </article>
                <article class="pb-card p-space-lg">
                    <span class="material-symbols-outlined text-primary text-3xl mb-3">account_tree</span>
                    <h3 class="font-headline text-headline-md text-on-surface mb-2">FlexLearn</h3>
                    <p class="font-body-md text-body-md text-on-surface-variant">Explainable personalized paths from real learning evidence — every recommendation includes a reason.</p>
                </article>
            </div>
        </div>
    </section>

    <footer class="py-10 border-t border-white/5 text-center">
        <p class="font-label-code text-label-code text-on-surface-variant">ParbaTo · One Classroom. Many Paths. Real-World Learning.</p>
    </footer>
</main>
@endsection
