<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Relatório - {{ $reportData['type'] }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            color: #333;
            margin: 0;
            padding: 0;
        }

        .header {
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .header-logo {
            float: left;
            width: 150px;
        }

        .header-info {
            float: right;
            text-align: right;
            width: calc(100% - 160px);
        }

        .header-title {
            font-size: 18px;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 5px;
        }

        .header-subtitle {
            font-size: 14px;
            color: #666;
            margin-bottom: 5px;
        }

        .header-meta {
            font-size: 10px;
            color: #999;
        }

        .filters {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
        }

        .filters-title {
            font-weight: bold;
            margin-bottom: 5px;
            color: #495057;
        }

        .filter-item {
            display: inline-block;
            background-color: #e9ecef;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
            margin-right: 5px;
            margin-bottom: 3px;
        }

        .clearfix {
            clear: both;
        }

        .page-number {
            text-align: center;
            font-size: 10px;
            color: #999;
            margin-top: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-logo">
            @if(isset($company_logo))
                <img src="{{ $company_logo }}" alt="Logo" style="max-width: 100%; height: auto;">
            @else
                <div style="width: 100%; height: 50px; background-color: #007bff; color: white; display: flex; align-items: center; justify-content: center; font-weight: bold;">
                    LOGO
                </div>
            @endif
        </div>

        <div class="header-info">
            <div class="header-title">
                {{ $reportData['type'] }}
            </div>
            <div class="header-subtitle">
                {{ $reportData['generated_at'] }}
            </div>
            <div class="header-meta">
                Empresa: {{ $company_name ?? 'Easy Budget' }} |
                Gerado em: {{ now()->format('d/m/Y H:i:s') }} |
                Usuário: {{ $user_name ?? 'Sistema' }}
            </div>
        </div>

        <div class="clearfix"></div>
    </div>

    @if(isset($reportData['filters']) && !empty($reportData['filters']))
        <div class="filters">
            <div class="filters-title">Filtros Aplicados:</div>
            @foreach($reportData['filters'] as $key => $value)
                @if(!empty($value))
                    <span class="filter-item">
                        <strong>{{ ucfirst(str_replace('_', ' ', $key)) }}:</strong>
                        {{ is_array($value) ? implode(', ', $value) : $value }}
                    </span>
                @endif
            @endforeach
        </div>
    @endif
</body>
</html>
