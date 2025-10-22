{{-- Email Alert Component --}}
@php
    $type  = $type ?? 'info';
    $types = [
        'info'    => [
            'bg'     => '#dbeafe',
            'border' => '#3b82f6',
            'text'   => '#1e40af',
            'icon'   => 'ℹ️'
        ],
        'success' => [
            'bg'     => '#d1fae5',
            'border' => '#10b981',
            'text'   => '#047857',
            'icon'   => '✅'
        ],
        'warning' => [
            'bg'     => '#fef3c7',
            'border' => '#f59e0b',
            'text'   => '#92400e',
            'icon'   => '⚠️'
        ],
        'error'   => [
            'bg'     => '#fee2e2',
            'border' => '#ef4444',
            'text'   => '#dc2626',
            'icon'   => '❌'
        ]
    ];
    $style = $types[ $type ] ?? $types[ 'info' ];
@endphp

<div
    style="background-color: {{ $style[ 'bg' ] }}; border-left: 4px solid {{ $style[ 'border' ] }}; padding: 16px; margin: 16px 0; border-radius: 4px;">
    <div style="color: {{ $style[ 'text' ] }}; font-size: 14px; line-height: 1.5;">
        <strong>{{ $style[ 'icon' ] }} {{ $title ?? 'Atenção' }}</strong><br>
        {{ $slot ?? $message ?? 'Mensagem de alerta' }}
    </div>
</div>
