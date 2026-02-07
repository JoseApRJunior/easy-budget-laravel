@props([
    'title',
    'mobileTitle' => null,
    'icon' => 'list-ul',
    'total' => null,
    'padding' => 'p-0',
    'gap' => null,
    'col' => null,
    'variant' => 'primary',
    'mb' => '',
    'actions' => [],
])

@if($col)
    <div class="{{ $col }}">
@endif

<div {{ $attributes->merge(['class' => 'card border-0 shadow-sm '.$mb]) }}>
    <div class="card-header border-1 py-3 bg-transparent">
        <div class="row align-items-center g-2">
            <div class="col-12 col-md-auto">
                <h5 class="card-title mb-0 d-flex align-items-center">
                    <i class="bi bi-{{ $icon }} me-2" style="color: {{ config("theme.colors.$variant", config('theme.colors.primary')) }};"></i>
                    <span class="{{ $mobileTitle ? 'd-none d-sm-inline' : '' }}">{{ $title }}</span>
                    @if($mobileTitle)
                        <span class="d-sm-none">{{ $mobileTitle }}</span>
                    @endif
                    @if(isset($total))
                        <span class="ms-2 text-muted fw-normal" style="font-size: 0.85rem;">({{ $total }})</span>
                    @endif
                </h5>
            </div>

            @if(isset($headerActions))
                <div class="col-12 col-md text-md-end">
                    {{ $headerActions }}
                </div>
            @endif
        </div>
    </div>

    <div @class(['card-body', $padding])>
        @if(isset($desktop) || isset($mobile))
            @if(isset($desktop))
                <div class="desktop-view">{{ $desktop }}</div>
            @endif

            @if(isset($mobile))
                <div class="mobile-view">
                    <div class="list-group list-group-flush">{{ $mobile }}</div>
                </div>
            @endif
        @elseif($gap)
            <div @class(['d-flex flex-column', "gap-$gap"=> $gap])>{{ $slot }}</div>
        @else
            {{ $slot }}
        @endif
    </div>

    @if(isset($footer))
        <div class="card-footer py-3 border-top-0">{{ $footer }}</div>
    @endif
</div>

@if($col)
    </div>
@endif
