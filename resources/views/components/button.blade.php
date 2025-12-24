@props([
'variant' => 'primary',
'outline' => false,
'icon' => null,
'size' => null,
'type' => 'button',
'href' => null,
'label' => null,
])

@php
$classes = 'btn ';
$classes .= $outline ? "btn-outline-{$variant}" : "btn-{$variant}";
if ($size) {
$classes .= " btn-{$size}";
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
