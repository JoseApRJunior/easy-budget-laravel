@props([
    'gap' => 3
])

<div {{ $attributes->merge(['class' => "d-grid gap-{$gap}"]) }}>
    {{ $slot }}
</div>
