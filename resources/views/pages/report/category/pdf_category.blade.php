@extends('layouts.pdf')

@section('content')
    <div class="container-fluid">
        {{-- Cabeçalho --}}
        <div class="row">
            <div class="col-8">
                @if($company['logo_url'])
                    <img src="{{ $company['logo_url'] }}" alt="Logo" style="max-height: 80px;" class="mb-4">
                @endif
                <h4 class="text-dark">{{ $company['name'] }}</h4>
                <p class="text-muted mb-0">{{ $company['address'] }}</p>
                <p class="text-muted mb-0">CNPJ: {{ $company['cnpj'] }}</p>
            </div>
            <div class="col-4 text-end">
                <h2 class="text-primary">Relatório de Categorias</h2>
                <p class="text-muted mb-0">Data: {{ now()->format('d/m/Y') }}</p>
                <p class="text-muted mb-0">Horário: {{ now()->format('H:i:s') }}</p>
            </div>
        </div>

        <hr class="my-4 bg-secondary">

        {{-- Tabela de Categorias --}}
        <div class="card border-0">
            <div class="card-body p-0">
                <table class="table table-sm table-striped table-bordered">
                    <thead class="bg-light">
                        <tr>
                            <th style="width: 30%; text-align: left;">Categoria</th>
                            <th style="width: 30%; text-align: left;">Subcategoria</th>
                            <th style="width: 15%; text-align: center;">Situação</th>
                            <th style="width: 25%; text-align: left;">Criado em</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($categories as $category)
                            <tr>
                                <td>{{ $category['parent_name'] }}</td>
                                <td>{{ $category['name'] }}</td>
                                <td class="text-center">{{ $category['status'] }}</td>
                                <td>{{ $category['created_at'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-1">
                                    Nenhuma categoria encontrada para os filtros aplicados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Totais e Estatísticas --}}
        @if (count($categories) > 0)
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card border-0 bg-light">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-12 text-center">
                                    <h6 class="text-muted mb-1">Total de Registros</h6>
                                    <h4 class="text-dark mb-0">{{ count($categories) }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

    </div>
@endsection
