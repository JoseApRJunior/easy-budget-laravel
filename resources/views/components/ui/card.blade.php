@props([
    'header' => null,
    'footer' => null,
    'noPadding' => false,
    'class' => '',
])

<div {{ $attributes->merge(['class' => 'card border-0 shadow-sm h-100 ' . $class]) }}>
    @if(isset($header) && $header->isNotEmpty())
        <div class="card-header border-1 py-3 bg-transparent">
            {{ $header }}
        </div>
    @endif

    <div class="card-body {{ $noPadding ? 'p-0' : '' }}">
        {{ $slot }}
    </div>

    @if(isset($footer) && $footer->isNotEmpty())
        <div class="card-footer border-1 py-3 bg-transparent">
            {{ $footer }}
        </div>
    @endif
</div>
