@extends('layouts.app')

@section('title', 'Dashboard Administrativo')

@section('content')
    <div class="container-fluid py-4">
        <x-layout.page-header
            title="Dashboard Administrativo"
            icon="shield-lock"
            :breadcrumb-items="[
                'Admin' => '#'
            ]">
        </x-layout.page-header>

        <!-- Cards de Ação -->
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card h-100 shadow-sm border-0 hover-shadow">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-opacity-10 me-3">
                                <i class="bi bi-people-fill fs-4 text-success"></i>
                            </div>
                            <h5 class="card-title mb-0">Gerenciar Usuários</h5>
                        </div>
                        <p class="card-text text-muted flex-grow-1">
                            Visualize e gerencie os usuários e provedores do sistema.
                        </p>
                        <a href="/admin/user" class="btn btn-success">Acessar Usuários</a>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card h-100 shadow-sm border-0 hover-shadow">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-opacity-10 me-3">
                                <i class="bi bi-card-checklist fs-4 text-primary"></i>
                            </div>
                            <h5 class="card-title mb-0">Gerenciar Assinaturas</h5>
                        </div>
                        <p class="card-text text-muted flex-grow-1">
                            Acompanhe, cancele ou estorne assinaturas de planos dos usuários.
                        </p>
                        <a href="/admin/plans/subscriptions" class="btn btn-primary">Gerenciar Assinaturas</a>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 shadow-sm border-0 hover-shadow">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex align-items-center mb-3">
                            <div class="bg-opacity-10 me-3">
                                <i class="bi bi-bug-fill fs-4 text-warning"></i>
                            </div>
                            <h5 class="card-title mb-0">Logs do Sistema</h5>
                        </div>
                        <p class="card-text text-muted flex-grow-1">
                            Monitore os logs de erro e eventos importantes da aplicação.
                        </p>
                        <a href="/admin/logs" class="btn btn-warning">Acessar Logs</a>
                    </div>
                </div>
            </div>

            {{-- Adicione mais cards aqui para outras funcionalidades administrativas --}}

        </div>
    </div>
@endsection
