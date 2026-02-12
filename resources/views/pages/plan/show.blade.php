@extends('layouts.app')

@section('content')
    <div class="container-fluid py-4">
        <x-layout.page-header
            :title="$plan->name"
            icon="gem"
            :breadcrumb-items="[
                'Dashboard' => route('provider.dashboard'),
                'Planos' => route('provider.plans.index'),
                $plan->name => '#'
            ]">
            <p class="text-muted mb-0">{{ $plan->description }}</p>
        </x-layout.page-header>

        <div class="row">
            <!-- Informações Principais -->
            <div class="col-lg-8 mb-4">
                <div class="card shadow-lg border-0 rounded-lg h-100">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-info-circle me-2"></i>Detalhes do Plano
                        </h5>
                    </div>

                    <div class="card-body p-4">
                        <!-- Preço em destaque -->
                        <div class="text-center mb-4">
                            <div class="display-4 fw-bold text-primary mb-2">
                                R$ {{ number_format($plan->price, 2, ',', '.') }}
                            </div>
                            <div class="text-muted">por mês</div>
                        </div>

                        <!-- Limites -->
                        <div class="row text-center mb-4">
                            <div class="col-md-6">
                                <div class="border rounded p-3">
                                    <div class="h3 fw-bold text-primary mb-1">
                                        {{ number_format($plan->max_budgets, 0, ',', '.') }}</div>
                                    <div class="text-muted small">Orçamentos/mês</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="border rounded p-3">
                                    <div class="h3 fw-bold text-success mb-1">
                                        {{ number_format($plan->max_clients, 0, ',', '.') }}</div>
                                    <div class="text-muted small">Clientes</div>
                                </div>
                            </div>
                        </div>

                        <!-- Recursos incluídos -->
                        <div class="mb-4">
                            <h5 class="fw-bold mb-3">
                                <i class="bi bi-check-circle text-success me-2"></i>Recursos Incluídos
                            </h5>
                            <div class="row">
                                @php
                                    $features = is_array($plan->features)
                                        ? $plan->features
                                        : json_decode($plan->features ?? '[]', true);
                                @endphp
                                @foreach ($features as $feature)
                                    @php
                                        $resource = $availableResources[$feature] ?? null;
                                        $configFeature = config("features.{$feature}");

                                        if ($resource) {
                                            $isVisible = $resource->isVisibleTo(auth()->user());
                                        } elseif ($configFeature) {
                                            $inDev = $configFeature['in_dev'] ?? false;
                                            $isVisible = ! $inDev || (auth()->check() && (auth()->user()->hasRole('admin') || (auth()->user()->is_beta ?? false)));
                                        } else {
                                            $isVisible = false;
                                        }
                                    @endphp

                                    @if ($isVisible)
                                        <div class="col-md-6 mb-2">
                                            <div class="d-flex align-items-center">
                                                <i class="bi bi-check-lg text-success me-2"></i>
                                                <span class="small">
                                                    @if(config("features.{$feature}"))
                                                        {{ config("features.{$feature}.name") }}
                                                    @else
                                                        {{ $feature }}
                                                    @endif
                                                </span>
                                            </div>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>

                        <!-- Informações técnicas -->
                        <div class="border-top pt-3">
                            <div class="row text-muted small">
                                <div class="col-md-6">
                                    <strong>Slug:</strong> {{ $plan->slug }}
                                </div>
                                <div class="col-md-6">
                                    <strong>ID:</strong> #{{ $plan->id }}
                                </div>
                                <div class="col-md-6">
                                    <strong>Criado em:</strong> {{ $plan->created_at->format('d/m/Y H:i') }}
                                </div>
                                <div class="col-md-6">
                                    <strong>Atualizado em:</strong> {{ $plan->updated_at->format('d/m/Y H:i') }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ações e Estatísticas -->
            <div class="col-lg-4">
                <!-- Card de Ações -->
                <div class="card shadow-lg border-0 rounded-lg mb-4">
                    <div class="card-header bg-light">
                        <h6 class="card-title mb-0">
                            <i class="bi bi-gear me-2"></i>Ações
                        </h6>
                    </div>

                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="{{ route('plans.edit', $plan->slug) }}" class="btn btn-warning">
                                <i class="bi bi-pencil-square me-2"></i>Editar Plano
                            </a>

                            @if ($plan->status)
                                <form action="{{ route('plans.deactivate', $plan->slug) }}" method="POST"
                                    class="d-grid">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-outline-secondary"
                                        onclick="return confirm('Tem certeza que deseja desativar este plano?')">
                                        <i class="bi bi-pause-circle me-2"></i>Desativar Plano
                                    </button>
                                </form>
                            @else
                                <form action="{{ route('plans.activate', $plan->slug) }}" method="POST" class="d-grid">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="btn btn-success">
                                        <i class="bi bi-play-circle me-2"></i>Ativar Plano
                                    </button>
                                </form>
                            @endif

                            <form action="{{ route('plans.destroy', $plan->slug) }}" method="POST" class="d-grid">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger"
                                    onclick="return confirm('Tem certeza que deseja excluir este plano? Esta ação não pode ser desfeita.')">
                                    <i class="bi bi-trash me-2"></i>Excluir Plano
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Card de Estatísticas -->
                <div class="card shadow-lg border-0 rounded-lg">
                    <div class="card-header bg-info text-white">
                        <h6 class="card-title mb-0">
                            <i class="bi bi-bar-chart me-2"></i>Estatísticas
                        </h6>
                    </div>

                    <div class="card-body">
                        <div class="text-center">
                            <div class="h4 mb-1">{{ $plan->planSubscriptions()->count() }}</div>
                            <div class="text-muted small">Assinaturas Ativas</div>
                        </div>

                        <hr>

                        <div class="text-center">
                            <div class="h4 mb-1">{{ $plan->planSubscriptions()->where('status', 'active')->count() }}
                            </div>
                            <div class="text-muted small">Assinaturas Totais</div>
                        </div>
                    </div>
                </div>

                <!-- Botão voltar -->
                <div class="text-center mt-3">
                    <x-ui.button
                        href="{{ route('plans.index') }}"
                        variant="outline-primary"
                        icon="bi bi-arrow-left"
                        feature="plans">
                        Voltar para Lista
                    </x-ui.button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            // Confirmação para ações destrutivas
            document.querySelectorAll('form[action*="destroy"], form[action*="deactivate"]').forEach(form => {
                form.addEventListener('submit', function(e) {
                    const action = this.action.includes('destroy') ? 'excluir' : 'desativar';
                    const confirmed = confirm(`Tem certeza que deseja ${action} este plano?`);

                    if (!confirmed) {
                        e.preventDefault();
                    }
                });
            });
        </script>
    @endpush
@endsection
