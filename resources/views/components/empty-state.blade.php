@props(['title' => 'Nothing here yet', 'description' => null, 'class' => ''])
<div {{ $attributes->merge(['class' => 'pb-card p-10 text-center text-on-surface-variant '.$class]) }} role="status">
    <p class="font-headline text-headline-sm text-on-surface">{{ $title }}</p>
    @if($description)<p class="mt-2 text-sm">{{ $description }}</p>@endif
    {{ $slot }}
</div>
