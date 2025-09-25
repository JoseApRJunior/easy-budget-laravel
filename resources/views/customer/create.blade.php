@extends( 'layouts.app' )

@section( 'content' )
<div class="container-fluid py-1">
  <!-- Cabeçalho -->
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">
      <i class="bi bi-person-plus me-2"></i>Criar Novo Cliente
    </h1>
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="/provider">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="/provider/customers">Clientes</a></li>
        <li class="breadcrumb-item active">Novo</li>
      </ol>
    </nav>
  </div>

  <form id="createForm" action="/provider/customers/create" method="POST" enctype="multipart/form-data">
    @csrf

    <div class="row g-4">
      <!-- Dados Pessoais -->
      <div class="col-12 col-lg-4">
        <div class="card border-0 shadow-sm">
          <div class="card-header bg-transparent border-0">
            <h5 class="card-title mb-0">
              <i class="bi bi-person me-2"></i>Dados Pessoais
            </h5>
          </div>
          <div class="card-body">
            <div class="row g-3">
              <!-- Campos pessoais aqui -->
              @include( 'partials.customer.personal_fields' )
            </div>
          </div>
        </div>
      </div>

      <!-- Dados Profissionais -->
      <div class="col-12 col-lg-4">
        <div class="card border-0 shadow-sm">
          <div class="card-header bg-transparent border-0">
            <h5 class="card-title mb-0">
              <i class="bi bi-briefcase me-2"></i>Dados Profissionais
            </h5>
          </div>
          <div class="card-body">
            <div class="row g-3">
              <!-- Campos profissionais aqui -->
              @include( 'partials.customer.professional_fields' )
            </div>
          </div>
        </div>
      </div>

      <!-- Endereço -->
      <div class="col-12 col-lg-4">
        <div class="card border-0 shadow-sm">
          <div class="card-header bg-transparent border-0">
            <h5 class="card-title mb-0">
              <i class="bi bi-geo-alt me-2"></i>Endereço
            </h5>
          </div>
          <div class="card-body">
            <div class="row g-3">
              <!-- Campos de endereço aqui -->
              @include( 'partials.customer.address_fields' )
            </div>
          </div>
        </div>
      </div>

      <!--  Descrição -->
      <div class="col-12 col-lg-12">
        <div class="card border-0 shadow-sm ">
          <div class="card-header bg-transparent border-0">
            <h5 class="card-title mb-0 ">
              <i class="bi bi-info-circle-fill me-2 "></i>
              <span>Informações Adicionais</span>
            </h5>
          </div>
          <div class="card-body p-4">
            <div class="row align-items-start g-4">
              <!-- Coluna da Descrição -->
              <div class="col-md-12">
                <div class="form-group">
                  <label for="description" class="form-label fw-bold mb-3">
                    Descrição Profissional
                  </label>
                  <textarea class="form-control form-control-lg shadow-sm rounded-3" id="description" name="description"
                    rows="4" maxlength="250" placeholder="Descreva sua experiência profissional..."
                    style="resize: none;"></textarea>
                  <div class="d-flex justify-content-end mt-2">
                    <small class="text-muted">
                      <span id="char-count-value" class="fw-semibold">250</span> caracteres
                      restantes
                    </small>
                  </div>
                  @error( 'description' )
                  <div class="text-danger mt-1">{{ $message }}</div>
                  @enderror
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Botões de Ação -->
    <div class="d-flex justify-content-between align-items-center mt-4">
      <a href="/provider/customers" class="btn btn-outline-secondary">
        <i class="bi bi-x-circle me-2"></i>Cancelar
      </a>
      <button type="submit" class="btn btn-primary" id="createButton">
        <i class="bi bi-check-circle me-2"></i>Criar Cliente
      </button>
    </div>
  </form>
</div>
@endsection

@section( 'scripts' )
@parent
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
@vite(['resources/js/modules/masks.js'])
<script src="/assets/js/customer_create.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
  const textarea = document.getElementById('description');
  const charCount = document.getElementById('char-count-value');

  textarea.addEventListener('input', () => {
    const charsLeft = textarea.maxLength - textarea.value.length;
    charCount.textContent = charsLeft;
  });
});
</script>
@endsection