@props( [ 'url', 'color' => 'primary', 'block' => false ] )

@php
    $colors = [
        'primary' => 'bg-blue-600 hover:bg-blue-700 text-white',
        'success' => 'bg-green-600 hover:bg-green-700 text-white',
        'danger'  => 'bg-red-600 hover:bg-red-700 text-white',
    ];

    $colorClass = $colors[ $color ] ?? $colors[ 'primary' ];
    $blockClass = $block ? 'w-full' : '';
@endphp

<a href="{{$url}}" {{$attributes->merge( [ 'class' => 'inline-block px-6 py-3 rounded-md font-medium text-center transition-colors duration-200 ' . $colorClass . ' ' . $blockClass ] )}}>
    {{$slot}}
</a>
