@props([
    'leftTitle' => null,
    'rightTitle' => null,
])

<table style="width: 100%; table-layout: fixed; border-collapse: collapse; margin-top: 10px;">
    <tr>
        <td style="width: 48%; vertical-align: top;">
            @if($leftTitle)
                <x-pdf.section-header :title="$leftTitle" />
            @endif
            <div style="font-size: 10px;">
                {{ $left ?? '' }}
            </div>
        </td>
        <td style="width: 4%;"></td> {{-- Espaço entre colunas --}}
        <td style="width: 48%; vertical-align: top;">
            @if($rightTitle)
                <x-pdf.section-header :title="$rightTitle" />
            @endif
            <div style="font-size: 10px;">
                {{ $right ?? '' }}
            </div>
        </td>
    </tr>
</table>
