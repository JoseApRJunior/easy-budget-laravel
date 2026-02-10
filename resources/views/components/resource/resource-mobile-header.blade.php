@props([
    'title',
    'subtitle' => null,
])

<div {{ $attributes->merge(['class' => 'd-flex justify-content-between align-items-start mb-1 gap-2']) }}>
    <span class="fw-bold text-dark text-truncate" style="max-width: {{ $subtitle ? '60%' : '100%' }};">{{ $title }}</span>
    @if($subtitle)
        <span class="text-muted small text-end flex-shrink-1" style="min-width: 0;">{{ $subtitle }}</span>
    @endif
</div>
