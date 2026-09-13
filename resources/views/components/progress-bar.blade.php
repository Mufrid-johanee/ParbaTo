@props(['percent' => 0, 'label' => null, 'class' => ''])
<div {{ $attributes->merge(['class' => $class]) }} role="progressbar" aria-valuenow="{{ (int) $percent }}" aria-valuemin="0" aria-valuemax="100" @if($label) aria-label="{{ $label }}" @endif>
    <div class="w-full h-2 rounded-full bg-surface-container overflow-hidden">
        <div class="h-full rounded-full bg-primary transition-[width] duration-300" style="width: {{ max(0, min(100, (float) $percent)) }}%"></div>
    </div>
</div>
