@props([
    'id',                        // ID do formulário
    'route' => null,             // Rota do formulário (opcional, padrão: URL atual)
    'method' => 'GET',           // Método HTTP
    'title' => 'Filtros de Busca', // Título do card
    'icon' => 'filter',          // Ícone do título
    'filters' => [],             // Filtros atuais
    'resetRoute' => null,        // Rota para limpar filtros (opcional)
    'showResetButton' => true,   // Mostrar botão de limpar
    'submitLabel' => 'Filtrar',  // Label do botão de submit
    'submitIcon' => 'search',    // Ícone do botão de submit
    'resetLabel' => 'Limpar',    // Label do botão de reset
    'resetIcon' => 'x',          // Ícone do botão de reset
])

@php
    $route = $route ?? request()->url();
    $resetRoute = $resetRoute ?? $route;
@endphp

<div {{ $attributes->merge(['class' => 'card mb-4']) }}>
    <div class="card-header p-0 border-bottom-0">
        <button
            class="btn btn-link w-100 text-start text-decoration-none p-3 d-flex align-items-center justify-content-between"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#collapse{{ $id }}"
            aria-expanded="false"
            aria-controls="collapse{{ $id }}"
        >
            <h5 class="mb-0 d-flex align-items-center text-dark">
                <span class="me-2">
                    <i class="bi bi-{{ $icon }} me-1"></i>
                    {{ $title }}
                </span>
            </h5>
            <i class="bi bi-chevron-down collapse-icon"></i>
        </button>
    </div>

    <form id="{{ $id }}" method="{{ $method }}" action="{{ $route }}">
        @if(strtoupper($method) !== 'GET')
            @csrf
            @method($method)
        @endif

        {{-- Área Colapsável (Campos extras) --}}
        <div id="collapse{{ $id }}" class="collapse d-md-block">
            <div class="card-body pt-0">
                <div class="row g-3">
                    {{ $slot }}
                </div>
            </div>
        </div>

        {{-- Área Sempre Visível (Botões) --}}
        <div class="card-footer bg-transparent pt-3 pb-3">
            <div class="d-flex gap-2">
                <x-ui.button
                    type="submit"
                    variant="primary"
                    :icon="$submitIcon"
                    :label="$submitLabel"
                    class="flex-grow-1"
                    id="btn{{ ucfirst($id) }}"
                />

                @if($showResetButton)
                    <x-ui.button
                        type="link"
                        :href="$resetRoute"
                        variant="outline-secondary"
                        :icon="$resetIcon"
                        :label="$resetLabel"
                    />
                @endif
            </div>
        </div>
    </form>
</div>

<style>
    [data-bs-toggle="collapse"] .collapse-icon {
        transition: transform 0.3s ease;
    }
    [data-bs-toggle="collapse"]:not(.collapsed) .collapse-icon {
        transform: rotate(180deg);
    }
    @media (min-width: 768px) {
        [data-bs-toggle="collapse"] {
            pointer-events: none;
            cursor: default;
        }
        [data-bs-toggle="collapse"] .collapse-icon {
            display: none;
        }
        /* No desktop, removemos o padding extra do topo do body se o header existir */
        .card-body.pt-0 {
            padding-top: 1rem !important;
        }
    }
</style>
