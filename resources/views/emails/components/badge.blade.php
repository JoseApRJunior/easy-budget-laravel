{{-- Email Badge Component --}}
@php
    $type  = $type ?? 'primary';
    $types = [
        'primary' => [
            'bg'   => '#3b82f6',
            'text' => '#ffffff'
        ],
        'success' => [
            'bg'   => '#10b981',
            'text' => '#ffffff'
        ],
        'warning' => [
            'bg'   => '#f59e0b',
            'text' => '#ffffff'
        ],
        'error'   => [
            'bg'   => '#ef4444',
            'text' => '#ffffff'
        ],
        'info'    => [
            'bg'   => '#6b7280',
            'text' => '#ffffff'
        ]
    ];
    $style = $types[ $type ] ?? $types[ 'primary' ];
@endphp

<span
    style="display: inline-block; background-color: {{ $style[ 'bg' ] }}; color: {{ $style[ 'text' ] }}; padding: 4px 8px; border-radius: 12px; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">
    {{ $slot ?? $text ?? 'Badge' }}
</span>
