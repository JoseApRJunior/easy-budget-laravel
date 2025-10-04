@extends( 'layout' )


@section( 'content' )
    <div class="container-fluid py-1">
        <!-- Cabeçalho -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="bi bi-person-gear me-2"></i>Atualizar Cliente
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ url( '/provider' ) }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ url( '/provider/customers' ) }}">Clientes</a></li>
                    <li class="breadcrumb-item active">Atualizar</li>
                </ol>
            </nav>
        </div>

        <form id="updateForm" action="{{ url( '/provider/customers/update/' . $customer->id ) }}" method="POST"
            enctype="multipart/form-data">
            @csrf
            @method( 'PUT' )

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
                                        <textarea
                                            class="form-control form-control-lg shadow-sm rounded-3 @error( 'description' ) is-invalid @enderror"
                                            id="description" name="description" rows="4" maxlength="250"
                                            placeholder="Descreva sua experiência profissional..."
                                            style="resize: none;">{{ old( 'description', $customer->description ) }}</textarea>
                                        <div class="d-flex justify-content-end mt-2">
                                            <small class="text-muted">
                                                <span id="char-count-value" class="fw-semibold">250</span> caracteres
                                                restantes
                                            </small>
                                        </div>
                                        @error( 'description' )
                                            <div class="invalid-feedback">{{ $message }}</div>
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
                <div class="d-flex gap-2">
                    <a href="{{ url( '/provider/customers' ) }}" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle me-2"></i>Cancelar
                    </a>
                </div>
                <small class="text-muted">
                    Última atualização: {{ \Carbon\Carbon::parse( $customer->updated_at )->format( 'd/m/Y H:i' ) }}
                </small>
                <button type="submit" class="btn btn-primary" id="updateButton">
                    <i class="bi bi-check-circle me-2"></i>Atualizar Cadastro
                </button>
            </div>
        </form>
    </div>
@endsection

@section( 'scripts' )
    @parent
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.mask/1.14.16/jquery.mask.min.js"></script>
    <script src="{{ asset( 'assets/js/modules/masks/masks.js' ) }}" type="module"></script>
    <script src="{{ asset( 'assets/js/provider_update.js' ) }}" type="module"></script>
    <script>
        document.addEventListener( 'DOMContentLoaded', function () {
            const textarea = document.getElementById( 'description' );
            const charCount = document.getElementById( 'char-count-value' );

            function updateCharCount() {
                const charsLeft = textarea.maxLength - textarea.value.length;
                charCount.textContent = charsLeft;
            }

            textarea.addEventListener( 'input', updateCharCount );

            // Initial count
            updateCharCount();
        } );
    </script>
@endsection
