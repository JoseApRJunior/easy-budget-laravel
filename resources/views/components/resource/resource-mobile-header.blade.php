@props([
    'title' => null,
    'subtitle' => null,
])

<div {{ $attributes->merge(['class' => 'd-flex justify-content-between align-items-start mb-1 gap-2']) }}>
    <div class="fw-bold text-dark" style="font-size: 0.9rem; width: {{ $subtitle ? '60%' : '100%' }}; word-break: break-word;">
        {{ $title ?? $slot }}
    </div>
    @if($subtitle)
        <span class="text-muted small text-end flex-fill" style="width: 40%; min-width: 0;">{{ $subtitle }}</span>
    @endif
</div>
