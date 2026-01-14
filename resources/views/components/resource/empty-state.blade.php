@props([
    'icon' => 'inbox',           // Ícone do Bootstrap Icons
    'resource' => 'item',        // Nome do recurso no plural (categorias, produtos, etc.)
    'resourceSingular' => null,  // Nome do recurso no singular (opcional)
    'isTrashView' => false,      // Se está na visualização de lixeira
    'isSearchView' => false,     // Se está em uma busca
    'message' => null,           // Mensagem customizada (opcional)
    'submessage' => null,        // Submensagem customizada (opcional)
    'iconSize' => '2rem',        // Tamanho do ícone
])

@php
    $resourceSingular = $resourceSingular ?? rtrim($resource, 's');

    // Mensagem padrão baseada no contexto
    if (!$message) {
        if ($isTrashView) {
            $message = "Nenhum(a) {$resourceSingular} deletado(a) encontrado(a).";
            $submessage = $submessage ?? "Você ainda não deletou nenhum(a) {$resourceSingular}.";
        } elseif ($isSearchView) {
            $message = "Nenhum(a) {$resourceSingular} encontrado(a) com os filtros aplicados.";
            $submessage = $submessage ?? "Tente ajustar os filtros de busca.";
        } else {
            $message = "Nenhum(a) {$resourceSingular} encontrado(a).";
            $submessage = $submessage ?? null;
        }
    }
@endphp

<div {{ $attributes->merge(['class' => 'p-4 text-center text-muted']) }}>
    <i class="bi bi-{{ $icon }} mb-2" style="font-size: {{ $iconSize }};"></i>
    <br>
    {{ $message }}
    @if($submessage)
        <br>
        <small>{{ $submessage }}</small>
    @endif

    {{-- Slot para conteúdo adicional (ex: botão de ação) --}}
    @if(!$slot->isEmpty())
        <div class="mt-3">
            {{ $slot }}
        </div>
    @endif
</div>
