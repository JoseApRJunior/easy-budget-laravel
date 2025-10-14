@props([
    'queueStats' => [],
    'refreshInterval' => 5000
])

<div class="queue-monitor-card">
    <div class="queue-monitor-header">
        <h6 class="mb-0">
            <i class="fas fa-queue me-2"></i>
            Monitor de Filas de E-mail
        </h6>
        <div class="queue-controls">
            <button class="btn btn-sm btn-outline-primary" onclick="refreshQueueStats()" title="Atualizar">
                <i class="fas fa-sync-alt"></i>
            </button>
            <button class="btn btn-sm btn-outline-success" onclick="retryFailedJobs()" title="Retentar Jobs">
                <i class="fas fa-redo"></i>
            </button>
        </div>
    </div>

    <div class="queue-stats-container">
        @if(isset($queueStats['queues']))
            @foreach($queueStats['queues'] as $type => $stats)
            <div class="queue-item" data-queue="{{ $type }}">
                <div class="queue-header">
                    <div class="queue-info">
                        <h6 class="queue-name">{{ ucfirst($type) }}</h6>
                        <span class="queue-status status-{{ $stats['status'] }}">
                            {{ getStatusLabel($stats['status']) }}
                        </span>
                    </div>
                    <div class="queue-metrics">
                        <span class="metric queued" title="Jobs na fila">
                            <i class="fas fa-clock"></i>
                            {{ number_format($stats['queued_emails']) }}
                        </span>
                        <span class="metric processing" title="Jobs processando">
                            <i class="fas fa-cog"></i>
                            {{ number_format($stats['processing_emails']) }}
                        </span>
                        <span class="metric failed" title="Jobs com falha">
                            <i class="fas fa-exclamation-triangle"></i>
                            {{ number_format($stats['failed_emails']) }}
                        </span>
                    </div>
                </div>

                @if($stats['avg_wait_time_sec'] > 0)
                <div class="queue-details">
                    <div class="detail-item">
                        <small class="text-muted">Tempo médio de espera:</small>
                        <strong>{{ number_format($stats['avg_wait_time_sec'], 1) }}s</strong>
                    </div>
                    <div class="detail-item">
                        <small class="text-muted">Jobs/hora:</small>
                        <strong>{{ number_format($stats['total_jobs_hour']) }}</strong>
                    </div>
                </div>
                @endif

                @if($stats['failed_emails'] > 0)
                <div class="queue-alert">
                    <i class="fas fa-exclamation-triangle text-warning"></i>
                    {{ $stats['failed_emails'] }} jobs com falha detectados
                </div>
                @endif
            </div>
            @endforeach
        @else
            <div class="no-data">
                <i class="fas fa-inbox text-muted"></i>
                <p class="text-muted">Nenhuma estatística disponível</p>
            </div>
        @endif
    </div>

    @if(isset($queueStats['totals']))
    <div class="queue-totals">
        <div class="total-item">
            <span class="total-label">Total na Fila:</span>
            <span class="total-value">{{ number_format($queueStats['totals']['total_queued']) }}</span>
        </div>
        <div class="total-item">
            <span class="total-label">Processando:</span>
            <span class="total-value">{{ number_format($queueStats['totals']['total_processing']) }}</span>
        </div>
        <div class="total-item">
            <span class="total-label">Com Falha:</span>
            <span class="total-value text-danger">{{ number_format($queueStats['totals']['total_failed']) }}</span>
        </div>
    </div>
    @endif

    @if(isset($queueStats['performance']))
    <div class="queue-performance">
        <h6 class="section-title">
            <i class="fas fa-tachometer-alt me-2"></i>
            Performance
        </h6>
        <div class="performance-metrics">
            <div class="metric-item">
                <span class="metric-label">Jobs/min:</span>
                <span class="metric-value">{{ $queueStats['performance']['throughput']['jobs_per_minute'] }}</span>
            </div>
            <div class="metric-item">
                <span class="metric-label">Tempo médio:</span>
                <span class="metric-value">{{ $queueStats['performance']['last_hour']['avg_wait_time_sec'] }}s</span>
            </div>
        </div>
    </div>
    @endif
</div>

<style>
.queue-monitor-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    overflow: hidden;
}

.queue-monitor-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 1rem 1.5rem;
    display: flex;
    justify-content: between;
    align-items: center;
}

.queue-controls {
    display: flex;
    gap: 0.5rem;
}

.queue-controls .btn {
    border-color: rgba(255, 255, 255, 0.3);
    color: white;
}

.queue-controls .btn:hover {
    background: rgba(255, 255, 255, 0.1);
    border-color: rgba(255, 255, 255, 0.5);
    color: white;
}

.queue-stats-container {
    padding: 1.5rem;
}

.queue-item {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1rem;
    border-left: 4px solid #667eea;
    transition: all 0.3s ease;
}

