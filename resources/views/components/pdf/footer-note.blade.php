@props([
    'note'
])

@php
    $colors = config('pdf_theme.colors');
@endphp

<div style="margin-top: 30px; width: 100%; text-align: center;">
    <p style="font-size: 8px; color: {{ $colors['secondary'] }}; margin-bottom: 0; font-style: italic;">{{ $note }}</p>
</div>
