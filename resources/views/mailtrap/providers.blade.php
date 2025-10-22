@extends( 'layouts.app' )

@section( 'content' )
    <div class="container-fluid py-4">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="h3 mb-0 text-gray-800">
                            <i class="bi bi-gear me-2"></i>
                            {{ $title ?? 'Configuração de Provedores de E-mail' }}
                        </h1>
                        <p class="text-muted mt-1">Gerencie os provedores de e-mail disponíveis no sistema</p>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-primary" onclick="refreshProviders()">
                            <i class="bi bi-arrow-clockwise me-1"></i>
                            Atualizar
                        </button>
                        <a href="{{ route( 'mailtrap.index' ) }}" class="btn btn-secondary">
                            <i class="bi bi-arrow-left me-1"></i>
                            Voltar ao Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Status do Provedor Atual -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-left-primary">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h6 class="text-primary font-weight-bold text-uppercase mb-1">
                                    Provedor Atualmente Ativo
                                </h6>
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-circle-fill me-2"
                                        style="color: {{ isset( $current_provider ) && $current_provider[ 'is_active' ] ? '#28a745' : '#dc3545' }}"></i>
                                    <span class="h4 mb-0 font-weight-bold text-gray-800">
                                        {{ $current_provider[ 'provider' ] ?? 'Nenhum' }}
                                    </span>
                                    @if( isset( $current_provider ) && $current_provider[ 'is_active' ] )
                                        <span class="badge bg-success ms-2">Ativo</span>
                                    @else
                                        <span class="badge bg-danger ms-2">Inativo</span>
                                    @endif
                                </div>
                                @if( isset( $current_provider ) && $current_provider[ 'description' ] )
                                    <p class="text-muted mt-2 mb-0">{{ $current_provider[ 'description' ] }}</p>
                                @endif
                            </div>
                            <div class="col-md-4 text-end">
                                <button type="button" class="btn btn-sm btn-primary" onclick="testCurrentProvider()">
                                    <i class="bi bi-lightning me-1"></i>
                                    Testar Provedor
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Lista de Provedores -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="m-0 font-weight-bold text-primary">
                            <i class="bi bi-envelope-at me-2"></i>
                            Provedores Disponíveis
                        </h6>
                        <span class="badge bg-info">{{ isset( $providers ) ? count( $providers ) : 0 }} provedores</span>
                    </div>
                    <div class="card-body">
                        @if( isset( $providers ) && count( $providers ) > 0 )
                            <div class="row">
                                @foreach( $providers as $providerKey => $provider )
                                    <div class="col-xl-6 col-lg-12 mb-4">
                                        <div
                                            class="card h-100 border-left {{ isset( $current_provider ) && $current_provider[ 'provider' ] === $providerKey ? 'border-success' : 'border-secondary' }}">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between align-items-start mb-3">
                                                    <div class="d-flex align-items-center">
                                                        <div class="provider-icon me-3">
                                                            @if( $providerKey === 'mailtrap' )
                                                                <i class="bi bi-envelope-paper fs-2 text-primary"></i>
                                                            @elseif( $providerKey === 'smtp' )
                                                                <i class="bi bi-envelope-exclamation fs-2 text-warning"></i>
                                                            @elseif( $providerKey === 'ses' )
                                                                <i class="bi bi-cloud-arrow-up fs-2 text-success"></i>
                                                            @elseif( $providerKey === 'log' )
                                                                <i class="bi bi-journal-text fs-2 text-info"></i>
                                                            @else
                                                                <i class="bi bi-envelope fs-2 text-secondary"></i>
                                                            @endif
                                                        </div>
                                                        <div>
                                                            <h5 class="card-title mb-1">
                                                                {{ $provider[ 'name' ] ?? ucfirst( $providerKey ) }}</h5>
                                                            <p class="card-text small text-muted mb-0">
                                                                {{ $provider[ 'description' ] ?? 'Provedor de e-mail' }}</p>
                                                        </div>
                                                    </div>
                                                    <div class="text-end">
                                                        @if( isset( $current_provider ) && $current_provider[ 'provider' ] === $providerKey )
                                                            <span class="badge bg-success">
                                                                <i class="bi bi-check-circle me-1"></i>
                                                                Ativo
                                                            </span>
                                                        @else
                                                            <button type="button" class="btn btn-sm btn-outline-primary"
                                                                onclick="activateProvider('{{ $providerKey }}')">
                                                                <i class="bi bi-check-lg me-1"></i>
                                                                Ativar
                                                            </button>
                                                        @endif
                                                    </div>
                                                </div>

                                                <!-- Configurações do Provedor -->
                                                @if( isset( $provider_configs[ $providerKey ] ) )
                                                    <div class="provider-config mb-3">
                                                        <h6 class="small text-muted mb-2">CONFIGURAÇÕES</h6>
                                                        <div class="config-details">
                                                            @foreach( $provider_configs[ $providerKey ] as $key => $value )
                                                                @if( $key !== 'password' && $key !== 'secret' && !empty( $value ) )
                                                                    <div
                                                                        class="config-item d-flex justify-content-between align-items-center py-1">
                                                                        <small class="text-muted">{{ strtoupper( $key ) }}:</small>
                                                                        <small class="fw-bold">
                                                                            @if( is_bool( $value ) )
                                                                                {{ $value ? 'Sim' : 'Não' }}
                                                                            @else
                                                                                {{ Str::limit( $value, 30 ) }}
                                                                            @endif
                                                                        </small>
                                                                    </div>
                                                                @endif
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                @endif

                                                <!-- Ações do Provedor -->
                                                <div class="provider-actions d-flex gap-2">
                                                    <button type="button" class="btn btn-sm btn-outline-primary flex-fill"
                                                        onclick="testProvider('{{ $providerKey }}')">
                                                        <i class="bi bi-lightning me-1"></i>
                                                        Testar
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-info flex-fill"
                                                        onclick="viewProviderConfig('{{ $providerKey }}')">
                                                        <i class="bi bi-eye me-1"></i>
                                                        Detalhes
                                                    </button>
                                                    @if( $providerKey !== 'log' )
                                                        <button type="button" class="btn btn-sm btn-outline-secondary flex-fill"
                                                            onclick="configureProvider('{{ $providerKey }}')">
                                                            <i class="bi bi-pencil me-1"></i>
                                                            Configurar
                                                        </button>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-5">
                                <i class="bi bi-envelope-x fs-1 text-muted mb-3"></i>
                                <h6 class="text-muted">Nenhum provedor configurado</h6>
                                <p class="text-muted">Configure pelo menos um provedor de e-mail para começar.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Estatísticas dos Provedores -->
        @if( isset( $provider_stats ) )
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="m-0 font-weight-bold text-primary">
                                <i class="bi bi-graph-up me-2"></i>
                                Estatísticas dos Provedores
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                @foreach( $provider_stats as $provider => $stats )
                                    <div class="col-md-3 mb-3">
                                        <div class="card border">
                                            <div class="card-body text-center">
                                                <div class="provider-stat-icon mb-2">
                                                    @if( $provider === 'mailtrap' )
                                                        <i class="bi bi-envelope-paper fs-3 text-primary"></i>
                                                    @elseif( $provider === 'smtp' )
                                                        <i class="bi bi-envelope-exclamation fs-3 text-warning"></i>
                                                    @elseif( $provider === 'ses' )
                                                        <i class="bi bi-cloud-arrow-up fs-3 text-success"></i>
                                                    @else
                                                        <i class="bi bi-envelope fs-3 text-secondary"></i>
                                                    @endif
                                                </div>
                                                <h6 class="text-uppercase small text-muted">{{ ucfirst( $provider ) }}</h6>
                                                <div class="d-flex justify-content-center gap-3">
                                                    <div class="text-center">
                                                        <div class="fw-bold text-success">{{ $stats[ 'success_rate' ] ?? 0 }}%</div>
                                                        <small class="text-muted">Sucesso</small>
                                                    </div>
                                                    <div class="text-center">
                                                        <div class="fw-bold text-primary">{{ $stats[ 'total_tests' ] ?? 0 }}</div>
                                                        <small class="text-muted">Testes</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- Modal de Configuração do Provedor -->
    <div class="modal fade" id="providerConfigModal" tabindex="-1" aria-labelledby="providerConfigModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="providerConfigModalLabel">Configuração do Provedor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="providerConfigForm">
                        <div id="configFields">
                            <!-- Campos dinâmicos serão inseridos aqui via JavaScript -->
                        </div>
                    </form>
                    <div id="configResult" class="mt-3" style="display: none;">
                        <div class="alert" id="configAlert">
                            <div id="configMessage"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="saveProviderConfig()">Salvar
                        Configuração</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Detalhes do Provedor -->
    <div class="modal fade" id="providerDetailsModal" tabindex="-1" aria-labelledby="providerDetailsModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="providerDetailsModalLabel">Detalhes do Provedor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="providerDetailsContent">
                        <!-- Conteúdo dinâmico será inserido aqui -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                    <button type="button" class="btn btn-primary" onclick="testProviderFromDetails()">Testar
                        Provedor</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@push( 'scripts' )
    <script>
        let currentProviderKey = '';

        function refreshProviders() {
            window.location.reload();
        }

        function testCurrentProvider() {
            const currentProvider = '{{ $current_provider[ "provider" ] ?? "" }}';
            if ( currentProvider ) {
                testProvider( currentProvider );
            } else {
                showAlert( 'Nenhum provedor ativo selecionado', 'warning' );
            }
        }

        function testProvider( providerKey ) {
            showLoading( 'Testando provedor...' );

            fetch( '{{ route( "mailtrap.test-provider" ) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify( {
                    provider: providerKey
                } )
            } )
                .then( response => response.json() )
                .then( data => {
                    hideLoading();
                    if ( data.success ) {
                        showAlert( 'Provedor testado com sucesso!', 'success' );
                    } else {
                        showAlert( 'Erro ao testar provedor: ' + ( data.error || 'Erro desconhecido' ), 'danger' );
                    }
                } )
                .catch( error => {
                    hideLoading();
                    showAlert( 'Erro interno: ' + error.message, 'danger' );
                } );
        }

        function activateProvider( providerKey ) {
            if ( confirm( 'Tem certeza que deseja ativar este provedor?' ) ) {
                showLoading( 'Ativando provedor...' );

                // Simular ativação (em produção seria uma chamada real)
                setTimeout( () => {
                    hideLoading();
                    showAlert( 'Provedor ativado com sucesso!', 'success' );
                    setTimeout( () => window.location.reload(), 1500 );
                }, 1000 );
            }
        }

        function configureProvider( providerKey ) {
            currentProviderKey = providerKey;

            // Buscar configuração atual do provedor
            fetch( `{{ route( 'mailtrap.provider-config', '' ) }}/${providerKey}` )
                .then( response => response.json() )
                .then( data => {
                    if ( data.success ) {
                        showProviderConfigModal( providerKey, data.config );
                    } else {
                        showAlert( 'Erro ao carregar configuração: ' + ( data.error || 'Erro desconhecido' ), 'danger' );
                    }
                } )
                .catch( error => {
                    showAlert( 'Erro interno: ' + error.message, 'danger' );
                } );
        }

        function viewProviderConfig( providerKey ) {
            showLoading( 'Carregando detalhes...' );

            fetch( `{{ route( 'mailtrap.provider-config', '' ) }}/${providerKey}` )
                .then( response => response.json() )
                .then( data => {
                    hideLoading();
                    if ( data.success ) {
                        showProviderDetailsModal( providerKey, data );
                    } else {
                        showAlert( 'Erro ao carregar detalhes: ' + ( data.error || 'Erro desconhecido' ), 'danger' );
                    }
                } )
                .catch( error => {
                    hideLoading();
                    showAlert( 'Erro interno: ' + error.message, 'danger' );
                } );
        }

        function showProviderConfigModal( providerKey, config ) {
            const modal = new bootstrap.Modal( document.getElementById( 'providerConfigModal' ) );
            const fieldsDiv = document.getElementById( 'configFields' );

            // Criar campos dinâmicos baseados no provedor
            let fieldsHtml = '';

            if ( providerKey === 'smtp' ) {
                fieldsHtml = `
                <div class="mb-3">
                    <label for="smtp_host" class="form-label">Host SMTP</label>
                    <input type="text" class="form-control" id="smtp_host" name="host" value="${config.host || ''}" required>
                </div>
                <div class="mb-3">
                    <label for="smtp_port" class="form-label">Porta</label>
                    <input type="number" class="form-control" id="smtp_port" name="port" value="${config.port || '587'}" required>
                </div>
                <div class="mb-3">
                    <label for="smtp_username" class="form-label">Usuário</label>
                    <input type="text" class="form-control" id="smtp_username" name="username" value="${config.username || ''}" required>
                </div>
                <div class="mb-3">
                    <label for="smtp_password" class="form-label">Senha</label>
                    <input type="password" class="form-control" id="smtp_password" name="password" value="${config.password || ''}" required>
                </div>
                <div class="mb-3">
                    <label for="smtp_encryption" class="form-label">Criptografia</label>
                    <select class="form-select" id="smtp_encryption" name="encryption">
                        <option value="tls" ${config.encryption === 'tls' ? 'selected' : ''}>TLS</option>
                        <option value="ssl" ${config.encryption === 'ssl' ? 'selected' : ''}>SSL</option>
                        <option value="" ${!config.encryption ? 'selected' : ''}>Nenhuma</option>
                    </select>
                </div>
            `;
            } else if ( providerKey === 'mailtrap' ) {
                fieldsHtml = `
                <div class="mb-3">
                    <label for="mailtrap_api_key" class="form-label">API Key</label>
                    <input type="password" class="form-control" id="mailtrap_api_key" name="api_key" value="${config.api_key || ''}" required>
                </div>
                <div class="mb-3">
                    <label for="mailtrap_inbox_id" class="form-label">Inbox ID</label>
                    <input type="text" class="form-control" id="mailtrap_inbox_id" name="inbox_id" value="${config.inbox_id || ''}" required>
                </div>
            `;
            } else if ( providerKey === 'ses' ) {
                fieldsHtml = `
                <div class="mb-3">
                    <label for="ses_key" class="form-label">Access Key ID</label>
                    <input type="text" class="form-control" id="ses_key" name="key" value="${config.key || ''}" required>
                </div>
                <div class="mb-3">
                    <label for="ses_secret" class="form-label">Secret Access Key</label>
                    <input type="password" class="form-control" id="ses_secret" name="secret" value="${config.secret || ''}" required>
                </div>
                <div class="mb-3">
                    <label for="ses_region" class="form-label">Região</label>
                    <input type="text" class="form-control" id="ses_region" name="region" value="${config.region || 'us-east-1'}" required>
                </div>
            `;
            }

            fieldsDiv.innerHTML = fieldsHtml;
            modal.show();
        }

        function showProviderDetailsModal( providerKey, data ) {
            const modal = new bootstrap.Modal( document.getElementById( 'providerDetailsModal' ) );
            const contentDiv = document.getElementById( 'providerDetailsContent' );

            const isSuccess = data.test_result && data.test_result.is_success;

            contentDiv.innerHTML = `
            <div class="row">
                <div class="col-md-6">
                    <h6>Informações Gerais</h6>
                    <table class="table table-sm">
                        <tr>
                            <td><strong>Provedor:</strong></td>
                            <td>${data.provider}</td>
                        </tr>
                        <tr>
                            <td><strong>Status do Teste:</strong></td>
                            <td>
                                ${isSuccess ?
                    '<span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Sucesso</span>' :
                    '<span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i>Falha</span>'
                }
                            </td>
                        </tr>
                        ${data.test_result ? `
                            <tr>
                                <td><strong>Mensagem:</strong></td>
                                <td>${data.test_result.message || 'N/A'}</td>
                            </tr>
                        ` : ''}
                    </table>
                </div>
                <div class="col-md-6">
                    <h6>Configurações</h6>
                    <div class="config-details">
                        ${Object.entries( data.config || {} ).map( ( [key, value] ) => {
                    if ( key !== 'password' && key !== 'secret' && value ) {
                        return `
                                    <div class="d-flex justify-content-between align-items-center py-1">
                                        <small class="text-muted">${key.toUpperCase()}:</small>
                                        <small class="fw-bold">${typeof value === 'boolean' ? ( value ? 'Sim' : 'Não' ) : value}</small>
                                    </div>
                                `;
                    }
                    return '';
                } ).join( '' )}
                    </div>
                </div>
            </div>
        `;

            modal.show();
        }

        function saveProviderConfig() {
            const form = document.getElementById( 'providerConfigForm' );
            const formData = new FormData( form );

            showLoading( 'Salvando configuração...' );

            fetch( '{{ route( "mailtrap.test-provider" ) }}', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: formData
            } )
                .then( response => response.json() )
                .then( data => {
                    hideLoading();

                    const resultDiv = document.getElementById( 'configResult' );
                    const alertDiv = document.getElementById( 'configAlert' );
                    const messageDiv = document.getElementById( 'configMessage' );

                    resultDiv.style.display = 'block';

                    if ( data.success ) {
                        alertDiv.className = 'alert alert-success';
                        messageDiv.innerHTML = '<i class="bi bi-check-circle me-2"></i>Configuração salva com sucesso!';

                        setTimeout( () => {
                            bootstrap.Modal.getInstance( document.getElementById( 'providerConfigModal' ) ).hide();
                            window.location.reload();
                        }, 1500 );
                    } else {
                        alertDiv.className = 'alert alert-danger';
                        messageDiv.innerHTML = '<i class="bi bi-x-circle me-2"></i>Erro ao salvar configuração: ' + ( data.error || 'Erro desconhecido' );
                    }
                } )
                .catch( error => {
                    hideLoading();

                    const resultDiv = document.getElementById( 'configResult' );
                    const alertDiv = document.getElementById( 'configAlert' );
                    const messageDiv = document.getElementById( 'configMessage' );

                    resultDiv.style.display = 'block';
                    alertDiv.className = 'alert alert-danger';
                    messageDiv.innerHTML = '<i class="bi bi-exclamation-triangle me-2"></i>Erro interno: ' + error.message;
                } );
        }

        function testProviderFromDetails() {
            const modal = bootstrap.Modal.getInstance( document.getElementById( 'providerDetailsModal' ) );
            modal.hide();

            // Aguardar modal fechar completamente
            setTimeout( () => {
                testProvider( currentProviderKey );
            }, 300 );
        }

        function showLoading( message = 'Carregando...' ) {
            if ( !document.getElementById( 'loadingOverlay' ) ) {
                const overlay = document.createElement( 'div' );
                overlay.id = 'loadingOverlay';
                overlay.className = 'd-flex justify-content-center align-items-center position-fixed w-100 h-100 bg-dark bg-opacity-50';
                overlay.style.zIndex = '9999';
                overlay.style.top = '0';
                overlay.style.left = '0';
                overlay.innerHTML = `
                <div class="bg-white p-4 rounded shadow">
                    <div class="d-flex align-items-center">
                        <div class="spinner-border text-primary me-3" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <span>${message}</span>
                    </div>
                </div>
            `;
                document.body.appendChild( overlay );
            }
        }

        function hideLoading() {
            const overlay = document.getElementById( 'loadingOverlay' );
            if ( overlay ) {
                overlay.remove();
            }
        }

        function showAlert( message, type = 'info' ) {
            const alertDiv = document.createElement( 'div' );
            alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
            alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 10000; min-width: 300px;';
            alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

            document.body.appendChild( alertDiv );

            setTimeout( () => {
                if ( alertDiv.parentNode ) {
                    alertDiv.remove();
                }
            }, 5000 );
        }
    </script>
@endpush
