@props([
    'gap' => 2,
    'fullWidthMobile' => true
])

<div {{ $attributes->merge(['class' => ($fullWidthMobile ? 'd-grid d-md-flex' : 'd-flex') . " gap-{$gap} " . ($fullWidthMobile ? 'w-100 w-md-auto' : '')]) }}>
    {{ $slot }}
</div>
