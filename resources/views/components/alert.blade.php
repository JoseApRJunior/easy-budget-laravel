@props( [ 'type', 'message' ] )

@php
    $baseClasses = 'alert alert-dismissible fade show';

    $typeClasses = [
        'error'   => 'alert-danger',
        'success' => 'alert-success',
        'message' => 'alert-info',
        'warning' => 'alert-warning',
    ];

    $iconClasses = [
        'error'   => 'bi-exclamation-triangle',
        'success' => 'bi-check-circle',
        'message' => 'bi-info-circle',
        'warning' => 'bi-exclamation-triangle',
    ];

    $alertClass = $typeClasses[ $type ] ?? $typeClasses[ 'message' ];
    $iconClass  = $iconClasses[ $type ] ?? $iconClasses[ 'message' ];
@endphp
<div class="container ">
    <div x-data="{ show: true }" x-show="show" x-transition:leave="transition ease-in duration-300"
        x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" {{ $attributes->merge( [ 'class' => $baseClasses . ' ' . $alertClass ] ) }} role="alert">
        <div class="d-flex align-items-center">
            <i class="bi {{ $iconClass }} me-2"></i>
            <div class="flex-grow-1">{!! $message !!}</div>
            <button @click="show = false" type="button" class="btn-close" data-bs-dismiss="alert"
                aria-label="Fechar"></button>
        </div>
    </div>
</div>
