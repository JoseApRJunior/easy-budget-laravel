@props( [ 'type' => 'message', 'message' => null ] )

@php
    $baseClasses = 'alert alert-dismissible fade show py-1 px-2';

    $typeClasses = [
        'error'   => 'alert-danger',
        'success' => 'alert-success',
        'message' => 'alert-info',
        'info'    => 'alert-info',
        'warning' => 'alert-warning',
    ];

    $iconClasses = [
        'error'   => 'bi-exclamation-triangle',
        'success' => 'bi-check-circle',
        'message' => 'bi-info-circle',
        'info'    => 'bi-info-circle',
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
            <div class="flex-grow-1 small">
                {!! $message ?? $slot !!}
            </div>
            <button @click="show = false" type="button" data-bs-dismiss="alert" class="m-0 p-0 ms-2 align-self-center" style="line-height:1; font-size:.875rem; cursor:pointer; color:#dc3545; background:transparent; border:0; outline:0; box-shadow:none;" aria-label="Fechar">×</button>
        </div>
    </div>
</div>
