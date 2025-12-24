@props(['title', 'icon' => null, 'breadcrumbItems' => []])

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0">
            @if($icon)<i class="bi bi-{{ $icon }} me-2"></i>@endif{{ $title }}
        </h1>
        {{ $slot }}
    </div>
    @if(!empty($breadcrumbItems))
    <nav aria-label="breadcrumb" class="d-none d-md-block">
        <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="{{ route('provider.dashboard') }}">Dashboard</a></li>
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
