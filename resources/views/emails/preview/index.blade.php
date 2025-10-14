@extends( 'layouts.app' )

@section( 'title', 'Email Preview System' )

@section( 'page-title', 'Sistema de Preview de E-mails' )

@section( 'breadcrumb' )
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route( 'dashboard' ) }}">Dashboard</a></li>
            <li class="breadcrumb-item active">Email Preview</li>
        </ol>
    </nav>
@endsection

@push( 'styles' )
    <style>
        .email-preview-container {
            background: #f8f9fa;
            min-height: 100vh;
        }

        .preview-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
        }

        .device-selector {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 1rem;
        }

        .device-button {
            border: none;
            background: transparent;
            padding: 1rem;
            border-radius: 8px;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
            flex: 1;
        }

        .device-button:hover {
            background: #f8f9fa;
            transform: translateY(-2px);
        }

        .device-button.active {
            background: #667eea;
            color: white;
        }

        .locale-selector {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 1rem;
        }

        .locale-button {
            border: none;
            background: transparent;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex: 1;
        }

        .locale-button:hover {
            background: #f8f9fa;
        }

        .locale-button.active {
            background: #28a745;
            color: white;
        }

        .email-type-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            cursor: pointer;
            border: 2px solid transparent;
        }

        .email-type-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .email-type-card.selected {
            border-color: #667eea;
            background: #f8f9ff;
        }

        .email-type-icon {
            width: 48px;
            height: 48px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .email-preview-frame {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            position: relative;
        }

        .preview-toolbar {
            background: #f8f9fa;
            padding: 1rem;
            border-bottom: 1px solid #dee2e6;
            display: flex;
            justify-content: between;
            align-items: center;
        }

        .preview-content {
            padding: 2rem;
            min-height: 600px;
            overflow-y: auto;
        }

        .device-frame {
            border: 8px solid #333;
            border-radius: 20px;
            overflow: hidden;
            margin: 2rem auto;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
            position: relative;
        }

        .device-frame.mobile {
            width: 375px;
            height: 667px;
        }

        .device-frame.tablet {
            width: 768px;
            height: 600px;
        }

        .device-frame.desktop {
            width: 100%;
            max-width: 1200px;
            height: 800px;
        }

        .device-notch {
            position: absolute;
            top: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 120px;
            height: 20px;
            background: #333;
            border-radius: 0 0 15px 15px;
            z-index: 10;
        }

        .stats-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
            margin-bottom: 1rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .stat-item {
            text-align: center;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: #667eea;
            display: block;
        }

        .stat-label {
            font-size: 0.9rem;
            color: #6c757d;
            margin-top: 0.5rem;
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            margin-top: 1rem;
        }

        .btn-action {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 6px;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #5a67d8;
            transform: translateY(-1px);
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-success:hover {
            background: #218838;
            transform: translateY(-1px);
        }

        .btn-warning {
            background: #ffc107;
            color: #212529;
        }

        .btn-warning:hover {
            background: #e0a800;
            transform: translateY(-1px);
        }

        .loading-spinner {
            display: none;
            text-align: center;
            padding: 2rem;
        }

        .loading-spinner.show {
            display: block;
        }

        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        .error-message {
            background: #f8d7da;
            color: #721c24;
            padding: 1rem;
            border-radius: 8px;
            margin: 1rem 0;
            display: none;
        }

        .error-message.show {
            display: block;
        }

        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 1rem;
            border-radius: 8px;
            margin: 1rem 0;
            display: none;
        }

        .success-message.show {
            display: block;
        }

        .comparison-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-top: 2rem;
        }

        .comparison-item {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .comparison-header {
            background: #f8f9fa;
            padding: 1rem;
            font-weight: bold;
            text-align: center;
            border-bottom: 1px solid #dee2e6;
        }

        @media (max-width: 768px) {
            .comparison-container {
                grid-template-columns: 1fr;
            }

            .device-frame.mobile,
            .device-frame.tablet {
                width: 100%;
                max-width: 375px;
            }
        }
    </style>
@endpush

@section( 'content' )
    <div class="email-preview-container">
        <div class="preview-header">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-8">
                        <h1 class="mb-2">
                            <i class="fas fa-envelope-open-text me-3"></i>
                            Sistema de Preview de E-mails
                        </h1>
                        <p class="mb-0 opacity-75">
                            Visualize e teste templates de e-mail em diferentes dispositivos e idiomas
                        </p>
                    </div>
                    <div class="col-md-4 text-md-end mt-3 mt-md-0">
                        <div class="stats-card d-inline-block">
                            <div class="stat-value" id="totalPreviews">{{ $stats[ 'total_previews' ] ?? 0 }}</div>
                            <div class="stat-label">Total de Previews</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="container-fluid">
            <!-- Seletores de Configuração -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="device-selector p-3">
                        <h6 class="mb-3">
                            <i class="fas fa-mobile-alt me-2"></i>
                            Dispositivo para Preview
                        </h6>
                        <div class="d-flex gap-2">
                            @foreach( $availableDevices as $key => $device )
                                <button class="device-button" data-device="{{ $key }}" onclick="selectDevice('{{ $key }}')">
                                    <i class="fas fa-{{ $device[ 'icon' ] }}"></i>
                                    {{ $device[ 'name' ] }}
                                    <small class="d-block">{{ $device[ 'width' ] }}x{{ $device[ 'height' ] }}</small>
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-12">
                    <div class="locale-selector p-3">
                        <h6 class="mb-3">
                            <i class="fas fa-language me-2"></i>
                            Idioma para Preview
                        </h6>
                        <div class="d-flex gap-2">
                            @foreach( $availableLocales as $key => $locale )
                                <button class="locale-button" data-locale="{{ $key }}" onclick="selectLocale('{{ $key }}')">
                                    <span class="flag">{{ $locale[ 'flag' ] }}</span>
                                    {{ $locale[ 'name' ] }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tipos de E-mail -->
            <div class="row mb-4">
                <div class="col-12">
                    <h5 class="mb-3">
                        <i class="fas fa-envelope me-2"></i>
                        Tipos de E-mail Disponíveis
                    </h5>
                    <div class="row" id="emailTypesContainer">
                        @foreach( $availableEmails as $key => $email )
                            <div class="col-lg-3 col-md-4 col-sm-6 mb-3">
                                <div class="email-type-card p-3 h-100" data-email-type="{{ $key }}"
                                    onclick="selectEmailType('{{ $key }}')">
                                    <div class="email-type-icon bg-light d-flex align-items-center justify-content-center"
                                        style="background-color: {{ $email[ 'category' ] === 'authentication' ? '#e3f2fd' : ( $email[ 'category' ] === 'business' ? '#fff3e0' : '#f3e5f5' ) }} !important;">
                                        <i class="fas fa-{{ $email[ 'icon' ] }}"
                                            style="color: {{ $email[ 'category' ] === 'authentication' ? '#2196f3' : ( $email[ 'category' ] === 'business' ? '#ff9800' : '#9c27b0' ) }};"></i>
                                    </div>
                                    <h6 class="mb-2">{{ $email[ 'name' ] }}</h6>
                                    <p class="small text-muted mb-0">{{ $email[ 'description' ] }}</p>
                                    <span
                                        class="badge bg-{{ $email[ 'category' ] === 'authentication' ? 'primary' : ( $email[ 'category' ] === 'business' ? 'warning' : 'secondary' ) }} mt-2">
                                        {{ ucfirst( $email[ 'category' ] ) }}
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Área de Preview -->
            <div class="row">
                <div class="col-lg-8">
                    <div class="email-preview-frame">
                        <div class="preview-toolbar">
                            <div class="d-flex justify-content-between align-items-center w-100">
                                <div>
                                    <strong id="currentEmailType">Selecione um tipo de e-mail</strong>
                                    <span class="text-muted ms-2" id="currentDevice">• Desktop</span>
                                    <span class="text-muted ms-2" id="currentLocale">• Português (Brasil)</span>
                                </div>
                                <div class="action-buttons">
                                    <button class="btn-action btn-primary" onclick="refreshPreview()">
                                        <i class="fas fa-sync-alt"></i>
                                        Atualizar
                                    </button>
                                    <button class="btn-action btn-success" onclick="testQueue()">
                                        <i class="fas fa-paper-plane"></i>
                                        Testar Fila
                                    </button>
                                    <button class="btn-action btn-warning" onclick="exportTemplate()">
                                        <i class="fas fa-download"></i>
                                        Exportar
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div id="previewLoading" class="loading-spinner">
                            <div class="spinner"></div>
                            <p class="mt-2 text-muted">Carregando preview...</p>
                        </div>

                        <div id="previewError" class="error-message"></div>

                        <div class="preview-content">
                            <div id="deviceFrame" class="device-frame desktop">
                                <div class="device-notch"></div>
                                <iframe id="emailFrame" src="" width="100%" height="100%" frameborder="0"></iframe>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Painel Lateral -->
                <div class="col-lg-4">
                    <!-- Estatísticas -->
                    <div class="stats-card">
                        <h6 class="mb-3">
                            <i class="fas fa-chart-bar me-2"></i>
                            Estatísticas do Sistema
                        </h6>
                        <div class="stats-grid">
                            <div class="stat-item">
                                <span class="stat-value"
                                    id="avgRenderTime">{{ $stats[ 'average_render_time' ] ?? 0 }}ms</span>
                                <span class="stat-label">Tempo Médio</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-value"
                                    id="cacheHitRate">{{ number_format( ( $stats[ 'cache_hits' ] ?? 0 ) * 100, 1 ) }}%</span>
                                <span class="stat-label">Cache Hit</span>
                            </div>
                        </div>
                    </div>

                    <!-- Comparação de Idiomas -->
                    <div class="stats-card">
                        <h6 class="mb-3">
                            <i class="fas fa-language me-2"></i>
                            Comparação de Idiomas
                        </h6>
                        <button class="btn btn-outline-primary btn-sm w-100 mb-3" onclick="compareLocales()">
                            <i class="fas fa-columns me-2"></i>
                            Comparar Idiomas Selecionados
                        </button>
                        <div id="comparisonContainer"></div>
                    </div>

                    <!-- Configurações Avançadas -->
                    <div class="stats-card">
                        <h6 class="mb-3">
                            <i class="fas fa-cogs me-2"></i>
                            Configurações Avançadas
                        </h6>
                        <div class="mb-3">
                            <label class="form-label">Tenant para Preview</label>
                            <select class="form-select" id="tenantSelect">
                                <option value="">Padrão</option>
                                @foreach( $tenants as $tenant )
                                    <option value="{{ $tenant[ 'id' ] }}">{{ $tenant[ 'name' ] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Dados Customizados (JSON)</label>
                            <textarea class="form-control" id="customDataInput" rows="3"
                                placeholder='{"custom_field": "valor"}'></textarea>
                        </div>
                        <div class="action-buttons">
                            <button class="btn-action btn-warning" onclick="simulateError()">
                                <i class="fas fa-exclamation-triangle"></i>
                                Simular Erro
                            </button>
                            <button class="btn-action btn-primary" onclick="clearCache()">
                                <i class="fas fa-broom"></i>
                                Limpar Cache
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Teste de Fila -->
    <div class="modal fade" id="queueTestModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Teste de Envio via Fila</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">E-mail de Teste</label>
                        <input type="email" class="form-control" id="testEmailInput" placeholder="seu-email@teste.com">
                    </div>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        Este teste irá enfileirar o e-mail atual para envio real através do sistema de filas.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-success" onclick="executeQueueTest()">Enviar para Teste</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Comparação -->
    <div class="modal fade" id="comparisonModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Comparação de Idiomas</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="comparisonContent"></div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push( 'scripts' )
    <script>
        let currentEmailType = null;
        let currentLocale = 'pt-BR';
        let currentDevice = 'desktop';
        let currentTenantId = null;
        let customData = {};

        // Inicialização
        document.addEventListener( 'DOMContentLoaded', function () {
            loadStats();
            setupEventListeners();
        } );

        function setupEventListeners() {
            // Tenant selection
            document.getElementById( 'tenantSelect' ).addEventListener( 'change', function () {
                currentTenantId = this.value || null;
                if ( currentEmailType ) {
                    refreshPreview();
                }
            } );

            // Custom data input
            document.getElementById( 'customDataInput' ).addEventListener( 'input', function () {
                try {
                    customData = this.value ? JSON.parse( this.value ) : {};
                    if ( currentEmailType ) {
                        refreshPreview();
                    }
                } catch ( e ) {
                    console.error( 'JSON inválido:', e );
                }
            } );
        }

        function selectDevice( device ) {
            currentDevice = device;

            // Atualizar botões
            document.querySelectorAll( '.device-button' ).forEach( btn => {
                btn.classList.remove( 'active' );
            } );
            document.querySelector( `[data-device="${device}"]` ).classList.add( 'active' );

            // Atualizar frame
            const deviceFrame = document.getElementById( 'deviceFrame' );
            deviceFrame.className = `device-frame ${device}`;

            // Atualizar label
            document.getElementById( 'currentDevice' ).textContent = `• ${getDeviceName( device )}`;

            if ( currentEmailType ) {
                refreshPreview();
            }
        }

        function selectLocale( locale ) {
            currentLocale = locale;

            // Atualizar botões
            document.querySelectorAll( '.locale-button' ).forEach( btn => {
                btn.classList.remove( 'active' );
            } );
            document.querySelector( `[data-locale="${locale}"]` ).classList.add( 'active' );

            // Atualizar label
            document.getElementById( 'currentLocale' ).textContent = `• ${getLocaleName( locale )}`;

            if ( currentEmailType ) {
                refreshPreview();
            }
        }

        function selectEmailType( emailType ) {
            currentEmailType = emailType;

            // Atualizar cards
            document.querySelectorAll( '.email-type-card' ).forEach( card => {
                card.classList.remove( 'selected' );
            } );
            document.querySelector( `[data-email-type="${emailType}"]` ).classList.add( 'selected' );

            // Atualizar título
            document.getElementById( 'currentEmailType' ).textContent = getEmailTypeName( emailType );

            // Carregar preview
            refreshPreview();
        }

        function refreshPreview() {
            if ( !currentEmailType ) return;

            showLoading();

            const params = new URLSearchParams( {
                locale: currentLocale,
                device: currentDevice,
                tenant_id: currentTenantId || '',
                custom_data: JSON.stringify( customData )
            } );

            const iframe = document.getElementById( 'emailFrame' );
            iframe.src = `/emails/preview/${currentEmailType}?${params.toString()}`;

            iframe.onload = function () {
                hideLoading();
            };

            iframe.onerror = function () {
                hideLoading();
                showError( 'Erro ao carregar preview do e-mail' );
            };
        }

        function testQueue() {
            if ( !currentEmailType ) {
                showError( 'Selecione um tipo de e-mail primeiro' );
                return;
            }

            const modal = new bootstrap.Modal( document.getElementById( 'queueTestModal' ) );
            modal.show();
        }

        function executeQueueTest() {
            const testEmail = document.getElementById( 'testEmailInput' ).value;

            if ( !testEmail ) {
                showError( 'Digite um e-mail de teste' );
                return;
            }

            const modal = bootstrap.Modal.getInstance( document.getElementById( 'queueTestModal' ) );
            modal.hide();

            showLoading();

            fetch( `/emails/preview/${currentEmailType}/test-queue`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector( 'meta[name="csrf-token"]' ).getAttribute( 'content' )
                },
                body: JSON.stringify( {
                    locale: currentLocale,
                    tenant_id: currentTenantId,
                    test_recipient: testEmail
                } )
            } )
                .then( response => response.json() )
                .then( data => {
                    hideLoading();
                    if ( data.success ) {
                        showSuccess( `E-mail de teste enfileirado com sucesso! Queue: ${data.data.queue}` );
                    } else {
                        showError( data.error || 'Erro ao enfileirar e-mail de teste' );
                    }
                } )
                .catch( error => {
                    hideLoading();
                    showError( 'Erro na requisição: ' + error.message );
                } );
        }

        function compareLocales() {
            if ( !currentEmailType ) {
                showError( 'Selecione um tipo de e-mail primeiro' );
                return;
            }

            const selectedLocales = Array.from( document.querySelectorAll( '.locale-button.active' ) )
                .map( btn => btn.dataset.locale );

            if ( selectedLocales.length < 2 ) {
                showError( 'Selecione pelo menos 2 idiomas para comparação' );
                return;
            }

            showLoading();

            fetch( `/emails/preview/${currentEmailType}/compare-locales`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector( 'meta[name="csrf-token"]' ).getAttribute( 'content' )
                },
                body: JSON.stringify( {
                    locales: selectedLocales,
                    tenant_id: currentTenantId
                } )
            } )
                .then( response => response.json() )
                .then( data => {
                    hideLoading();
                    if ( data.success ) {
                        showComparisonModal( data.data );
                    } else {
                        showError( data.error || 'Erro na comparação de idiomas' );
                    }
                } )
                .catch( error => {
                    hideLoading();
                    showError( 'Erro na requisição: ' + error.message );
                } );
        }

        function showComparisonModal( comparisonData ) {
            const container = document.getElementById( 'comparisonContent' );
            const modal = new bootstrap.Modal( document.getElementById( 'comparisonModal' ) );

            let html = `<div class="comparison-container">`;

            Object.entries( comparisonData.comparisons ).forEach( ( [locale, comparison] ) => {
                html += `
                    <div class="comparison-item">
                        <div class="comparison-header">
                            ${getLocaleName( locale )} ${getLocaleFlag( locale )}
                        </div>
                        <div class="preview-content">
                `;

                if ( comparison.status === 'success' ) {
                    html += comparison.preview.html;
                } else {
                    html += `<div class="alert alert-danger">Erro: ${comparison.error}</div>`;
                }

                html += `
                        </div>
                    </div>
                `;
            } );

            html += `</div>`;
            container.innerHTML = html;
            modal.show();
        }

        function exportTemplate() {
            if ( !currentEmailType ) {
                showError( 'Selecione um tipo de e-mail primeiro' );
                return;
            }

            const link = document.createElement( 'a' );
            link.href = `/emails/preview/${currentEmailType}/export?locale=${currentLocale}&tenant_id=${currentTenantId || ''}`;
            link.download = `${currentEmailType}_${currentLocale}_${new Date().toISOString().split( 'T' )[0]}.html`;
            link.click();
        }

        function simulateError() {
            if ( !currentEmailType ) {
                showError( 'Selecione um tipo de e-mail primeiro' );
                return;
            }

            showLoading();

            fetch( `/emails/preview/${currentEmailType}/simulate-error`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector( 'meta[name="csrf-token"]' ).getAttribute( 'content' )
                },
                body: JSON.stringify( {
                    error_type: 'render_error',
                    locale: currentLocale
                } )
            } )
                .then( response => response.json() )
                .then( data => {
                    hideLoading();
                    if ( data.success === false ) {
                        showError( `Erro simulado: ${data.error}` );
                    } else {
                        showSuccess( 'Cenário de erro simulado com sucesso' );
                    }
                } )
                .catch( error => {
                    hideLoading();
                    showError( 'Erro na simulação: ' + error.message );
                } );
        }

        function clearCache() {
            fetch( '/emails/preview/clear-cache', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector( 'meta[name="csrf-token"]' ).getAttribute( 'content' )
                }
            } )
                .then( response => response.json() )
                .then( data => {
                    if ( data.success ) {
                        showSuccess( data.message );
                        loadStats();
                    } else {
                        showError( data.error || 'Erro ao limpar cache' );
                    }
                } )
                .catch( error => {
                    showError( 'Erro na requisição: ' + error.message );
                } );
        }

        function loadStats() {
            fetch( '/emails/preview/config' )
                .then( response => response.json() )
                .then( data => {
                    if ( data.stats ) {
                        document.getElementById( 'totalPreviews' ).textContent = data.stats.total_previews || 0;
                        document.getElementById( 'avgRenderTime' ).textContent = `${data.stats.average_render_time || 0}ms`;
                        document.getElementById( 'cacheHitRate' ).textContent = `${( ( data.stats.cache_hits || 0 ) * 100 ).toFixed( 1 )}%`;
                    }
                } )
                .catch( error => {
                    console.error( 'Erro ao carregar estatísticas:', error );
                } );
        }

        function showLoading() {
            document.getElementById( 'previewLoading' ).classList.add( 'show' );
            document.getElementById( 'previewError' ).classList.remove( 'show' );
        }

        function hideLoading() {
            document.getElementById( 'previewLoading' ).classList.remove( 'show' );
        }

        function showError( message ) {
            const errorDiv = document.getElementById( 'previewError' );
            errorDiv.textContent = message;
            errorDiv.classList.add( 'show' );
        }

        function showSuccess( message ) {
            // Criar elemento temporário para mensagem de sucesso
            const successDiv = document.createElement( 'div' );
            successDiv.className = 'success-message show';
            successDiv.textContent = message;

            const container = document.querySelector( '.email-preview-frame' );
            container.insertBefore( successDiv, container.firstChild );

            setTimeout( () => {
                successDiv.remove();
            }, 5000 );
        }

        function getDeviceName( device ) {
            const names = {
                'desktop': 'Desktop',
                'tablet': 'Tablet',
                'mobile': 'Mobile'
            };
            return names[device] || device;
        }

        function getLocaleName( locale ) {
            const names = {
                'pt-BR': 'Português (Brasil)',
                'en': 'English',
                'es': 'Español'
            };
            return names[locale] || locale;
        }

        function getLocaleFlag( locale ) {
            const flags = {
                'pt-BR': '🇧🇷',
                'en': '🇺🇸',
                'es': '🇪🇸'
            };
            return flags[locale] || '';
        }

        function getEmailTypeName( emailType ) {
            const names = {
                @foreach( $availableEmails as $key => $email )
                    '{{ $key }}': '{{ $email[ 'name' ] }}',
                @endforeach
            };
        return names[emailType] || emailType;
        }

        // Auto-refresh stats a cada 30 segundos
        setInterval( loadStats, 30000 );
    </script>
@endpush
