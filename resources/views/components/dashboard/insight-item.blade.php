@php
    $themeColor = config("theme.colors.$variant", config('theme.colors.primary'));
@endphp

<div {{ $attributes->merge(['class' => 'd-flex align-items-start']) }}>
    <div class="avatar-circle-xs p-2 rounded me-3" style="background-color: {{ $themeColor }}1a; width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">
        <i class="bi bi-{{ $icon }}" style="color: {{ $themeColor }};"></i>
    </div>
    <div>
        @if($description)
            <p class="small mb-0" style="color: {{ config('theme.colors.secondary', '#94a3b8') }};">{{ $description }}</p>
        @else
            {{ $slot }}
        @endif
    </div>
</div>