.queue-item:hover {
    transform: translateX(4px);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.queue-item:last-child {
    margin-bottom: 0;
}

.queue-header {
    display: flex;
    justify-content: between;
    align-items: center;
    margin-bottom: 0.5rem;
}

.queue-info {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.queue-name {
    font-size: 1rem;
    font-weight: 600;
    margin: 0;
    color: #333;
}

.queue-status {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
    border-radius: 12px;
    width: fit-content;
}

.status-healthy {
    background: #d4edda;
    color: #155724;
}

.status-warning {
    background: #fff3cd;
    color: #856404;
}

.status-critical {
    background: #f8d7da;
    color: #721c24;
}

.status-active {
    background: #cce7ff;
    color: #0066cc;
}

.status-idle {
    background: #e2e3e5;
    color: #383d41;
}

.queue-metrics {
    display: flex;
    gap: 1rem;
}

.metric {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.9rem;
    font-weight: 500;
}

.metric.queued {
    color: #ffc107;
}

.metric.processing {
    color: #17a2b8;
}

.metric.failed {
    color: #dc3545;
}

.queue-details {
    display: flex;
    gap: 2rem;
    margin-top: 0.5rem;
    padding-top: 0.5rem;
    border-top: 1px solid #dee2e6;
}

.detail-item {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.detail-item small {
    font-size: 0.75rem;
}

.queue-alert {
    background: #fff3cd;
    color: #856404;
    padding: 0.5rem;
    border-radius: 4px;
    margin-top: 0.5rem;
    font-size: 0.8rem;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.queue-totals {
    background: #f8f9fa;
    padding: 1rem 1.5rem;
    border-top: 1px solid #dee2e6;
    display: flex;
    justify-content: space-around;
}

.total-item {
    text-align: center;
}

.total-label {
    display: block;
    font-size: 0.8rem;
    color: #6c757d;
}

.total-value {
    display: block;
    font-size: 1.5rem;
    font-weight: bold;
    color: #333;
}

.queue-performance {
    padding: 1rem 1.5rem;
    border-top: 1px solid #dee2e6;
}

.section-title {
    font-size: 0.9rem;
    font-weight: 600;
    color: #333;
    margin-bottom: 0.5rem;
}

.performance-metrics {
    display: flex;
    gap: 2rem;
}

.metric-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
}

.metric-label {
    font-size: 0.8rem;
    color: #6c757d;
    margin-bottom: 0.25rem;
}

.metric-value {
    font-size: 1.2rem;
    font-weight: bold;
    color: #667eea;
}

.no-data {
    text-align: center;
    padding: 2rem;
    color: #6c757d;
}

.no-data i {
    font-size: 3rem;
    margin-bottom: 1rem;
    display: block;
}

/* Responsividade */
@media (max-width: 768px) {
    .queue-metrics {
        flex-direction: column;
        gap: 0.5rem;
    }

    .queue-details {
        flex-direction: column;
        gap: 0.5rem;
    }

    .queue-totals {
        flex-direction: column;
        gap: 1rem;
    }

    .performance-metrics {
        flex-direction: column;
        gap: 1rem;
    }
}
</style>

<script>
let queueStatsInterval;

document.addEventListener('DOMContentLoaded', function() {
    startQueueMonitoring();
});

function startQueueMonitoring() {
    refreshQueueStats();
    queueStatsInterval = setInterval(refreshQueueStats, {{ $refreshInterval }});
}

function stopQueueMonitoring() {
    if (queueStatsInterval) {
        clearInterval(queueStatsInterval);
    }
}

function refreshQueueStats() {
    fetch('/emails/queue-stats')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateQueueDisplay(data.data);
            }
        })
        .catch(error => {
            console.error('Erro ao atualizar estatísticas da fila:', error);
        });
}

function updateQueueDisplay(stats) {
    // Atualizar elementos da interface com os novos dados
    const container = document.querySelector('.queue-stats-container');
    if (container && stats.queues) {
        // Atualizar cada fila
        Object.entries(stats.queues).forEach(([type, queueStats]) => {
            const queueElement = container.querySelector(`[data-queue="${type}"]`);
            if (queueElement) {
                // Atualizar métricas
                const metrics = queueElement.querySelectorAll('.metric');
                if (metrics.length >= 3) {
                    metrics[0].querySelector('span:last-child').textContent = queueStats.queued_emails;
                    metrics[1].querySelector('span:last-child').textContent = queueStats.processing_emails;
                    metrics[2].querySelector('span:last-child').textContent = queueStats.failed_emails;
                }

                // Atualizar status
                const statusElement = queueElement.querySelector('.queue-status');
                if (statusElement) {
                    statusElement.className = `queue-status status-${queueStats.status}`;
                    statusElement.textContent = getStatusLabel(queueStats.status);
                }
            }
        });
    }

    // Atualizar totais
    if (stats.totals) {
        const totalsContainer = document.querySelector('.queue-totals');
        if (totalsContainer) {
            const totalValues = totalsContainer.querySelectorAll('.total-value');
            if (totalValues.length >= 3) {
                totalValues[0].textContent = stats.totals.total_queued;
                totalValues[1].textContent = stats.totals.total_processing;
                totalValues[2].textContent = stats.totals.total_failed;
            }
        }
    }
}

function retryFailedJobs() {
    if (confirm('Deseja retentar todos os jobs de e-mail com falha?')) {
        fetch('/emails/retry-failed-jobs', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(`Sucesso: ${data.data.retried} jobs foram retryados.`);
                refreshQueueStats();
            } else {
                alert('Erro: ' + data.error);
            }
        })
        .catch(error => {
            alert('Erro na requisição: ' + error.message);
        });
    }
}

function getStatusLabel(status) {
    const labels = {
        'healthy': 'Saudável',
        'warning': 'Aviso',
        'critical': 'Crítico',
        'active': 'Ativo',
        'idle': 'Ocioso'
    };
    return labels[status] || status;
}

// Limpar interval quando a página for descarregada
window.addEventListener('beforeunload', function() {
    stopQueueMonitoring();
});
</script>

@php
function getStatusLabel($status) {
    $labels = [
        'healthy' => 'Saudável',
        'warning' => 'Aviso',
        'critical' => 'Crítico',
        'active' => 'Ativo',
        'idle' => 'Ocioso'
    ];
    return $labels[$status] ?? $status;
}
@endphp
