@props( [
    'header'  => null,
    'footer'  => null,
    'padding' => 'default',
    'shadow'  => 'sm',
    'border'  => true,
    'rounded' => 'lg'
] )

@php
    $paddingClasses = [
        'none'    => '',
        'sm'      => 'p-4',
        'default' => 'p-6',
        'lg'      => 'p-8',
    ];

    $shadowClasses = [
        'none'    => '',
        'sm'      => 'shadow-sm',
        'default' => 'shadow',
        'md'      => 'shadow-md',
        'lg'      => 'shadow-lg',
        'xl'      => 'shadow-xl',
    ];

    $roundedClasses = [
        'none'    => '',
        'sm'      => 'rounded-sm',
        'default' => 'rounded',
        'md'      => 'rounded-md',
        'lg'      => 'rounded-lg',
        'xl'      => 'rounded-xl',
    ];

    $baseClasses = collect( [
        'bg-white',
        $border ? 'border border-gray-200' : '',
        $shadowClasses[ $shadow ] ?? $shadowClasses[ 'sm' ],
        $roundedClasses[ $rounded ] ?? $roundedClasses[ 'lg' ],
        'overflow-hidden',
    ] )->filter()->implode( ' ' );
@endphp

<div {{ $attributes->merge( [ 'class' => $baseClasses ] ) }}>
    @if( $header )
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
            @if( is_string( $header ) )
                <h3 class="text-lg font-semibold text-gray-900">{{ $header }}</h3>
            @else
                {{ $header }}
            @endif
        </div>
    @endif

    <div class="{{ $paddingClasses[ $padding ] ?? $paddingClasses[ 'default' ] }}">
        {{ $slot }}
    </div>

    @if( $footer )
        <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
            {{ $footer }}
        </div>
    @endif
</div>
