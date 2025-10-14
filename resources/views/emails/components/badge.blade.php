@php
    $text = $text ?? '';
    $type = $type ?? 'default';
    $size = $size ?? 'medium';

    $badgeConfig = [
        'success' => [
            'bg'   => '#10B981',
            'text' => '#FFFFFF'
        ],
        'error'   => [
            'bg'   => '#EF4444',
            'text' => '#FFFFFF'
        ],
        'warning' => [
            'bg'   => '#F59E0B',
            'text' => '#FFFFFF'
        ],
        'info'    => [
            'bg'   => '#3B82F6',
            'text' => '#FFFFFF'
        ],
        'default' => [
            'bg'   => '#6B7280',
            'text' => '#FFFFFF'
        ]
    ];

    $sizeConfig = [
        'small'  => [
            'padding'   => '4px 8px',
            'font-size' => '10px'
        ],
        'medium' => [
            'padding'   => '6px 12px',
            'font-size' => '12px'
        ],
        'large'  => [
            'padding'   => '8px 16px',
            'font-size' => '14px'
        ]
    ];

    $config    = $badgeConfig[ $type ] ?? $badgeConfig[ 'default' ];
    $sizeStyle = $sizeConfig[ $size ] ?? $sizeConfig[ 'medium' ];
@endphp

{{-- Email Badge Component --}}
<span style="
    display: inline-block;
    background-color: {{ $config[ 'bg' ] }};
    color: {{ $config[ 'text' ] }};
    padding: {{ $sizeStyle[ 'padding' ] }};
    font-size: {{ $sizeStyle[ 'font-size' ] }};
    font-weight: bold;
    border-radius: 12px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
">
    {{ $text }}
</span>
