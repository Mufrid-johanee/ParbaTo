<header class="fixed top-0 left-0 right-0 lg:left-64 h-16 bg-surface-container-lowest/80 backdrop-blur-xl z-40 flex items-center justify-between px-4 sm:px-space-lg border-b border-white/5"
    x-data="{ notifOpen: false, unread: {{ auth()->user()->unreadNotifications()->count() }} }">
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

        <div class="relative">
            <button type="button" class="relative p-2 rounded-lg text-on-surface-variant hover:text-on-surface hover:bg-surface-container-high" @click="notifOpen = !notifOpen" aria-label="Notifications">
                <span class="material-symbols-outlined text-xl">notifications</span>
                <span x-show="unread > 0" x-text="unread > 9 ? '9+' : unread" class="absolute -top-0.5 -right-0.5 min-w-[18px] h-[18px] px-1 rounded-full bg-primary text-on-primary text-[10px] flex items-center justify-center font-label-code"></span>
            </button>
            <div
                x-show="notifOpen"
                @click.outside="notifOpen = false"
                x-transition
                class="absolute right-0 mt-2 w-80 max-w-[90vw] rounded-xl border border-white/10 bg-surface-container-lowest shadow-xl z-50"
                style="display:none"
            >
                <div class="flex items-center justify-between px-3 py-2 border-b border-white/5">
                    <span class="font-headline text-sm text-on-surface">Notifications</span>
                    <a href="{{ route('notifications.index') }}" class="text-xs text-primary">View all</a>
                </div>
                <div class="max-h-80 overflow-y-auto">
                    @forelse(auth()->user()->notifications()->latest()->limit(10)->get() as $n)
                        <form method="POST" action="{{ route('notifications.read', $n->id) }}">
                            @csrf
                            <button type="submit" class="w-full text-left px-3 py-2.5 hover:bg-surface-container-high border-b border-white/5 {{ $n->read_at ? 'opacity-60' : '' }}">
                                <div class="flex gap-2">
                                    @unless($n->read_at)<span class="w-1.5 h-1.5 rounded-full bg-primary mt-1.5 shrink-0"></span>@else<span class="w-1.5 shrink-0"></span>@endunless
                                    <div>
                                        <p class="text-sm text-on-surface">{{ $n->data['title'] ?? 'Update' }}</p>
                                        <p class="text-xs text-on-surface-variant line-clamp-2">{{ $n->data['body'] ?? '' }}</p>
                                    </div>
                                </div>
                            </button>
                        </form>
                    @empty
                        <p class="p-4 text-sm text-on-surface-variant text-center">No notifications</p>
                    @endforelse
                </div>
            </div>
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
