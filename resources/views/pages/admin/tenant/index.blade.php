@extends('layouts.app')

@section('content')
    <div class="container-fluid py-4">
        <x-layout.page-header
            title="Gerenciar Tenants"
            icon="building-gear"
            :breadcrumb-items="[
                'Admin' => url('/admin'),
                'Tenants' => '#'
            ]">
        </x-layout.page-header>
                    <div class="card-body">
                        <p>Lista de tenants do sistema</p>
                    </div>
@endsection
