@extends('layouts.app')

@section('title', 'ClassTwin Live')

@section('content')
<div
    class="flex flex-col w-full gap-space-lg pb-8"
    x-data="classTwinLive({
        presenceUrl: '{{ route('classtwin.presence', $session) }}',
        heartbeatUrl: '{{ route('classtwin.heartbeat', $session) }}',
        isLive: {{ $session->isLive() ? 'true' : 'false' }},
        canHeartbeat: {{ (!$isTeacher && $userAttendance) ? 'true' : 'false' }}
    })"
    x-init="boot()"
>
    <div class="w-full bg-surface-container-low rounded-xl p-space-md shadow-xl flex flex-col xl:flex-row xl:items-center justify-between gap-space-md border border-white/5">
        <div class="flex flex-col gap-space-xs">
            <div class="flex items-center gap-space-sm flex-wrap">
                <a href="{{ route('classrooms.show', $session->classroom) }}" class="font-headline text-headline-md text-on-surface hover:text-primary transition-colors">{{ $session->title ?? $session->classroom->name }}</a>
                @if($session->classroom->course)
                    <span class="px-space-xs py-0.5 rounded font-label-code text-label-code bg-surface-container-highest text-primary">{{ $session->classroom->course->code }}</span>
                @endif
                <div class="flex items-center gap-1.5 px-space-sm py-0.5 rounded-full {{ $session->isLive() ? 'bg-error-container/40' : 'bg-surface-container' }}">
                    <span class="w-2 h-2 rounded-full {{ $session->isLive() ? 'bg-error animate-ping' : 'bg-outline' }}"></span>
                    <span class="font-label-meta text-label-meta uppercase tracking-wider {{ $session->isLive() ? 'text-error' : 'text-on-surface-variant' }}">{{ ucfirst($session->status) }} Session</span>
                </div>
            </div>
            <div class="flex items-center gap-space-md text-on-surface-variant font-body-sm text-body-sm flex-wrap">
                <span class="inline-flex items-center gap-1.5"><span class="material-symbols-outlined text-base text-secondary">school</span>{{ $session->classroom->teacher->preferredName() }}</span>
                <span>·</span>
                <span class="inline-flex items-center gap-1.5"><span class="material-symbols-outlined text-base">meeting_room</span>{{ $session->classroom->room_label ?: $session->classroom->name }}</span>
                <span>·</span>
                <span class="inline-flex items-center gap-1.5"><span class="material-symbols-outlined text-base text-primary">group</span>
                    <span x-text="stats.present_count + '/' + stats.member_count + ' Present'">{{ $stats['present_count'] }}/{{ $stats['member_count'] }} Present</span>
                </span>
                @if($session->started_at)
                    <span>·</span>
                    <span class="inline-flex items-center gap-1.5"><span class="material-symbols-outlined text-base">schedule</span>
                        Started {{ $session->started_at->format('H:i') }}
                        @if($stats['duration_minutes'] !== null)
                            · {{ $stats['duration_minutes'] }} min
                        @endif
                    </span>
                @endif
            </div>
        </div>
        <div class="flex items-center gap-space-sm flex-wrap">
            <div class="px-space-sm py-1.5 rounded-lg bg-surface-container flex items-center gap-space-xs">
                <span class="w-2.5 h-2.5 rounded-full bg-secondary"></span>
                <span class="font-label-meta text-label-meta uppercase text-on-surface-variant">Present</span>
                <span class="font-label-code text-label-code text-secondary ml-1" x-text="stats.present_count">{{ $stats['present_count'] }}</span>
            </div>
            <div class="px-space-sm py-1.5 rounded-lg bg-surface-container flex items-center gap-space-xs">
                <span class="w-2.5 h-2.5 rounded-full bg-tertiary-container animate-pulse"></span>
                <span class="font-label-meta text-label-meta uppercase text-on-surface-variant">Needs Help</span>
                <span class="font-label-code text-label-code text-tertiary ml-1" x-text="helpOpen">{{ $session->helpRequests->count() }}</span>
            </div>
            @if($isTeacher && $session->isLive())
                <form method="POST" action="{{ route('classtwin.end', $session) }}" onsubmit="return confirm('End this live session?')">
                    @csrf
                    <button type="submit" class="pb-btn-secondary min-h-11 px-4 text-error border-error/30">End session</button>
                </form>
            @endif
        </div>
    </div>

    @if(session('status'))
        <div class="rounded-xl bg-secondary-container/15 border border-secondary/20 px-space-md py-space-sm text-secondary" role="status">{{ session('status') }}</div>
    @endif

    @if(! $session->isLive())
        <div class="rounded-xl border border-white/10 bg-surface-container px-space-md py-space-sm text-on-surface-variant" role="status">
            This session is {{ $session->status }}. Attendance and presence updates are closed.
        </div>
    @endif

    <div class="grid grid-cols-1 xl:grid-cols-12 gap-space-lg">
        <div class="xl:col-span-7 flex flex-col gap-space-md">
            <div class="bg-surface-container-low rounded-xl p-space-md shadow-xl relative overflow-hidden border border-white/5">
                <div class="absolute inset-0 opacity-15 pointer-events-none" style="background-size: 24px 24px; background-image: radial-gradient(circle, #8083ff 1px, transparent 1px);"></div>
                <div class="flex flex-wrap items-center justify-between gap-2 z-10 pb-space-sm relative">
                    <div class="flex items-center gap-space-xs">
                        <span class="w-2 h-2 rounded-full bg-secondary animate-ping"></span>
                        <span class="font-label-meta text-label-meta uppercase tracking-widest text-secondary">Digital Twin Mapping</span>
                    </div>
                    <div class="flex flex-wrap gap-2 font-label-code text-label-code text-on-surface-variant">
                        <span class="inline-flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-secondary"></span> Active</span>
                        <span class="inline-flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-tertiary"></span> Idle</span>
                        <span class="inline-flex items-center gap-1"><span class="w-2 h-2 rounded-full bg-outline-variant"></span> Absent</span>
                    </div>
                </div>

                <div class="hidden sm:grid relative z-10 gap-space-sm pb-space-sm" style="grid-template-columns: repeat({{ $session->classroom->cols }}, minmax(0, 1fr));">
                    @foreach($session->classroom->members->where('status', 'active')->sortBy(['desk_row', 'desk_col']) as $member)
                        @php
                            $row = collect($presence)->firstWhere('user_id', $member->user_id);
                            $p = $row['presence'] ?? 'absent';
                        @endphp
                        <div
                            class="group relative flex flex-col items-center p-2 rounded-lg bg-surface-container hover:bg-surface-container-high transition-all"
                            data-user-id="{{ $member->user_id }}"
                            :class="presenceClass({{ $member->user_id }})"
                        >
                            <div class="w-8 h-8 rounded-full bg-surface-container-highest flex items-center justify-center relative">
                                <span class="font-label-code text-label-code text-on-surface">{{ strtoupper(substr($member->user->preferredName(), 0, 2)) }}</span>
                                <span class="absolute bottom-0 right-0 w-2 h-2 rounded-full" :class="dotClass({{ $member->user_id }})"></span>
                            </div>
                            <span class="font-label-code text-label-code text-on-surface mt-1 truncate w-full text-center">{{ $member->desk_label ?? 'D' }}</span>
                            <span class="font-label-meta text-label-meta text-on-surface-variant truncate w-full text-center">{{ Str::limit($member->user->preferredName(), 8) }}</span>
                            <span class="font-label-code text-[10px] uppercase text-on-surface-variant" x-text="presenceLabel({{ $member->user_id }})">{{ $p }}</span>
                        </div>
                    @endforeach
                </div>

                <div class="sm:hidden relative z-10 space-y-2">
                    @foreach($session->classroom->members->where('status', 'active') as $member)
                        @php $row = collect($presence)->firstWhere('user_id', $member->user_id); @endphp
                        <div class="flex items-center justify-between rounded-lg bg-surface-container px-3 py-2">
                            <div class="flex items-center gap-3">
                                <span class="w-2 h-2 rounded-full" :class="dotClass({{ $member->user_id }})"></span>
                                <div>
                                    <div class="font-body-md text-body-md text-on-surface">{{ $member->user->preferredName() }}</div>
                                    <div class="font-label-code text-label-code text-on-surface-variant">{{ $member->desk_label }}</div>
                                </div>
                            </div>
                            <span class="font-label-code text-label-code uppercase" x-text="presenceLabel({{ $member->user_id }})">{{ $row['presence'] ?? 'absent' }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="xl:col-span-5 flex flex-col gap-space-md">
            @if($isTeacher)
                <div class="pb-card p-space-md">
                    <h3 class="font-headline text-headline-sm text-on-surface mb-2">Attendance code</h3>
                    @if($session->isLive() && ($plainCode || session('attendance_code')))
                        @php $code = $plainCode ?: session('attendance_code'); @endphp
                        <p class="font-display-hero text-3xl font-bold tracking-[0.25em] text-primary text-center py-3">{{ $code }}</p>
                        <p class="font-body-sm text-body-sm text-on-surface-variant text-center mb-3">Scan the QR or enter the code. Server stores only a hash. Rotate anytime.</p>
                        @if(! empty($qrSvg))
                            <div class="mx-auto mb-3 w-[200px] h-[200px] rounded-xl bg-white p-2 flex items-center justify-center overflow-hidden" role="img" aria-label="QR code for attendance code {{ $code }}">
                                {!! $qrSvg !!}
                            </div>
                        @endif
                        <form method="POST" action="{{ route('classtwin.rotate', $session) }}">
                            @csrf
                            <button type="submit" class="pb-btn-secondary w-full min-h-11">Rotate code</button>
                        </form>
                    @elseif($session->isLive())
                        <p class="font-body-sm text-body-sm text-on-surface-variant mb-3">Plain code is only shown right after start/rotate (hashed in DB). Rotate to display a new code.</p>
                        <form method="POST" action="{{ route('classtwin.rotate', $session) }}">
                            @csrf
                            <button type="submit" class="pb-btn-primary w-full min-h-11">Generate attendance code</button>
                        </form>
                    @else
                        <p class="text-on-surface-variant">Session closed.</p>
                    @endif
                </div>

                <div class="pb-card p-space-md">
                    <h3 class="font-headline text-headline-sm text-on-surface mb-3">Manual attendance</h3>
                    @if($session->isLive())
                        <form method="POST" action="{{ route('classtwin.mark', $session) }}" class="space-y-3">
                            @csrf
                            <select name="user_id" class="pb-input" required>
                                <option value="">Select student</option>
                                @foreach($session->classroom->members->where('status', 'active') as $member)
                                    <option value="{{ $member->user_id }}">{{ $member->user->preferredName() }}</option>
                                @endforeach
                            </select>
                            <select name="status" class="pb-input" required>
                                <option value="present">Present</option>
                                <option value="late">Late</option>
                                <option value="absent">Absent</option>
                                <option value="excused">Excused</option>
                            </select>
                            <button type="submit" class="pb-btn-secondary w-full min-h-11">Mark attendance</button>
                        </form>
                    @else
                        <p class="text-on-surface-variant">Unavailable after session ends.</p>
                    @endif
                </div>
            @else
                <div class="pb-card p-space-md">
                    <h3 class="font-headline text-headline-sm text-on-surface mb-3">Your attendance</h3>
                    @if($userAttendance)
                        <div class="rounded-lg bg-secondary-container/15 border border-secondary/30 px-3 py-3 text-secondary font-body-md text-body-md">
                            Checked in ({{ $userAttendance->method }}) at {{ $userAttendance->checked_in_at->format('H:i') }}
                            · status {{ $userAttendance->status }}
                        </div>
                        @if($session->isLive())
                            <p class="font-body-sm text-body-sm text-on-surface-variant mt-2">Presence heartbeat runs while this page is open.</p>
                        @endif
                    @elseif($session->isLive())
                        <form method="POST" action="{{ route('classtwin.attendance', $session) }}" class="space-y-3 mb-3">
                            @csrf
                            <p class="font-body-sm text-body-sm text-on-surface-variant">Enter the session attendance code from your teacher (QR / spoken code).</p>
                            <input type="text" name="code" class="pb-input uppercase tracking-widest" placeholder="ATTENDANCE CODE" required autocomplete="off">
                            @error('code')<p class="text-error text-sm">{{ $message }}</p>@enderror
                            <button type="submit" class="pb-btn-primary w-full min-h-11">Validate attendance</button>
                        </form>
                        <form method="POST" action="{{ route('classtwin.join', $session) }}">
                            @csrf
                            <button type="submit" class="pb-btn-secondary w-full min-h-11">Join session without code</button>
                        </form>
                        <p class="font-body-sm text-body-sm text-on-surface-variant mt-2">Code check-in is preferred. Join without code marks presence via membership (fallback).</p>
                    @else
                        <p class="text-body-md text-on-surface-variant">Session is not live.</p>
                    @endif
                </div>

                @if($session->isLive())
                    <div class="pb-card p-space-md">
                        <h3 class="font-headline text-headline-sm text-on-surface mb-3">Request help</h3>
                        <form method="POST" action="{{ route('classtwin.help', $session) }}" class="space-y-3">
                            @csrf
                            <input type="text" name="message" class="pb-input" placeholder="Optional short message" maxlength="255">
                            <button type="submit" class="pb-btn-secondary w-full min-h-11">Signal for help</button>
                        </form>
                    </div>
                @endif
            @endif

            <div class="pb-card p-space-md">
                <h3 class="font-headline text-headline-sm text-on-surface mb-3">Open help requests</h3>
                @forelse($session->helpRequests as $help)
                    <div class="py-2 border-b border-white/5 last:border-0 flex items-start justify-between gap-2">
                        <div>
                            <div class="font-body-md text-body-md text-on-surface">{{ $help->user->preferredName() }}</div>
                            <div class="font-body-sm text-body-sm text-on-surface-variant">{{ $help->message ?: 'Needs assistance' }}</div>
                        </div>
                        @if($isTeacher)
                            <form method="POST" action="{{ route('classtwin.help.resolve', [$session, $help]) }}">
                                @csrf
                                <button type="submit" class="font-label-code text-label-code text-primary min-h-10 px-2">Resolve</button>
                            </form>
                        @endif
                    </div>
                @empty
                    <p class="text-on-surface-variant text-body-sm">No open help requests.</p>
                @endforelse
            </div>

            <div class="pb-card p-space-md">
                <h3 class="font-headline text-headline-sm text-on-surface mb-2">Continue learning</h3>
                <a href="{{ route('learnquest.index') }}" class="pb-btn-secondary w-full min-h-11 inline-flex items-center justify-center">Open LearnQuest</a>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function classTwinLive({ presenceUrl, heartbeatUrl, isLive, canHeartbeat }) {
    return {
        presenceUrl,
        heartbeatUrl,
        isLive,
        canHeartbeat,
        presence: @json(collect($presence)->keyBy('user_id')),
        stats: @json($stats),
        helpOpen: {{ $session->helpRequests->count() }},
        timer: null,
        boot() {
            if (!this.isLive) return;
            this.timer = setInterval(() => this.refresh(), 15000);
            if (this.canHeartbeat) {
                this.sendHeartbeat();
                setInterval(() => this.sendHeartbeat(), 30000);
            }
        },
        async refresh() {
            try {
                const res = await fetch(this.presenceUrl, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' });
                if (!res.ok) return;
                const json = await res.json();
                const map = {};
                (json.data.presence || []).forEach(row => { map[row.user_id] = row; });
                this.presence = map;
                this.stats = json.data.stats || this.stats;
                this.helpOpen = json.data.help_open ?? this.helpOpen;
                if (json.data.session_status && json.data.session_status !== 'live') {
                    this.isLive = false;
                }
            } catch (e) {}
        },
        async sendHeartbeat() {
            if (!this.canHeartbeat || !this.isLive) return;
            try {
                await fetch(this.heartbeatUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    credentials: 'same-origin',
                });
            } catch (e) {}
        },
        presenceFor(id) {
            return (this.presence[id] && this.presence[id].presence) || 'absent';
        },
        presenceLabel(id) {
            return this.presenceFor(id);
        },
        presenceClass(id) {
            const p = this.presenceFor(id);
            if (p === 'active') return 'shadow-[0_0_10px_rgba(76,215,246,0.25)]';
            if (p === 'idle') return 'opacity-90';
            return 'opacity-60';
        },
        dotClass(id) {
            const p = this.presenceFor(id);
            if (p === 'active') return 'bg-secondary';
            if (p === 'idle') return 'bg-tertiary';
            return 'bg-outline-variant';
        },
    }
}
</script>
@endpush
@endsection
