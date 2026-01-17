@props([
    'title' => null,
    'subtitle' => null,
])

<div class="container-fluid p-0 flex-grow-1 d-flex flex-column">
    <div class="row g-0 auth-split-row flex-grow-1">
        {{-- Lado Esquerdo (Welcome) --}}
        <div class="col-lg-6 d-none d-lg-flex bg-primary bg-gradient align-items-center justify-content-center text-white p-5">
            <div class="text-center" style="max-width: 500px;">
                {{ $welcome }}
            </div>
        </div>

        {{-- Lado Direito (Form) --}}
        <div class="col-lg-6 d-flex align-items-center justify-content-center" style="background-color: var(--bg-color);">
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
    /*
       Garante que o split layout ocupe todo o espaço disponível entre header e footer
    */
    .auth-split-row {
        min-height: 100%;
    }

    /* Previne scroll horizontal */
    .container-fluid.p-0 {
        overflow-x: hidden;
    }

    @media (max-width: 991.98px) {
        .auth-split-row {
            min-height: auto;
        }
    }
</style>
