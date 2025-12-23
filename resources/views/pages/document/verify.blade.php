@extends('layouts.guest')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-5">
                    <div class="text-center mb-4">
                        <h1 class="h4 mt-2">Verificação de Documento</h1>
                    </div>

                    @if($found)
                        <div class="alert alert-success">
                            <strong>Documento Autêntico</strong>
                        </div>

                        <div class="p-3" style="background:#f8f9fa;border-radius:.5rem;">
                            <table class="table table-borderless mb-0">
                                <tr>
                                    <th width="30%">Tipo</th>
                                    <td><span class="badge bg-primary">{{ $type }}</span></td>
                                </tr>
                                <tr>
                                    <th>Código</th>
                                    <td>{{ $document->code ?? 'N/A' }}</td>
                                </tr>
                                <tr>
                                    <th>Data de Emissão</th>
                                    <td>{{ optional($document->created_at)->format('d/m/Y H:i') }}</td>
                                </tr>
                                <tr>
                                    <th>Hash</th>
                                    <td><code class="small">{{ $hash }}</code></td>
                                </tr>
                                <tr>
                                    <th>Verificado em</th>
                                    <td>{{ $verified_at->format('d/m/Y H:i:s') }}</td>
                                </tr>
                            </table>
                        </div>

                        <div class="alert alert-info mt-3">
                            Este documento foi gerado pelo sistema Easy Budget.
                        </div>
                    @else
                        <div class="alert alert-danger">
                            <strong>Documento Não Encontrado</strong>
                        </div>
                        <p class="text-muted">O hash de verificação fornecido não corresponde a nenhum documento.</p>
                        <p class="small text-muted">Hash: <code>{{ $hash }}</code></p>
                    @endif

                    <div class="text-center mt-4">
                        <a href="{{ route('home') }}" class="btn btn-outline-primary">Voltar para Home</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
