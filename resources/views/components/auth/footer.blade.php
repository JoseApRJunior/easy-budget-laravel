@props([
    'text' => null,
    'linkText' => null,
    'linkHref' => null,
])

<div class="text-center mt-4">
    <p class="small text-muted mb-0">
        @if($slot->isNotEmpty())
            {{ $slot }}
        @else
            {{ $text }}
            <a href="{{ $linkHref }}" class="fw-bold text-primary text-decoration-none">{{ $linkText }}</a>
        @endif
    </p>
</div>
