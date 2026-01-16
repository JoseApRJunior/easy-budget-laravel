@props([
    'variant' => 'primary',
    'outline' => false,
    'icon' => null,
    'size' => null,
    'type' => null,
    'href' => null,
    'label' => null,
    'bold' => true,
])

@php
    // Se tiver href, é um link, a menos que o tipo seja explicitamente outra coisa
    $isLink = $href !== null || $type === 'link';
    $tag = $isLink ? 'a' : 'button';
    
    // Se for botão e não tiver tipo, o padrão é 'button'
    // Mas se estiver dentro de um formulário, às vezes queremos 'submit'
    $buttonType = $type ?: ($isLink ? null : 'button');

    $classes = 'btn ';
    $classes .= $outline ? "btn-outline-{$variant}" : "btn-{$variant}";
    if ($variant === 'info' && !$outline) {
        $classes .= ' text-white';
    }
    if ($size) {
        $classes .= " btn-{$size}";
    }
    if ($bold) {
        $classes .= ' fw-bold';
    }
@endphp

<{{ $tag }}
    @if($isLink) 
        href="{{ $href }}" 
    @else 
        type="{{ $buttonType }}" 
    @endif
    {{ $attributes->merge(['class' => $classes]) }}>
    @if($icon)
        <i class="bi bi-{{ $icon }} {{ ($label || !$slot->isEmpty()) ? 'me-2' : '' }}"></i>
    @endif
    {{ $slot->isEmpty() ? $label : $slot }}
</{{ $tag }}>
