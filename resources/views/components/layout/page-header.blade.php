@props([
    'title',
    'icon' => null,
    'breadcrumbItems' => [],
    'description' => null
])

@php
    $backRoute = null;
    $backLabel = 'Voltar';
    if (!empty($breadcrumbItems) && count($breadcrumbItems) > 1) {
        $keys = array_keys($breadcrumbItems);
        $prevKey = $keys[count($keys) - 2];
        $backRoute = $breadcrumbItems[$prevKey];
        $backLabel = $prevKey;
    }
@endphp

<div class="mb-1">
    @if($backRoute)
        <div class="d-md-none d-flex justify-content-end mb-3">
            <a href="{{ $backRoute }}" class="btn btn-link btn-sm text-decoration-none p-0 text-muted" style="font-size: 0.8rem;">
                <i class="bi bi-chevron-left"></i> {{ $backLabel }}
            </a>
        </div>
    @endif

    <div class="d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center">
            <div>
                <h1 class="h3 mb-0">
                    @if($icon)<i class="bi bi-{{ $icon }} me-2"></i>@endif{{ $title }}
                </h1>
                <div class="d-none d-md-block">
                    @if($description)
                        <p class="text-muted mb-0">{{ $description }}</p>
                    @else
                        {{ $slot }}
                    @endif
                </div>
            </div>
        </div>

        @if(!empty($breadcrumbItems))
        <nav aria-label="breadcrumb" class="d-none d-md-block">
            <ol class="breadcrumb mb-0">
                @foreach($breadcrumbItems as $label => $route)
                    @if($loop->last)
                        <li class="breadcrumb-item active" aria-current="page">{{ $label }}</li>
                    @else
                        <li class="breadcrumb-item"><a href="{{ $route }}">{{ $label }}</a></li>
                    @endif
                @endforeach
            </ol>
        </nav>
        @endif
    </div>
    <div class="d-md-none mt-2">
        @if($description)
            <p class="text-muted mb-0 small">{{ $description }}</p>
        @else
            {{ $slot }}
        @endif
    </div>
</div>
