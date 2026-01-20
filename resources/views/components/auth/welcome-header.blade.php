@props([
    'icon' => null,
    'title',
    'subtitle' => null,
])

<div class="mb-4">
    @if($icon)
        <div class="mb-4">
            <i class="bi bi-{{ $icon }} display-3 text-white-50"></i>
        </div>
    @endif

    <span class="h3 fw-bold mb-3">{{ $title }}</span>

    @if($subtitle)
        <p class="lead text-white-50 mb-4">
            {{ $subtitle }}
        </p>
    @endif
</div>
