@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'alert alert-success d-flex align-items-center mb-4', 'role' => 'alert']) }}>
        <i class="bi bi-check-circle-fill me-2"></i>
        <div class="small">
            {{ $status }}
        </div>
    </div>
@endif
