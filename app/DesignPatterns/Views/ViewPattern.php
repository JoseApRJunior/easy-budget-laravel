<?php

declare(strict_types=1);

namespace App\DesignPatterns\Views;

/**
 * Padrão Unificado para Views no Easy Budget Laravel
 *
 * Define convenções consistentes para desenvolvimento de views Blade,
 * garantindo uniformidade, manutenibilidade e reutilização de código.
 */
class ViewPattern
{
    /**
     * PADRÃO UNIFICADO PARA VIEWS
     *
     * Baseado na análise das views existentes, definimos 3 níveis:
     */

    /**
     * NÍVEL 1 - View Básica (Páginas Simples)
     * Para páginas simples sem muita interatividade
     *
     * @example home/index.blade.php, about.blade.php
     */
    public function basicView(): string
    {
        return '
@extends(\'layouts.app\')

@section(\'content\')
<main class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-5">
                    <h1 class="h2 mb-4">@yield(\'title\', \'Título da Página\')</h1>

                    @yield(\'content\')
                </div>
            </div>
        </div>
    </div>
</main>
@endsection

@push(\'styles\')
<style>
    /* Estilos específicos da página */
</style>
@endpush

@push(\'scripts\')
<script>
    // Scripts específicos da página
</script>
@endpush';
    }

    /**
     * NÍVEL 2 - View Intermediária (Com Formulários)
     * Para páginas com formulários e validação
     *
     * @example customer/create.blade.php, product/edit.blade.php
     */
    public function formView(): string
    {
        return '
@extends(\'layouts.app\')

@section(\'content\')
<div class="container py-1">
    <!-- Cabeçalho -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">@yield(\'title\', \'Título da Página\')</h1>
            <p class="text-muted mb-0">@yield(\'subtitle\', \'Subtítulo da página\')</p>
        </div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                @yield(\'breadcrumbs\')
            </ol>
        </nav>
    </div>

    <!-- Formulário -->
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <form method="POST" action="@yield(\'action\', \'#\')">
                        @csrf
                        @method(@yield(\'method\', \'POST\'))

                        @yield(\'form-fields\')

                        <!-- Botões -->
                        <div class="d-flex justify-content-between pt-4">
                            <a href="@yield(\'back-url\', \'javascript:history.back()\')"
                               class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-2"></i>Voltar
                            </a>
                            <button type="submit" class="btn btn-primary">
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
    /* Estilos específicos do formulário */
</style>
@endpush

@push(\'scripts\')
<script>
    // Validação e interatividade do formulário
</script>
@endpush';
    }

    /**
     * NÍVEL 3 - View Avançada (Com Tabelas e AJAX)
     * Para páginas com tabelas, filtros e interatividade avançada
     *
     * @example customer/index.blade.php, report/index.blade.php
     */
    public function advancedView(): string
    {
        return '
@extends(\'layouts.app\')

@section(\'content\')
<div class="container-fluid py-1">
    <!-- Cabeçalho -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="bi bi-table me-2"></i>@yield(\'title\', \'Listagem\')
            </h1>
            <p class="text-muted mb-0">@yield(\'subtitle\', \'Gerencie os registros\')</p>
        </div>
        <div class="d-flex gap-2">
            @yield(\'header-actions\')
        </div>
    </div>

    <!-- Filtros e Busca -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <form method="GET" class="row g-3">
                @yield(\'filters\')

                <div class="col-12">
                    <div class="d-flex justify-content-between">
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

    <!-- Estado Inicial -->
    <div id="initial-state" class="card border-0 shadow-sm text-center py-5">
        <div class="card-body">
            <i class="bi bi-search text-primary mb-3" style="font-size: 3rem;"></i>
            <h5 class="text-muted mb-3">@yield(\'initial-message\', \'Use os filtros acima para buscar\')</h5>
            <p class="text-muted mb-0">@yield(\'initial-description\', \'Digite os critérios de busca e clique em filtrar\')</p>
        </div>
    </div>

    <!-- Loading -->
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
                        <i class="bi bi-list me-2"></i>Resultados
                    </h5>
                    <span id="results-count" class="text-muted"></span>
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
                @include(\'partials.components.table_paginator\')
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
</div>
@endsection

@push(\'styles\')
<style>
    /* Estilos específicos da listagem */
    .table th {
        border-top: none;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.875rem;
        letter-spacing: 0.05em;
    }

    .hover-shadow:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15) !important;
    }
</style>
@endpush

@push(\'scripts\')
<script>
    // Funcionalidades avançadas da listagem
    class AdvancedListing {
        constructor() {
            this.initializeComponents();
            this.bindEvents();
        }

        initializeComponents() {
            // Inicialização dos componentes
        }

        bindEvents() {
            // Bind de eventos
        }

        async loadData(filters = {}) {
            // Carregamento de dados via AJAX
        }

        showLoading() {
            // Exibir estado de loading
        }

        showResults(data) {
            // Exibir resultados
        }

        showError(message) {
            // Exibir erro
        }
    }

    // Inicializar quando DOM estiver pronto
    document.addEventListener(\'DOMContentLoaded\', function() {
        new AdvancedListing();
    });
</script>
@endpush';
    }

    /**
     * CONVENÇÕES PARA ESTRUTURA BLADE
     */

    /**
     * Estrutura Padronizada de Views
     */
    public function bladeStructureConventions(): string
    {
        return '
// ✅ CORRETO - Estrutura padronizada de views

// 1. Extensão de layout consistente
@extends(\'layouts.app\')

// 2. Seções obrigatórias bem definidas
@section(\'content\')
    <!-- Conteúdo principal -->
@endsection

// 3. Push de estilos organizados
@push(\'styles\')
<style>
    /* Estilos específicos da página */
    .custom-class {
        /* estilos */
    }
</style>
@endpush

// 4. Push de scripts organizados
@push(\'scripts\')
<script>
    // Scripts específicos da página
    $(document).ready(function() {
        // Inicialização
    });
</script>
@endpush

// 5. Componentes reutilizáveis
@component(\'partials.components.card\', [\'title\' => \'Título do Card\'])
    <p>Conteúdo do card reutilizável</p>
@endcomponent

// 6. Estados condicionais claros
@if($data->isEmpty())
    <div class="alert alert-info">
        Nenhum registro encontrado.
    </div>
@else
    <div class="table-responsive">
        <table class="table">
            <!-- tabela -->
        </table>
    </div>
@endif

// 7. Loops com estrutura consistente
@foreach($items as $item)
    <div class="item-card">
        <h3>{{ $item->name }}</h3>
        <p>{{ $item->description }}</p>
    </div>
@endforeach

// 8. Formatação de dados consistente
<span class="badge bg-{{ $status->color }}">
    {{ $status->label }}
</span>

// ❌ INCORRETO - Não fazer isso

// 1. Não misturar lógica PHP com HTML
@if($condition)
    <div class="<?php echo $class; ?>">
        <!-- HTML complexo com PHP misturado -->
    </div>
@endif

// 2. Não usar estilos inline
<div style="color: red; font-size: 20px;">
    <!-- estilos inline -->

// 3. Não fazer queries no template
@php
    $expensiveData = DB::table(\'users\')->get(); // ❌ Muito pesado
@endphp

// 4. Não usar JavaScript inline
<button onclick="alert(\'teste\')"> // ❌ JavaScript inline

// 5. Não criar estruturas muito complexas
@if($condition1)
    @if($condition2)
        @if($condition3)
            <!-- HTML muito aninhado -->';
    }

    /**
     * EXEMPLOS PRÁTICOS DE IMPLEMENTAÇÃO
     */

    /**
     * Exemplo de View Nível 1 - Básica
     */
    public function basicViewExample(): string
    {
        return '
@extends(\'layouts.app\')

@section(\'content\')
<main class="container py-5">
    <!-- Cabeçalho da página -->
    <div class="text-center mb-5">
        <h1 class="display-5 fw-bold text-primary mb-3">@yield(\'title\', \'Sobre Nós\')</h1>
        <p class="lead text-muted">@yield(\'description\', \'Conheça nossa empresa\')</p>
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
    // Animações ou interatividade simples
    document.addEventListener(\'DOMContentLoaded\', function() {
        // Inicialização básica
    });
</script>
@endpush';
    }

    /**
     * Exemplo de View Nível 2 - Com Formulário
     */
    public function formViewExample(): string
    {
        return '
@extends(\'layouts.app\')

@section(\'content\')
<div class="container py-1">
    <!-- Cabeçalho -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">
                <i class="bi bi-person-plus me-2"></i>Novo Cliente
            </h1>
            <p class="text-muted mb-0">Cadastre um novo cliente no sistema</p>
        </div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route(\'provider.dashboard\') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route(\'provider.customers.index\') }}">Clientes</a></li>
                <li class="breadcrumb-item active">Novo</li>
            </ol>
        </nav>
    </div>

    <!-- Formulário -->
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-5">
                    <form method="POST" action="{{ route(\'customers.store\') }}" id="customerForm">
                        @csrf

                        <!-- Dados Pessoais -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5 class="border-bottom pb-2 mb-3">
                                    <i class="bi bi-person me-2"></i>Dados Pessoais
                                </h5>
                            </div>

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

                            <div class="col-md-6 mb-3">
                                <label for="last_name" class="form-label">Sobrenome *</label>
                                <input type="text"
                                       class="form-control @error(\'last_name\') is-invalid @enderror"
                                       id="last_name"
                                       name="last_name"
                                       value="{{ old(\'last_name\') }}"
                                       required>
                                @error(\'last_name\')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Contato -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5 class="border-bottom pb-2 mb-3">
                                    <i class="bi bi-envelope me-2"></i>Contato
                                </h5>
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">E-mail *</label>
                                <input type="email"
                                       class="form-control @error(\'email\') is-invalid @enderror"
                                       id="email"
                                       name="email"
                                       value="{{ old(\'email\') }}"
                                       required>
                                @error(\'email\')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label">Telefone</label>
                                <input type="tel"
                                       class="form-control @error(\'phone\') is-invalid @enderror"
                                       id="phone"
                                       name="phone"
                                       value="{{ old(\'phone\') }}">
                                @error(\'phone\')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Botões -->
                        <div class="d-flex justify-content-between pt-4 border-top">
                            <a href="{{ route(\'customers.index\') }}" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-2"></i>Voltar
                            </a>
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="bi bi-check-lg me-2"></i>Criar Cliente
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
    }
</style>
@endpush

@push(\'scripts\')
<script>
    // Máscaras e validações do formulário
    document.addEventListener(\'DOMContentLoaded\', function() {
        // Máscara de telefone
        const phoneInput = document.getElementById(\'phone\');
        if (phoneInput) {
            phoneInput.addEventListener(\'input\', function(e) {
                let value = e.target.value.replace(/\D/g, \'\');
                if (value.length <= 11) {
                    value = value.replace(/(\d{2})(\d{5})(\d{4})/, \'($1) $2-$3\');
                    e.target.value = value;
                }
            });
        }

        // Validação em tempo real
        const form = document.getElementById(\'customerForm\');
        if (form) {
            form.addEventListener(\'submit\', function(e) {
                // Validações customizadas antes do submit
            });
        }
    });
</script>
@endpush';
    }

    /**
     * Exemplo de View Nível 3 - Avançada
     */
    public function advancedViewExample(): string
    {
        return '
@extends(\'layouts.app\')

@section(\'content\')
<div class="container-fluid py-1">
    <!-- Cabeçalho -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="bi bi-people me-2"></i>Clientes
            </h1>
            <p class="text-muted mb-0">Gerencie todos os clientes cadastrados</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route(\'customers.create\') }}" class="btn btn-success">
                <i class="bi bi-person-plus me-2"></i>Novo Cliente
            </a>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#exportModal">
                <i class="bi bi-download me-2"></i>Exportar
            </button>
        </div>
    </div>

    <!-- Cards de Estatísticas -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <i class="bi bi-people text-primary mb-2" style="font-size: 2rem;"></i>
                    <h3 class="h4 mb-1">{{ $stats[\'total\'] ?? 0 }}</h3>
                    <p class="text-muted mb-0">Total de Clientes</p>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <i class="bi bi-check-circle text-success mb-2" style="font-size: 2rem;"></i>
                    <h3 class="h4 mb-1">{{ $stats[\'active\'] ?? 0 }}</h3>
                    <p class="text-muted mb-0">Clientes Ativos</p>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <i class="bi bi-clock text-warning mb-2" style="font-size: 2rem;"></i>
                    <h3 class="h4 mb-1">{{ $stats[\'recent\'] ?? 0 }}</h3>
                    <p class="text-muted mb-0">Novos (7 dias)</p>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <i class="bi bi-star text-info mb-2" style="font-size: 2rem;"></i>
                    <h3 class="h4 mb-1">{{ $stats[\'vip\'] ?? 0 }}</h3>
                    <p class="text-muted mb-0">Clientes VIP</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros Avançados -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <form method="GET" class="row g-3" id="filterForm">
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
                        <option value="inactive" {{ request(\'status\') === \'inactive\' ? \'selected\' : \'\' }}>Inativo</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label for="type" class="form-label">Tipo</label>
                    <select class="form-select" id="type" name="type">
                        <option value="">Todos</option>
                        <option value="individual" {{ request(\'type\') === \'individual\' ? \'selected\' : \'\' }}>Pessoa Física</option>
                        <option value="company" {{ request(\'type\') === \'company\' ? \'selected\' : \'\' }}>Pessoa Jurídica</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="tags" class="form-label">Tags</label>
                    <select class="form-select" id="tags" name="tags[]" multiple>
                        @foreach($availableTags as $tag)
                            <option value="{{ $tag->id }}"
                                {{ in_array($tag->id, request(\'tags\', [])) ? \'selected\' : \'\' }}>
                                {{ $tag->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">&nbsp;</label>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search me-2"></i>Filtrar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Estados da Interface -->
    <div id="initial-state" class="card border-0 shadow-sm text-center py-5 {{ $customers->isNotEmpty() ? \'d-none\' : \'\' }}">
        <div class="card-body">
            <i class="bi bi-funnel text-primary mb-3" style="font-size: 3rem;"></i>
            <h5 class="text-muted mb-3">Use os filtros acima para buscar clientes</h5>
            <p class="text-muted mb-0">Digite os critérios de busca e clique em filtrar</p>
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
    <div id="results-container" class="card border-0 shadow-sm {{ $customers->isEmpty() ? \'d-none\' : \'\' }}">
        <div class="card-header bg-transparent border-0">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-list me-2"></i>Clientes Encontrados
                </h5>
                <div class="d-flex align-items-center gap-3">
                    <span id="results-count" class="text-muted">
                        {{ $customers->total() }} resultados
                    </span>
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown">
                            <i class="bi bi-gear me-1"></i>Ações
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#" id="exportResults">
                                <i class="bi bi-download me-2"></i>Exportar Resultados
                            </a></li>
                            <li><a class="dropdown-item" href="#" id="printResults">
                                <i class="bi bi-printer me-2"></i>Imprimir
                            </a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="text-start px-4">Cliente</th>
                            <th>Tipo</th>
                            <th>Contato</th>
                            <th>Status</th>
                            <th>Cadastro</th>
                            <th class="text-end px-4">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($customers as $customer)
                            <tr>
                                <td class="px-4">
                                    <div class="d-flex align-items-center">
                                        <div class="avatar-sm me-3">
                                            <span class="avatar-title bg-primary text-white rounded-circle">
                                                {{ substr($customer->full_name, 0, 1) }}
                                            </span>
                                        </div>
                                        <div>
                                            <h6 class="mb-0">{{ $customer->full_name }}</h6>
                                            @if($customer->company_name)
                                                <small class="text-muted">{{ $customer->company_name }}</small>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $customer->customer_type === \'individual\' ? \'info\' : \'success\' }}">
                                        {{ $customer->customer_type_label }}
                                    </span>
                                </td>
                                <td>
                                    <div class="small">
                                        <div>{{ $customer->primary_email }}</div>
                                        <div class="text-muted">{{ $customer->formatted_phone }}</div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $customer->status === \'active\' ? \'success\' : \'secondary\' }}">
                                        {{ $customer->status_label }}
                                    </span>
                                </td>
                                <td>
                                    <small class="text-muted">
                                        {{ $customer->created_at->format(\'d/m/Y\') }}
                                    </small>
                                </td>
                                <td class="text-end px-4">
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                            <i class="bi bi-three-dots"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li><a class="dropdown-item" href="{{ route(\'customers.show\', $customer) }}">
                                                <i class="bi bi-eye me-2"></i>Ver Detalhes
                                            </a></li>
                                            <li><a class="dropdown-item" href="{{ route(\'customers.edit\', $customer) }}">
                                                <i class="bi bi-pencil me-2"></i>Editar
                                            </a></li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li><a class="dropdown-item text-danger" href="#"
                                                   onclick="confirmDelete({{ $customer->id }})">
                                                <i class="bi bi-trash me-2"></i>Excluir
                                            </a></li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Paginação -->
        @if($customers instanceof \\Illuminate\\Pagination\\LengthAwarePaginator)
            <div class="card-footer bg-transparent border-0">
                {{ $customers->appends(request()->query())->links() }}
            </div>
        @endif
    </div>
</div>

<!-- Modal de Confirmação de Exclusão -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title">Confirmar Exclusão</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Tem certeza que deseja excluir este cliente?</p>
                <p class="text-muted mb-0">Esta ação não pode ser desfeita.</p>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form method="POST" id="deleteForm" class="d-inline">
                    @csrf
                    @method(\'DELETE\')
                    <button type="submit" class="btn btn-danger">Excluir</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push(\'styles\')
<style>
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
    }

    .table th {
        border-top: none;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 0.875rem;
        letter-spacing: 0.05em;
        background-color: #f8f9fa;
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
</style>
@endpush

@push(\'scripts\')
<script>
    // Funcionalidades avançadas da listagem
    class CustomerListing {
        constructor() {
            this.initializeComponents();
            this.bindEvents();
            this.setupRealTimeUpdates();
        }

        initializeComponents() {
            this.searchInput = document.getElementById(\'search\');
            this.statusFilter = document.getElementById(\'status\');
            this.typeFilter = document.getElementById(\'type\');
            this.tagsFilter = document.getElementById(\'tags\');
        }

        bindEvents() {
            // Busca em tempo real
            if (this.searchInput) {
                let searchTimeout;
                this.searchInput.addEventListener(\'input\', (e) => {
                    clearTimeout(searchTimeout);
                    searchTimeout = setTimeout(() => {
                        this.performSearch();
                    }, 500);
                });
            }

            // Filtros de mudança imediata
            [this.statusFilter, this.typeFilter, this.tagsFilter].forEach(filter => {
                if (filter) {
                    filter.addEventListener(\'change\', () => {
                        this.performSearch();
                    });
                }
            });
        }

        async performSearch() {
            const formData = new FormData(document.getElementById(\'filterForm\'));
            const filters = {};

            for (let [key, value] of formData.entries()) {
                if (value.trim() !== \'\') {
                    filters[key] = value;
                }
            }

            this.showLoading();

            try {
                const response = await fetch(`{{ route(\'customers.index\') }}?${new URLSearchParams(filters)}`);
                const data = await response.text();

                this.showResults(data);
            } catch (error) {
                this.showError(\'Erro ao buscar clientes. Tente novamente.\');
            }
        }

        showLoading() {
            document.getElementById(\'initial-state\').classList.add(\'d-none\');
            document.getElementById(\'results-container\').classList.add(\'d-none\');
            document.getElementById(\'loading-state\').classList.remove(\'d-none\');
        }

        showResults(data) {
            document.getElementById(\'loading-state\').classList.add(\'d-none\');
            document.getElementById(\'results-container\').classList.remove(\'d-none\');

            // Atualizar contador se necessário
            const resultsCount = document.querySelectorAll(\'#results-container tbody tr\').length;
            document.getElementById(\'results-count\').textContent = `${resultsCount} resultados`;
        }

        showError(message) {
            document.getElementById(\'loading-state\').classList.add(\'d-none\');
            document.getElementById(\'error-state\').classList.remove(\'d-none\');
            document.getElementById(\'error-message\').textContent = message;
        }

        setupRealTimeUpdates() {
            // Atualizações em tempo real via WebSocket ou polling
            setInterval(() => {
                this.refreshStats();
            }, 30000); // Atualiza a cada 30 segundos
        }

        async refreshStats() {
            try {
                const response = await fetch(\'{{ route(\'customers.stats\') }}\');
                const stats = await response.json();

                // Atualizar cards de estatísticas
                this.updateStatsCards(stats);
            } catch (error) {
                console.error(\'Erro ao atualizar estatísticas:\', error);
            }
        }

        updateStatsCards(stats) {
            // Atualização dinâmica das estatísticas
        }
    }

    // Inicializar quando DOM estiver pronto
    document.addEventListener(\'DOMContentLoaded\', function() {
        new CustomerListing();
    });

    // Função global para confirmação de exclusão
    window.confirmDelete = function(customerId) {
        const deleteForm = document.getElementById(\'deleteForm\');
        deleteForm.action = `/customers/${customerId}`;

        const deleteModal = new bootstrap.Modal(document.getElementById(\'deleteModal\'));
        deleteModal.show();
    };
</script>
@endpush';
    }

    /**
     * GUIA DE IMPLEMENTAÇÃO
     */
    public function getImplementationGuide(): string
    {
        return '
## Guia de Implementação - Escolhendo o Nível Correto

### NÍVEL 1 - View Básica
✅ Quando usar:
- Páginas estáticas ou com pouco conteúdo
- Sobre, termos de uso, política de privacidade
- Landing pages simples
- Páginas de erro personalizadas

❌ Não usar quando:
- Formulários complexos necessários
- Tabelas ou listagens avançadas
- Interatividade AJAX necessária
- Múltiplos estados de interface

### NÍVEL 2 - View com Formulário
✅ Quando usar:
- Páginas de criação/edição
- Formulários com validação
- Cadastro de entidades simples
- Configurações básicas

❌ Não usar quando:
- Listagens com filtros avançados
- Múltiplas tabelas ou gráficos
- Interatividade complexa necessária
- Muitos estados de interface

### NÍVEL 3 - View Avançada
✅ Quando usar:
- Listagens com filtros e busca
- Dashboards com estatísticas
- Interfaces com AJAX pesado
- Múltiplos estados (inicial, loading, resultados, erro)
- Tabelas com paginação e ordenação

❌ Não usar quando:
- Página muito simples (use nível 1)
- Formulário simples (use nível 2)
- Não há necessidade de interatividade avançada

## Benefícios do Padrão

✅ **Consistência**: Todas as views seguem estrutura unificada
✅ **Manutenibilidade**: Código familiar e fácil de modificar
✅ **Performance**: Estados de loading e erro padronizados
✅ **UX**: Transições suaves entre estados
✅ **Funcionalidade**: Componentes reutilizáveis inclusos
✅ **Escalabilidade**: Preparado para funcionalidades avançadas

## Estrutura Recomendada

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
│   └── shared/               # Partiais compartilhados
└── components/               # Componentes avançados
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

### **Estados de Interface Padronizados:**
```php
<!-- Estado inicial (quando não há dados) -->
<div id="initial-state" class="text-center py-5">
    <i class="bi bi-search text-primary mb-3" style="font-size: 3rem;"></i>
    <h5>Use os filtros acima para buscar</h5>
</div>

<!-- Estado de loading -->
<div id="loading-state" class="d-none text-center py-5">
    <div class="spinner-border text-primary mb-3"></div>
    <p>Processando sua solicitação...</p>
</div>

<!-- Estado de resultados -->
<div id="results-container" class="d-none">
    <!-- Resultados da busca -->
</div>

<!-- Estado de erro -->
<div id="error-state" class="d-none">
    <div class="alert alert-danger">
        Erro ao carregar dados
    </div>
</div>
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

<!-- Tabela com paginação -->
@include(\'partials.components.data-table\', [
    \'columns\' => $columns,
    \'data\' => $data,
    \'paginator\' => $paginator
])
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

<!-- Formatação de data -->
<small class="text-muted">
    {{ $date->format(\'d/m/Y H:i\') }}
</small>
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
