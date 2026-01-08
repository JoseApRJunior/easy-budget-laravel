@props([
    'service'
])

<div style="width: 100%; margin-bottom: 25px; page-break-inside: avoid;">
    {{-- Cabeçalho do Serviço - Design mais limpo com borda lateral --}}
    <div style="width: 100%; border-left: 3px solid #333; padding-left: 12px; margin-bottom: 12px; background-color: #fcfcfc; padding-top: 5px; padding-bottom: 5px;">
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="text-align: left;">
                    <span style="font-size: 12px; font-weight: bold; color: #1a1a1a; text-transform: uppercase; letter-spacing: 0.5px;">
                        {{ $service->category->name }}
                    </span>
                </td>
                <td style="text-align: right;">
                    <span style="font-size: 8px; color: #333;; text-transform: uppercase; letter-spacing: 0.5px;">
                        Status: <span style="color: #333; font-weight: bold;">{{ $service->status->getDescription() }}</span>
                    </span>
                </td>
            </tr>
        </table>
    </div>

    <div style="width: 100%; padding-left: 15px;">
        {{-- Descrição do Serviço - Mais discreta --}}
        @if($service->description)
            <div style="margin-bottom: 12px; font-size: 10px; color: #333; font-style: italic; line-height: 1.4;">
                {{ $service->description }}
            </div>
        @endif

        {{-- Tabela de Itens - Minimalista --}}
        <table style="width: 100%; font-size: 10px; table-layout: fixed; border-collapse: collapse;">
            <thead>
                <tr style="border-bottom: 1px solid #333;">
                    <th style="width: 55%; text-align: left; padding: 5px 0; font-size: 8px; color: #333;; text-transform: uppercase; letter-spacing: 1px;">Item / Detalhes</th>
                    <th style="width: 10%; text-align: center; padding: 5px 0; font-size: 8px; color: #333;; text-transform: uppercase; letter-spacing: 1px;">Qtd</th>
                    <th style="width: 17.5%; text-align: right; padding: 5px 0; font-size: 8px; color: #333;; text-transform: uppercase; letter-spacing: 1px;">Unitário</th>
                    <th style="width: 17.5%; text-align: right; padding: 5px 0; font-size: 8px; color: #333;; text-transform: uppercase; letter-spacing: 1px;">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach( $service->serviceItems as $item )
                <tr style="border-bottom: 0.5px solid #eee;">
                    <td style="padding: 8px 0; vertical-align: top;">
                        <div style="font-weight: bold; color: #222; font-size: 10px;">{{ $item->product?->name ?? 'Item' }}</div>
                        @if($item->product?->description)
                            <div style="font-size: 8.5px; color: #333; line-height: 1.3; margin-top: 3px; padding-right: 10px;">
                                {{ $item->product->description }}
                            </div>
                        @endif
                    </td>
                    <td style="text-align: center; padding: 8px 0; vertical-align: top; color: #333;">{{ $item->quantity }}</td>
                    <td style="text-align: right; padding: 8px 0; vertical-align: top; color: #333;">R$ {{ \App\Helpers\CurrencyHelper::format($item->unit_value) }}</td>
                    <td style="text-align: right; padding: 8px 0; vertical-align: top; font-weight: bold; color: #1a1a1a;">R$ {{ \App\Helpers\CurrencyHelper::format($item->quantity * $item->unit_value) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" style="text-align: right; padding-top: 12px; padding-right: 15px;">
                        <span style="font-size: 8px; color: #333;; text-transform: uppercase; letter-spacing: 1px;">Total do Serviço</span>
                    </td>
                    <td style="text-align: right; padding-top: 12px;">
                        <span style="font-weight: bold; color: #000; font-size: 12px; border-bottom: 2px solid #333; padding-bottom: 2px;">
                            R$ {{ \App\Helpers\CurrencyHelper::format($service->total) }}
                        </span>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
