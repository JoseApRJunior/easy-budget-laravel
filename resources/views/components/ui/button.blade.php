@props([
'variant' => 'primary',
'outline' => false,
'icon' => null,
'size' => null,
'type' => 'button',
'href' => null,
'label' => null,
'bold' => true, // Novo default para botões mais profissionais
])

@php
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

$tag = $type === 'link' ? 'a' : 'button';
@endphp

<{{ $tag }}
    @if($type==='link' ) href="{{ $href }}" @else type="{{ $type }}" @endif
    {{ $attributes->merge(['class' => $classes]) }}>
    @if($icon)
    <i class="bi bi-{{ $icon }} {{ ($label || !$slot->isEmpty()) ? 'me-2' : '' }}"></i>
    @endif
    {{ $slot->isEmpty() ? $label : $slot }}
</{{ $tag }}>
