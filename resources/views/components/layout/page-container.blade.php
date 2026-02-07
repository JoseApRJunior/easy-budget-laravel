@props([
    'fluid' => true,
    'padding' => 'py-1'
])

<div {{ $attributes->merge(['class' => ($fluid ? 'container-fluid' : 'container') . ' ' . $padding]) }}>
    {{ $slot }}
</div>
