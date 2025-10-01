@extends( 'layouts.app' )

@section( 'title', 'Preview do Template: ' . $template->name )

@section( 'page-title', 'Preview do Template' )

@section( 'content' )
    <div class="email-template-preview">
        <!-- Header -->
        <div class="mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <nav class="flex" aria-label="Breadcrumb">
                        <ol class="inline-flex items-center space-x-1 md:space-x-3">
                            <li class="inline-flex items-center">
                                <a href="{{ route( 'email-templates.index' ) }}" class="text-gray-700 hover:text-blue-600">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z">
                                        </path>
                                    </svg>
                                    Email Templates
                                </a>
                            </li>
                            <li>
                                <div class="flex items-center">
                                    <svg class="w-4 h-4 text-gray-400 mx-1" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 5l7 7-7 7"></path>
                                    </svg>
                                    <span class="text-gray-500">{{ $template->name }}</span>
                                </div>
                            </li>
                        </ol>
                    </nav>
                    <h1 class="text-2xl font-bold text-gray-900 mt-2">{{ $template->name }}</h1>
                    <p class="text-gray-600">{{ $template->subject }}</p>
                </div>

                <div class="flex items-center space-x-3">
                    <a href="{{ route( 'email-templates.edit', $template ) }}" class="btn btn-primary">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                            </path>
                        </svg>
                        Editar Template
                    </a>
                    <button onclick="printPreview()" class="btn btn-secondary">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z">
                            </path>
                        </svg>
                        Imprimir
                    </button>
                </div>
            </div>
        </div>

        <!-- Controles de Preview -->
        <div class="preview-controls mb-6">
            <div class="bg-white p-4 rounded-lg shadow-sm border">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <div class="flex items-center space-x-2">
                            <label class="text-sm font-medium text-gray-700">Dispositivo:</label>
                            <select id="deviceSelector"
                                class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="desktop">Desktop (1200px)</option>
                                <option value="tablet">Tablet (768px)</option>
                                <option value="mobile">Mobile (375px)</option>
                            </select>
                        </div>

                        <div class="flex items-center space-x-2">
                            <label class="text-sm font-medium text-gray-700">Orientação:</label>
                            <div class="flex bg-gray-100 rounded-lg p-1">
                                <button id="portraitBtn"
                                    class="px-3 py-1 text-sm rounded-md bg-white shadow-sm">Retrato</button>
                                <button id="landscapeBtn"
                                    class="px-3 py-1 text-sm rounded-md text-gray-600 hover:text-gray-900">Paisagem</button>
                            </div>
                        </div>

                        <div class="flex items-center space-x-2">
                            <label class="text-sm font-medium text-gray-700">Zoom:</label>
                            <select id="zoomSelector"
                                class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="0.5">50%</option>
                                <option value="0.75">75%</option>
                                <option value="1" selected>100%</option>
                                <option value="1.25">125%</option>
                                <option value="1.5">150%</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex items-center space-x-3">
                        <button onclick="refreshPreview()" class="btn btn-secondary">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                                </path>
                            </svg>
                            Atualizar
                        </button>
                        <button onclick="togglePreviewMode()" class="btn btn-info">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z">
                                </path>
                            </svg>
                            <span id="previewModeText">Modo Grade</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Área de Preview -->
        <div class="preview-area">
            <div id="previewContainer" class="preview-container bg-gray-100 p-8 min-h-screen">
                <!-- Desktop View -->
                <div id="desktopFrame" class="device-frame desktop-frame bg-white shadow-lg mx-auto" style="width: 800px;">
                    <div class="frame-header bg-gray-800 text-white p-2 text-sm text-center">
                        <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z">
                            </path>
                        </svg>
                        Desktop Preview (800px)
                    </div>
                    <div class="frame-content p-4">
                        <div class="email-content">
                            {!! $processedContent !!}
                        </div>
                    </div>
                </div>

                <!-- Tablet View -->
                <div id="tabletFrame" class="device-frame tablet-frame bg-white shadow-lg mx-auto hidden"
                    style="width: 600px;">
                    <div class="frame-header bg-gray-800 text-white p-2 text-sm text-center">
                        <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                        </svg>
                        Tablet Preview (600px)
                    </div>
                    <div class="frame-content p-3">
                        <div class="email-content">
                            {!! $processedContent !!}
                        </div>
                    </div>
                </div>

                <!-- Mobile View -->
                <div id="mobileFrame" class="device-frame mobile-frame bg-white shadow-lg mx-auto hidden"
                    style="width: 375px;">
                    <div class="frame-header bg-gray-800 text-white p-2 text-sm text-center">
                        <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 18h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                        </svg>
                        Mobile Preview (375px)
                    </div>
                    <div class="frame-content p-2">
                        <div class="email-content">
                            {!! $processedContent !!}
                        </div>
                    </div>
                </div>

                <!-- Grid Mode -->
                <div id="gridMode" class="grid-mode hidden">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <!-- Desktop -->
                        <div class="device-preview">
                            <div class="preview-header text-sm font-medium text-gray-700 mb-2">Desktop (800px)</div>
                            <div class="device-frame-small bg-white border shadow-sm"
                                style="width: 100%; max-width: 300px; height: 400px; overflow: hidden;">
                                <div class="frame-content p-2 text-xs">
                                    <div class="email-content">
                                        {!! $processedContent !!}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tablet -->
                        <div class="device-preview">
                            <div class="preview-header text-sm font-medium text-gray-700 mb-2">Tablet (600px)</div>
                            <div class="device-frame-small bg-white border shadow-sm"
                                style="width: 100%; max-width: 250px; height: 350px; overflow: hidden;">
                                <div class="frame-content p-2 text-xs">
                                    <div class="email-content">
                                        {!! $processedContent !!}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Mobile -->
                        <div class="device-preview">
                            <div class="preview-header text-sm font-medium text-gray-700 mb-2">Mobile (375px)</div>
                            <div class="device-frame-small bg-white border shadow-sm"
                                style="width: 100%; max-width: 200px; height: 300px; overflow: hidden;">
                                <div class="frame-content p-1 text-xs">
                                    <div class="email-content">
                                        {!! $processedContent !!}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Informações do Template -->
        <div class="template-info mt-8">
            <div class="bg-white p-6 rounded-lg shadow-sm border">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Informações do Template</h3>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h4 class="font-medium text-gray-700 mb-2">Detalhes Gerais</h4>
                        <dl class="space-y-2">
                            <div class="flex justify-between">
                                <dt class="text-sm text-gray-600">Nome:</dt>
                                <dd class="text-sm font-medium">{{ $template->name }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-sm text-gray-600">Categoria:</dt>
                                <dd class="text-sm font-medium">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                        @if( $template->category === 'transactional' ) bg-blue-100 text-blue-800
                                        @elseif( $template->category === 'promotional' ) bg-purple-100 text-purple-800
                                        @elseif( $template->category === 'notification' ) bg-green-100 text-green-800
                                        @else bg-gray-100 text-gray-800 @endif">
                                        {{ ucfirst( $template->category ) }}
                                    </span>
                                </dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-sm text-gray-600">Status:</dt>
                                <dd class="text-sm font-medium">
                                    @if( $template->is_active )
                                        <span class="text-green-600">Ativo</span>
                                    @else
                                        <span class="text-red-600">Inativo</span>
                                    @endif
                                </dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-sm text-gray-600">Criado em:</dt>
                                <dd class="text-sm font-medium">{{ $template->created_at->format( 'd/m/Y H:i' ) }}</dd>
                            </div>
                        </dl>
                    </div>

                    <div>
                        <h4 class="font-medium text-gray-700 mb-2">Variáveis Utilizadas</h4>
                        @if( $template->variables && count( $template->variables ) > 0 )
                            <div class="flex flex-wrap gap-1">
                                @foreach( $template->variables as $variable )
                                    <code
                                        class="inline-flex items-center px-2 py-1 rounded text-xs font-mono bg-gray-100 text-gray-800">
                                                {{ $variable }}
                                            </code>
                                @endforeach
                            </div>
                        @else
                            <p class="text-sm text-gray-500">Nenhuma variável utilizada</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push( 'scripts' )
    <script>
        let currentDevice = 'desktop';
        let currentOrientation = 'portrait';
        let currentZoom = 1;
        let isGridMode = false;

        document.addEventListener( 'DOMContentLoaded', function () {
            setupDeviceControls();
            setupZoomControls();
            updatePreviewFrames();
        } );

        function setupDeviceControls() {
            // Device selector
            document.getElementById( 'deviceSelector' )?.addEventListener( 'change', function ( e ) {
                currentDevice = e.target.value;
                updatePreviewFrames();
            } );

            // Orientation controls
            document.getElementById( 'portraitBtn' )?.addEventListener( 'click', function () {
                currentOrientation = 'portrait';
                updateOrientationButtons();
                updatePreviewFrames();
            } );

            document.getElementById( 'landscapeBtn' )?.addEventListener( 'click', function () {
                currentOrientation = 'landscape';
                updateOrientationButtons();
                updatePreviewFrames();
            } );
        }

        function setupZoomControls() {
            document.getElementById( 'zoomSelector' )?.addEventListener( 'change', function ( e ) {
                currentZoom = parseFloat( e.target.value );
                updateZoom();
            } );
        }

        function updateOrientationButtons() {
            const portraitBtn = document.getElementById( 'portraitBtn' );
            const landscapeBtn = document.getElementById( 'landscapeBtn' );

            if ( currentOrientation === 'portrait' ) {
                portraitBtn.classList.add( 'bg-white', 'shadow-sm' );
                portraitBtn.classList.remove( 'text-gray-600', 'hover:text-gray-900' );
                landscapeBtn.classList.add( 'text-gray-600', 'hover:text-gray-900' );
                landscapeBtn.classList.remove( 'bg-white', 'shadow-sm' );
            } else {
                landscapeBtn.classList.add( 'bg-white', 'shadow-sm' );
                landscapeBtn.classList.remove( 'text-gray-600', 'hover:text-gray-900' );
                portraitBtn.classList.add( 'text-gray-600', 'hover:text-gray-900' );
                portraitBtn.classList.remove( 'bg-white', 'shadow-sm' );
            }
        }

        function updatePreviewFrames() {
            // Hide all frames
            document.getElementById( 'desktopFrame' ).classList.add( 'hidden' );
            document.getElementById( 'tabletFrame' ).classList.add( 'hidden' );
            document.getElementById( 'mobileFrame' ).classList.add( 'hidden' );
            document.getElementById( 'gridMode' ).classList.add( 'hidden' );

            if ( isGridMode ) {
                document.getElementById( 'gridMode' ).classList.remove( 'hidden' );
            } else {
                // Show selected device frame
                const frameId = currentDevice + 'Frame';
                document.getElementById( frameId ).classList.remove( 'hidden' );
            }

            updateZoom();
        }

        function updateZoom() {
            const container = document.getElementById( 'previewContainer' );
            if ( container ) {
                container.style.transform = `scale(${currentZoom})`;
                container.style.transformOrigin = 'top center';
            }
        }

        function togglePreviewMode() {
            isGridMode = !isGridMode;

            const modeText = document.getElementById( 'previewModeText' );
            if ( modeText ) {
                modeText.textContent = isGridMode ? 'Modo Único' : 'Modo Grade';
            }

            updatePreviewFrames();
        }

        function refreshPreview() {
            // Recarregar conteúdo se necessário
            location.reload();
        }

        function printPreview() {
            const printContent = document.querySelector( '.email-content' ).innerHTML;
            const printWindow = window.open( '', '_blank' );

            printWindow.document.write( `
            <!DOCTYPE html>
            <html>
            <head>
                <title>Preview - {{ $template->name }}</title>
                <meta charset="utf-8">
                <style>
                    body { font-family: Arial, sans-serif; margin: 20px; }
                    .email-content { max-width: 800px; margin: 0 auto; }
                </style>
            </head>
            <body>
                <div class="email-content">
                    ${printContent}
                </div>
            </body>
            </html>
        `);

            printWindow.document.close();
            printWindow.print();
        }

        // Keyboard shortcuts
        document.addEventListener( 'keydown', function ( e ) {
            // Ctrl/Cmd + R to refresh
            if ( ( e.ctrlKey || e.metaKey ) && e.key === 'r' ) {
                e.preventDefault();
                refreshPreview();
            }

            // G to toggle grid mode
            if ( e.key === 'g' && !e.ctrlKey && !e.metaKey && !e.altKey ) {
                togglePreviewMode();
            }

            // Number keys for device switching
            if ( e.key >= '1' && e.key <= '3' && !e.ctrlKey && !e.metaKey && !e.altKey ) {
                e.preventDefault();
                const devices = ['desktop', 'tablet', 'mobile'];
                currentDevice = devices[parseInt( e.key ) - 1];

                const selector = document.getElementById( 'deviceSelector' );
                if ( selector ) {
                    selector.value = currentDevice;
                }

                updatePreviewFrames();
            }
        } );
    </script>
@endpush

@push( 'styles' )
    <style>
        .btn {
            @apply inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md transition-colors duration-200;
        }

        .btn-primary {
            @apply bg-blue-600 text-white hover:bg-blue-700;
        }

        .btn-secondary {
            @apply bg-gray-200 text-gray-900 hover:bg-gray-300;
        }

        .btn-info {
            @apply bg-green-600 text-white hover:bg-green-700;
        }

        .device-frame {
            transition: all 0.3s ease-in-out;
            margin: 20px auto;
            border-radius: 10px;
            overflow: hidden;
        }

        .device-frame-small {
            border-radius: 8px;
            overflow: hidden;
        }

        .frame-header {
            background: linear-gradient(135deg, #374151, #4B5563);
        }

        .frame-content {
            min-height: 400px;
            background: white;
        }

        .preview-container {
            transition: transform 0.3s ease-in-out;
            transform-origin: top center;
        }

        .grid-mode {
            padding: 20px;
        }

        .device-preview {
            text-align: center;
        }

        .preview-header {
            margin-bottom: 8px;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .preview-controls .flex {
                flex-direction: column;
                gap: 1rem;
            }

            .device-frame {
                width: 100% !important;
                max-width: none;
            }

            .grid-mode .grid {
                grid-template-columns: 1fr;
            }
        }

        /* Email content responsive */
        .email-content * {
            max-width: 100%;
        }

        .email-content img {
            height: auto;
            max-width: 100%;
        }

        .email-content table {
            width: 100%;
            max-width: 100%;
        }

        /* Print styles */
        @media print {

            .preview-controls,
            .template-info,
            .btn {
                display: none !important;
            }

            .preview-area {
                padding: 0 !important;
                background: white !important;
            }

            .device-frame {
                box-shadow: none !important;
                border: none !important;
            }

            .frame-header {
                display: none !important;
            }
        }
    </style>
@endpush
