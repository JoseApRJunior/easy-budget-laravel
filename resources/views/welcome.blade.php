@extends( 'layouts.app' )

@section( 'title', 'Bem-vindo ao Easy Budget Laravel' )

@section( 'content' )
<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-md-8 text-center">
      <h1 class="display-4 mb-4">
        <i class="bi bi-check-circle-fill text-success me-2"></i>
        Migração Concluída!
      </h1>
      <p class="lead mb-4">
        As views do sistema antigo foram migradas com sucesso para templates Blade do Laravel.
      </p>

      <div class="card border-0 shadow-sm">
        <div class="card-body">
          <h5 class="card-title">Views Migradas:</h5>
          <div class="row mt-3">
            <div class="col-md-6">
              <ul class="list-unstyled text-start">
                <li><i class="bi bi-check-circle text-success me-2"></i>Layout base (app.blade.php)</li>
                <li><i class="bi bi-check-circle text-success me-2"></i>Header com navegação</li>
                <li><i class="bi bi-check-circle text-success me-2"></i>Footer com informações</li>
                <li><i class="bi bi-check-circle text-success me-2"></i>Menu de navegação</li>
              </ul>
            </div>
            <div class="col-md-6">
              <ul class="list-unstyled text-start">
                <li><i class="bi bi-check-circle text-success me-2"></i>Menu do usuário</li>
                <li><i class="bi bi-check-circle text-success me-2"></i>Página inicial com planos</li>
                <li><i class="bi bi-check-circle text-success me-2"></i>Dashboard administrativo</li>
                <li><i class="bi bi-check-circle text-success me-2"></i>Componente de alertas</li>
              </ul>
            </div>
          </div>
        </div>
      </div>

      <div class="mt-4">
        <a href="{{ url( '/' ) }}" class="btn btn-primary btn-lg me-2">
          <i class="bi bi-house me-2"></i>Ir para Home
        </a>
        <a href="{{ url( '/admin/dashboard' ) }}" class="btn btn-outline-primary btn-lg">
          <i class="bi bi-speedometer2 me-2"></i>Ver Dashboard
        </a>
      </div>
    </div>
  </div>
</div>
@endsection