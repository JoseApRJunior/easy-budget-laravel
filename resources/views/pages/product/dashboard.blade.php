@extends( 'layouts.app' )

@section( 'title', 'Dashboard de Produtos' )

@section( 'content' )
  <div class="container-fluid py-4">
    <!-- Cabeçalho -->
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div>
        <h1 class="h3 mb-0">
          <i class="bi bi-box-seam-fill me-2"></i>Dashboard de Produtos
        </h1>
        <p class="text-muted mb-0">
          Visão geral do seu catálogo de produtos com atalhos rápidos para gestão.
        </p>
      </div>
      <nav aria-label="breadcrumb">
        <ol class="breadcrumb mb-0">
          <li class="breadcrumb-item">
            <a href="{{ route( 'provider.dashboard' ) }}">Dashboard</a>
          </li>
          <li class="breadcrumb-item active" aria-current="page">
            <a href="{{ route( 'provider.products.dashboard' ) }}">Produtos</a>
          </li>
        </ol>
      </nav>
    </div>

    @php
      $total    = $stats[ 'total_products' ] ?? 0;
      $active   = $stats[ 'active_products' ] ?? 0;
      $inactive = $stats[ 'inactive_products' ] ?? max( 0, $total - $active );
      $recent   = $stats[ 'recent_products' ] ?? collect();

      $activityRate = $total > 0
        ? number_format( ( $active / $total ) * 100, 1, ',', '.' )
        : 0;
    @endphp

    <!-- Cards de Métricas -->
    <div class="row g-4 mb-4">
      <div class="col-md-3">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-body d-flex flex-column justify-content-between">
            <div class="d-flex align-items-center mb-3">
              <div class="avatar-circle bg-primary bg-gradient me-3">
                <i class="bi bi-box-seam text-white"></i>
              </div>
              <div>
                <h6 class="text-muted mb-1">Total de Produtos</h6>
                <h3 class="mb-0">{{ $total }}</h3>
              </div>
            </div>
            <p class="text-muted small mb-0">
              Quantidade total de produtos cadastrados para este tenant.
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
                <h6 class="text-muted mb-1">Produtos Ativos</h6>
                <h3 class="mb-0">{{ $active }}</h3>
              </div>
            </div>
            <p class="text-muted small mb-0">
              Produtos disponíveis para uso em orçamentos, serviços e vendas.
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
                <h6 class="text-muted mb-1">Produtos Inativos</h6>
                <h3 class="mb-0">{{ $inactive }}</h3>
              </div>
            </div>
            <p class="text-muted small mb-0">
              Itens desativados ou em revisão, não disponíveis para novos lançamentos.
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
                <h3 class="mb-0">{{ $activityRate }}%</h3>
              </div>
            </div>
            <p class="text-muted small mb-0">
              Percentual de produtos ativos em relação ao total cadastrado.
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- Conteúdo Principal -->
    <div class="row g-4">
      <!-- Produtos Recentes -->
      <div class="col-lg-8">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
              <i class="bi bi-clock-history me-2"></i>Produtos Recentes
            </h5>
            <a href="{{ route( 'provider.products.index' ) }}" class="btn btn-sm btn-outline-primary">
              Ver todos
            </a>
          </div>
          <div class="card-body">
            @if( $recent instanceof \Illuminate\Support\Collection && $recent->isNotEmpty() )
              <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                  <thead class="table-light">
                    <tr>
                      <th>Produto</th>
                      <th>SKU</th>
                      <th>Categoria</th>
                      <th>Status</th>
                      <th>Cadastrado em</th>
                      <th class="text-end">Ações</th>
                    </tr>
                  </thead>
                  <tbody>
                    @foreach( $recent as $product )
                      <tr>
                        <td>{{ $product->name }}</td>
                        <td><span class="text-code">{{ $product->sku }}</span></td>
                        <td>{{ $product->category->name ?? '—' }}</td>
                        <td>
                          @if( $product->active )
                            <span class="badge bg-success-subtle text-success">Ativo</span>
                          @else
                            <span class="badge bg-danger-subtle text-danger">Inativo</span>
                          @endif
                        </td>
                        <td>{{ optional( $product->created_at )->format( 'd/m/Y' ) }}</td>
                        <td class="text-end">
                          <a href="{{ route( 'provider.products.show', $product->sku ) }}" class="btn btn-sm btn-outline-secondary">
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
                Nenhum produto recente encontrado. Cadastre novos produtos para visualizar aqui.
              </p>
            @endif
          </div>
        </div>
      </div>

      <!-- Insights e Atalhos -->
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
                <i class="bi bi-box-arrow-in-up-right text-primary me-2"></i>
                Mantenha os produtos mais usados sempre ativos para agilizar orçamentos.
              </li>
              <li class="mb-2">
                <i class="bi bi-tag-fill text-success me-2"></i>
                Use categorias e unidades para padronizar seu catálogo.
              </li>
              <li class="mb-2">
                <i class="bi bi-exclamation-triangle-fill text-warning me-2"></i>
                Revise produtos inativos que ainda são utilizados em serviços.
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
            <a href="{{ route( 'provider.products.create' ) }}" class="btn btn-sm btn-success">
              <i class="bi bi-plus-circle me-2"></i>Novo Produto
            </a>
            <a href="{{ route( 'provider.products.index' ) }}" class="btn btn-sm btn-outline-primary">
              <i class="bi bi-box-seam me-2"></i>Listar Produtos
            </a>
            <a href="{{ route( 'provider.reports.products' ) }}" class="btn btn-sm btn-outline-secondary">
              <i class="bi bi-file-earmark-text me-2"></i>Relatório de Produtos
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
