@props( [
    'type'    => 'primary',
    'size'    => 'md',
    'rounded' => 'full',
    'dot'     => false
] )
@php
    $typeClasses = [
        'primary'   => 'bg-blue-100 text-blue-800',
        'secondary' => 'bg-gray-100 text-gray-800',
        'success'   => 'bg-green-100 text-green-800',
        'danger'    => 'bg-red-100 text-red-800',
        'warning'   => 'bg-yellow-100 text-yellow-800',
        'info'      => 'bg-blue-100 text-blue-800',
        'light'     => 'bg-gray-50 text-gray-600',
        'dark'      => 'bg-gray-800 text-gray-100',
    ];

    $sizeClasses = [
        'xs' => 'px-1.5 py-0.5 text-xs',
        'sm' => 'px-2 py-1 text-xs',
        'md' => 'px-2.5 py-0.5 text-xs',
        'lg' => 'px-3 py-1 text-sm',
    ];

    $roundedClasses = [
        'none'    => 'rounded-none',
        'sm'      => 'rounded-sm',
        'default' => 'rounded',
        'md'      => 'rounded-md',
        'lg'      => 'rounded-lg',
        'full'    => 'rounded-full',
    ];

    $classes = collect( [
        'inline-flex items-center font-medium',
        $typeClasses[ $type ] ?? $typeClasses[ 'primary' ],
        $sizeClasses[ $size ] ?? $sizeClasses[ 'md' ],
        $roundedClasses[ $rounded ] ?? $roundedClasses[ 'full' ],
    ] )->implode( ' ' );
@endphp
@if( $dot )
    <span {{ $attributes->merge( [ 'class' => $classes ] ) }}>
        <span class="w-2 h-2 rounded-full bg-current opacity-75 mr-1.5"></span>
        {{ $slot }}
        </
    span>
@else
    <span {{ $attributes->merge( [ 'class' => $classes ] ) }}>
        {{ $slot }}
    </span>
@endif
