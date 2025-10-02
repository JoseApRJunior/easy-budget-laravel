@props( [ 'featureSlug', 'condition' => true ] )

@php
    // TODO: A lógica para buscar o recurso e verificar seu status precisa ser implementada.
    // As funções originais do Twig 'getResource' e 'entityProperty' precisam ser portadas para PHP/Laravel.

    // Exemplo de como a lógica poderia ser implementada (assumindo um Helper):
    // $resource = \App\Helpers\FeatureHelper::getResource($featureSlug);
    // $isInactive = $resource && $resource->status === 'inactive';

    // Usando um valor placeholder por enquanto
    $isInactive = false;
@endphp

@if ( $condition && $isInactive )
    <div class="alert alert-warning m-2 d-flex" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <div>
            Recurso desativado temporariamente
        </div>
    </div>
@endif

<div class="feature-content @if ( $condition && $isInactive ) feature-disabled @endif">
    {{ $slot }}
</div>
