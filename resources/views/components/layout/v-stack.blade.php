@props([
    'gap' => 4,
])

<div {{ $attributes->merge(['class' => "d-flex flex-column gap-$gap"]) }}>
    {{ $slot }}
</div>
