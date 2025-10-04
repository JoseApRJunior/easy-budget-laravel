@props( [ 'type', 'message' ] )

@php
    $baseClasses = 'rounded-md p-4 text-sm';

    $typeClasses = [
        'error'   => 'bg-red-100 text-red-700',
        'success' => 'bg-green-100 text-green-700',
        'message' => 'bg-blue-100 text-blue-700',
        'warning' => 'bg-yellow-100 text-yellow-700',
    ];

    $iconClasses = [
        'error'   => 'bi-x-circle-fill',
        'success' => 'bi-check-circle-fill',
        'message' => 'bi-info-circle-fill',
        'warning' => 'bi-exclamation-triangle-fill',
    ];

    $alertClass = $typeClasses[ $type ] ?? $typeClasses[ 'message' ];
    $iconClass  = $iconClasses[ $type ] ?? $iconClasses[ 'message' ];
@endphp

<div x-data="{ show: true }" x-show="show" x-transition:leave="transition ease-in duration-300"
    x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" {{ $attributes->merge( [ 'class' => $baseClasses . ' ' . $alertClass ] ) }} role="alert">
    <div class="flex">
        <div class="flex-shrink-0">
            <i class="bi {{ $iconClass }} text-xl"></i>
        </div>
        <div class="ml-3">
            <div class="font-medium">{!! $message !!}</div>
        </div>
        <div class="ml-auto pl-3">
            <div class="-mx-1.5 -my-1.5">
                <button @click="show = false" type="button"
                    class="inline-flex rounded-md p-1.5 hover:bg-opacity-20 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-gray-50"
                    aria-label="Fechar">
                    <span class="sr-only">Fechar</span>
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
        </div>
    </div>
</div>
