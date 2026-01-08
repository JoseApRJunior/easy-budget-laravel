@props([
    'dueDate' => null,
    'generatedAt' => null
])

<table style="width: 100%; font-size: 9px; color: #444; border-collapse: collapse;">
    <tr>
        <td style="text-align: left;">
            @if($dueDate) * Válido até {{ $dueDate->format('d/m/Y') }} @endif
        </td>
        <td style="text-align: right;">
            @if($generatedAt) * Gerado em {{ $generatedAt->format('d/m/Y H:i') }} @endif
        </td>
    </tr>
</table>
