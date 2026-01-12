@props([
    'icon' => 'info-circle',
    'variant' => 'primary',
    'description' => null,
])

<div {{ $attributes->merge(['class' => 'd-flex align-items-start']) }}>
    <div @class([
        'avatar-circle-xs p-2 rounded me-3',
        "bg-$variant bg-opacity-10"
    ])>
        <i @class([
            "bi bi-$icon",
            "text-$variant"
        ])></i>
    </div>
    <div>
        @if($description)
            <p class="small mb-0 text-muted">{{ $description }}</p>
        @else
            {{ $slot }}
        @endif
    </div>
</div>
