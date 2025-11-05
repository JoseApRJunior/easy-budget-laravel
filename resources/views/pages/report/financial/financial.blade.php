@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Relatório Financeiro</h1>
            <p class="text-muted mb-0">Análise completa da situação financeira</p>
        </div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ route('provider.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="{{ route('provider.reports.index') }}">Relatórios</a></li>
                <li class="breadcrumb-item active">Financeiro</li>
            </ol>
        </nav>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="bi bi-graph-up text-primary mb-3" style="font-size: 3rem;"></i>
                    <h5>Relatório Financeiro</h5>
                    <p class="text-muted">Esta funcionalidade está em desenvolvimento.</p>
                    <a href="{{ route('provider.reports.index') }}" class="btn btn-primary">
                        <i class="bi bi-arrow-left me-2"></i>Voltar aos Relatórios
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection