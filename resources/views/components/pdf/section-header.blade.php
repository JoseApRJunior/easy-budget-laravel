@props([
    'title'
])

@php
    $colors = config('pdf_theme.colors');
@endphp

<h6 style="font-size: 10px; letter-spacing: 1px; text-transform: uppercase; margin-bottom: 10px; font-weight: bold; color: {{ $colors['secondary'] }}; padding-bottom: 4px;">
    {{ $title }}
</h6>
