@props( [ 'summary' ] )

<div class="card hover-card mb-4">
    <div class="card-header bg-primary">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Resumo Financeiro</h5>
            <span class="badge bg-light text-primary">
                {{ month_year_pt( now() ) }}
            </span>
        </div>
    </div>
    <div class="card-body">
        {{-- Faturamento Mensal --}}
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h6 class="mb-0">Faturamento Mensal</h6>
                <small class="text-muted">Total aprovado/pago</small>
            </div>
            <div class="text-end">
                <h5 class="mb-0 text-success">
                    R$ {{ number_format( $summary[ 'monthly_revenue' ] ?? 0, 2, ',', '.' ) }}
                </h5>
            </div>
        </div>

        {{-- Orçamentos Pendentes --}}
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h6 class="mb-0">Orçamentos Pendentes</h6>
                <small class="text-muted">
                    {{ $summary[ 'pending_budgets' ][ 'count' ] ?? 0 }} orçamento(s)
                </small>
            </div>
            <div class="text-end">
                <h5 class="mb-0 text-warning">
                    R$ {{ number_format( $summary[ 'pending_budgets' ][ 'total' ] ?? 0, 2, ',', '.' ) }}
                </h5>
            </div>
        </div>

        {{-- Pagamentos Atrasados --}}
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h6 class="mb-0">Pagamentos Atrasados</h6>
                <small class="text-muted">
                    {{ $summary[ 'overdue_payments' ][ 'count' ] ?? 0 }} pagamento(s)
                </small>
            </div>
            <div class="text-end">
                <h5 class="mb-0 text-danger">
                    R$ {{ number_format( $summary[ 'overdue_payments' ][ 'total' ] ?? 0, 2, ',', '.' ) }}
                </h5>
            </div>
        </div>

        {{-- Projeção Próximo Mês --}}
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h6 class="mb-0">Projeção Próximo Mês</h6>
                <small class="text-muted">{{ now()->addMonth()->format( 'M/Y' ) }}</small>
            </div>
            <div class="text-end">
                <h5 class="mb-0 text-info">
                    R$ {{ number_format( $summary[ 'next_month_projection' ] ?? 0, 2, ',', '.' ) }}
                </h5>
            </div>
        </div>
    </div>
    <div class="card-footer bg-light">
        <div class="d-flex justify-content-between align-items-center">
            <small class="text-muted">Última atualização: {{ now()->format( 'd/m/Y H:i' ) }}</small>
            <a href="{{ route( 'provider.reports.financial' ) }}" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-graph-up me-1"></i>Ver Relatório Completo
            </a>
        </div>
    </div>
</div>
