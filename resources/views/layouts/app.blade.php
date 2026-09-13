@extends('layouts.base')

@section('body')
<div x-data="{ sidebarOpen: false }" class="min-h-screen">
    {{-- Mobile overlay --}}
    <div
        x-show="sidebarOpen"
        x-transition.opacity
        class="fixed inset-0 z-40 bg-black/60 lg:hidden"
        @click="sidebarOpen = false"
        style="display: none;"
    ></div>

    @include('layouts.partials.sidebar')

    <div class="lg:pl-64 pb-20 lg:pb-0">
        @include('layouts.partials.topbar')

        <main id="main-content" class="relative pt-16 bg-surface min-h-screen w-full px-4 sm:px-6 lg:px-space-lg py-space-lg" tabindex="-1">
            @if (session('status'))
                <div class="mb-space-md rounded-lg border border-success/30 bg-success/10 px-4 py-3 text-body-md text-success" role="status">
                    {{ session('status') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-space-md rounded-lg border border-error/30 bg-error-container/30 px-4 py-3 text-body-md text-error" role="alert">
                    <ul class="list-disc pl-4 space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    @include('layouts.partials.mobile-nav')
</div>
@endsection
