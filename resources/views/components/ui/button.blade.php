@props([
'variant' => 'primary',
'outline' => false,
'icon' => null,
'size' => null,
'type' => null,
'href' => null,
'label' => null,
'bold' => true,
'feature' => null,
])

@php
// Se feature for informada e o usuário não tiver acesso, não renderiza nada
if ($feature && !Gate::check($feature)) {
    return;
}

// Se tiver href, é um link
$isLink = $href !== null || $type === 'link';
$tag = $isLink ? 'a' : 'button';
$buttonType = $type ?: ($isLink ? null : 'button');

// Base das classes
$classes = 'btn d-inline-flex align-items-center justify-content-center ';
$classes .= $outline ? "btn-outline-{$variant}" : "btn-{$variant}";

// Lógica de Cor do Texto (Só se aplica se NÃO for outline)
// Se for outline, o Bootstrap já cuida da cor do texto (que deve ser igual à borda)
if (!$outline) {
// Lista de botões que PRECISAM de texto branco
$whiteTextVariants = ['primary', 'secondary', 'success', 'danger', 'dark'];

// Lista de botões que PRECISAM de texto escuro
$darkTextVariants = ['warning', 'info', 'light'];

if (in_array($variant, $whiteTextVariants)) {
$classes .= ' text-white';
} elseif (in_array($variant, $darkTextVariants)) {
// Aqui usamos text-dark (quase preto) para leitura máxima.
// Se você quiser aquele seu cinza específico APENAS nos fundos claros,
// crie uma classe CSS .text-slate e use aqui em vez de text-dark.
$classes .= ' text-dark';
}
}

if ($size) {
$classes .= " btn-{$size}";
}

// Bootstrap já tem fw-bold, mas se quiser garantir:
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

    {{-- Removemos o style inline hardcoded para evitar problemas de especificidade --}}
    {{ $attributes->merge(['class' => $classes]) }}>

    @if($icon)
    {{-- Adicionei a verificação de margin-end apenas se houver texto --}}
    <i class="bi bi-{{ $icon }} {{ ($label || !$slot->isEmpty()) ? 'me-2' : '' }}"></i>
    @endif

    {{ $slot->isEmpty() ? $label : $slot }}
</{{ $tag }}>
