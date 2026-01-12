@props([
    'fluid' => true,
    'padding' => 'py-2'
])

<div {{ $attributes->merge(['class' => ($fluid ? 'container-fluid' : 'container') . ' ' . $padding]) }}>
    {{ $slot }}
</div>
