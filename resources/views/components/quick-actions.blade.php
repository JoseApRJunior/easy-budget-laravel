<div class="card border-0 shadow-sm hover-card mb-4">
    <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">
            <i class="bi bi-lightning-charge me-2"></i>Ações Rápidas
        </h5>
    </div>

    <div class="card-body">
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route( 'provider.budgets.create' ) }}" class="btn btn-primary">
                <i class="bi bi-file-earmark-plus me-1"></i> Criar Novo Orçamento
            </a>

            <a href="{{ route( 'provider.reports.index' ) }}" class="btn btn-info">
                <i class="bi bi-graph-up me-1"></i> Ver Relatórios
            </a>

            <a href="{{ route( 'provider.services.index' ) }}" class="btn btn-outline-success">
                <i class="bi bi-tools me-1"></i> Gerenciar Serviços
            </a>

            <a href="{{ route( 'provider.customers.index' ) }}" class="btn btn-outline-dark">
                <i class="bi bi-people me-1"></i> Clientes
            </a>
        </div>
    </div>
</div>
