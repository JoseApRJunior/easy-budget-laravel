@php
    $themeColor = config("theme.colors.$variant", config('theme.colors.primary'));
@endphp

<div {{ $attributes->merge(['class' => 'd-flex align-items-start mb-3']) }}>
    <div class="avatar-circle-xs rounded me-3 shadow-sm" style="background-color: {{ $themeColor }}1a; width: 32px; height: 32px; min-width: 32px; display: flex; align-items: center; justify-content: center;">
        <i class="bi bi-{{ $icon }}" style="color: {{ $themeColor }}; font-size: 0.9rem;"></i>
    </div>
    <div class="flex-grow-1">
        @if($description)
            <p class="small mb-0 fw-medium" style="color: var(--text-color); line-height: 1.4;">{{ $description }}</p>
        @else
            <div class="small mb-0 fw-medium" style="color: var(--text-color); line-height: 1.4;">
                {{ $slot }}
            </div>
        @endif
    </div>
</div>
