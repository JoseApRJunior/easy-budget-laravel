<?php

declare(strict_types=1);

namespace App\DesignPatterns\Views;

/**
 * Templates Práticos para Views
 *
 * Fornece templates prontos para uso imediato no desenvolvimento,
 * seguindo o padrão unificado definido em ViewPattern.
 */
class ViewTemplates
{
    /**
     * TEMPLATE COMPLETO - View Nível 1 (Básica)
     */
    public function basicViewTemplate(): string
    {
        return '
@extends(\'layouts.app\')

@section(\'content\')
<main class="container py-5">
    <!-- Cabeçalho da página -->
    <div class="text-center mb-5">
        <h1 class="display-5 fw-bold text-primary mb-3">@yield(\'title\', \'Título da Página\')</h1>
        <p class="lead text-muted">@yield(\'description\', \'Descrição da página\')</p>
    </div>

    <!-- Conteúdo principal -->
    <div class="row g-4">
        <div class="col-lg-8 mx-auto">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-5">
                    @yield(\'page-content\')
                </div>
            </div>
        </div>
    </div>

    <!-- Call to Action -->
    @yield(\'cta-section\')
</main>
@endsection

@push(\'styles\')
<style>
    /* Estilos específicos da página */
    .hero-section {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 1rem;
        padding: 4rem 0;
    }
</style>
@endpush

@push(\'scripts\')
<script>
    // Scripts específicos da página
    document.addEventListener(\'DOMContentLoaded\', function() {
        // Inicialização básica
    });
</script>
@endpush';
    }

    /**
     * TEMPLATE COMPLETO - View Nível 2 (Com Formulário)
     */
    public function formViewTemplate(): string
    {
        return '
@extends(\'layouts.app\')

@section(\'content\')
<div class="container py-1">
    <!-- Cabeçalho -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="bi bi-plus-circle me-2"></i>@yield(\'title\', \'Novo Registro\')
            </h1>
            <p class="text-muted mb-0">@yield(\'subtitle\', \'Cadastre um novo registro\')</p>
        </div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="@yield(\'dashboard-route\', \'#\')">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="@yield(\'index-route\', \'#\')">@yield(\'module-name\', \'Módulo\')</a></li>
                <li class="breadcrumb-item active">@yield(\'action-name\', \'Novo\')</li>
            </ol>
        </nav>
    </div>

    <!-- Formulário -->
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-5">
                    <form method="POST" action="@yield(\'action\', \'#\')" id="mainForm">
                        @csrf
                        @method(@yield(\'method\', \'POST\'))

                        <!-- Seções do formulário -->
                        @yield(\'form-sections\')

                        <!-- Botões de ação -->
                        <div class="d-flex justify-content-between pt-4 border-top">
                            <a href="@yield(\'back-url\', \'javascript:history.back()\')"
                               class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-2"></i>Voltar
                            </a>
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-check-lg me-2"></i>@yield(\'submit-text\', \'Salvar\')
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push(\'styles\')
<style>
    /* Estilos do formulário */
    .form-section {
        background: #f8f9fa;
        border-radius: 0.5rem;
        padding: 1.5rem;
        margin-bottom: 2rem;
    }

    .section-title {
        color: #495057;
        border-bottom: 2px solid #dee2e6;
        padding-bottom: 0.5rem;
        margin-bottom: 1rem;
    }

    .form-label {
        font-weight: 600;
        color: #495057;
    }

    .is-invalid {
        border-color: #dc3545;
        box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
    }

    .invalid-feedback {
        display: block;
    }
</style>
@endpush

@push(\'scripts\')
<script>
    // Funcionalidades do formulário
    document.addEventListener(\'DOMContentLoaded\', function() {
        const form = document.getElementById(\'mainForm\');

        if (form) {
            // Máscaras de entrada
            setupInputMasks();

            // Validação em tempo real
            setupRealTimeValidation();

            // Submit com indicador de loading
            form.addEventListener(\'submit\', function(e) {
                const submitBtn = form.querySelector(\'button[type="submit"]\');
                if (submitBtn) {
                    submitBtn.innerHTML = \'<i class="bi bi-hourglass-split me-2"></i>Salvando...\';
                    submitBtn.disabled = true;
                }
            });
        }

        function setupInputMasks() {
            // Máscara de telefone
            const phoneInputs = document.querySelectorAll(\'input[type="tel"]\');
            phoneInputs.forEach(input => {
                input.addEventListener(\'input\', function(e) {
                    let value = e.target.value.replace(/\D/g, \'\');
                    if (value.length <= 11) {
                        value = value.replace(/(\d{2})(\d{5})(\d{4})/, \'($1) $2-$3\');
                        e.target.value = value;
                    }
                });
            });

            // Máscara de CPF/CNPJ
            const documentInputs = document.querySelectorAll(\'input[name="cpf"], input[name="cnpj"]\');
            documentInputs.forEach(input => {
                input.addEventListener(\'input\', function(e) {
                    let value = e.target.value.replace(/\D/g, \'\');
                    if (e.target.name === \'cpf\' && value.length <= 11) {
                        value = value.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, \'$1.$2.$3-$4\');
                    } else if (e.target.name === \'cnpj\' && value.length <= 14) {
                        value = value.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, \'$1.$2.$3/$4-$5\');
                    }
                    e.target.value = value;
                });
            });
        }

        function setupRealTimeValidation() {
            // Validação básica em tempo real
            const requiredInputs = form.querySelectorAll(\'input[required], select[required], textarea[required]\');

            requiredInputs.forEach(input => {
                input.addEventListener(\'blur\', function() {
                    if (this.value.trim() === \'\') {
                        this.classList.add(\'is-invalid\');
                    } else {
                        this.classList.remove(\'is-invalid\');
                    }
                });
            });
        }
    });
</script>
@endpush';
    }

    /**
     * TEMPLATE COMPLETO - View Nível 3 (Avançada)
     */
    public function advancedViewTemplate(): string
    {
        return '
@extends(\'layouts.app\')

@section(\'content\')
<div class="container-fluid py-1">
    <!-- Cabeçalho -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="bi bi-table me-2"></i>@yield(\'title\', \'Listagem Avançada\')
            </h1>
            <p class="text-muted mb-0">@yield(\'subtitle\', \'Gerencie os registros com filtros avançados\')</p>
        </div>
        <div class="d-flex gap-2">
            @yield(\'header-actions\')
        </div>
    </div>

    <!-- Cards de Estatísticas -->
    @yield(\'stats-cards\')

    <!-- Filtros Avançados -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <form method="GET" class="row g-3" id="filterForm">
                @yield(\'filters\')

                <div class="col-12">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex gap-2">
                            @yield(\'filter-actions\')
                        </div>
                        <button type="button" class="btn btn-outline-secondary" id="clearFilters">
                            <i class="bi bi-x-circle me-2"></i>Limpar Filtros
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Estados da Interface -->
    <div id="initial-state" class="card border-0 shadow-sm text-center py-5 @yield(\'initial-state-class\', \'\')">
        <div class="card-body">
            <i class="bi bi-search text-primary mb-3" style="font-size: 3rem;"></i>
            <h5 class="text-muted mb-3">@yield(\'initial-message\', \'Use os filtros acima para buscar\')</h5>
            <p class="text-muted mb-0">@yield(\'initial-description\', \'Digite os critérios de busca e clique em filtrar\')</p>
        </div>
    </div>

    <!-- Loading State -->
    <div id="loading-state" class="d-none">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <div class="spinner-border text-primary mb-3" role="status">
                    <span class="visually-hidden">Carregando...</span>
                </div>
                <p class="text-muted mb-0">Processando sua solicitação...</p>
            </div>
        </div>
    </div>

    <!-- Resultados -->
    <div id="results-container" class="d-none">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-transparent border-0">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="bi bi-list me-2"></i>@yield(\'results-title\', \'Resultados\')
                    </h5>
                    <div class="d-flex align-items-center gap-3">
                        <span id="results-count" class="text-muted"></span>
                        <div class="dropdown">
                            <button class="btn btn-outline-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown">
                                <i class="bi bi-gear me-1"></i>Ações
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                @yield(\'result-actions\')
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            @yield(\'table-header\')
                        </thead>
                        <tbody id="results-body">
                            @yield(\'table-body\')
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Paginação -->
            <div class="card-footer bg-transparent border-0">
                @yield(\'pagination\')
            </div>
        </div>
    </div>

    <!-- Estados de Erro -->
    <div id="error-state" class="d-none">
        <div class="card border-0 shadow-sm border-danger">
            <div class="card-body text-center py-5">
                <i class="bi bi-exclamation-triangle text-danger mb-3" style="font-size: 3rem;"></i>
                <h5 class="text-danger mb-3">Erro ao carregar dados</h5>
                <p class="text-muted mb-4" id="error-message"></p>
                <button class="btn btn-danger" onclick="location.reload()">
                    <i class="bi bi-arrow-clockwise me-2"></i>Tentar Novamente
                </button>
            </div>
        </div>
    </div>

    <!-- Estados Vazios -->
    <div id="empty-state" class="d-none">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <i class="bi bi-inbox text-muted mb-3" style="font-size: 3rem;"></i>
                <h5 class="text-muted mb-3">Nenhum resultado encontrado</h5>
                <p class="text-muted mb-0">Tente ajustar os filtros ou cadastre novos registros</p>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Confirmação -->
@yield(\'confirmation-modal\')
@endsection

@push(\'styles\')
<style>
    /* Estilos da listagem avançada */
    .table th {
        border-top: none;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.875rem;
        letter-spacing: 0.05em;
        background-color: #f8f9fa;
    }

    .table td {
        vertical-align: middle;
    }

    .avatar-sm {
        width: 2.5rem;
        height: 2.5rem;
    }

    .avatar-title {
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        border-radius: 50%;
    }

    .hover-shadow:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15) !important;
    }

    .fade-in {
        animation: fadeIn 0.5s ease-in;
    }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* Estados da interface */
    .state-card {
        transition: all 0.3s ease;
    }

    /* Filtros */
    .filter-card {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border: 1px solid #dee2e6;
    }

    /* Cards de estatísticas */
    .stat-card {
        background: linear-gradient(135deg, #fff 0%, #f8f9fa 100%);
        border: none;
        transition: transform 0.2s ease;
    }

    .stat-card:hover {
        transform: translateY(-2px);
    }

    .stat-icon {
        width: 3rem;
        height: 3rem;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
    }
</style>
@endpush

@push(\'scripts\')
<script>
    // Classe principal para listagem avançada
    class AdvancedListing {
        constructor() {
            this.initializeComponents();
            this.bindEvents();
            this.setupRealTimeFeatures();
        }

        initializeComponents() {
            this.filterForm = document.getElementById(\'filterForm\');
            this.searchInputs = document.querySelectorAll(\'input[type="search"], input[type="text"]\');
            this.selectFilters = document.querySelectorAll(\'select\');
            this.clearFiltersBtn = document.getElementById(\'clearFilters\');
        }

        bindEvents() {
            // Busca em tempo real com debounce
            this.searchInputs.forEach(input => {
                let searchTimeout;
                input.addEventListener(\'input\', (e) => {
                    clearTimeout(searchTimeout);
                    searchTimeout = setTimeout(() => {
                        this.performSearch();
                    }, 500);
                });
            });

            // Filtros de mudança imediata
            this.selectFilters.forEach(select => {
                select.addEventListener(\'change\', () => {
                    this.performSearch();
                });
            });

            // Limpar filtros
            if (this.clearFiltersBtn) {
                this.clearFiltersBtn.addEventListener(\'click\', () => {
                    this.clearFilters();
                });
            }

            // Submit do formulário
            if (this.filterForm) {
                this.filterForm.addEventListener(\'submit\', (e) => {
                    e.preventDefault();
                    this.performSearch();
                });
            }
        }

        async performSearch() {
            if (!this.filterForm) return;

            const formData = new FormData(this.filterForm);
            const filters = {};

            for (let [key, value] of formData.entries()) {
                if (value.trim() !== \'\') {
                    filters[key] = value;
                }
            }

            this.showLoading();

            try {
                const response = await fetch(window.location.pathname + \'?\' + new URLSearchParams(filters));
                const data = await response.text();

                this.showResults(data);
            } catch (error) {
                console.error(\'Erro na busca:\', error);
                this.showError(\'Erro ao buscar dados. Tente novamente.\');
            }
        }

        showLoading() {
            this.hideAllStates();
            document.getElementById(\'loading-state\').classList.remove(\'d-none\');
        }

        showResults(data) {
            this.hideAllStates();
            document.getElementById(\'results-container\').classList.remove(\'d-none\');

            // Atualizar contador se necessário
            this.updateResultsCount();
        }

        showError(message) {
            this.hideAllStates();
            document.getElementById(\'error-state\').classList.remove(\'d-none\');
            document.getElementById(\'error-message\').textContent = message;
        }

        showEmpty() {
            this.hideAllStates();
            document.getElementById(\'empty-state\').classList.remove(\'d-none\');
        }

        hideAllStates() {
            const states = [\'initial-state\', \'loading-state\', \'results-container\', \'error-state\', \'empty-state\'];
            states.forEach(stateId => {
                const element = document.getElementById(stateId);
                if (element) {
                    element.classList.add(\'d-none\');
                }
            });
        }

        clearFilters() {
            if (this.filterForm) {
                this.filterForm.reset();
                this.performSearch();
            }
        }

        updateResultsCount() {
            const resultsBody = document.getElementById(\'results-body\');
            if (resultsBody) {
                const count = resultsBody.children.length;
                const countElement = document.getElementById(\'results-count\');
                if (countElement) {
                    countElement.textContent = `${count} resultado${count !== 1 ? \'s\' : \'\'}`;
                }
            }
        }

        setupRealTimeFeatures() {
            // Atualizações em tempo real
            if (typeof Echo !== \'undefined\') {
                Echo.channel(\'updates\')
                    .listen(\'.data.updated\', (e) => {
                        if (e.module === \'@yield(\'module-name\', \'module\')\') {
                            this.refreshData();
                        }
                    });
            }

            // Refresh automático a cada 30 segundos
            setInterval(() => {
                this.refreshStats();
            }, 30000);
        }

        async refreshStats() {
            try {
                const response = await fetch(\'{{ route(\\"@yield(\\"module-name\\", \\"module\\").stats\\") }}\');
                const stats = await response.json();

                this.updateStatsCards(stats);
            } catch (error) {
                console.error(\'Erro ao atualizar estatísticas:\', error);
            }
        }

        updateStatsCards(stats) {
            // Atualização dinâmica das estatísticas
            Object.keys(stats).forEach(key => {
                const element = document.querySelector(`[data-stat="${key}"]`);
                if (element) {
                    element.textContent = stats[key];
                }
            });
        }

        refreshData() {
            // Refresh dos dados atuais
            this.performSearch();
        }
    }

    // Inicializar quando DOM estiver pronto
    document.addEventListener(\'DOMContentLoaded\', function() {
        window.advancedListing = new AdvancedListing();
    });

    // Funções globais para ações comuns
    window.confirmDelete = function(id, name) {
        const modal = document.getElementById(\'deleteModal\');
        const form = document.getElementById(\'deleteForm\');
        const message = document.getElementById(\'deleteMessage\');

        if (form) {
            form.action = `/{{ request()->segment(2) }}/${id}`;
        }

        if (message) {
            message.textContent = `Tem certeza que deseja excluir "${name}"?`;
        }

        if (modal) {
            const bsModal = new bootstrap.Modal(modal);
            bsModal.show();
        }
    };

    window.exportResults = function(format = \'excel\') {
        const filters = new URLSearchParams(new FormData(document.getElementById(\'filterForm\')));
        filters.append(\'export\', format);

        window.open(`/{{ request()->segment(2) }}/export?${filters.toString()}`, \'_blank\');
    };
</script>
@endpush';
    }

    /**
     * GUIA DE UTILIZAÇÃO DOS TEMPLATES
     */
    public function getUsageGuide(): string
    {
        return '
## Como Usar os Templates de Views

### 1. Escolha o Nível Correto

**Nível 1 (Básica):**
- Para páginas estáticas ou com pouco conteúdo
- Sobre, termos de uso, política de privacidade
- Landing pages simples
- Páginas de erro personalizadas

**Nível 2 (Com Formulário):**
- Para páginas de criação/edição
- Formulários com validação
- Cadastro de entidades simples
- Configurações básicas

**Nível 3 (Avançada):**
- Para listagens com filtros e busca
- Dashboards com estatísticas
- Interfaces com AJAX pesado
- Múltiplos estados (inicial, loading, resultados, erro)

### 2. Personalize os Placeholders

**Substitua os placeholders @yield:**
- `@yield(\'title\')` → Título da página
- `@yield(\'subtitle\')` → Subtítulo explicativo
- `@yield(\'action\')` → URL do formulário
- `@yield(\'form-fields\')` → Campos específicos do formulário

### 3. Implemente Seções Específicas

**Para formulários (Nível 2):**
```php
@section(\'form-sections\')
    <!-- Dados Pessoais -->
    <div class="form-section">
        <h5 class="section-title">
            <i class="bi bi-person me-2"></i>Dados Pessoais
        </h5>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label for="first_name" class="form-label">Nome *</label>
                <input type="text"
                       class="form-control @error(\'first_name\') is-invalid @enderror"
                       id="first_name"
                       name="first_name"
                       value="{{ old(\'first_name\') }}"
                       required>
                @error(\'first_name\')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>
@endsection
```

**Para filtros avançados (Nível 3):**
```php
@section(\'filters\')
    <div class="col-md-3">
        <label for="search" class="form-label">Buscar</label>
        <input type="text"
               class="form-control"
               id="search"
               name="search"
               value="{{ request(\'search\') }}"
               placeholder="Nome, email, CPF...">
    </div>

    <div class="col-md-2">
        <label for="status" class="form-label">Status</label>
        <select class="form-select" id="status" name="status">
            <option value="">Todos</option>
            <option value="active" {{ request(\'status\') === \'active\' ? \'selected\' : \'\' }}>Ativo</option>
        </select>
    </div>
@endsection
```

### 4. Implemente Estados Específicos

**Estados obrigatórios para views avançadas:**
```php
@section(\'initial-state-class\')
    {{ $data->isEmpty() ? \'d-none\' : \'\' }}
@endsection

@section(\'results-title\')
    Clientes Encontrados
@endsection

@section(\'table-header\')
    <tr>
        <th>Nome</th>
        <th>Email</th>
        <th>Status</th>
        <th>Ações</th>
    </tr>
@endsection
```

### 5. Configure Ações do Cabeçalho

**Para ações no cabeçalho:**
```php
@section(\'header-actions\')
    <a href="{{ route(\'{module}.create\') }}" class="btn btn-success">
        <i class="bi bi-plus-circle me-2"></i>Novo Registro
    </a>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#exportModal">
        <i class="bi bi-download me-2"></i>Exportar
    </button>
@endsection
```

## Benefícios dos Templates

✅ **Rapidez**: Criação rápida de views padronizadas
✅ **Consistência**: Todas seguem estrutura unificada
✅ **Funcionalidade**: Estados de interface inclusos
✅ **Interatividade**: AJAX e filtros prontos
✅ **Responsividade**: Design mobile-first incluso

## Estrutura de Arquivos Recomendada

```
resources/views/
├── layouts/                    # Layouts base
│   └── app.blade.php          # Layout principal
├── pages/                     # Páginas organizadas por módulo
│   ├── home/
│   │   └── index.blade.php    # Nível 1 - Básica
│   ├── customer/
│   │   ├── index.blade.php    # Nível 3 - Avançada
│   │   ├── create.blade.php   # Nível 2 - Formulário
│   │   ├── edit.blade.php     # Nível 2 - Formulário
│   │   └── show.blade.php     # Nível 2 - Formulário
│   └── product/
│       ├── index.blade.php    # Nível 3 - Avançada
│       └── form.blade.php     # Nível 2 - Formulário
├── partials/                  # Componentes reutilizáveis
│   ├── components/            # Componentes Blade
│   │   ├── stat-card.blade.php
│   │   ├── data-table.blade.php
│   │   └── form-section.blade.php
│   └── shared/               # Partiais compartilhados
└── components/               # Componentes avançados
    ├── modals/
    └── widgets/
```

## Convenções de Desenvolvimento

### **Estrutura Obrigatória:**
```php
@extends(\'layouts.app\')

@section(\'content\')
    <!-- Conteúdo principal -->
@endsection

@push(\'styles\')
    <!-- Estilos específicos -->
@endpush

@push(\'scripts\')
    <!-- Scripts específicos -->
@endpush
```

### **Estados de Interface:**
```php
<!-- Sempre implementar os 4 estados principais -->
<div id="initial-state">Estado inicial</div>
<div id="loading-state" class="d-none">Carregando...</div>
<div id="results-container" class="d-none">Resultados</div>
<div id="error-state" class="d-none">Erro</div>
```

### **Componentes Reutilizáveis:**
```php
<!-- Card de estatísticas -->
@component(\'partials.components.stat-card\', [
    \'icon\' => \'bi-people\',
    \'color\' => \'primary\',
    \'value\' => $stats[\'total\'],
    \'label\' => \'Total de Clientes\'
])
@endcomponent

<!-- Seção de formulário -->
@component(\'partials.components.form-section\', [\'title\' => \'Dados Pessoais\'])
    <!-- Campos do formulário -->
@endcomponent
```

### **Formatação de Dados:**
```php
<!-- Badges de status -->
<span class="badge bg-{{ $status->color }}">
    {{ $status->label }}
</span>

<!-- Formatação de moeda -->
<span class="fw-bold text-success">
    R$ {{ number_format($value, 2, \',\', \'.\') }}
</span>

<!-- Avatar com iniciais -->
<div class="avatar-sm">
    <span class="avatar-title bg-primary text-white">
        {{ substr($name, 0, 1) }}
    </span>
</div>
```

## Boas Práticas

### **1. Estados de Interface**
- Sempre implemente estados de loading
- Mostre mensagens claras para estados vazios
- Trate erros de forma amigável
- Use animações suaves entre estados

### **2. Performance**
- Use paginação para grandes datasets
- Implemente busca em tempo real com debounce
- Carregue dados sob demanda quando possível
- Otimize imagens e recursos

### **3. Acessibilidade**
- Use labels adequados em formulários
- Implemente navegação por teclado
- Use cores com contraste adequado
- Inclua textos alternativos para imagens

### **4. Responsividade**
- Teste em diferentes tamanhos de tela
- Use classes Bootstrap responsivas
- Implemente tabelas responsivas
- Otimize para dispositivos móveis

### **5. Manutenibilidade**
- Use componentes reutilizáveis
- Mantenha estrutura consistente
- Documente funcionalidades complexas
- Use nomes de classe descritivos';
    }
}
