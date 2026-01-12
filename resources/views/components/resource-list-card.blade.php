@props([
    'title',
    'mobileTitle' => null,
    'icon' => 'list-ul',
    'total' => null,
    'padding' => 'p-0',
    'gap' => null,
])

<div {{ $attributes->merge(['class' => 'card border-0 shadow-sm']) }}>
    <div class="card-header">
        <div class="row align-items-center">
            <div class="col-12 col-lg-8 mb-2 mb-lg-0">
                <h5 class=" card-title mb-0 d-flex align-items-center flex-wrap">
                    <span class="me-2">
                        <i class="bi bi-{{ $icon }} me-1"></i>
                        <span class="{{ $mobileTitle ? 'd-none d-sm-inline' : '' }}">{{ $title }}</span>
                        @if($mobileTitle)
                            <span class="d-sm-none">{{ $mobileTitle }}</span>
                        @endif
                    </span>
                    @if(isset($total))
                        <span class="text-muted" style="font-size: 0.875rem;">
                            ({{ $total }})
                        </span>
                    @endif
                </h5>
            </div>

            @if(isset($headerActions))
                {{ $headerActions }}
            @endif
        </div>
    </div>

    <div @class(['card-body', $padding])>
        @if(isset($desktop) || isset($mobile))
            @if(isset($desktop))
                <div class="desktop-view">
                    {{ $desktop }}
                </div>
            @endif

            @if(isset($mobile))
                <div class="mobile-view">
                    <div class="list-group list-group-flush">
                        {{ $mobile }}
                    </div>
                </div>
            @endif
        @elseif($gap)
            <div @class(['d-flex flex-column', "gap-$gap" => $gap])>
                {{ $slot }}
            </div>
        @else
            {{ $slot }}
        @endif
    </div>

    @if(isset($footer))
        <div class="card-footer py-3 border-top-0">
            {{ $footer }}
        </div>
    @endif
</div>
