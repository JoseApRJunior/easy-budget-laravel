@props([
    'title'
])

<div class="mb-3" style="margin-bottom: 15px;">
    <x-pdf.section-header :title="$title" />
    <div class="text-dark">
        {{ $slot }}
    </div>
</div>
