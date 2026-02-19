@props([
    'title' => null,
    'subtitle' => null,
    'statusItem' => null,
    'mb' => 'mb-4'
])

<div {{ $attributes->merge(['class' => 'card border-0 shadow-sm ' . $mb]) }}>
    @if($title || $subtitle || $statusItem || isset($actions))
    <div class="card-header border-bottom-0 pt-4 px-4 pb-0">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
            <div>
                @if($title)
                <h4 class="card-title fw-bold mb-1">{{ $title }}</h4>
                @endif
                @if($subtitle)
                <p class="text-muted small mb-0">{{ $subtitle }}</p>
                @endif
            </div>
            <div class="d-flex align-items-center gap-2">
                @if($statusItem)
                <x-ui.status-badge :item="$statusItem" />
                @endif
                @if(isset($actions))
                <div class="d-flex gap-2">
                    {{ $actions }}
                </div>
                @endif
            </div>
        </div>
    </div>
    @endif

    <div class="card-body p-4">
        <div class="row g-4">
            {{ $slot }}
        </div>
    </div>
</div>
