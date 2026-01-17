@props([
    'icon' => 'shield-check',
])

<div class="mt-5 text-white-50 small">
    <i class="bi bi-{{ $icon }} me-1"></i>
    {{ $slot }}
</div>
