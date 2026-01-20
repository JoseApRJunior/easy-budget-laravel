@props([
    'title' => null,
    'subtitle' => null,
    'icon' => null,
    'maxWidth' => '550px',
])

<div class="row justify-content-center align-items-center min-vh-100 py-5 mx-0">
    <div class="col-12 px-3" style="max-width: {{ $maxWidth }};">
        <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="card-body p-4 p-md-5">
                <div class="text-center mb-4">
                    @if($icon)
                        <div class="mb-3">
                            <div class="d-inline-flex align-items-center justify-content-center  bg-opacity-10 rounded-circle" style="width: 80px; height: 80px;">
                                <i class="bi bi-{{ $icon }} display-6 "></i>
                            </div>
                        </div>
                    @endif

                    @if($title)
                        <h3 class="fw-bold text-dark mb-2">{{ $title }}</h3>
                    @endif

                    @if($subtitle)
                        <p class="text-muted small px-3">{{ $subtitle }}</p>
                    @endif
                </div>

                {{ $slot }}
            </div>
        </div>
    </div>
</div>
