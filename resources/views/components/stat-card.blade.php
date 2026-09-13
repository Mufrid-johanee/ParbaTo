@props(['label', 'value', 'hint' => null, 'class' => ''])
<div {{ $attributes->merge(['class' => 'pb-card p-space-md '.$class]) }}>
    <div class="font-label-meta text-label-meta uppercase text-on-surface-variant">{{ $label }}</div>
    <div class="font-headline text-headline-lg text-on-surface mt-1">{{ $value }}</div>
    @if($hint)<p class="text-xs text-on-surface-variant mt-1">{{ $hint }}</p>@endif
</div>
