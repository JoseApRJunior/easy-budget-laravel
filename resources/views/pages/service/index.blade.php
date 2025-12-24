@extends('layouts.app')

@section('title', 'Gestão de Serviços')

@section('content')
    <div class="container-fluid py-1">
        <x-page-header 
            title="Serviços" 
            icon="gear" 
            :breadcrumb-items="[
                'Serviços' => '#'
            ]"
        >
            <p class="text-muted mb-0">Lista de todos os serviços registrados no sistema</p>
        </x-page-header>

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
