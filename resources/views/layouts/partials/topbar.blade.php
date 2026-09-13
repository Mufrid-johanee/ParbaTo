<header class="fixed top-0 left-0 right-0 lg:left-64 h-16 bg-surface-container-lowest/80 backdrop-blur-xl z-40 flex items-center justify-between px-4 sm:px-space-lg border-b border-white/5">
    <div class="flex items-center gap-3 flex-1 max-w-md">
        <button type="button" class="lg:hidden p-2 rounded-lg text-on-surface-variant hover:bg-surface-container-high" @click="sidebarOpen = true" aria-label="Open menu">
            <span class="material-symbols-outlined">menu</span>
        </button>
        <div class="relative w-full hidden sm:flex items-center">
            <span class="material-symbols-outlined absolute left-3 text-on-surface-variant text-lg pointer-events-none">search</span>
            <input class="w-full h-9 pl-9 pr-3 rounded-lg bg-surface-container text-on-surface placeholder:text-on-surface-variant font-body-sm text-body-sm focus:outline-none focus:ring-1 focus:ring-primary" placeholder="Search curriculum, missions..." type="search" disabled>
        </div>
    </div>
    <div class="flex items-center gap-space-md">
        <div class="hidden xl:flex items-center gap-space-xs px-space-sm py-space-xs rounded-full bg-surface-container-low">
            <span class="material-symbols-outlined text-sm text-primary">school</span>
            <span class="font-label-code text-label-code text-on-surface-variant capitalize">{{ auth()->user()->role }}</span>
        </div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="p-2 rounded-lg text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high transition-colors" title="Log out">
                <span class="material-symbols-outlined text-xl">logout</span>
            </button>
        </form>
        <div class="flex h-8 w-8 items-center justify-center rounded-full bg-primary-container/30 text-primary font-label-code text-label-code">
            {{ strtoupper(substr(auth()->user()->preferredName(), 0, 1)) }}
        </div>
    </div>
</header>
