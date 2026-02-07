@props([
    'icon' => 'shield-check',
])

<div class="mt-6 text-white/60 small">
    <div class="d-flex align-items-center bg-white/5 p-3 rounded-lg">
        <i class="bi bi-{{ $icon }} me-2"></i>
        {{ $slot }}
    </div>
</div>
