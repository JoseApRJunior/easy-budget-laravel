@props( [
    'type'     => 'button',
    'variant'  => 'primary',
    'size'     => 'md',
    'disabled' => false,
    'loading'  => false,
    'href'     => null,
    'target'   => '_self'
] )

@php
    $baseClasses = 'inline-flex items-center justify-center font-semibold transition-all duration-150 ease-in-out focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed';

    $variantClasses = [
        'primary'           => 'bg-blue-600 text-white hover:bg-blue-700 active:bg-blue-800 focus:ring-blue-500 border border-transparent',
        'secondary'         => 'bg-gray-600 text-white hover:bg-gray-700 active:bg-gray-800 focus:ring-gray-500 border border-transparent',
        'success'           => 'bg-green-600 text-white hover:bg-green-700 active:bg-green-800 focus:ring-green-500 border border-transparent',
        'danger'            => 'bg-red-600 text-white hover:bg-red-700 active:bg-red-800 focus:ring-red-500 border border-transparent',
        'warning'           => 'bg-yellow-600 text-white hover:bg-yellow-700 active:bg-yellow-800 focus:ring-yellow-500 border border-transparent',
        'info'              => 'bg-blue-600 text-white hover:bg-blue-700 active:bg-blue-800 focus:ring-blue-500 border border-transparent',
        'outline-primary'   => 'bg-transparent text-blue-600 border border-blue-600 hover:bg-blue-50 active:bg-blue-100 focus:ring-blue-500',
        'outline-secondary' => 'bg-transparent text-gray-600 border border-gray-600 hover:bg-gray-50 active:bg-gray-100 focus:ring-gray-500',
        'ghost'             => 'bg-transparent text-gray-700 hover:bg-gray-100 active:bg-gray-200 focus:ring-gray-500 border border-transparent',
        'link'              => 'bg-transparent text-blue-600 hover:text-blue-700 active:text-blue-800 focus:ring-blue-500 border border-transparent p-0 h-auto',
    ];

    $sizeClasses = [
        'xs' => 'px-2.5 py-1.5 text-xs rounded',
        'sm' => 'px-3 py-2 text-sm rounded-lg',
        'md' => 'px-4 py-2 text-sm rounded-lg',
        'lg' => 'px-6 py-3 text-base rounded-lg',
        'xl' => 'px-8 py-4 text-lg rounded-lg',
    ];

    $classes = collect( [ $baseClasses, $variantClasses[ $variant ] ?? $variantClasses[ 'primary' ], $sizeClasses[ $size ] ?? $sizeClasses[ 'md' ] ] )
        ->filter()
        ->implode( ' ' );
@endphp

@if( $href )
    <a
        href="{{ $href }}"
        target="{{ $target }}"
        @if( $disabled ) aria-disabled="true" @endif
        {{ $attributes->merge( [ 'class' => $classes ] ) }}
    >
        @if( $loading )
            <i class="bi bi-arrow-clockwise animate-spin mr-2"></i>
        @endif
        {{ $slot }}
    </a>
@else
    <button
        type="{{ $type }}"
        @if( $disabled ) disabled @endif
        {{ $attributes->merge( [ 'class' => $classes ] ) }}
    >
        @if( $loading )
            <i class="bi bi-arrow-clockwise animate-spin mr-2"></i>
        @endif
        {{ $slot }}
    </button>
@endif
