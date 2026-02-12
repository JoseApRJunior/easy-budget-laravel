@props([
    'resource',                  // Nome do recurso (categories, products, etc.)
    'exportFormats' => ['xlsx', 'pdf'], // Formatos de exportação disponíveis
    'filters' => [],             // Filtros atuais para passar na exportação
    'createRoute' => null,       // Rota customizada para criar (opcional)
    'exportRoute' => null,       // Rota customizada para exportar (opcional)
    'createLabel' => 'Novo',     // Label do botão criar
    'size' => 'sm',              // Tamanho dos botões
    'showExport' => true,        // Mostrar botão de exportação
    'showCreate' => true,        // Mostrar botão de criar
    'feature' => null,           // Feature flag para controle de acesso (opcional)
])

@php
    // Se feature não for informada, tenta usar o resource.
    // Se o resource contiver ponto (ex: inventory.movements), pega a primeira parte (inventory)
    $effectiveFeature = $feature ?? (str_contains($resource, '.') ? explode('.', $resource)[0] : $resource);

    // Mapear ícones e estilos por formato
    $formatConfig = [
        'xlsx' => [
            'icon' => 'bi-file-earmark-excel',
            'color' => 'text-success',
            'label' => 'Excel (.xlsx)'
        ],
        'pdf' => [
            'icon' => 'bi-file-earmark-pdf',
            'color' => 'text-danger',
            'label' => 'PDF (.pdf)'
        ],
        'csv' => [
            'icon' => 'bi-file-earmark-text',
            'color' => 'text-info',
            'label' => 'CSV (.csv)'
        ],
    ];
@endphp

<div {{ $attributes->merge(['class' => 'col-12 col-lg-4 mt-2 mt-lg-0']) }}>
    <div class="d-flex justify-content-start justify-content-lg-end gap-2">
        @if ($showExport)
            <div class="dropdown">
                <x-ui.button
                    variant="outline-secondary"
                    :size="$size"
                    icon="download"
                    label="Exportar"
                    class="dropdown-toggle"
                    data-bs-toggle="dropdown"
                    aria-expanded="false"
                    id="exportDropdown{{ ucfirst($resource) }}"
                    :feature="$effectiveFeature"
                />
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="exportDropdown{{ ucfirst($resource) }}">
                    @foreach($exportFormats as $format)
                        @if(isset($formatConfig[$format]))
                            @php
                                $routeName = $exportRoute ?? 'provider.' . $resource . '.export';
                                $resolvedExportUrl = Route::has($routeName)
                                    ? route($routeName, array_merge(collect($filters)->map(fn($v) => is_null($v) ? '' : $v)->toArray(), ['format' => $format]))
                                    : '#';
                            @endphp
                            <li>
                                <a class="dropdown-item" href="{{ $resolvedExportUrl }}">
                                    <i class="bi {{ $formatConfig[$format]['icon'] }} me-2 {{ $formatConfig[$format]['color'] }}"></i>
                                    {{ $formatConfig[$format]['label'] }}
                                </a>
                            </li>
                        @endif
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($showCreate)
            @php
                $resolvedCreateRoute = $createRoute ?? (Route::has('provider.' . $resource . '.create') ? route('provider.' . $resource . '.create') : '#');
            @endphp
            <x-ui.button
                type="link"
                :href="$resolvedCreateRoute"
                :size="$size"
                icon="plus"
                :label="$createLabel"
                :feature="$effectiveFeature"
            />
        @endif

        {{-- Slot para ações customizadas adicionais --}}
        {{ $slot }}
    </div>
</div>
