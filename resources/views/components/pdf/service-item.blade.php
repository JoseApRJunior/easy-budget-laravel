@props([
    'service'
])

@php
    $colors = config('pdf_theme.colors');
@endphp

<div style="width: 100%; margin-bottom: 25px; page-break-inside: avoid;">
    {{-- Cabeçalho do Serviço - Design mais limpo com borda lateral --}}
    <div style="width: 100%; border-left: 3px solid {{ $colors['primary'] }}; padding-left: 12px; margin-bottom: 12px; background-color: #fcfcfc; padding-top: 5px; padding-bottom: 5px;">
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="text-align: left;">
                    <span style="font-size: 12px; font-weight: bold; color: {{ $colors['dark'] }}; text-transform: uppercase; letter-spacing: 0.5px;">
                        {{ $service->category->name }}
                    </span>
                </td>
                <td style="text-align: right;">
                    <span style="font-size: 8px; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; color: {{ $colors['secondary'] }};">
                        Status: <span style="color: {{ $colors['primary'] }}">{{ $service->status->getDescription() }}</span>
                    </span>
                </td>
            </tr>
        </table>
    </div>

    <div style="width: 100%; padding-left: 15px;">
        {{-- Descrição do Serviço - Mais discreta --}}
        @if($service->description)
            <div style="margin-bottom: 12px; font-size: 10px; color: {{ $colors['text'] }}; font-style: italic; line-height: 1.4;">
                {{ $service->description }}
            </div>
        @endif

        {{-- Tabela de Itens - Minimalista --}}
        <table style="width: 100%; font-size: 10px; table-layout: fixed; border-collapse: collapse;">
            <thead>
                <tr style="border-bottom: 1px solid {{ $colors['primary'] }};">
                    <th style="width: 55%; text-align: left; padding: 5px 0; font-size: 8px; color: {{ $colors['secondary'] }}; text-transform: uppercase; letter-spacing: 1px;">Item / Detalhes</th>
                    <th style="width: 10%; text-align: center; padding: 5px 0; font-size: 8px; color: {{ $colors['secondary'] }}; text-transform: uppercase; letter-spacing: 1px;">Qtd</th>
                    <th style="width: 17.5%; text-align: right; padding: 5px 0; font-size: 8px; color: {{ $colors['secondary'] }}; text-transform: uppercase; letter-spacing: 1px;">Unitário</th>
                    <th style="width: 17.5%; text-align: right; padding: 5px 0; font-size: 8px; color: {{ $colors['secondary'] }}; text-transform: uppercase; letter-spacing: 1px;">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach( $service->serviceItems as $item )
                <tr style="border-bottom: 0.5px solid {{ $colors['border'] }};">
                    <td style="padding: 8px 0; vertical-align: top;">
                        <div style="font-weight: bold; color: {{ $colors['text'] }}; font-size: 10px;">{{ $item->product?->name ?? 'Item' }}</div>
                        @if($item->product?->description)
                            <div style="font-size: 8.5px; color: {{ $colors['secondary'] }}; line-height: 1.3; margin-top: 3px; padding-right: 10px;">
                                {{ $item->product->description }}
                            </div>
                        @endif
                    </td>
                    <td style="text-align: center; padding: 8px 0; vertical-align: top; color: {{ $colors['text'] }};">{{ $item->quantity }}</td>
                    <td style="text-align: right; padding: 8px 0; vertical-align: top; color: {{ $colors['text'] }};">R$ {{ \App\Helpers\CurrencyHelper::format($item->unit_value) }}</td>
                    <td style="text-align: right; padding: 8px 0; vertical-align: top; font-weight: bold; color: {{ $colors['dark'] }};">R$ {{ \App\Helpers\CurrencyHelper::format($item->quantity * $item->unit_value) }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" style="text-align: right; padding-top: 12px; padding-right: 15px;">
                        <span style="font-size: 8px; color: {{ $colors['secondary'] }}; text-transform: uppercase; letter-spacing: 1px;">Total do Serviço</span>
                    </td>
                    <td style="text-align: right; padding-top: 12px;">
                        <span style="font-weight: bold; color: {{ $colors['dark'] }}; font-size: 12px; border-bottom: 2px solid {{ $colors['primary'] }}; padding-bottom: 2px;">
                            R$ {{ \App\Helpers\CurrencyHelper::format($service->total) }}
                        </span>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
