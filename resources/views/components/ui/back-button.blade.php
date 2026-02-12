@props(['indexRoute' => null, 'defaultIndexRoute' => null, 'label' => 'Voltar', 'routeParams' => [], 'feature' => null])

@php
// Se feature for informada e o usuário não tiver acesso, não renderiza nada
if ($feature && !Gate::check($feature)) {
    return;
}

$previousUrl = url()->previous();
$indexUrl = $indexRoute ? route($indexRoute, $routeParams) : $previousUrl;
$defaultUrl = $defaultIndexRoute ? route($defaultIndexRoute, $routeParams) : $indexUrl;

// Se o URL anterior for outra página de detalhes (show), voltamos para o index para quebrar o loop
// Verificamos se o URL anterior começa com o index e se não é exatamente o index (o que indicaria um show ou sub-página)
$isAnotherShowPage = $indexRoute && str_starts_with($previousUrl, $indexUrl) &&
$previousUrl !== $indexUrl &&
!str_contains($previousUrl, '?');

$backUrl = $isAnotherShowPage ? $indexUrl : $previousUrl;
@endphp

<a href="{{ $backUrl ?: $defaultUrl }}" {{ $attributes->merge(['class' => 'btn btn-outline-secondary d-inline-flex align-items-center justify-content-center']) }}>
    <i class="bi bi-arrow-left me-2"></i>{{ $slot->isEmpty() ? $label : $slot }}
</a>
