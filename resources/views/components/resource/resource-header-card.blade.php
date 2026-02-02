@props([
    'mb' => 'mb-4'
])

<div {{ $attributes->merge(['class' => 'card border-0 shadow-sm ' . $mb]) }}>
    <div class="card-body p-4">
        <div class="row g-4">
            {{ $slot }}
        </div>
    </div>
</div>
