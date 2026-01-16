@props([
    'icon' => 'check-circle-fill',
    'label',
])

<div class="col-md-6">
    <div class="d-flex align-items-center">
        <i class="bi bi-{{ $icon }} text-info me-2"></i>
        <span>{{ $label }}</span>
    </div>
</div>
