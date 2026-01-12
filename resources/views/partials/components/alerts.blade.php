{{-- DEBUG: Log de sessão para componente de alertas --}}
@php
    \Illuminate\Support\Facades\Log::info( 'ALERTS_COMPONENT: Verificando mensagens de sessão', [
        'session_all'   => session()->all(),
        'has_status'    => session()->has( 'status' ),
        'status_value'  => session( 'status' ),
        'has_error'     => session()->has( 'error' ),
        'error_value'   => session( 'error' ),
        'current_route' => request()->route() ? request()->route()->getName() : 'no_route',
        'current_url'   => request()->fullUrl(),
        'timestamp'     => now()->toISOString()
    ] );
@endphp

<div class="container my-4">
    {{-- Exibição de Erros de Validação do Laravel --}}
    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show py-2 px-3 mb-4 shadow-sm border-start border-danger border-4" role="alert">
            <div class="d-flex align-items-center">
                <i class="bi bi-exclamation-octagon-fill me-2 fs-5"></i>
                <div>
                    <strong class="d-block mb-1">Ops! Verifique os campos abaixo:</strong>
                    <ul class="mb-0 ps-3 small">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                <button type="button" class="btn-close small" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    @endif

    @php
        $flashTypes = [
            'error'   => 'danger',
            'success' => 'success',
            'message' => 'info',
            'warning' => 'warning',
        ];
    @endphp

    @foreach ( $flashTypes as $type => $style )
    @if ( session()->has( $type ) )
        {{-- DEBUG: Log quando encontra mensagem --}}
        @php
            \Illuminate\Support\Facades\Log::info( 'ALERTS_COMPONENT: Encontrou mensagem de sessão', [
                'type'      => $type,
                'message'   => session( $type ),
                'style'     => $style,
                'timestamp' => now()->toISOString()
            ] );
            $message = session($type);
            // Mapeia os tipos do Laravel para os tipos do EasyAlert
            $alertMethod = ($type === 'error') ? 'error' : (($type === 'success') ? 'success' : (($type === 'warning') ? 'warning' : 'info'));
        @endphp
        
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Pequeno delay para garantir que o easyAlert foi inicializado
                setTimeout(function() {
                    if (window.easyAlert) {
                        const options = {
                            duration: "{{ $type }}" === 'error' ? 15000 : 5000 // 15 segundos para erros
                        };
                        window.easyAlert.{{ $alertMethod }}("{!! addslashes($message) !!}", options);
                    }
                }, 100);
            });
        </script>

        {{-- Mantém o componente visual original como fallback apenas para erros de validação --}}
        {{-- <x-ui.alert :type="$type" :message="session( $type )" class="mb-4" /> --}}
    @else
            {{-- DEBUG: Log quando não encontra mensagem --}}
            @php
                \Illuminate\Support\Facades\Log::info( 'ALERTS_COMPONENT: Não encontrou mensagem de sessão', [
                    'type'        => $type,
                    'has_session' => session()->has( $type ),
                    'timestamp'   => now()->toISOString()
                ] );
            @endphp
        @endif
    @endforeach
</div>
