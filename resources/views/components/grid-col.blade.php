@props([
    'size' => 'col-12',
])

<div {{ $attributes->merge(['class' => $size]) }}>
    {{ $slot }}
</div>
