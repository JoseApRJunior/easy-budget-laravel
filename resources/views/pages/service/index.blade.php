@extends('layouts.app')

@section('title', 'Gestão de Serviços')

@section('content')
    <div class="container-fluid py-1">
        {{-- Cabeçalho --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-0">
                    <i class="bi bi-gear me-2"></i>Serviços
                </h1>
                <p class="text-muted">Lista de todos os serviços registrados no sistema</p>
            </div>
            <nav aria-label="breadcrumb" class="d-none d-md-block">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('provider.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Listar</li>
                </ol>
            </nav>
        </div>

        {{-- Conteúdo dos serviços --}}
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-list me-2"></i>Serviços Cadastrados
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            Sistema de gestão de serviços em desenvolvimento. Em breve você poderá cadastrar e gerenciar
                            todos os seus serviços.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
