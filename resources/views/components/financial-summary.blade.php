@props( [ 'summary' ] )

<div class="card border-0 shadow-sm h-100">
    <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0 fw-bold text-dark">
                <i class="bi bi-graph-up-arrow me-2 text-primary"></i>Resumo Financeiro
            </h5>
            <span class="badge bg-primary bg-opacity-10 text-primary rounded-pill px-3">
                {{ month_year_pt(now()) }}
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
    <div class="card-footer bg-white border-top-0 pb-4">
        <div class="d-flex justify-content-between align-items-center">
            <small class="text-muted small">
                <i class="bi bi-clock-history me-1"></i>{{ now()->format('d/m/Y H:i') }}
            </small>
            <a href="{{ route('provider.reports.financial') }}" class="btn btn-sm btn-link text-decoration-none p-0">
                Relatório Completo <i class="bi bi-arrow-right ms-1"></i>
            </a>
        </div>
    </div>
</div>
