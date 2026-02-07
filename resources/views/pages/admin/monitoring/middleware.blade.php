<x-app-layout :title="$pageTitle . ' - Easy Budget'">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-chart-line text-primary me-2"></i>
                {{ $pageTitle }}
            </h1>
            <a href="{{ url('/admin/monitoring') }}" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
        </div>

        <div class="card shadow">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Detalhes do Middleware</h6>
            </div>
            <div class="card-body">
                <div class="text-center py-1">
                    <i class="fas fa-info-circle fa-3x text-info mb-3"></i>
                    <h5 class="text-muted">Métricas específicas para {{ $middlewareName }}</h5>
                    <p class="text-muted">Dados detalhados em desenvolvimento</p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
