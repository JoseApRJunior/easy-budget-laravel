@extends('emails.layouts.base')

@section('content')
<div style="text-align: center; margin-bottom: 30px;">
    <div style="display: inline-block; padding: 8px 16px; border-radius: 20px; color: white; font-weight: bold; font-size: 14px; margin-bottom: 15px; background-color: {{ $severityColor }};">
        {{ $severityLabel }}
    </div>
    <div style="display: inline-block; padding: 8px 16px; border-radius: 20px; color: white; font-weight: bold; font-size: 14px; margin-bottom: 15px; background-color: {{ $alertTypeColor }}; margin-left: 10px;">
        {{ $alertTypeLabel }}
    </div>
</div>

<div style="background-color: #f8f9fa; padding: 20px; border-radius: 6px; margin: 20px 0; border-left: 4px solid {{ $severityColor }};">
    <div style="display: flex; justify-content: space-between; margin-bottom: 10px; padding: 8px 0; border-bottom: 1px solid #e9ecef;">
        <span style="font-weight: 600; color: #495057;">Métrica:</span>
        <span style="font-weight: bold; color: #212529;">{{ $alert->metric_name }}</span>
    </div>
    <div style="display: flex; justify-content: space-between; margin-bottom: 10px; padding: 8px 0; border-bottom: 1px solid #e9ecef;">
        <span style="font-weight: 600; color: #495057;">Valor Atual:</span>
        <span style="font-weight: bold; color: #212529;">{{ $alert->current_value }}</span>
    </div>
    <div style="display: flex; justify-content: space-between; margin-bottom: 10px; padding: 8px 0; border-bottom: 1px solid #e9ecef;">
        <span style="font-weight: 600; color: #495057;">Limite:</span>
        <span style="font-weight: bold; color: #212529;">{{ $alert->threshold_value }}</span>
    </div>
    <div style="display: flex; justify-content: space-between; margin-bottom: 10px; padding: 8px 0;">
        <span style="font-weight: 600; color: #495057;">Horário:</span>
        <span style="font-weight: bold; color: #212529;">{{ $alert->triggered_at->format('d/m/Y H:i:s') }}</span>
    </div>
</div>

<div style="background-color: #fff3cd; border: 1px solid #ffeaa7; border-radius: 6px; padding: 15px; margin: 20px 0; color: #856404;">
    {{ $alert->message }}
</div>

<div style="text-align: center; margin-top: 30px;">
    <a href="{{ config('app.url') }}/monitoring" style="display: inline-block; padding: 12px 24px; background-color: {{ $statusColor ?? '#0d6efd' }}; color: white; text-decoration: none; border-radius: 6px; font-weight: bold;">
        Ver Painel de Monitoramento
    </a>
</div>
@endsection
