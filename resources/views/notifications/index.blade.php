@extends('layouts.app')

@section('title', 'Notifications')

@section('content')
<div class="flex flex-col gap-space-lg max-w-2xl">
    <header class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
        <div>
            <h1 class="font-headline text-headline-xl text-on-surface">Notifications</h1>
            <p class="text-on-surface-variant">In-app only — no email or push.</p>
        </div>
        <form method="POST" action="{{ route('notifications.read-all') }}">@csrf
            <button class="rounded-lg border border-white/10 px-3 py-2 text-sm">Mark all read</button>
        </form>
    </header>

    <div class="flex gap-2">
        <a href="{{ route('notifications.index', ['filter' => 'all']) }}" @class(['px-3 py-1.5 rounded-lg text-sm', 'bg-primary text-on-primary' => $filter==='all', 'bg-surface-container' => $filter!=='all'])>All</a>
        <a href="{{ route('notifications.index', ['filter' => 'unread']) }}" @class(['px-3 py-1.5 rounded-lg text-sm', 'bg-primary text-on-primary' => $filter==='unread', 'bg-surface-container' => $filter!=='unread'])>Unread</a>
    </div>

    @forelse($notifications as $notification)
        <form method="POST" action="{{ route('notifications.read', $notification->id) }}" class="pb-card p-space-md {{ $notification->read_at ? 'opacity-70' : '' }}">
            @csrf
            <button type="submit" class="w-full text-left">
                <div class="flex justify-between gap-3">
                    <div>
                        <p class="font-headline text-headline-sm text-on-surface">{{ $notification->data['title'] ?? 'Notification' }}</p>
                        <p class="text-sm text-on-surface-variant mt-1">{{ $notification->data['body'] ?? '' }}</p>
                    </div>
                    @unless($notification->read_at)
                        <span class="w-2 h-2 rounded-full bg-primary shrink-0 mt-2"></span>
                    @endunless
                </div>
                <p class="text-xs text-on-surface-variant mt-2">{{ $notification->created_at->diffForHumans() }}</p>
            </button>
        </form>
    @empty
        <div class="pb-card p-10 text-center text-on-surface-variant">
            {{ $filter === 'unread' ? 'No unread notifications' : 'No notifications yet' }}
        </div>
    @endforelse

    {{ $notifications->withQueryString()->links() }}
</div>
@endsection
