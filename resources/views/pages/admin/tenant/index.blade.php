@extends('layouts.app')

@section('content')
    <div class="container-fluid py-4">
        <x-page-header
            title="Gerenciar Tenants"
            icon="building-gear"
            :breadcrumb-items="[
                'Admin' => url('/admin'),
                'Tenants' => '#'
            ]">
        </x-page-header>
                    <div class="card-body">
                        <p>Lista de tenants do sistema</p>
                    </div>
@endsection
