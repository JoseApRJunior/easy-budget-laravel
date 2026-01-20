@props([
    'gap' => 2,
    'align' => 'center',
    'justify' => 'start',
])

<div {{ $attributes->merge(['class' => "d-flex align-items-{$align} justify-content-{$justify} gap-{$gap}"]) }}>
    {{ $slot }}
</div>
