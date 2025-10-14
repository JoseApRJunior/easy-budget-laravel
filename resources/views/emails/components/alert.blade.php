@php
    $type    = $type ?? 'info';
    $message = $message ?? '';
    $icon    = $icon ?? '';

    $alertConfig = [
        'success' => [
            'bg'     => '#D1FAE5',
            'border' => '#10B981',
            'text'   => '#065F46',
            'icon'   => '✅'
        ],
        'error'   => [
            'bg'     => '#FEE2E2',
            'border' => '#EF4444',
            'text'   => '#991B1B',
            'icon'   => '❌'
        ],
        'warning' => [
            'bg'     => '#FEF3C7',
            'border' => '#F59E0B',
            'text'   => '#92400E',
            'icon'   => '⚠️'
        ],
        'info'    => [
            'bg'     => '#DBEAFE',
            'border' => '#3B82F6',
            'text'   => '#1E40AF',
            'icon'   => 'ℹ️'
        ]
    ];

    $config = $alertConfig[ $type ] ?? $alertConfig[ 'info' ];
    $icon   = $icon ?: $config[ 'icon' ];
@endphp

{{-- Email Alert Component --}}
<div style="
    background-color: {{ $config[ 'bg' ] }};
    border-left: 4px solid {{ $config[ 'border' ] }};
    padding: 16px;
    margin: 16px 0;
    border-radius: 0 6px 6px 0;
    color: {{ $config[ 'text' ] }};
    font-size: 14px;
    line-height: 1.5;
">
    @if( $icon )
        <strong style="font-size: 16px;">{{ $icon }}</strong>
    @endif

    {!! $message !!}
</div>
