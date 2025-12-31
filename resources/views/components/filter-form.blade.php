@props([
    'id',                        // ID do formulário
    'route',                     // Rota do formulário
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
    $resetRoute = $resetRoute ?? $route;
@endphp

<div {{ $attributes->merge(['class' => 'card mb-4']) }}>
    <div class="card-header">
        <h5 class="mb-0">
            <i class="bi bi-{{ $icon }} me-1"></i> {{ $title }}
        </h5>
    </div>
    <div class="card-body">
        <form id="{{ $id }}" method="{{ $method }}" action="{{ $route }}">
            @if(strtoupper($method) !== 'GET')
                @csrf
                @method($method)
            @endif

            <div class="row g-3">
                {{-- Slot para os campos do formulário --}}
                {{ $slot }}

                {{-- Botões de ação --}}
                <div class="col-12">
                    <div class="d-flex gap-2">
                        <x-button
                            type="submit"
                            variant="primary"
                            :icon="$submitIcon"
                            :label="$submitLabel"
                            class="flex-grow-1"
                            id="btn{{ ucfirst($id) }}"
                        />

                        @if($showResetButton)
                            <x-button
                                type="link"
                                :href="$resetRoute"
                                variant="outline-secondary"
                                :icon="$resetIcon"
                                :label="$resetLabel"
                            />
                        @endif
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
