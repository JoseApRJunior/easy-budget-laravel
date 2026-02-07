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
                <h2 class="text-primary">{{ $title }}</h2>
                <p class="text-muted mb-0">Data: {{ now()->format('d/m/Y') }}</p>
                <p class="text-muted mb-0">Horário: {{ now()->format('H:i:s') }}</p>
            </div>
        </div>

        <hr class="my-4 bg-secondary">

        {{-- Tabela de Inventário --}}
        <div class="card border-0">
            <div class="card-body p-0">
                <table class="table table-sm table-striped table-bordered">
                    <thead class="bg-light">
                        <tr>
                            @foreach ($headers as $header)
                                <th style="text-align: left;">{{ $header }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $row)
                            <tr>
                                @foreach ($row as $value)
                                    <td>{{ $value }}</td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ count($headers) }}" class="text-center text-muted py-1">
                                    Nenhum registro encontrado.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Totais e Estatísticas --}}
        <div class="mt-4 text-end">
            <p class="text-muted small">Total de registros: {{ count($items) }}</p>
        </div>
    </div>
@endsection
