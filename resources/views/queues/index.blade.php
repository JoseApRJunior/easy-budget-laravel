@extends( 'layouts.app' )

@section( 'title', 'Gerenciamento de Filas' )

@section( 'content' )
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h3 mb-0">Gerenciamento de Filas de E-mail</h1>
                    <div>
                        <x-button variant="primary" label="Atualizar" icon="sync-alt" onclick="refreshStats()" class="me-2" />
                        <x-button variant="success" label="Testar E-mail" icon="paper-plane" onclick="testEmail()" />
                    </div>
                </div>

                <!-- Status de Saúde -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-heartbeat me-2"></i>Status de Saúde das Filas
                                </h5>
                            </div>
                            <div class="card-body">
                                <div id="health-status">
                                    <div class="text-center">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">Carregando...</span>
                                        </div>
                                        <p class="mt-2">Carregando status...</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Estatísticas das Filas -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-chart-bar me-2"></i>Estatísticas das Filas
                                </h5>
                            </div>
                            <div class="card-body">
                                <div id="queue-stats">
                                    <div class="text-center">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">Carregando...</span>
                                        </div>
                                        <p class="mt-2">Carregando estatísticas...</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Ações de Gerenciamento -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-broom me-2"></i>Limpeza de Jobs
                                </h5>
                            </div>
                            <div class="card-body">
                                <p class="text-muted">Remove jobs antigos e falhados para manter o sistema limpo.</p>
                                <div class="d-flex gap-2">
                                    <x-button variant="warning" label="Limpar Jobs (7 dias)" onclick="cleanupJobs(7)" />
                                    <x-button variant="warning" outline label="Limpar Jobs (30 dias)" onclick="cleanupJobs(30)" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-redo me-2"></i>Retry de Jobs Falhados
                                </h5>
                            </div>
                            <div class="card-body">
                                <p class="text-muted">Tenta processar novamente jobs que falharam.</p>
                                <x-button variant="info" label="Retry Jobs Falhados" onclick="retryFailedJobs()" />
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Logs de Atividade -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-list me-2"></i>Logs de Atividade Recente
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Data/Hora</th>
                                                <th>Nível</th>
                                                <th>Mensagem</th>
                                                <th>Contexto</th>
                                            </tr>
                                        </thead>
                                        <tbody id="activity-logs">
                                            <tr>
                                                <td colspan="4" class="text-center text-muted">
                                                    Carregando logs...
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Teste de E-mail -->
    <div class="modal fade" id="testEmailModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Testar Envio de E-mail</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="testEmail" class="form-label">E-mail de Destino</label>
                        <input type="email" class="form-control" id="testEmail" value="{{ auth()->user()->email }}">
                    </div>
                    <div id="testEmailResult"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                    <button type="button" class="btn btn-primary" onclick="sendTestEmail()">Enviar Teste</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function refreshStats() {
            loadStats();
            loadHealth();
            loadActivityLogs();
        }

        function loadStats() {
            $( '#queue-stats' ).html( `
            <div class="text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Carregando...</span>
                </div>
                <p class="mt-2">Carregando estatísticas...</p>
            </div>
        `);

            $.get( '{{ route( "queues.stats" ) }}' )
                .done( function ( response ) {
                    if ( response.success ) {
                        renderStats( response.data );
                    } else {
                        $( '#queue-stats' ).html( `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Erro ao carregar estatísticas: ${response.error}
                        </div>
                    `);
                    }
                } )
                .fail( function () {
                    $( '#queue-stats' ).html( `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Erro de conexão ao carregar estatísticas
                    </div>
                `);
                } );
        }

        function loadHealth() {
            $( '#health-status' ).html( `
            <div class="text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Carregando...</span>
                </div>
                <p class="mt-2">Carregando status...</p>
            </div>
        `);

            $.get( '{{ route( "queues.health" ) }}' )
                .done( function ( response ) {
                    if ( response.success ) {
                        renderHealth( response.data );
                    } else {
                        $( '#health-status' ).html( `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Erro ao carregar status: ${response.error}
                        </div>
                    `);
                    }
                } )
                .fail( function () {
                    $( '#health-status' ).html( `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Erro de conexão ao carregar status
                    </div>
                `);
                } );
        }

        function renderStats( data ) {
            let html = '<div class="row">';

            if ( data.queues ) {
                Object.keys( data.queues ).forEach( type => {
                    const queue = data.queues[type];
                    const statusClass = queue.status === 'critical' ? 'danger' :
                        queue.status === 'warning' ? 'warning' : 'success';

                    html += `
                    <div class="col-md-6 col-lg-3 mb-3">
                        <div class="card border-${statusClass}">
                            <div class="card-body">
                                <h6 class="card-title text-${statusClass}">
                                    <i class="fas fa-envelope me-2"></i>
                                    ${type.charAt( 0 ).toUpperCase() + type.slice( 1 )}
                                </h6>
                                <div class="row text-center">
                                    <div class="col-4">
                                        <small class="text-muted">Na Fila</small>
                                        <div class="fw-bold">${queue.queued_emails}</div>
                                    </div>
                                    <div class="col-4">
                                        <small class="text-muted">Processando</small>
                                        <div class="fw-bold">${queue.processing_emails}</div>
                                    </div>
                                    <div class="col-4">
                                        <small class="text-muted">Falhos</small>
                                        <div class="fw-bold text-danger">${queue.failed_emails}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                } );
            }

            html += '</div>';
            $( '#queue-stats' ).html( html );
        }

        function renderHealth( data ) {
            const statusClass = data.status === 'critical' ? 'danger' :
                data.status === 'warning' ? 'warning' : 'success';

            let html = `
            <div class="d-flex align-items-center">
                <div class="flex-shrink-0 me-3">
                    <i class="fas fa-circle text-${statusClass}" style="font-size: 1.5rem;"></i>
                </div>
                <div class="flex-grow-1">
                    <h5 class="mb-1">Status: ${data.status.toUpperCase()}</h5>
                    <p class="text-muted mb-0">${getHealthMessage( data.status )}</p>
                </div>
            </div>
        `;

            if ( data.alerts && data.alerts.length > 0 ) {
                html += '<div class="mt-3"><div class="alert alert-danger"><strong>Alertas:</strong><ul class="mb-0 mt-2">';
                data.alerts.forEach( alert => {
                    html += `<li>${alert}</li>`;
                } );
                html += '</ul></div></div>';
            }

            if ( data.warnings && data.warnings.length > 0 ) {
                html += '<div class="mt-2"><div class="alert alert-warning"><strong>Avisos:</strong><ul class="mb-0 mt-2">';
                data.warnings.forEach( warning => {
                    html += `<li>${warning}</li>`;
                } );
                html += '</ul></div></div>';
            }

            $( '#health-status' ).html( html );
        }

        function getHealthMessage( status ) {
            const messages = {
                'healthy': 'Todas as filas estão funcionando normalmente.',
                'warning': 'Algumas filas precisam de atenção.',
                'critical': 'Problemas críticos detectados nas filas.',
                'error': 'Erro ao verificar status das filas.'
            };
            return messages[status] || 'Status desconhecido.';
        }

        function testEmail() {
            $( '#testEmailModal' ).modal( 'show' );
        }

        function sendTestEmail() {
            const email = $( '#testEmail' ).val();
            const $result = $( '#testEmailResult' );

            $result.html( `
            <div class="text-center">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Enviando...</span>
                </div>
                <p class="mt-2">Enviando e-mail de teste...</p>
            </div>
        `);

            $.post( '{{ route( "queues.test-email" ) }}', { email: email } )
                .done( function ( response ) {
                    if ( response.success ) {
                        $result.html( `
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i>
                            ${response.message}
                        </div>
                    `);
                        setTimeout( () => {
                            $( '#testEmailModal' ).modal( 'hide' );
                            refreshStats();
                        }, 2000 );
                    } else {
                        $result.html( `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            ${response.message}
                        </div>
                    `);
                    }
                } )
                .fail( function () {
                    $result.html( `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Erro de conexão ao enviar teste
                    </div>
                `);
                } );
        }

        function cleanupJobs( days ) {
            if ( !confirm( `Tem certeza que deseja limpar jobs com mais de ${days} dias?` ) ) {
                return;
            }

            $.post( '{{ route( "queues.cleanup" ) }}', { days: days } )
                .done( function ( response ) {
                    if ( response.success ) {
                        alert( response.message );
                        refreshStats();
                    } else {
                        alert( 'Erro: ' + response.message );
                    }
                } )
                .fail( function () {
                    alert( 'Erro de conexão ao limpar jobs' );
                } );
        }

        function retryFailedJobs() {
            if ( !confirm( 'Tem certeza que deseja retentar todos os jobs falhados?' ) ) {
                return;
            }

            $.post( '{{ route( "queues.retry" ) }}' )
                .done( function ( response ) {
                    if ( response.success ) {
                        alert( response.message );
                        refreshStats();
                    } else {
                        alert( 'Erro: ' + response.message );
                    }
                } )
                .fail( function () {
                    alert( 'Erro de conexão ao retentar jobs' );
                } );
        }

        function loadActivityLogs() {
            $( '#activity-logs' ).html( `
            <tr>
                <td colspan="4" class="text-center text-muted">
                    <div class="spinner-border spinner-border-sm text-primary" role="status">
                        <span class="visually-hidden">Carregando...</span>
                    </div>
                    Carregando logs...
                </td>
            </tr>
        `);

            // Simulação de logs (em produção, seria uma rota específica)
            setTimeout( () => {
                $( '#activity-logs' ).html( `
                <tr>
                    <td>${new Date().toLocaleString()}</td>
                    <td><span class="badge bg-info">INFO</span></td>
                    <td>E-mail enviado com sucesso</td>
                    <td>tipo: normal, fila: emails</td>
                </tr>
                <tr>
                    <td>${new Date( Date.now() - 300000 ).toLocaleString()}</td>
                    <td><span class="badge bg-warning">WARNING</span></td>
                    <td>Job falhou - tentando novamente</td>
                    <td>tipo: critical, tentativa: 2</td>
                </tr>
                <tr>
                    <td>${new Date( Date.now() - 600000 ).toLocaleString()}</td>
                    <td><span class="badge bg-success">INFO</span></td>
                    <td>E-mail enfileirado para processamento</td>
                    <td>tipo: high, destinatário: teste@exemplo.com</td>
                </tr>
            `);
            }, 1000 );
        }

        // Carregar dados iniciais
        $( document ).ready( function () {
            refreshStats();
            setInterval( refreshStats, 30000 ); // Atualizar a cada 30 segundos
        } );
    </script>

@endsection
