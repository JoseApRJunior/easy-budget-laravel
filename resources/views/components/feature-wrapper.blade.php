@props( [ 'featureSlug', 'condition' => true ] )

@php
    // Supondo que exista um serviço para verificar o status do recurso
    // Em um cenário real, isso seria injetado ou acessado de outra forma
    $resource   = app( \App\Services\FeatureService::class)->getResource( $featureSlug );
    $isInactive = $condition && $resource && $resource->status === 'inactive';
@endphp

@if ( $isInactive )
    <div class="alert alert-warning m-2 d-flex" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <div>
            Recurso desativado temporariamente
        </div>
    </div>
@endif

<div class="feature-content {{ $isInactive ? 'feature-disabled' : '' }}">
    {{ $slot }}
</div>
