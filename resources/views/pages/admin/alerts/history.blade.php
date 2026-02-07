<x-app-layout :title="$pageTitle . ' - Easy Budget'">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-history text-info me-2"></i>
                Histórico de Alertas
            </h1>
            <a href="{{ url('/admin/alerts') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
        </div>

        <div class="card shadow">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Histórico</h6>
            </div>
            <div class="card-body">
                <div class="text-center py-1">
                    <i class="fas fa-history fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">Histórico de Alertas</h5>
                    <p class="text-muted">Nenhum histórico disponível</p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
