@php
    $url       = $url ?? '#';
    $text      = $text ?? __( 'emails.common.button_view' );
    $color     = $color ?? 'primary';
    $size      = $size ?? 'medium';
    $fullWidth = $fullWidth ?? false;
    $styles    = $styles ?? [];

    // Configurações de cores
    $colorConfig = [
        'primary'   => [
            'bg'    => '#0D6EFD',
            'hover' => '#0B5ED7',
            'text'  => '#FFFFFF',
        ],
        'success'   => [
            'bg'    => '#10B981',
            'hover' => '#059669',
            'text'  => '#FFFFFF',
        ],
        'warning'   => [
            'bg'    => '#F59E0B',
            'hover' => '#D97706',
            'text'  => '#FFFFFF',
        ],
        'danger'    => [
            'bg'    => '#EF4444',
            'hover' => '#DC2626',
            'text'  => '#FFFFFF',
        ],
        'secondary' => [
            'bg'    => '#6B7280',
            'hover' => '#4B5563',
            'text'  => '#FFFFFF',
        ],
    ];

    $currentColor = $colorConfig[ $color ] ?? $colorConfig[ 'primary' ];

    // Configurações de tamanho
    $sizeConfig = [
        'small'  => [
            'padding'    => '8px 16px',
            'font-size'  => '12px',
            'min-height' => '32px',
        ],
        'medium' => [
            'padding'    => '12px 24px',
            'font-size'  => '14px',
            'min-height' => '40px',
        ],
        'large'  => [
            'padding'    => '16px 32px',
            'font-size'  => '16px',
            'min-height' => '48px',
        ],
    ];

    $currentSize = $sizeConfig[ $size ] ?? $sizeConfig[ 'medium' ];

    // Construir string de estilos
    $styleArray = [
        'display: inline-block',
        'padding: ' . $currentSize[ 'padding' ],
        'font-size: ' . $currentSize[ 'font-size' ],
        'font-weight: bold',
        'text-align: center',
        'text-decoration: none',
        'border-radius: 6px',
        'border: none',
        'cursor: pointer',
        'background-color: ' . $currentColor[ 'bg' ],
        'color: ' . $currentColor[ 'text' ] . ' !important',
        'min-height: ' . $currentSize[ 'min-height' ],
        'line-height: 1.4',
    ];

    if ( $fullWidth ) {
        $styleArray[] = 'display: block';
        $styleArray[] = 'width: 100%';
    }

    // Adicionar estilos customizados
    foreach ( $styles as $key => $value ) {
        $styleArray[] = $key . ': ' . $value;
    }

    $finalStyle = implode( '; ', $styleArray );
@endphp

{{-- Outlook compatibility wrapper --}}
<!--[if mso]>
<table align="center" border="0" cellspacing="0" cellpadding="0" style="margin: 0 auto;">
    <tr>
        <td align="center" bgcolor="{{ $currentColor['bg'] }}" style="border-radius: 6px;">
<![endif]-->

<a href="{{ $url }}" style="{{ $finalStyle }}" target="_blank" rel="noopener noreferrer">
    {{-- Fallback para clientes que não suportam padding em links --}}
    <!--[if mso]>
        <span style="color: {{ $currentColor['text'] }}; font-weight: bold; font-size: {{ $currentSize['font-size'] }};">
        <![endif]-->
    {{ $text }}
    <!--[if mso]>
        </span>
        <![endif]-->
</a>

<!--[if mso]>
        </td>
    </tr>
</table>
<![endif]-->
