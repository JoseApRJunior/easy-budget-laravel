futu@extends( 'layouts.admin' )

@section( 'title', 'Gerenciar Backups' )

@section( 'content' )
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="card-title">Backups do Sistema</h3>
                        <div>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal"
                                data-bs-target="#createBackupModal">
                                <i class="bi bi-plus-circle"></i> Criar Backup
                            </button>
                            <button type="button" class="btn btn-warning" data-bs-toggle="modal"
                                data-bs-target="#cleanupModal">
                                <i class="bi bi-trash"></i> Limpeza
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        @if ( $backups->isEmpty() )
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i> Nenhum backup encontrado.
                            </div>
                        @else
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Arquivo</th>
                                            <th>Tipo</th>
                                            <th>Tamanho</th>
                                            <th>Data</th>
                                            <th>Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ( $backups as $backup )
                                            <tr>
                                                <td>{{ $backup->filename }}</td>
                                                <td>
                                                    <span class="badge bg-{{ $backup->type == 'Manual' ? 'primary' : 'success' }}">
                                                        {{ $backup->type }}
                                                    </span>
                                                </td>
                                                <td>{{ $backup->size }}</td>
                                                <td>{{ $backup->date }}</td>
                                                <td>
                                                    <div class="btn-group" role="group">
                                                        <form method="POST" action="{{ url( '/admin/backups/restore' ) }}"
                                                            style="display: inline;">
                                                            @csrf
                                                            <input type="hidden" name="filename" value="{{ $backup->filename }}">
                                                            <button type="submit" class="btn btn-sm btn-success">
                                                                <i class="bi bi-arrow-clockwise"></i> Restaurar
                                                            </button>
                                                        </form>
                                                        <form method="POST" action="{{ url( '/admin/backups/delete' ) }}"
                                                            style="display: inline;">
                                                            @csrf
                                                            <input type="hidden" name="filename" value="{{ $backup->filename }}">
                                                            <button type="submit" class="btn btn-sm btn-danger">
                                                                <i class="bi bi-trash"></i> Excluir
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modals -->
    <div class="modal fade" id="createBackupModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Criar Backup</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Deseja criar um backup manual do banco de dados?</p>
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle"></i>
                        Este processo pode levar alguns minutos.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <form method="POST" action="{{ url( '/admin/backups/create' ) }}" style="display: inline;">
                        @csrf
                        <button type="submit" class="btn btn-primary">Criar Backup</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="cleanupModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Limpeza de Backups</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="{{ url( '/admin/backups/cleanup' ) }}">
                    @csrf
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="days" class="form-label">Manter backups dos últimos (dias):</label>
                            <input type="number" class="form-control" id="days" name="days" value="30" min="1" max="365">
                        </div>
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle"></i>
                            Esta ação irá remover permanentemente os backups antigos.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-warning">Executar Limpeza</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
