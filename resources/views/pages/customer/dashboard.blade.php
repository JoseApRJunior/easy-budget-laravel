@extends( 'layouts.app' )

@section( 'title', 'Dashboard de Clientes' )

@section( 'content' )
  <div class="container-fluid py-4">
    <!-- Cabeçalho -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h1 class="h3 mb-0">
          <i class="bi bi-people-fill me-2"></i>Dashboard de Clientes
        </h1>
        <p class="text-muted mb-0">
          Visão geral dos clientes do seu negócio com base nos dados consolidados.
        </p>
      </div>
      <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
          <li class="breadcrumb-item">
            <a href="{{ route( 'provider.dashboard' ) }}">Dashboard</a>
          </li>
          <li class="breadcrumb-item">
            <a href="{{ route( 'provider.customers.index' ) }}">Clientes</a>
          </li>
          <li class="breadcrumb-item active" aria-current="page">
            Dashboard
          </li>
        </ol>
      </nav>
    </div>

    @php
      $total    = $stats[ 'total_customers' ] ?? 0;
      $active   = $stats[ 'active_customers' ] ?? 0;
      $inactive = $stats[ 'inactive_customers' ] ?? 0;
      $recent   = $stats[ 'recent_customers' ] ?? collect();
    @endphp

    <!-- Cards de Métricas -->
    <div class="row g-4 mb-4">
      <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body d-flex flex-column justify-content-between">
            <div class="d-flex align-items-center mb-3">
              <div class="avatar-circle bg-primary bg-gradient me-3">
                <i class="bi bi-people-fill text-white"></i>
              </div>
              <div>
                <h6 class="text-muted mb-1">Total de Clientes</h6>
                <h3 class="mb-0">{{ $total }}</h3>
              </div>
            </div>
            <p class="text-muted small mb-0">
              Contagem total de clientes cadastrados no sistema para este tenant.
            </p>
          </div>
        </div>
      </div>

      <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body d-flex flex-column justify-content-between">
            <div class="d-flex align-items-center mb-3">
              <div class="avatar-circle bg-success bg-gradient me-3">
                <i class="bi bi-check-circle-fill text-white"></i>
              </div>
              <div>
                <h6 class="text-muted mb-1">Clientes Ativos</h6>
                <h3 class="mb-0">{{ $active }}</h3>
              </div>
            </div>
            <p class="text-muted small mb-0">
              Clientes com status ativo e aptos a receber propostas e serviços.
            </p>
          </div>
        </div>
      </div>

      <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body d-flex flex-column justify-content-between">
            <div class="d-flex align-items-center mb-3">
              <div class="avatar-circle bg-secondary bg-gradient me-3">
                <i class="bi bi-pause-circle-fill text-white"></i>
              </div>
              <div>
                <h6 class="text-muted mb-1">Clientes Inativos</h6>
                <h3 class="mb-0">{{ $inactive }}</h3>
              </div>
            </div>
            <p class="text-muted small mb-0">
              Clientes marcados como inativos para controle interno.
            </p>
          </div>
        </div>
      </div>

      <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body d-flex flex-column justify-content-between">
            <div class="d-flex align-items-center mb-3">
              <div class="avatar-circle bg-info bg-gradient me-3">
                <i class="bi bi-graph-up-arrow text-white"></i>
              </div>
              <div>
                <h6 class="text-muted mb-1">Taxa de Atividade</h6>
                @php
                  $activityRate = $total > 0 ? number_format( ( $active / $total ) * 100, 1, ',', '.' ) : 0;
                @endphp
                <h3 class="mb-0">{{ $activityRate }}%</h3>
              </div>
            </div>
            <p class="text-muted small mb-0">
              Percentual de clientes ativos em relação ao total.
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Clientes Recentes -->
    <div class="row g-4">
      <div class="col-lg-8">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
              <i class="bi bi-clock-history me-2"></i>Clientes Recentes
            </h5>
            <a href="{{ route( 'provider.customers.index' ) }}" class="btn btn-sm btn-outline-primary">
              Ver todos
            </a>
          </div>
          <div class="card-body">
            @if( $recent instanceof \Illuminate\Support\Collection && $recent->isNotEmpty() )
              <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                  <thead class="table-light">
                    <tr>
                      <th>Cliente</th>
                      <th>E-mail</th>
                      <th>Telefone</th>
                      <th>Cadastrado em</th>
                      <th class="text-end">Ações</th>
                    </tr>
                  </thead>
                  <tbody>
                    @foreach( $recent as $customer )
                      @php
                        $common  = $customer->commonData ?? $customer->common_data ?? null;
                        $contact = $customer->contact ?? null;

                        $name = $common?->company_name
                          ?? trim( ( $common->first_name ?? '' ) . ' ' . ( $common->last_name ?? '' ) )
                          ?: 'Cliente';

                        $email = $contact->email_personal
                          ?? $contact->email_business
                          ?? null;

                        $phone = $contact->phone_personal
                          ?? $contact->phone_business
                          ?? null;
                      @endphp
                      <tr>
                        <td>{{ $name }}</td>
                        <td>{{ $email ?? '—' }}</td>
                        <td>{{ $phone ?? '—' }}</td>
                        <td>{{ optional( $customer->created_at )->format( 'd/m/Y' ) }}</td>
                        <td class="text-end">
                          <a href="{{ route( 'provider.customers.show', $customer ) }}"
                            class="btn btn-sm btn-outline-secondary">
                            <i class="bi bi-eye"></i>
                          </a>
                        </td>
                      </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>
            @else
              <p class="text-muted mb-0">
                Nenhum cliente recente encontrado. Cadastre novos clientes para visualizar aqui.
              </p>
            @endif
          </div>
        </div>
      </div>

      <!-- Indicadores Laterais -->
      <div class="col-lg-4">
        <div class="card border-0 shadow-sm mb-3">
          <div class="card-header bg-transparent border-0">
            <h6 class="mb-0">
              <i class="bi bi-lightbulb me-2"></i>Insights Rápidos
            </h6>
          </div>
          <div class="card-body">
            <ul class="list-unstyled mb-0 small text-muted">
              <li class="mb-2">
                <i class="bi bi-check-circle-fill text-success me-2"></i>
                Mantenha seus clientes ativos com informações completas e atualizadas.
              </li>
              <li class="mb-2">
                <i class="bi bi-funnel-fill text-primary me-2"></i>
                Use filtros na listagem de clientes para segmentar sua base.
              </li>
              <li class="mb-2">
                <i class="bi bi-bar-chart-line-fill text-info me-2"></i>
                Acompanhe a evolução do cadastro de clientes para entender seu crescimento.
              </li>
            </ul>
          </div>
        </div>

        <div class="card border-0 shadow-sm">
          <div class="card-header bg-transparent border-0">
            <h6 class="mb-0">
              <i class="bi bi-link-45deg me-2"></i>Atalhos
            </h6>
          </div>
          <div class="card-body d-grid gap-2">
            <a href="{{ route( 'provider.customers.create' ) }}" class="btn btn-sm btn-success">
              <i class="bi bi-person-plus me-2"></i>Novo Cliente
            </a>
            <a href="{{ route( 'provider.customers.index' ) }}" class="btn btn-sm btn-outline-primary">
              <i class="bi bi-people me-2"></i>Listar Clientes
            </a>
            <a href="{{ url( '/provider/reports/customers' ) }}" class="btn btn-sm btn-outline-secondary">
              <i class="bi bi-file-earmark-text me-2"></i>Relatório de Clientes
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection

@push( 'styles' )
  <style>
    .avatar-circle {
      width: 46px;
      height: 46px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
    }
  </style>
@endpush
