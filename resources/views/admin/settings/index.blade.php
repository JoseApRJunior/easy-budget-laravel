@extends( 'layouts.admin' )

@section( 'title', 'Configurações do Sistema' )

@section( 'breadcrumb' )
    <li class="breadcrumb-item active">Configurações</li>
@endsection

@section( 'page_actions' )
    <button type="button" class="btn btn-success" onclick="saveSettings()">
        <i class="bi bi-check-circle me-1"></i>Salvar Alterações
    </button>
@endsection

@section( 'admin_content' )
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="bi bi-gear me-2"></i>Configurações do Sistema
                        </h5>
                        <button type="button" class="btn btn-success btn-sm" onclick="saveSettings()">
                            <i class="bi bi-check-circle me-1"></i>Salvar Alterações
                        </button>
                    </div>
                    <div class="card-body">
                        <form id="settingsForm">
                            @csrf

                            {{-- Configurações Gerais --}}
                            <div class="row mb-4">
                                <div class="col-12">
                                    <h6 class="section-title">
                                        <i class="bi bi-info-circle me-2"></i>Configurações Gerais
                                    </h6>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="app_name" class="form-label">Nome da Aplicação</label>
                                    <input type="text" class="form-control" id="app_name" name="app_name"
                                        value="{{ config( 'app.name', 'Easy Budget' ) }}">
                                </div>
                                <div class="col-md-6">
                                    <label for="app_env" class="form-label">Ambiente</label>
                                    <select class="form-select" id="app_env" name="app_env">
                                        <option value="local" {{ config( 'app.env' ) == 'local' ? 'selected' : '' }}>Local
                                        </option>
                                        <option value="production" {{ config( 'app.env' ) == 'production' ? 'selected' : '' }}>
                                            Produção</option>
                                        <option value="staging" {{ config( 'app.env' ) == 'staging' ? 'selected' : '' }}>Staging
                                        </option>
                                    </select>
                                </div>
                            </div>

                            {{-- Configurações de Email --}}
                            <div class="row mb-4">
                                <div class="col-12">
                                    <h6 class="section-title">
                                        <i class="bi bi-envelope me-2"></i>Configurações de Email
                                    </h6>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="mail_driver" class="form-label">Driver de Email</label>
                                    <select class="form-select" id="mail_driver" name="mail_driver">
                                        <option value="smtp" {{ config( 'mail.driver' ) == 'smtp' ? 'selected' : '' }}>SMTP
                                        </option>
                                        <option value="sendmail" {{ config( 'mail.driver' ) == 'sendmail' ? 'selected' : '' }}>
                                            Sendmail</option>
                                        <option value="mailgun" {{ config( 'mail.driver' ) == 'mailgun' ? 'selected' : '' }}>
                                            Mailgun</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="mail_host" class="form-label">Servidor SMTP</label>
                                    <input type="text" class="form-control" id="mail_host" name="mail_host"
                                        value="{{ config( 'mail.host' ) }}" placeholder="smtp.exemplo.com">
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="mail_port" class="form-label">Porta SMTP</label>
                                    <input type="number" class="form-control" id="mail_port" name="mail_port"
                                        value="{{ config( 'mail.port' ) }}" placeholder="587">
                                </div>
                                <div class="col-md-6">
                                    <label for="mail_encryption" class="form-label">Criptografia</label>
                                    <select class="form-select" id="mail_encryption" name="mail_encryption">
                                        <option value="tls" {{ config( 'mail.encryption' ) == 'tls' ? 'selected' : '' }}>TLS
                                        </option>
                                        <option value="ssl" {{ config( 'mail.encryption' ) == 'ssl' ? 'selected' : '' }}>SSL
                                        </option>
                                        <option value="">Nenhuma</option>
                                    </select>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="mail_username" class="form-label">Usuário SMTP</label>
                                    <input type="text" class="form-control" id="mail_username" name="mail_username"
                                        value="{{ config( 'mail.username' ) }}">
                                </div>
                                <div class="col-md-6">
                                    <label for="mail_password" class="form-label">Senha SMTP</label>
                                    <input type="password" class="form-control" id="mail_password" name="mail_password"
                                        value="{{ config( 'mail.password' ) }}">
                                </div>
                            </div>

                            {{-- Configurações de Backup --}}
                            <div class="row mb-4">
                                <div class="col-12">
                                    <h6 class="section-title">
                                        <i class="bi bi-shield-check me-2"></i>Configurações de Backup
                                    </h6>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="backup_enabled"
                                            name="backup_enabled" {{ config( 'backup.enabled', false ) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="backup_enabled">
                                            Backup Automático Habilitado
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label for="backup_frequency" class="form-label">Frequência</label>
                                    <select class="form-select" id="backup_frequency" name="backup_frequency">
                                        <option value="daily" {{ config( 'backup.frequency' ) == 'daily' ? 'selected' : '' }}>
                                            Diário</option>
                                        <option value="weekly" {{ config( 'backup.frequency' ) == 'weekly' ? 'selected' : '' }}>
                                            Semanal</option>
                                        <option value="monthly" {{ config( 'backup.frequency' ) == 'monthly' ? 'selected' : '' }}>Mensal</option>
                                    </select>
                                </div>
                            </div>

                            {{-- Configurações de Cache --}}
                            <div class="row mb-4">
                                <div class="col-12">
                                    <h6 class="section-title">
                                        <i class="bi bi-lightning me-2"></i>Configurações de Cache
                                    </h6>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="cache_driver" class="form-label">Driver de Cache</label>
                                    <select class="form-select" id="cache_driver" name="cache_driver">
                                        <option value="file" {{ config( 'cache.driver' ) == 'file' ? 'selected' : '' }}>Arquivo
                                        </option>
                                        <option value="redis" {{ config( 'cache.driver' ) == 'redis' ? 'selected' : '' }}>Redis
                                        </option>
                                        <option value="memcached" {{ config( 'cache.driver' ) == 'memcached' ? 'selected' : '' }}>Memcached</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="cache_ttl" class="form-label">TTL Padrão (minutos)</label>
                                    <input type="number" class="form-control" id="cache_ttl" name="cache_ttl"
                                        value="{{ config( 'cache.ttl', 60 ) }}" min="1">
                                </div>
                            </div>

                            {{-- Ações --}}
                            <div class="row">
                                <div class="col-12">
                                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                        <button type="button" class="btn btn-secondary me-md-2" onclick="resetSettings()">
                                            <i class="bi bi-arrow-clockwise me-1"></i>Resetar
                                        </button>
                                        <button type="button" class="btn btn-success" onclick="saveSettings()">
                                            <i class="bi bi-check-circle me-1"></i>Salvar Alterações
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push( 'scripts' )
    <script>
        function saveSettings() {
            const form = document.getElementById( 'settingsForm' );
            const formData = new FormData( form );

            // Simular salvamento (substituir pela chamada AJAX real)
            fetch( '/admin/settings', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector( 'meta[name="csrf-token"]' )?.getAttribute( 'content' ) || ''
                }
            } )
                .then( response => response.json() )
                .then( data => {
                    if ( data.success ) {
                        showAlert( 'Configurações salvas com sucesso!', 'success' );
                    } else {
                        showAlert( 'Erro ao salvar configurações: ' + ( data.message || 'Erro desconhecido' ), 'danger' );
                    }
                } )
                .catch( error => {
                    console.error( 'Erro:', error );
                    showAlert( 'Erro ao salvar configurações. Tente novamente.', 'danger' );
                } );
        }

        function resetSettings() {
            if ( confirm( 'Tem certeza que deseja resetar todas as configurações? Esta ação não pode ser desfeita.' ) ) {
                document.getElementById( 'settingsForm' ).reset();
                showAlert( 'Formulário resetado.', 'info' );
            }
        }

        function showAlert( message, type = 'info' ) {
            const alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                <i class="bi bi-info-circle me-2"></i>${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;

            // Remover alertas existentes
            const existingAlerts = document.querySelectorAll( '.alert' );
            existingAlerts.forEach( alert => alert.remove() );

            // Adicionar novo alerta no topo
            const container = document.querySelector( '.container-fluid .row .col-12' );
            const alertDiv = document.createElement( 'div' );
            alertDiv.innerHTML = alertHtml;
            container.insertBefore( alertDiv.firstElementChild, container.firstChild );
        }
    </script>
@endpush
