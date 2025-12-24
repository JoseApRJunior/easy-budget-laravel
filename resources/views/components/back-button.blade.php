@props(['indexRoute', 'defaultIndexRoute' => null, 'label' => 'Voltar'])

@php
$previousUrl = url()->previous();
$indexUrl = route($indexRoute);
$defaultUrl = $defaultIndexRoute ? route($defaultIndexRoute) : $indexUrl;

// Se o URL anterior for outra página de detalhes (show), voltamos para o index para quebrar o loop
// Verificamos se o URL anterior começa com o index e se não é exatamente o index (o que indicaria um show ou sub-página)
$isAnotherShowPage = str_starts_with($previousUrl, $indexUrl) &&
$previousUrl !== $indexUrl &&
!str_contains($previousUrl, '?');

$backUrl = $isAnotherShowPage ? $indexUrl : $previousUrl;
@endphp

<a href="{{ $backUrl ?: $defaultUrl }}" {{ $attributes->merge(['class' => 'btn btn-outline-secondary']) }}>
    <i class="bi bi-arrow-left me-2"></i>{{ $slot->isEmpty() ? $label : $slot }}
</a>
