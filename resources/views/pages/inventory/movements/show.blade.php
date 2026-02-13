@extends('layouts.app')

@section('title', 'Detalhes da Movimentação #' . $movement->id)

@section('content')
<div class="container-fluid py-4">
    <x-layout.page-header
        title="Detalhes da Movimentação"
        icon="arrow-left-right"
        :breadcrumb-items="[
            'Dashboard' => route('provider.dashboard'),
            'Inventário' => route('provider.inventory.dashboard'),
            'Movimentações' => route('provider.inventory.movements'),
            'Detalhes #' . $movement->id => '#'
        ]"
    >
        <p class="text-muted small">Informações detalhadas da movimentação #{{ $movement->id }}</p>
    </x-layout.page-header>

    <div class="row mt-4">
        <!-- Resumo da Movimentação -->
        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">Resumo da Operação</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="text-muted d-block small fw-bold text-uppercase">Tipo</label>
                        <span class="badge {{ $movement->type === 'entry' ? 'bg-success' : 'bg-danger' }} fs-6">
                            {{ $movement->type === 'entry' ? 'Entrada' : 'Saída' }}
                        </span>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted d-block small fw-bold text-uppercase">Quantidade</label>
                        <span class="h4 mb-0">{{ number_format($movement->quantity, 2, ',', '.') }}</span>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted d-block small fw-bold text-uppercase">Data e Hora</label>
                        <span>{{ $movement->created_at->format('d/m/Y H:i:s') }}</span>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted d-block small fw-bold text-uppercase">Responsável</label>
                        <span>{{ $user->name ?? 'N/A' }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detalhes do Produto -->
        <div class="col-md-8">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-info text-white">
                    <h5 class="card-title mb-0">Informações do Produto</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="text-muted d-block small fw-bold text-uppercase">Nome</label>
                            <p class="fw-bold">{{ $product->name }}</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted d-block small fw-bold text-uppercase">SKU / Código</label>
                            <p>{{ $product->sku }}</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted d-block small fw-bold text-uppercase">Estoque Atual</label>
                            <p>{{ number_format($product->inventory->quantity ?? 0, 2, ',', '.') }} {{ $product->unit ?? 'un' }}</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted d-block small fw-bold text-uppercase">Localização</label>
                            <p>{{ $product->inventory->location ?? 'Não informada' }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Informações Adicionais -->
        <div class="col-12 mt-4">
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">Informações Adicionais</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="text-muted d-block small fw-bold text-uppercase">Motivo / Observação</label>
                            <p class="mb-0">{{ $movement->reason ?: 'Nenhuma observação informada.' }}</p>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="text-muted d-block small fw-bold text-uppercase">Documento / Referência</label>
                            <p class="mb-0">{{ $movement->reference ?: 'Sem documento de referência.' }}</p>
                        </div>
                    </div>

                    @if($movement->metadata && count($movement->metadata) > 0)
                        <hr>
                        <div class="mt-3">
                            <label class="text-muted d-block small fw-bold text-uppercase mb-2">Dados Técnicos (JSON)</label>
                            <pre class="bg-light p-3 rounded"><code>{{ json_encode($movement->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</code></pre>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Footer Actions -->
    <div class="mt-4 pb-2">
        <div class="row align-items-center g-3">
            <div class="col-12 col-md-auto order-2 order-md-1">
                <x-ui.back-button index-route="provider.inventory.movements" class="w-100 w-md-auto px-md-3" />
            </div>

            <div class="col-12 col-md text-center d-none d-md-block order-md-2">
                <small class="text-muted">
                    Movimentação registrada em: {{ $movement->created_at->format('d/m/Y H:i') }}
                </small>
            </div>

            <div class="col-12 col-md-auto order-1 order-md-3">
                <div class="d-grid d-md-flex gap-2">
                    <x-ui.button type="link" :href="route('provider.inventory.show', $product->sku)" variant="info" icon="eye" label="Ver Inventário" style="min-width: 120px;" feature="inventory" />
                    <x-ui.button type="link" :href="route('provider.inventory.entry', $product->sku)" variant="success" icon="arrow-down-circle" label="Entrada" style="min-width: 120px;" feature="inventory" />
                    <x-ui.button type="link" :href="route('provider.inventory.exit', $product->sku)" variant="warning" icon="arrow-up-circle" label="Saída" style="min-width: 120px;" feature="inventory" />
                    <x-ui.button type="link" :href="route('provider.inventory.adjust', $product->sku)" variant="secondary" icon="sliders" label="Ajustar" style="min-width: 120px;" feature="inventory" />
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
