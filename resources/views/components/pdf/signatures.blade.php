@props([
    'providerName' => null,
    'customerName' => null,
    'providerLabel' => 'Prestador de Serviços',
    'customerLabel' => 'Cliente / Contratante'
])

<div style="width: 100%; margin-top: 50px;">
    <table style="width: 100%; border-collapse: collapse;">
        <tr>
            <td style="width: 45%; text-align: center; vertical-align: bottom;">
                <div style="padding-top: 40px;">
                    <div style="border-bottom: 1px solid #000; width: 85%; margin: 0 auto 8px auto;"></div>
                    <div style="line-height: 1.2;">
                        <strong style="font-size: 10px; color: #000; display: block;">
                            {{ $providerName }}
                        </strong>
                        <span style="font-size: 8px; color: #444; text-transform: uppercase; letter-spacing: 0.5px;">{{ $providerLabel }}</span>
                    </div>
                </div>
            </td>
            <td style="width: 10%;"></td>
            <td style="width: 45%; text-align: center; vertical-align: bottom;">
                <div style="padding-top: 40px;">
                    <div style="border-bottom: 1px solid #000; width: 85%; margin: 0 auto 8px auto;"></div>
                    <div style="line-height: 1.2;">
                        <strong style="font-size: 10px; color: #000; display: block;">{{ $customerName }}</strong>
                        <span style="font-size: 8px; color: #444; text-transform: uppercase; letter-spacing: 0.5px;">{{ $customerLabel }}</span>
                    </div>
                </div>
            </td>
        </tr>
    </table>
</div>
