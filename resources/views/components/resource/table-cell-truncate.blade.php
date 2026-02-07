@props([
    'text',
    'maxWidth' => '150px',
    'title' => null,
])

<div {{ $attributes->merge(['class' => 'd-flex align-items-center']) }}>
    <div class="text-truncate" style="max-width: {{ $maxWidth }};" title="{{ $title ?? $text }}">
        {{ $text }}
    </div>
</div>
