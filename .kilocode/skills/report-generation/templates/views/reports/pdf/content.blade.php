<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Conteúdo do Relatório</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #333;
            margin: 0;
            padding: 0;
        }

        .content {
            margin-top: 20px;
        }

        .summary {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }

        .summary-title {
            font-size: 14px;
            font-weight: bold;
            color: #495057;
            margin-bottom: 10px;
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 5px;
        }

        .summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            padding: 2px 0;
        }

        .summary-label {
            font-weight: bold;
            color: #6c757d;
        }

        .summary-value {
            font-weight: bold;
            color: #333;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 11px;
        }

        .data-table th {
            background-color: #e9ecef;
            border: 1px solid #dee2e6;
            padding: 8px;
            text-align: left;
            font-weight: bold;
            font-size: 12px;
        }

        .data-table td {
            border: 1px solid #dee2e6;
            padding: 6px 8px;
            font-size: 11px;
        }

        .data-table tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        .data-table tr:hover {
            background-color: #e7f3ff;
        }

        .charts {
            margin-bottom: 20px;
        }

        .chart-title {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #495057;
        }

        .chart-container {
            text-align: center;
            margin-bottom: 20px;
        }

        .chart-image {
            max-width: 100%;
            height: auto;
            border: 1px solid #dee2e6;
            border-radius: 4px;
        }

        .footer-notes {
            font-size: 10px;
            color: #6c757d;
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #dee2e6;
        }

        .currency {
            text-align: right;
            font-family: 'Courier New', monospace;
        }

        .number {
            text-align: right;
        }

        .date {
            font-family: 'Courier New', monospace;
        }
    </style>
</head>
<body>
    <div class="content">
        @if(isset($reportData['summary']) && !empty($reportData['summary']))
            <div class="summary">
                <div class="summary-title">Resumo Executivo</div>
                @foreach($reportData['summary'] as $key => $value)
                    <div class="summary-item">
                        <span class="summary-label">{{ ucfirst(str_replace('_', ' ', $key)) }}:</span>
                        <span class="summary-value">
                            @if(is_numeric($value))
                                R$ {{ number_format($value, 2, ',', '.') }}
                            @else
                                {{ $value }}
                            @endif
                        </span>
                    </div>
                @endforeach
            </div>
        @endif

        @if(isset($reportData['data']) && !empty($reportData['data']))
            <div class="data-table-container">
                <table class="data-table">
                    <thead>
                        <tr>
                            @foreach($reportData['data'] as $row)
                                @foreach($row as $key => $value)
                                    <th>{{ ucfirst(str_replace('_', ' ', $key)) }}</th>
                                @endforeach
                                @break
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($reportData['data'] as $row)
                            <tr>
                                @foreach($row as $key => $value)
                                    <td class="{{ $key === 'total' || $key === 'value' || $key === 'amount' ? 'currency' : ($key === 'quantity' || $key === 'count' ? 'number' : ($key === 'date' || $key === 'created_at' ? 'date' : '')) }}">
                                        @if(is_numeric($value) && ($key === 'total' || $key === 'value' || $key === 'amount'))
                                            R$ {{ number_format($value, 2, ',', '.') }}
                                        @elseif(is_numeric($value))
                                            {{ number_format($value, 0, '', '.') }}
                                        @elseif($key === 'date' || $key === 'created_at')
                                            {{ \Carbon\Carbon::parse($value)->format('d/m/Y') }}
                                        @else
                                            {{ $value }}
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @if(isset($reportData['charts']) && !empty($reportData['charts']))
            <div class="charts">
                <div class="chart-title">Gráficos e Visualizações</div>
                @foreach($reportData['charts'] as $chart)
                    <div class="chart-container">
                        <div class="chart-title">{{ $chart['title'] ?? 'Gráfico' }}</div>
                        @if(isset($chart['image']))
                            <img src="{{ $chart['image'] }}" alt="{{ $chart['title'] }}" class="chart-image">
                        @else
                            <div style="width: 100%; height: 300px; background-color: #f8f9fa; border: 1px dashed #dee2e6; display: flex; align-items: center; justify-content: center; color: #6c757d;">
                                Gráfico não disponível
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif

        <div class="footer-notes">
            <strong>Observações:</strong>
            <ul style="margin: 5px 0 0 20px; padding: 0;">
                <li>Os valores são expressos em Reais (R$)</li>
                <li>Os dados são referentes ao período selecionado nos filtros</li>
                <li>Este relatório foi gerado automaticamente pelo sistema</li>
                <li>Para mais informações, consulte o suporte técnico</li>
            </ul>
        </div>
    </div>
</body>
</html>
