@props(['title' => null, 'class' => ''])
<div {{ $attributes->merge(['class' => 'pb-card p-space-md '.$class]) }}>
    @if($title)
        <h3 class="font-headline text-headline-sm text-on-surface mb-3">{{ $title }}</h3>
    @endif
    {{ $slot }}
</div>
