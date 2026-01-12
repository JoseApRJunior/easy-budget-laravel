@props([
    'title',
    'subtitle' => null,
    'icon' => null,
    'iconClass' => null,
    'titleClass' => null
])

<div {{ $attributes->merge(['class' => 'd-flex align-items-center']) }}>
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
                    <span>{{ $subtitle }}</span>
                @endif
                {{ $slot }}
            </div>
        @endif
    </div>
</div>
