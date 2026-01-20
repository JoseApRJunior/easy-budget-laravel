@props([
    'alignment' => 'end', // start, center, end
    'mb' => 4
])

<div {{ $attributes->merge(['class' => "d-flex justify-content-$alignment mb-$mb"]) }}>
    {{ $slot }}
</div>
