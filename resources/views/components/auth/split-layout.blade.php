@props([
    'title' => null,
    'subtitle' => null,
])

<div class="auth-wrapper d-flex align-items-center justify-content-center py-5">
    {{-- Fundo Decorativo --}}
    <div class="auth-bg-shapes">
        <div class="shape-1"></div>
        <div class="shape-2"></div>
    </div>

    <div class="auth-main-card shadow-2xl overflow-hidden d-flex">
        {{-- Painel Lateral Interno (Marketing) --}}
        <div class="auth-side-panel d-none d-lg-flex flex-column justify-content-between p-5 text-white">
            <div class="auth-side-top">
                <div class="auth-logo-placeholder mb-5">
                    <x-ui.logo-light />
                </div>
                {{ $welcome }}
            </div>

            <div class="auth-side-bottom opacity-50 small">
                &copy; {{ date('Y') }} Easy Budget. Todos os direitos reservados.
            </div>
        </div>

        {{-- Painel de Formulário --}}
        <div class="auth-form-panel p-4 p-md-5 d-flex flex-column justify-content-center">
            <div class="auth-form-content mx-auto w-100" style="max-width: 420px;">
                <div class="mb-4">
                    @if($title)
                        <h2 class="fw-bold text-dark h3 mb-2">{{ $title }}</h2>
                    @endif
                    @if($subtitle)
                        <p class="text-muted small mb-0">{{ $subtitle }}</p>
                    @endif
                </div>

                {{ $slot }}
            </div>
        </div>
    </div>
</div>

<style>
    /* Reset e Fundo */
    .auth-wrapper {
        min-height: calc(100vh - 140px); /* Ajuste para dar espaço visual entre header e footer */
        position: relative;
        overflow: hidden;
        z-index: 1;
    }

    .auth-bg-shapes .shape-1 {
        position: absolute;
        top: -100px;
        right: -100px;
        width: 400px;
        height: 400px;
        background: radial-gradient(circle, rgba(13, 110, 253, 0.1) 0%, rgba(255,255,255,0) 70%);
        border-radius: 50%;
    }

    .auth-bg-shapes .shape-2 {
        position: absolute;
        bottom: -150px;
        left: -100px;
        width: 500px;
        height: 500px;
        background: radial-gradient(circle, rgba(13, 110, 253, 0.05) 0%, rgba(255,255,255,0) 70%);
        border-radius: 50%;
    }

    /* Card Principal */
    .auth-main-card {
        width: 100%;
        max-width: 1100px;
        min-height: 650px;
        background: white;
        border-radius: 2rem;
        z-index: 10;
        border: 1px solid rgba(0,0,0,0.05);
    }

    /* Painel Lateral */
    .auth-side-panel {
        flex: 0 0 400px;
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
        position: relative;
    }

    .auth-side-panel::before {
        content: "";
        position: absolute;
        top: 0; left: 0; right: 0; bottom: 0;
        background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2v-4h4v-2h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2v-4h4v-2H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    }

    .auth-form-panel {
        flex: 1;
        max-height: 100%;
        overflow-y: auto;
    }

    @media (max-width: 991.98px) {
        .auth-main-card { border-radius: 1.5rem; min-height: auto; }
        .auth-form-panel { padding: 2rem !important; }
    }
</style>
