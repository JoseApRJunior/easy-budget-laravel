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
            @endphp
            <x-alert :type="$type" :message="session( $type )" class="mb-4" />
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
