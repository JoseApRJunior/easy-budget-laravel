@props([
    'subtotal',
    'discount' => 0,
    'total',
    'subtotalLabel' => 'Subtotal Serviços',
    'discountLabel' => 'Desconto Total',
    'totalLabel' => 'VALOR TOTAL'
])

<div class="mb-3" style="border: 1px solid #444; border-radius: 4px; width: 100%;">
    <table style="width: 100%; table-layout: fixed; border-collapse: collapse; margin: 0;">
        <tr>
            <td style="width: 33.33%; padding: 8px 15px; border-right: 1px solid #444; text-align: left; vertical-align: middle;">
                <div style="font-size: 9px; color: #444; text-transform: uppercase; margin-bottom: 2px;">{{ $subtotalLabel }}</div>
                <div style="font-weight: bold; color: #000; font-size: 11px;">R$ {{ \App\Helpers\CurrencyHelper::format($subtotal) }}</div>
            </td>
            <td style="width: 33.33%; padding: 8px 15px; border-right: 1px solid #444; text-align: center; vertical-align: middle;">
                <div style="font-size: 9px; color: #444; text-transform: uppercase; margin-bottom: 2px;">{{ $discountLabel }}</div>
                <div style="font-weight: bold; color: #000; font-size: 11px;">- R$ {{ \App\Helpers\CurrencyHelper::format($discount) }}</div>
            </td>
            <td style="width: 33.33%; padding: 8px 15px; text-align: right; vertical-align: middle;">
                <div style="font-size: 9px; font-weight: bold; color: #444; text-transform: uppercase; margin-bottom: 2px;">{{ $totalLabel }}</div>
                <div style="font-weight: bold; color: #000; font-size: 16px;">R$ {{ \App\Helpers\CurrencyHelper::format($total) }}</div>
            </td>
        </tr>
    </table>
</div>
