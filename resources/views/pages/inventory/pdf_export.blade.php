@extends('layouts.pdf')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-8">
                <h4 class="text-dark">{{ config('app.name') }}</h4>
                <p class="text-muted mb-0">Relatório Gerencial</p>
            </div>
            <div class="col-4 text-end">
                <h2 class="text-primary">{{ $title }}</h2>
                <p class="text-muted mb-0">Gerado em: {{ $generated_at }}</p>
            </div>
        </div>

        <hr class="my-4 bg-secondary">

        <div class="card border-0">
            <div class="card-body p-0">
                <table class="table table-sm table-striped table-bordered">
                    <thead class="bg-light">
                        <tr>
                            @foreach ($headers as $header)
                                <th>{{ $header }}</th>
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

        <div class="mt-4 text-end">
            <p class="text-muted small">Total de registros: {{ count($items) }}</p>
        </div>
    </div>
@endsection
