@props([
    'datetime',
    'showTime' => true,
    'stack' => true
])

@php
    if (!$datetime instanceof \Carbon\Carbon && !$datetime instanceof \DateTimeInterface) {
        try {
            $datetime = \Illuminate\Support\Carbon::parse($datetime);
        } catch (\Exception $e) {
            $datetime = null;
        }
    }
@endphp

<div {{ $attributes->merge(['class' => 'small text-muted']) }}>
    @if($datetime)
        @if($stack)
            <div class="fw-bold text-dark">{{ $datetime->format('d/m/Y') }}</div>
            @if($showTime)
                <div>{{ $datetime->format('H:i') }}</div>
            @endif
        @else
            <span>{{ $datetime->format('d/m/Y' . ($showTime ? ' H:i' : '')) }}</span>
        @endif
    @else
        -
    @endif
</div>
