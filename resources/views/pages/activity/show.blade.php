@extends( 'layout' )

@section( 'title', 'Detalhes da Atividade #' . $activity->id )

@section( 'content' )
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <!-- Breadcrumb -->
                <nav aria-label="breadcrumb" class="mb-4">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item">
                            <a href="{{ url( '/admin/activities' ) }}">
                                <i class="fas fa-history me-1"></i>
                                Logs de Atividades
                            </a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">
                            Atividade #{{ $activity->id }}
                        </li>
                    </ol>
                </nav>

                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="card-title mb-0">
                            <i class="fas fa-file-alt me-2"></i>
                            Detalhes da Atividade #{{ $activity->id }}
                        </h3>
                        <a href="{{ url( '/admin/activities' ) }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left me-1"></i>
                            Voltar
                        </a>
                    </div>

                    <div class="card-body">
                        <div class="row">
                            <!-- Informações Básicas -->
                            <div class="col-md-6">
                                <h5 class="text-primary mb-3">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Informações Básicas
                                </h5>

                                <table class="table table-borderless">
                                    <tbody>
                                        <tr>
                                            <td class="fw-bold text-muted">ID da Atividade:</td>
                                            <td>
                                                <span class="badge bg-primary fs-6">{{ $activity->id }}</span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold text-muted">Tenant ID:</td>
                                            <td>
                                                <span class="badge bg-secondary">{{ $activity->tenantId }}</span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold text-muted">Usuário ID:</td>
                                            <td>
                                                <span class="badge bg-info">{{ $activity->userId }}</span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold text-muted">Tipo de Ação:</td>
                                            <td>
                                                <span class="badge bg-success">{{ $activity->actionType }}</span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="fw-bold text-muted">Tipo de Entidade:</td>
                                            <td>
                                                <span class="badge bg-warning text-dark">{{ $activity->entityType }}</span>
                                            </td>
                                        </tr>
                                        @if( $activity->entityId )
                                            <tr>
                                                <td class="fw-bold text-muted">ID da Entidade:</td>
                                                <td>
                                                    <span class="badge bg-dark">{{ $activity->entityId }}</span>
                                                </td>
                                            </tr>
                                        @endif
                                    </tbody>
                                </table>
                            </div>

                            <!-- Timestamps -->
                            <div class="col-md-6">
                                <h5 class="text-primary mb-3">
                                    <i class="fas fa-clock me-2"></i>
                                    Timestamps
                                </h5>

                                <table class="table table-borderless">
                                    <tbody>
                                        <tr>
                                            <td class="fw-bold text-muted">Criado em:</td>
                                            <td>
                                                <i class="fas fa-calendar-plus me-1 text-success"></i>
                                                {{ $activity->createdAt->format( 'd/m/Y H:i:s' ) }}
                                            </td>
                                        </tr>
                                        @if( $activity->updatedAt )
                                            <tr>
                                                <td class="fw-bold text-muted">Atualizado em:</td>
                                                <td>
                                                    <i class="fas fa-calendar-edit me-1 text-warning"></i>
                                                    {{ $activity->updatedAt->format( 'd/m/Y H:i:s' ) }}
                                                </td>
                                            </tr>
                                        @endif
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Descrição -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <h5 class="text-primary mb-3">
                                    <i class="fas fa-comment-alt me-2"></i>
                                    Descrição
                                </h5>
                                <div class="alert alert-light border">
                                    <p class="mb-0">{{ $activity->description }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Metadados -->
                        @if( $activity->metadata )
                            <div class="row mt-4">
                                <div class="col-12">
                                    <h5 class="text-primary mb-3">
                                        <i class="fas fa-database me-2"></i>
                                        Metadados
                                    </h5>
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <pre
                                                class="mb-0"><code>{{ json_encode( $activity->metadata, JSON_PRETTY_PRINT ) }}</code></pre>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="card-footer text-muted">
                        <small>
                            <i class="fas fa-info-circle me-1"></i>
                            Esta atividade foi registrada automaticamente pelo sistema para fins de auditoria e
                            rastreabilidade.
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push( 'scripts' )
    <script>
        // Highlight do código JSON nos metadados
        document.addEventListener( 'DOMContentLoaded', function () {
            const codeBlocks = document.querySelectorAll( 'pre code' );
            codeBlocks.forEach( function ( block ) {
                // Adiciona a classe para o highlight, se estiver usando alguma biblioteca para isso
                block.classList.add( 'language-json' );
            } );
        } );
    </script>
@endpush
