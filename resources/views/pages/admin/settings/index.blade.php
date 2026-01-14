@extends('layouts.app')

@section('content')
    <div class="container-fluid py-4">
        <x-layout.page-header
            title="Configurações do Sistema"
            icon="gear"
            :breadcrumb-items="[
                'Admin' => url('/admin'),
                'Configurações' => '#'
            ]">
        </x-layout.page-header>
                    <div class="card-body">
                        <p>Configurações administrativas</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
