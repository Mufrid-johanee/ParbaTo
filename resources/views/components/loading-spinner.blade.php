@props(['class' => ''])
<div {{ $attributes->merge(['class' => 'flex items-center justify-center p-6 '.$class]) }} role="status" aria-live="polite" aria-busy="true">
    <span class="inline-block h-8 w-8 rounded-full border-2 border-primary border-t-transparent animate-spin" aria-hidden="true"></span>
    <span class="sr-only">Loading</span>
</div>
