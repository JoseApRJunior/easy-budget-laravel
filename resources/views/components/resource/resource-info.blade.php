@props([
    'title',
    'subtitle' => null,
    'icon' => null,
    'iconClass' => null,
    'titleClass' => null,
    'href' => null
])

@php
    $tag = $href ? 'a' : 'div';
    $tagAttributes = $href ? "href=$href" : '';
@endphp

<{{ $tag }} {{ $tagAttributes }} {{ $attributes->merge(['class' => 'd-flex align-items-center' . ($href ? ' text-decoration-none group-hover-link transition-all' : '')]) }}>
    @if($icon)
        <div class="item-icon me-2 {{ $iconClass }}">
            <i class="bi bi-{{ $icon }}"></i>
        </div>
    @endif
    <div>
        <div class="item-name-cell {{ $titleClass }}">
            {{ $title }}
        </div>
        @if($subtitle || $slot->isNotEmpty())
            <div class="small d-flex align-items-center gap-1">
                @if($subtitle)
                    <span class="{{ $href ? 'text-primary' : '' }}">
                        {{ $subtitle }}
                        @if($href)
                            <i class="bi bi-arrow-up-right small ms-1 opacity-75"></i>
                        @endif
                    </span>
                @endif
                {{ $slot }}
            </div>
        @endif
    </div>
</{{ $tag }}>

<style>
    .group-hover-link:hover .text-primary {
        text-decoration: underline !important;
        filter: brightness(0.8);
    }
    .group-hover-link:hover .item-icon {
        transform: scale(1.1);
        transition: transform 0.2s ease;
    }
</style>
