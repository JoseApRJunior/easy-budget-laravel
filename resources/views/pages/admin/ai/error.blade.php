<x-app-layout title="Erro - Dashboard de IA">
    <div class="container-fluid">
        <!-- Cabeçalho da Página -->
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <h1 class="h3 mb-0 text-gray-800">Dashboard de Inteligência Artificial</h1>
        </div>

        <!-- Card de Erro -->
        <div class="row">
            <div class="col-12">
                <div class="card border-left-danger shadow mb-4">
                    <div class="card-body">
                        <div class="text-center">
                            <i class="fas fa-exclamation-triangle fa-3x text-danger mb-3"></i>
                            <h5 class="text-danger mb-3">{{ $error }}</h5>
                            <p class="text-gray-600">
                                Por favor, tente novamente mais tarde ou entre em contato com o suporte técnico.
                            </p>
                            <div class="mt-4">
                                <a href="{{ url( '/admin/ai' ) }}" class="btn btn-primary">
                                    <i class="fas fa-redo-alt mr-2"></i>
                                    Tentar Novamente
                                </a>
                                <a href="{{ url( '/admin/dashboard' ) }}" class="btn btn-secondary ml-2">
                                    <i class="fas fa-home mr-2"></i>
                                    Voltar ao Dashboard
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
