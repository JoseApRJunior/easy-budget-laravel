@props([
    'title' => null,
    'subtitle' => null,
])

<div class=" container-fluid auth-wrapper d-flex align-items-center justify-content-center py-1">
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
                <div class="mb-4 text-center">
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
        border-radius: 50%;
    }

    .auth-bg-shapes .shape-2 {
        position: absolute;
        bottom: -150px;
        left: -100px;
        width: 500px;
        height: 500px;
        border-radius: 50%;
    }

    /* Card Principal */
    .auth-main-card {
        width: 100%;
        max-width: 1100px;
        min-height: 650px;
        background: var(--surface-color);
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
        background-image: radial-gradient(rgba(255, 255, 255, 0.05) 1px, transparent 1px);
        background-size: 30px 30px;
    }

    .auth-form-panel {
        flex: 1;
        max-height: 100%;
        overflow-y: auto;
        background-color: var(--surface-color);
    }

    @media (max-width: 991.98px) {
        .auth-main-card { border-radius: 1.5rem; min-height: auto; }
        .auth-form-panel { padding: 2rem !important; }
    }
</style>
