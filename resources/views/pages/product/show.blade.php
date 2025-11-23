@extends( 'layouts.app' )

@section( 'title', 'Detalhes do Produto: ' . $product->name )

@section( 'content' )
  <div class="container-fluid">
    <div class="row">
      <div class="col-12">
        <div class="card">
          <div class="card-header">
            <h3 class="card-title">
              <i class="fas fa-box mr-2"></i>
              {{ $product->name }}
            </h3>
            <div class="card-tools">
              <a href="{{ route( 'provider.products.edit', $product->sku ) }}" class="btn btn-warning btn-sm">
                <i class="fas fa-edit"></i> Editar
              </a>
              <a href="{{ route( 'provider.products.index' ) }}" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> Voltar
              </a>
            </div>
          </div>

          <div class="card-body">
            <div class="row">
              <!-- Imagem do Produto -->
              <div class="col-md-4">
                <div class="form-group">
                  <label>Imagem</label>
                  <div class="text-center">
                    @if( $product->image )
                      <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="img-fluid rounded shadow"
                        style="max-width: 300px; max-height: 300px; object-fit: cover;">
                    @else
                      <div class="bg-light d-flex align-items-center justify-content-center rounded"
                        style="width: 300px; height: 300px; margin: 0 auto;">
                        <i class="fas fa-image text-muted fa-4x"></i>
                      </div>
                    @endif
                  </div>
                </div>
              </div>

              <!-- Detalhes do Produto -->
              <div class="col-md-8">
                <div class="row">
                  <!-- SKU -->
                  <div class="col-md-6">
                    <div class="info-box bg-light">
                      <div class="info-box-content">
                        <span class="info-box-text">SKU</span>
                        <span class="info-box-number">
                          <span class="text-code">{{ $product->sku }}</span>
                        </span>
                      </div>
                    </div>
                  </div>

                  <!-- Preço -->
                  <div class="col-md-6">
                    <div class="info-box bg-success">
                      <div class="info-box-content">
                        <span class="info-box-text text-white">Preço</span>
                        <span class="info-box-number text-white">
                          R$ {{ number_format( $product->price, 2, ',', '.' ) }}
                        </span>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="row">
                  <!-- Categoria -->
                  <div class="col-md-6">
                    <div class="form-group">
                      <label class="font-weight-bold">Categoria</label>
                      <p class="form-control-plaintext">
                        @if( $product->category )
                          <span class="badge badge-primary">{{ $product->category->name }}</span>
                        @else
                          <span class="text-muted">Nenhuma categoria</span>
                        @endif
                      </p>
                    </div>
                  </div>

                  <!-- Unidade -->
                  <div class="col-md-6">
                    <div class="form-group">
                      <label class="font-weight-bold">Unidade</label>
                      <p class="form-control-plaintext">
                        {{ $product->unit ?? 'Não especificada' }}
                      </p>
                    </div>
                  </div>
                </div>

                <!-- Status -->
                <div class="row">
                  <div class="col-md-6">
                    <div class="form-group">
                      <label class="font-weight-bold">Status</label>
                      <p class="form-control-plaintext">
                        @if( $product->active )
                          <span class="badge badge-success badge-lg">
                            <i class="fas fa-check-circle"></i> Ativo
                          </span>
                        @else
                          <span class="badge badge-danger badge-lg">
                            <i class="fas fa-times-circle"></i> Inativo
                          </span>
                        @endif
                      </p>
                    </div>
                  </div>

                  <!-- Data de Criação -->
                  <div class="col-md-6">
                    <div class="form-group">
                      <label class="font-weight-bold">Data de Criação</label>
                      <p class="form-control-plaintext">
                        <i class="fas fa-calendar-alt text-muted"></i>
                        {{ $product->created_at->format( 'd/m/Y H:i' ) }}
                      </p>
                    </div>
                  </div>
                </div>

                <!-- Data de Atualização -->
                <div class="row">
                  <div class="col-md-6">
                    <div class="form-group">
                      <label class="font-weight-bold">Última Atualização</label>
                      <p class="form-control-plaintext">
                        <i class="fas fa-clock text-muted"></i>
                        {{ $product->updated_at->format( 'd/m/Y H:i' ) }}
                      </p>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Informações Adicionais -->
            <div class="row mt-4">
              <div class="col-12">
                <div class="card card-outline card-primary">
                  <div class="card-header">
                    <h5 class="card-title">
                      <i class="fas fa-info-circle"></i> Informações do Sistema
                    </h5>
                  </div>
                  <div class="card-body">
                    <div class="row">
                      <div class="col-md-4">
                        <strong>ID:</strong> {{ $product->id }}
                      </div>
                      <div class="col-md-4">
                        <strong>Tenant ID:</strong> {{ $product->tenant_id }}
                      </div>
                      <div class="col-md-4">
                        <strong>Categoria ID:</strong> {{ $product->category_id ?? 'N/A' }}
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="card-footer">
            <div class="btn-group">
              <a href="{{ route( 'provider.products.edit', $product->sku ) }}" class="btn btn-warning">
                <i class="fas fa-edit"></i> Editar Produto
              </a>
              <form action="{{ route( 'provider.products.toggle-status', $product->sku ) }}" method="POST" class="d-inline"
                onsubmit="return confirm('{{ $product->active ? 'Desativar' : 'Ativar' }} este produto?')">
                @csrf
                @method( 'PATCH' )
                <button type="submit" class="btn {{ $product->active ? 'btn-secondary' : 'btn-success' }}">
                  <i class="fas fa-{{ $product->active ? 'ban' : 'check' }}"></i>
                  {{ $product->active ? 'Desativar' : 'Ativar' }}
                </button>
              </form>
              <form action="{{ route( 'provider.products.destroy', $product->sku ) }}" method="POST" class="d-inline"
                onsubmit="return confirm('Excluir este produto permanentemente?')">
                @csrf
                @method( 'DELETE' )
                <button type="submit" class="btn btn-danger">
                  <i class="fas fa-trash"></i> Excluir
                </button>
              </form>
            </div>
            <div class="float-right">
              <a href="{{ route( 'provider.products.index' ) }}" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Voltar à Lista
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection

@section( 'styles' )
  <style>
    .info-box {
      box-shadow: 0 0 1px rgba(0, 0, 0, .125), 0 1px 3px rgba(0, 0, 0, .2);
      border-radius: .25rem;
      margin-bottom: 1rem;
      background-color: #fff;
      display: flex;
      align-items: center;
      width: 100%;
    }

    .info-box .info-box-content {
      padding: 5px 10px;
      margin-left: 0;
      display: flex;
      flex-direction: column;
      width: 100%;
    }

    .info-box .info-box-text {
      text-transform: uppercase;
      font-weight: 700;
      font-size: .6875rem;
      color: #6c757d;
    }

    .info-box .info-box-number {
      font-size: 1.125rem;
      font-weight: 700;
      color: #495057;
    }

    .info-box.bg-success .info-box-text,
    .info-box.bg-success .info-box-number {
      color: #fff !important;
    }

    .badge-lg {
      font-size: 0.875rem;
      padding: 0.5rem 0.75rem;
    }
  </style>
@endsection
