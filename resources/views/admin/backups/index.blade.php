@extends( 'layouts.admin' )

@section( 'content' )
    <div class="container">
        <h1>Gerenciamento de Backups</h1>


        <div class="card">
            <div class="card-header">
                <form action="{{ route( 'admin.backups.create' ) }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-primary">Criar Novo Backup</button>
                </form>
            </div>
            <div class="card-body">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Arquivo</th>
                            <th>Data</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ( $backups as $backup )
                            <tr>
                                <td>{{ $backup }}</td>
                                <td>{{ \Carbon\Carbon::createFromTimestamp( Storage::disk( 'backups' )->lastModified( $backup ) )->format( 'd/m/Y H:i:s' ) }}
                                </td>
                                <td>
                                    <form action="{{ route( 'admin.backups.restore' ) }}" method="POST" class="d-inline">
                                        @csrf
                                        <input type="hidden" name="filename" value="{{ $backup }}">
                                        <button type="submit" class="btn btn-sm btn-success">Restaurar</button>
                                    </form>
                                    <form action="{{ route( 'admin.backups.destroy', $backup ) }}" method="POST" class="d-inline">
                                        @csrf
                                        @method( 'DELETE' )
                                        <button type="submit" class="btn btn-sm btn-danger">Excluir</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
