@props(['tone' => 'primary', 'class' => ''])
@php
$tones = [
    'primary' => 'bg-primary/15 text-primary',
    'secondary' => 'bg-secondary/15 text-secondary',
    'success' => 'bg-success/15 text-success',
    'error' => 'bg-error/15 text-error',
    'neutral' => 'bg-surface-container-highest text-on-surface-variant',
];
@endphp
<span {{ $attributes->merge(['class' => 'inline-flex items-center px-2 py-0.5 rounded-full font-label-code text-label-code '.($tones[$tone] ?? $tones['primary']).' '.$class]) }}>
    {{ $slot }}
</span>
