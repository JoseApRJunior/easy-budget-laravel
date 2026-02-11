@props([
    'plan',
    'availableResources' => [],
    'isPopular' => false
])

<div class="col position-relative">
    <article {{ $attributes->merge(['class' => 'plan-card card h-100 shadow-sm hover-card']) }} aria-label="Plano {{ $plan['name'] }}">
        <div class="card-body d-flex flex-column p-3">
            <div class="plan-card__header text-center mb-3">
                @if( $plan[ 'slug' ] == 'trial' )
                    <i class="bi bi-hourglass-split fs-1 text-primary mb-1"></i>
                @elseif( $plan[ 'slug' ] == 'basic' )
                    <i class="bi bi-rocket fs-1 text-primary mb-1"></i>
                @elseif( $plan[ 'slug' ] == 'pro' )
                    <i class="bi bi-star fs-1 text-success mb-1"></i>
                @else
                    <i class="bi bi-gem fs-1 text-info mb-1"></i>
                @endif
                <h3 class="card-title h5">{{ $plan[ 'name' ] }}</h3>
                <div class="plan-card__price">
                    <span class="plan-card__currency small">R$</span>
                    <span class="plan-card__value fs-3 fw-bold">{{ number_format( $plan[ 'price' ], 2, ',', '.' ) }}</span>
                    <span class="plan-card__period text-muted small">/mês</span>
                </div>
            </div>

            <p class="plan-card__description card-text small text-muted mb-3 text-center">{{ $plan[ 'description' ] }}</p>

            <ul class="plan-card__features feature-list list-unstyled mb-3 small" role="list">
                @foreach( $plan[ 'features' ] as $feature )
                    @php
                        $resource = $availableResources[$feature] ?? null;
                        $configFeature = config("features.{$feature}");

                        if ($resource) {
                            $isVisible = $resource->isVisibleTo(auth()->user());
                            $displayName = $resource->name;
                        } elseif ($configFeature) {
                            $inDev = $configFeature['in_dev'] ?? false;
                            $isVisible = ! $inDev || (auth()->check() && (auth()->user()->hasRole('admin') || (auth()->user()->is_beta ?? false)));
                            $displayName = $configFeature['name'] ?? $feature;
                        } else {
                            $isVisible = false;
                            $displayName = $feature;
                        }
                    @endphp

                    @if ($isVisible)
                        <li class="d-flex align-items-start mb-2">
                            <i class="bi bi-check-circle-fill text-success me-2"></i>
                            <span>{{ $displayName }}</span>
                        </li>
                    @endif
                @endforeach
            </ul>
        </div>
    </article>

    @if($isPopular)
        <div class="position-absolute top-0 start-50 translate-middle" style="z-index: 1000; margin-top: -10px;">
            <span class="badge bg-warning text-dark px-3 py-2 fs-6 fw-bold shadow-lg border">
                <i class="bi bi-star-fill me-1"></i>Mais Popular
            </span>
        </div>
    @endif
</div>
