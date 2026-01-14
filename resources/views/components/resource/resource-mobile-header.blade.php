@props([
    'title',
    'subtitle' => null,
])

<div {{ $attributes->merge(['class' => 'd-flex justify-content-between align-items-center mb-1']) }}>
    <span class="fw-bold text-dark">{{ $title }}</span>
    @if($subtitle)
        <span class="text-muted small">{{ $subtitle }}</span>
    @endif
</div>
