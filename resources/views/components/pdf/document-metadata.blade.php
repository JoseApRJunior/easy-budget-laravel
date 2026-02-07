@props([
    'dueDate' => null,
    'generatedAt' => null
])

<table style="width: 100%; font-size: 9px; color: {{ $pdfColors['text'] }}; border-collapse: collapse; margin-top: 5px;">
    <tr>
        <td style="text-align: left; font-style: italic;">
            @if($dueDate) * Válido até {{ $dueDate->format('d/m/Y') }} @endif
        </td>
        <td style="text-align: right; font-style: italic;">
            @if($generatedAt) * Gerado em {{ $generatedAt->format('d/m/Y H:i') }} @endif
        </td>
    </tr>
</table>
