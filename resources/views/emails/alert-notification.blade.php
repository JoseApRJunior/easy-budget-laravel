<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            background-color: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e9ecef;
        }
        .alert-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            color: white;
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 15px;
        }
        .alert-info {
            background-color: {{ $severityColor }};
        }
        .alert-type {
            background-color: {{ $alertTypeColor }};
            margin-left: 10px;
        }
        .metric-card {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 6px;
            margin: 20px 0;
            border-left: 4px solid {{ $severityColor }};
        }
        .metric-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            padding: 8px 0;
            border-bottom: 1px solid #e9ecef;
        }
        .metric-row:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }
        .metric-label {
            font-weight: 600;
            color: #495057;
        }
        .metric-value {
            font-weight: bold;
            color: #212529;
        }
        .message-box {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 6px;
            padding: 15px;
            margin: 20px 0;
            color: #856404;
        }
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
            text-align: center;
            color: #6c757d;
            font-size: 12px;
        }
        .action-button {
            display: inline-block;
            padding: 12px 24px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            font-weight: bold;
            margin-top: 20px;
        }
        .action-button:hover {
            background-color: #0056b3;
        }
        .timestamp {
            color: #6c757d;
            font-size: 14px;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div>
                <span class="alert-badge alert-info">
                    <i class="bi bi-exclamation-triangle"></i> {{ $severityLabel }}
                </span>
                <span class="alert-badge alert-type">
                    <i class="bi bi-info-circle"></i> {{ $alertTypeLabel }}
                </span>
            </div>
            <h1>Alerta do Sistema EasyBudget</h1>
            <p class="timestamp">{{ $alert->created_at->format('d/m/Y H:i:s') }}</p>
        </div>

        <div class="metric-card">
            <h3>Detalhes do Alerta</h3>
            <div class="metric-row">
                <span class="metric-label">Tenant:</span>
                <span class="metric-value">{{ $tenant->name }}</span>
            </div>
            <div class="metric-row">
                <span class="metric-label">Métrica:</span>
                <span class="metric-value">{{ ucfirst(str_replace('_', ' ', $alert->metric_name)) }}</span>
            </div>
            <div class="metric-row">
                <span class="metric-label">Valor Atual:</span>
                <span class="metric-value">{{ number_format($alert->metric_value, 2) }}</span>
            </div>
            <div class="metric-row">
                <span class="metric-label">Limiar Configurado:</span>
                <span class="metric-value">{{ number_format($alert->threshold_value, 2) }}</span>
            </div>
        </div>

        <div class="message-box">
            <strong>Mensagem:</strong><br>
            {{ $alert->message }}
        </div>

        @if(!empty($alert->additional_data))
            <div style="margin: 20px 0;">
                <h4>Informações Adicionais:</h4>
                <pre style="background-color: #f8f9fa; padding: 15px; border-radius: 4px; font-size: 12px; overflow-x: auto;">{{ json_encode($alert->additional_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
            </div>
        @endif

        <div style="text-align: center;">
            <a href="{{ route('admin.alerts.index') }}" class="action-button">
                Ver Dashboard de Alertas
            </a>
        </div>

        <div class="footer">
            <p>Este é um alerta automático do sistema EasyBudget.</p>
            <p>Se você acredita que este alerta foi enviado por engano, entre em contato com o suporte.</p>
            <p>&copy; {{ date('Y') }} EasyBudget. Todos os direitos reservados.</p>
        </div>
    </div>
</body>
</html>