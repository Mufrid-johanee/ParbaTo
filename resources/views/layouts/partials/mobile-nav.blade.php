<nav class="fixed bottom-0 inset-x-0 z-40 lg:hidden bg-surface-container-lowest/95 backdrop-blur-xl border-t border-white/5 px-2 py-2 safe-area-pb">
    <div class="grid grid-cols-4 gap-1">
        <a href="{{ route('dashboard') }}" class="flex flex-col items-center gap-0.5 py-2 rounded-lg {{ request()->routeIs('dashboard') ? 'text-primary' : 'text-on-surface-variant' }}">
            <span class="material-symbols-outlined text-xl">dashboard</span>
            <span class="font-label-meta text-[10px] uppercase">Home</span>
        </a>
        <a href="{{ route('classrooms.index') }}" class="flex flex-col items-center gap-0.5 py-2 rounded-lg {{ request()->routeIs('classrooms.*', 'classtwin.*') ? 'text-primary' : 'text-on-surface-variant' }}">
            <span class="material-symbols-outlined text-xl">sensors</span>
            <span class="font-label-meta text-[10px] uppercase">Twin</span>
        </a>
        <a href="{{ route('learnquest.index') }}" class="flex flex-col items-center gap-0.5 py-2 rounded-lg {{ request()->routeIs('learnquest.*') ? 'text-primary' : 'text-on-surface-variant' }}">
            <span class="material-symbols-outlined text-xl">explore</span>
            <span class="font-label-meta text-[10px] uppercase">Quests</span>
        </a>
        <a href="{{ route('flexlearn.index') }}" class="flex flex-col items-center gap-0.5 py-2 rounded-lg {{ request()->routeIs('flexlearn.*') ? 'text-primary' : 'text-on-surface-variant' }}">
            <span class="material-symbols-outlined text-xl">account_tree</span>
            <span class="font-label-meta text-[10px] uppercase">Path</span>
        </a>
    </div>
</nav>
