@props([
    'title' => null,
    'subtitle' => null,
])

<div class="container-fluid p-0 overflow-hidden">
    <div class="row g-0 min-vh-100" style="margin-top: -72px; padding-top: 72px;">
        {{-- Lado Esquerdo (Welcome) --}}
        <div class="col-lg-6 d-none d-lg-flex bg-primary bg-gradient align-items-center justify-content-center text-white p-5">
            <div class="text-center" style="max-width: 500px;">
                {{ $welcome }}
            </div>
        </div>

        {{-- Lado Direito (Form) --}}
        <div class="col-lg-6 d-flex align-items-center justify-content-center bg-light">
            <div class="w-100 p-4 p-md-5" style="max-width: 600px;">
                <div class="text-center mb-4">
                    @if($title)
                        <h2 class="fw-bold text-primary">{{ $title }}</h2>
                    @endif
                    @if($subtitle)
                        <p class="text-muted">{{ $subtitle }}</p>
                    @endif
                </div>

                {{ $slot }}
            </div>
        </div>
    </div>
</div>

<style>
    /* Ajuste para compensar o header sticky e evitar scroll desnecessário */
    .sticky-top + main .container-fluid {
        margin-top: 0;
    }

    /* Se estiver usando o split layout, queremos que ele ocupe a tela toda menos o header */
    main:has(.container-fluid.p-0) {
        flex-grow: 1;
    }
</style>
