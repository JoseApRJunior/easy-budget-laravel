@props([
    'id',
    'max' => 255,
    'current' => 0,
    'align' => 'end'
])

<div {{ $attributes->merge(['class' => "form-text text-muted small text-$align", 'id' => $id]) }}>
    {{ $current }} / {{ $max }} caracteres
</div>
