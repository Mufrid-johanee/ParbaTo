@extends('layouts.app')

@section('title', 'Admin')

@section('content')
<div class="flex flex-col gap-space-lg">
    <header>
        <h1 class="font-headline text-headline-xl text-on-surface">System overview</h1>
        <p class="text-on-surface-variant">Admin-only user and XP summary.</p>
    </header>
    <section class="grid grid-cols-2 lg:grid-cols-4 gap-space-md">
        <x-stat-card label="Users" :value="$stats['users']" />
        <x-stat-card label="Students" :value="$stats['students']" />
        <x-stat-card label="Teachers" :value="$stats['teachers']" />
        <x-stat-card label="Total student XP" :value="number_format($stats['total_xp'])" />
    </section>
    <div class="overflow-x-auto pb-card">
        <table class="min-w-full text-sm">
            <thead class="border-b border-white/10 text-on-surface-variant">
                <tr>
                    <th class="p-3 text-left" scope="col">Name</th>
                    <th class="p-3 text-left" scope="col">Email</th>
                    <th class="p-3 text-left" scope="col">Role</th>
                    <th class="p-3 text-left" scope="col">XP</th>
                    <th class="p-3 text-left" scope="col">Level</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $u)
                    <tr class="border-b border-white/5">
                        <td class="p-3 text-on-surface">{{ $u->preferredName() }}</td>
                        <td class="p-3 text-on-surface-variant">{{ $u->email }}</td>
                        <td class="p-3 capitalize">{{ $u->role }}</td>
                        <td class="p-3">{{ $u->xp }}</td>
                        <td class="p-3">{{ $u->level }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    {{ $users->links() }}
</div>
@endsection
