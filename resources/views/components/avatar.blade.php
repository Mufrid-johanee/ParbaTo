@props(['name' => '?', 'size' => 'md', 'class' => ''])
@php
$sizes = ['sm' => 'h-8 w-8 text-sm', 'md' => 'h-10 w-10 text-base', 'lg' => 'h-16 w-16 text-2xl'];
@endphp
<div {{ $attributes->merge(['class' => 'inline-flex items-center justify-center rounded-full bg-primary-container/30 text-primary font-headline '.($sizes[$size] ?? $sizes['md']).' '.$class]) }} aria-hidden="true">
    {{ strtoupper(substr($name, 0, 1)) }}
</div>
