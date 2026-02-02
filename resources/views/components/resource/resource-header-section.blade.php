@props([
    'title',
    'icon' => null,
])

<div {{ $attributes->merge(['class' => 'col-12 mt-2']) }}>
    <h6 class="fw-bold mb-3 d-flex align-items-center">
        @if($icon)
            <i class="bi bi-{{ $icon }} me-2"></i>
        @endif
        {{ $title }}
    </h6>
    <div class="row g-3">
        {{ $slot }}
    </div>
</div>
