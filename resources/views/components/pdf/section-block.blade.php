@props([
    'title'
])

@php
    $colors = config('pdf_theme.colors');
@endphp

<div style="margin-bottom: 20px;">
    <x-pdf.section-header :title="$title" />
    <div style="color: {{ $colors['text'] }}; font-size: 10px; line-height: 1.4;">
        {{ $slot }}
    </div>
</div>
