@props([
    'cols' => 2,
    'gap' => 3,
])

<div {{ $attributes->merge(['class' => "row g-{$gap}"]) }}>
    @if($cols == 2)
        @if(isset($left) && isset($right))
            <div class="col-md-6">
                {{ $left }}
            </div>
            <div class="col-md-6">
                {{ $right }}
            </div>
        @else
            {{ $slot }}
        @endif
    @else
        <div class="col-12">
            {{ $slot }}
        </div>
    @endif
</div>
