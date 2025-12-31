<div class="card border-0 shadow-sm hover-card mb-4">
    <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
        <h5 class="card-title mb-0">
            <i class="bi bi-lightning-charge me-2"></i>Ações Rápidas
        </h5>
    </div>

    <div class="card-body">
        <div class="d-flex flex-wrap gap-2">
            <x-button type="link" :href="route( 'provider.budgets.create' )" variant="primary" icon="file-earmark-plus" label="Criar Novo Orçamento" />
            <x-button type="link" :href="route( 'provider.reports.index' )" variant="info" icon="graph-up" label="Ver Relatórios" />
            <x-button type="link" :href="route( 'provider.services.index' )" variant="success" outline icon="tools" label="Gerenciar Serviços" />
            <x-button type="link" :href="route( 'provider.customers.index' )" variant="dark" outline icon="people" label="Clientes" />
        </div>
    </div>
</div>
