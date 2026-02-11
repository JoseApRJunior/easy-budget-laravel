@extends('layouts.app')

@section('title', 'Escolha seu Plano')

@section('content')
    <x-layout.page-container>
        <x-layout.page-header
            title="Escolha seu Plano"
            icon="gem"
            :breadcrumb-items="[
                'Dashboard' => route('provider.dashboard'),
                'Planos' => '#'
            ]">
            <x-slot:actions>
                @php
                    $pendingPlan = auth()->check() ? auth()->user()->pendingPlan() : null;
                @endphp
                @if ($pendingPlan && $pendingPlan->status == 'pending')
                    <div class="alert alert-warning d-flex align-items-center mb-0 py-2">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <span class="small me-3">Você tem um plano pendente ({{ $pendingPlan->name }}).</span>
                        <div class="d-flex gap-2">
                            <x-ui.button href="/plans/status" variant="warning" size="sm" icon="hourglass-split" label="Ver Status" />
                            <form action="/plans/cancel-pending" method="post" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-outline-dark btn-sm">
                                    <i class="bi bi-x-circle me-1"></i>Cancelar
                                </button>
                            </form>
                        </div>
                    </div>
                @endif
            </x-slot:actions>
        </x-layout.page-header>

        <x-layout.grid-row class="justify-content-center">
            @foreach ($plans as $plan)
                @php
                    $currentPlan = auth()->check() ? auth()->user()->activePlan() : null;
                    $isCurrentPlan = $currentPlan && $currentPlan->slug == $plan->slug;
                    $isPro = $plan->slug == 'pro';
                @endphp

                <div class="col-12 col-md-6 col-lg-4 mb-4 position-relative">
                    @if ($isPro)
                        <div class="position-absolute top-0 start-50 translate-middle" style="z-index: 10; margin-top: -10px;">
                            <span class="badge bg-warning text-dark px-3 py-2 shadow-sm rounded-pill border border-light">
                                <i class="bi bi-star-fill me-1"></i>Mais Popular
                            </span>
                        </div>
                    @endif

                    <div class="card h-100 border-0 shadow-sm hover-shadow transition-all {{ $isPro ? 'border border-primary' : '' }}" style="transition: transform 0.3s ease, box-shadow 0.3s ease;">
                        <div class="card-body d-flex flex-column p-4 text-center">
                            
                            <!-- Icon & Title -->
                            <div class="mb-3">
                                @if ($plan->slug == 'basic' || $plan->slug == 'trial')
                                    <div class="d-inline-flex align-items-center justify-content-center bg-primary bg-opacity-10 rounded-circle p-3 mb-3 text-primary">
                                        <i class="bi bi-rocket display-6"></i>
                                    </div>
                                @elseif ($plan->slug == 'pro')
                                    <div class="d-inline-flex align-items-center justify-content-center bg-success bg-opacity-10 rounded-circle p-3 mb-3 text-success">
                                        <i class="bi bi-star display-6"></i>
                                    </div>
                                @else
                                    <div class="d-inline-flex align-items-center justify-content-center bg-info bg-opacity-10 rounded-circle p-3 mb-3 text-info">
                                        <i class="bi bi-gem display-6"></i>
                                    </div>
                                @endif
                                <h3 class="h4 fw-bold mb-1">{{ $plan->name }}</h3>
                                <p class="text-muted small">{{ $plan->description }}</p>
                            </div>

                            <!-- Price -->
                            <div class="mb-4">
                                <div class="d-flex justify-content-center align-items-baseline">
                                    <span class="h5 text-muted fw-normal me-1">R$</span>
                                    <span class="display-5 fw-bold text-dark">{{ number_format($plan->price, 2, ',', '.') }}</span>
                                    <span class="text-muted ms-1">/mês</span>
                                </div>
                            </div>

                            <hr class="my-4 text-muted opacity-25">

                            <!-- Features -->
                            <ul class="list-unstyled text-start mb-4 flex-grow-1 px-2">
                                {{-- Explicit Limits --}}
                                <li class="mb-3 d-flex align-items-start">
                                    <i class="bi bi-check-circle-fill text-success me-2 mt-1 flex-shrink-0"></i>
                                    <span class="small">Até {{ $plan->max_budgets }} orçamentos</span>
                                </li>
                                <li class="mb-3 d-flex align-items-start">
                                    <i class="bi bi-check-circle-fill text-success me-2 mt-1 flex-shrink-0"></i>
                                    <span class="small">Até {{ $plan->max_clients }} clientes</span>
                                </li>

                                @php
                                    $features = is_array($plan->features) ? $plan->features : json_decode($plan->features ?? '[]', true);
                                @endphp
                                @foreach ($features as $feature)
                                    <li class="mb-3 d-flex align-items-start">
                                        <i class="bi bi-check-circle-fill text-success me-2 mt-1 flex-shrink-0"></i>
                                        <span class="small">
                                            @if(config("features.{$feature}"))
                                                {{ config("features.{$feature}.name") }}
                                            @else
                                                {{ $feature }}
                                            @endif
                                        </span>
                                    </li>
                                @endforeach
                            </ul>

                            <!-- Action Button -->
                            <form action="/plans/pay" method="post" class="mt-auto">
                                @csrf
                                <input type="hidden" name="planSlug" value="{{ $plan->slug }}" required>
                                <div class="d-grid">
                                    @if ($isCurrentPlan)
                                        <button type="button" class="btn btn-lg btn-success" disabled>
                                            <i class="bi bi-check-circle me-2"></i>Plano Atual
                                        </button>
                                    @else
                                        <button type="submit" class="btn btn-lg {{ $isPro ? 'btn-primary' : 'btn-outline-primary' }}">
                                            Escolher Plano
                                        </button>
                                    @endif
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </x-layout.grid-row>

        <!-- Trust Badges -->
        <div class="row justify-content-center mt-4">
            <div class="col-md-8">
                <div class="d-flex justify-content-center flex-wrap gap-4 text-center">
                    <div class="p-3">
                        <i class="bi bi-shield-lock text-success h3 d-block mb-2"></i>
                        <span class="small text-muted fw-bold text-uppercase">Pagamento Seguro</span>
                    </div>
                    <div class="p-3">
                        <i class="bi bi-arrow-counterclockwise text-success h3 d-block mb-2"></i>
                        <span class="small text-muted fw-bold text-uppercase">Cancelamento Fácil</span>
                    </div>
                    <div class="p-3">
                        <i class="bi bi-headset text-success h3 d-block mb-2"></i>
                        <span class="small text-muted fw-bold text-uppercase">Suporte 24/7</span>
                    </div>
                </div>
            </div>
        </div>
    </x-layout.page-container>
@endsection

@push('styles')
<style>
    .hover-shadow:hover {
        transform: translateY(-5px);
        box-shadow: 0 1rem 3rem rgba(0,0,0,.175)!important;
    }
</style>
@endpush
