@props([
    'icon' => 'check-circle-fill',
    'label',
])

<div class="col-md-6">
    <div class="d-flex align-items-center p-3 rounded-xl bg-white/5 hover:bg-white/10 transition-all duration-300">
        <div class="bg-white/15 rounded-lg p-2 me-3">
            <i class="bi bi-{{ $icon }} text-success"></i>
        </div>
        <span class="text-white/90 fw-medium">{{ $label }}</span>
    </div>
</div>
