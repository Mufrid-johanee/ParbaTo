@extends('layouts.app')

@section('title', 'ClassTwin Live')

@section('content')
<div class="flex flex-col w-full gap-space-lg pb-8">
    <div class="w-full bg-surface-container-low rounded-xl p-space-md shadow-xl flex flex-col xl:flex-row xl:items-center justify-between gap-space-md border border-white/5">
        <div class="flex flex-col gap-space-xs">
            <div class="flex items-center gap-space-sm flex-wrap">
                <span class="font-headline text-headline-md text-on-surface">{{ $session->title ?? $session->classroom->name }}</span>
                @if($session->classroom->course)
                    <span class="px-space-xs py-0.5 rounded font-label-code text-label-code bg-surface-container-highest text-primary">{{ $session->classroom->course->code }}</span>
                @endif
                <div class="flex items-center gap-1.5 px-space-sm py-0.5 rounded-full bg-error-container/40">
                    <span class="w-2 h-2 rounded-full bg-error animate-ping"></span>
                    <span class="font-label-meta text-label-meta uppercase tracking-wider text-error">{{ ucfirst($session->status) }} Session</span>
                </div>
            </div>
            <div class="flex items-center gap-space-md text-on-surface-variant font-body-sm text-body-sm flex-wrap">
                <span class="inline-flex items-center gap-1.5"><span class="material-symbols-outlined text-base text-secondary">school</span>{{ $session->classroom->teacher->preferredName() }}</span>
                <span>·</span>
                <span class="inline-flex items-center gap-1.5"><span class="material-symbols-outlined text-base">meeting_room</span>{{ $session->classroom->room_label }}</span>
                <span>·</span>
                <span class="inline-flex items-center gap-1.5"><span class="material-symbols-outlined text-base text-primary">group</span>{{ count($presentIds) }}/{{ $session->classroom->members->count() }} Present</span>
            </div>
        </div>
        <div class="flex items-center gap-space-sm flex-wrap">
            <div class="px-space-sm py-1.5 rounded-lg bg-surface-container flex items-center gap-space-xs">
                <span class="w-2.5 h-2.5 rounded-full bg-secondary"></span>
                <span class="font-label-meta text-label-meta uppercase text-on-surface-variant">Present</span>
                <span class="font-label-code text-label-code text-secondary ml-1">{{ count($presentIds) }}</span>
            </div>
            <div class="px-space-sm py-1.5 rounded-lg bg-surface-container flex items-center gap-space-xs">
                <span class="w-2.5 h-2.5 rounded-full bg-tertiary-container animate-pulse"></span>
                <span class="font-label-meta text-label-meta uppercase text-on-surface-variant">Needs Help</span>
                <span class="font-label-code text-label-code text-tertiary ml-1">{{ $session->helpRequests->count() }}</span>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-12 gap-space-lg">
        <div class="xl:col-span-7 flex flex-col gap-space-md">
            <div class="bg-surface-container-low rounded-xl p-space-md shadow-xl relative overflow-hidden border border-white/5">
                <div class="absolute inset-0 opacity-15 pointer-events-none" style="background-size: 24px 24px; background-image: radial-gradient(circle, #8083ff 1px, transparent 1px);"></div>
                <div class="flex items-center justify-between z-10 pb-space-sm relative">
                    <div class="flex items-center gap-space-xs">
                        <span class="w-2 h-2 rounded-full bg-secondary animate-ping"></span>
                        <span class="font-label-meta text-label-meta uppercase tracking-widest text-secondary">Digital Twin Mapping</span>
                    </div>
                </div>

                {{-- Desktop / tablet grid --}}
                <div class="hidden sm:grid relative z-10 gap-space-sm pb-space-sm" style="grid-template-columns: repeat({{ $session->classroom->cols }}, minmax(0, 1fr));">
                    @foreach($session->classroom->members->sortBy(['desk_row', 'desk_col']) as $member)
                        @php $present = in_array($member->user_id, $presentIds, true); @endphp
                        <div class="group relative flex flex-col items-center p-2 rounded-lg bg-surface-container hover:bg-surface-container-high transition-all {{ $present ? 'shadow-[0_0_10px_rgba(76,215,246,0.25)]' : 'opacity-70' }}">
                            <div class="w-8 h-8 rounded-full bg-surface-container-highest flex items-center justify-center relative">
                                <span class="font-label-code text-label-code {{ $present ? 'text-secondary' : 'text-outline' }}">{{ strtoupper(substr($member->user->preferredName(), 0, 2)) }}</span>
                                <span class="absolute bottom-0 right-0 w-2 h-2 rounded-full {{ $present ? 'bg-secondary' : 'bg-outline-variant' }}"></span>
                            </div>
                            <span class="font-label-code text-label-code text-on-surface mt-1 truncate w-full text-center">{{ $member->desk_label ?? 'D' }}</span>
                            <span class="font-label-meta text-label-meta text-on-surface-variant truncate w-full text-center">{{ Str::limit($member->user->preferredName(), 8) }}</span>
                        </div>
                    @endforeach
                </div>

                {{-- Mobile list representation --}}
                <div class="sm:hidden relative z-10 space-y-2">
                    @foreach($session->classroom->members as $member)
                        @php $present = in_array($member->user_id, $presentIds, true); @endphp
                        <div class="flex items-center justify-between rounded-lg bg-surface-container px-3 py-2">
                            <div class="flex items-center gap-3">
                                <span class="w-2 h-2 rounded-full {{ $present ? 'bg-secondary' : 'bg-outline-variant' }}"></span>
                                <div>
                                    <div class="font-body-md text-body-md text-on-surface">{{ $member->user->preferredName() }}</div>
                                    <div class="font-label-code text-label-code text-on-surface-variant">{{ $member->desk_label }}</div>
                                </div>
                            </div>
                            <span class="font-label-code text-label-code {{ $present ? 'text-secondary' : 'text-outline' }}">{{ $present ? 'PRESENT' : 'AWAY' }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="xl:col-span-5 flex flex-col gap-space-md">
            <div class="pb-card p-space-md">
                <h3 class="font-headline text-headline-sm text-on-surface mb-3">Attendance check-in</h3>
                @if($userAttendance)
                    <div class="rounded-lg bg-success/10 border border-success/30 px-3 py-3 text-success font-body-md text-body-md">
                        Checked in ({{ $userAttendance->method }}) at {{ $userAttendance->checked_in_at->format('H:i') }}
                    </div>
                @elseif($session->isLive())
                    <form method="POST" action="{{ route('classtwin.attendance', $session) }}" class="space-y-3">
                        @csrf
                        <p class="font-body-sm text-body-sm text-on-surface-variant">Enter the session attendance code shown by your teacher (QR / spoken code).</p>
                        <input type="text" name="code" class="pb-input uppercase tracking-widest" placeholder="ATTENDANCE CODE" required autocomplete="off">
                        <button type="submit" class="pb-btn-primary w-full">Validate attendance</button>
                    </form>
                @else
                    <p class="text-body-md text-on-surface-variant">Session is not live.</p>
                @endif
            </div>

            <div class="pb-card p-space-md">
                <h3 class="font-headline text-headline-sm text-on-surface mb-3">Request help</h3>
                <form method="POST" action="{{ route('classtwin.help', $session) }}" class="space-y-3">
                    @csrf
                    <input type="text" name="message" class="pb-input" placeholder="Optional short message" maxlength="255">
                    <button type="submit" class="pb-btn-secondary w-full">Signal for help</button>
                </form>
            </div>

            <div class="pb-card p-space-md">
                <h3 class="font-headline text-headline-sm text-on-surface mb-3">Open help requests</h3>
                @forelse($session->helpRequests as $help)
                    <div class="py-2 border-b border-white/5 last:border-0">
                        <div class="font-body-md text-body-md text-on-surface">{{ $help->user->preferredName() }}</div>
                        <div class="font-body-sm text-body-sm text-on-surface-variant">{{ $help->message ?: 'Needs assistance' }}</div>
                    </div>
                @empty
                    <p class="text-body-sm text-on-surface-variant">No open help requests.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
