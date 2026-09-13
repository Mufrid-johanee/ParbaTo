@props(['type' => 'info', 'class' => ''])
@php
$styles = [
    'info' => 'border-primary/30 bg-primary/10 text-primary',
    'success' => 'border-success/30 bg-success/10 text-success',
    'error' => 'border-error/30 bg-error-container/30 text-error',
    'warning' => 'border-warning/30 bg-warning/10 text-warning',
];
@endphp
<div {{ $attributes->merge(['class' => 'rounded-lg border px-4 py-3 text-body-md '.($styles[$type] ?? $styles['info']).' '.$class]) }} role="alert">
    {{ $slot }}
</div>
