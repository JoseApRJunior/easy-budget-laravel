@extends('layouts.app')

@section('content')
    <div class="container-fluid py-4">
        <x-layout.page-header
            title="Gerenciar Usuários"
            icon="people"
            :breadcrumb-items="[
                'Admin' => url('/admin'),
                'Usuários' => '#'
            ]">
            <a href="{{ url('/admin/users/create') }}" class="btn btn-primary">
                <i class="bi bi-plus-circle me-1"></i>Novo Usuário
            </a>
        </x-layout.page-header>
                    <div class="card-body">
                        <p>Lista de usuários do sistema</p>
                    </div>
@endsection
