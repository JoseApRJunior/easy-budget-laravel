@extends( 'layout' )

@section( 'title', 'Logs de Atividades' )

@section( 'content' )
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="card-title mb-0">
                            <i class="fas fa-history me-2"></i>
                            Logs de Atividades do Sistema
                        </h3>
                    </div>

                    <div class="card-body">
                        @if( session( 'error' ) )
                            <div class="alert alert-danger" role="alert">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                {{ session( 'error' ) }}
                            </div>
                        @endif

                        @if( $activities->isEmpty() )
                            <div class="alert alert-info text-center" role="alert">
                                <i class="fas fa-info-circle me-2"></i>
                                Nenhuma atividade registrada ainda.
                            </div>
                        @else
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead class="table-dark">
                                        <tr>
                                            <th scope="col">#</th>
                                            <th scope="col">Usuário</th>
                                            <th scope="col">Ação</th>
                                            <th scope="col">Entidade</th>
                                            <th scope="col">Descrição</th>
                                            <th scope="col">Data/Hora</th>
                                            <th scope="col">Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach( $activities as $activity )
                                            <tr>
                                                <td>{{ $activity->id }}</td>
                                                <td>
                                                    <span class="badge bg-primary">
                                                        ID: {{ $activity->userId }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-info">
                                                        {{ $activity->actionType }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-secondary">
                                                        {{ $activity->entityType }}
                                                    </span>
                                                    @if( $activity->entityId )
                                                        <small class="text-muted d-block">
                                                            ID: {{ $activity->entityId }}
                                                        </small>
                                                    @endif
                                                </td>
                                                <td>
                                                    <span class="text-truncate d-inline-block" style="max-width: 200px;"
                                                        title="{{ $activity->description }}">
                                                        {{ $activity->description }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <small class="text-muted">
                                                        {{ $activity->createdAt->format( 'd/m/Y H:i:s' ) }}
                                                    </small>
                                                </td>
                                                <td>
                                                    <a href="{{ url( '/admin/activities/' . $activity->id ) }}"
                                                        class="btn btn-sm btn-outline-primary" title="Ver detalhes">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div class="mt-3">
                                <p class="text-muted small">
                                    <i class="fas fa-info-circle me-1"></i>
                                    Total de {{ $activities->count() }} atividade(s) registrada(s).
                                </p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push( 'scripts' )
    <script>
        // Auto-refresh da página a cada 30 segundos para mostrar novas atividades
        setTimeout( function () {
            location.reload();
        }, 30000 );
    </script>
@endpush
