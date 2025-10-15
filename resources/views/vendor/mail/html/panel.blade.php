@props( [ 'slot' ] )

<div {{$attributes->merge( [ 'class' => 'bg-gray-50 border border-gray-200 rounded-lg p-4 my-4' ] )}}>
    <div class="text-sm text-gray-700">
        {{$slot}}
    </div>
</div>
