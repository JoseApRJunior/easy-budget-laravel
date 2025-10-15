@props( [ 'slot' ] )

<div {{$attributes->merge( [ 'class' => 'text-xs text-gray-500 mt-6 pt-4 border-t border-gray-200' ] )}}>
    {{$slot}}
</div>
