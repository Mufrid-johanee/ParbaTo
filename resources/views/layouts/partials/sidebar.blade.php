<aside
    class="fixed left-0 top-0 h-full w-64 bg-surface-container-lowest z-50 flex flex-col border-r border-white/5
           transform transition-transform duration-200 ease-out
           -translate-x-full lg:translate-x-0"
    :class="{ 'translate-x-0': sidebarOpen }"
>
    <div class="h-16 px-space-md flex items-center justify-between">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-space-sm">
            <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary-container/20 text-primary font-headline font-bold text-sm">P</span>
            <span class="font-headline text-headline-sm text-on-surface tracking-tight">ParbaTo</span>
        </a>
        <span class="px-space-xs py-0.5 rounded font-label-code text-label-code bg-surface-container text-secondary">TWIN-OS</span>
    </div>

    <div class="px-space-md py-space-sm">
        <div class="flex items-center justify-between px-space-sm py-space-xs rounded-lg bg-surface-container-low">
            <div class="flex items-center gap-space-xs">
                <span class="w-2 h-2 rounded-full bg-secondary animate-pulse"></span>
                <span class="font-label-meta text-label-meta text-on-surface-variant uppercase tracking-wider">Sync</span>
            </div>
            <span class="font-label-code text-label-code text-secondary">READY</span>
        </div>
    </div>

    <nav class="flex-1 overflow-y-auto px-space-sm py-space-xs flex flex-col gap-space-xs">
        <span class="px-space-sm pt-space-xs pb-1 font-label-meta text-label-meta text-on-surface-variant/70 uppercase tracking-wider">Overview</span>
        <a href="{{ route('home') }}" class="pb-nav-link {{ request()->routeIs('home') ? 'pb-nav-link-active' : '' }}">
            <span class="material-symbols-outlined text-lg">public</span><span>Landing</span>
        </a>
        <a href="{{ route('dashboard') }}" class="pb-nav-link {{ request()->routeIs('dashboard') ? 'pb-nav-link-active' : '' }}">
            <span class="material-symbols-outlined text-lg">dashboard</span><span>Dashboard</span>
        </a>

        <span class="px-space-sm pt-space-md pb-1 font-label-meta text-label-meta text-on-surface-variant/70 uppercase tracking-wider">Engines</span>
        <a href="{{ route('learnquest.index') }}" class="pb-nav-link {{ request()->routeIs('learnquest.*') ? 'pb-nav-link-active' : '' }}">
            <span class="material-symbols-outlined text-lg">explore</span><span>LearnQuest</span>
        </a>
        <a href="{{ route('flexlearn.index') }}" class="pb-nav-link {{ request()->routeIs('flexlearn.*') ? 'pb-nav-link-active' : '' }}">
            <span class="material-symbols-outlined text-lg">account_tree</span><span>FlexLearn</span>
        </a>

        @if(auth()->user()?->hasRole('teacher', 'admin'))
            <span class="px-space-sm pt-space-md pb-1 font-label-meta text-label-meta text-on-surface-variant/70 uppercase tracking-wider">Faculty</span>
            <a href="{{ route('analytics.teacher') }}" class="pb-nav-link {{ request()->routeIs('analytics.*') ? 'pb-nav-link-active' : '' }}">
                <span class="material-symbols-outlined text-lg">query_stats</span><span>Analytics</span>
            </a>
            <a href="{{ route('flexlearn.teacher.index') }}" class="pb-nav-link {{ request()->routeIs('flexlearn.teacher.*') ? 'pb-nav-link-active' : '' }}">
                <span class="material-symbols-outlined text-lg">psychology</span><span>Student mastery</span>
            </a>
            <a href="{{ route('learnquest.evaluations.index') }}" class="pb-nav-link {{ request()->routeIs('learnquest.evaluations.*') ? 'pb-nav-link-active' : '' }}">
                <span class="material-symbols-outlined text-lg">grading</span><span>Evaluations</span>
            </a>
        @endif
    </nav>

    <div class="p-space-sm">
        <div class="p-space-sm rounded-xl bg-surface-container-low flex flex-col gap-space-xs">
            <div class="flex items-center justify-between">
                <span class="font-label-meta text-label-meta uppercase tracking-wider text-on-surface-variant">XP</span>
                <span class="font-label-code text-label-code text-primary">{{ auth()->user()->xp ?? 0 }} XP</span>
            </div>
            <div class="w-full h-1.5 rounded-full bg-surface-container">
                <div class="h-full rounded-full bg-primary-container" style="width: {{ min(100, ((auth()->user()->xp ?? 0) % 500) / 5) }}%"></div>
            </div>
            <span class="font-label-code text-label-code text-on-surface-variant">Level {{ auth()->user()->level ?? 1 }}</span>
        </div>
    </div>
</aside>
