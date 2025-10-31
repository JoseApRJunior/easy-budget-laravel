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
                    <li class="breadcrumb-item"><a href="{{ url( '/provider' ) }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ url( '/provider/customers' ) }}">Clientes</a></li>
                    <li class="breadcrumb-item active">Novo</li>
                </ol>
            </nav>
        </div>

        <form id="createForm" action="{{ url( '/provider/customers/create' ) }}" method="POST"
            enctype="multipart/form-data">
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
                                        <textarea class="form-control form-control-lg shadow-sm rounded-3" id="description"
                                            name="description" rows="4" maxlength="250"
                                            placeholder="Descreva sua experiência profissional..."
                                            style="resize: none;"></textarea>
                                        <div class="d-flex justify-content-end mt-2">
                                            <small class="text-muted">
                                                <span id="char-count-value" class="fw-semibold">250</span> caracteres
                                                restantes
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Botões de Ação -->
            <div class="d-flex justify-content-between align-items-center mt-4">
                <a href="{{ url( '/provider/customers' ) }}" class="btn btn-outline-secondary">
                    <i class="bi bi-x-circle me-2"></i>Cancelar
                </a>
                <button type="submit" class="btn btn-primary" id="createButton">
                    <i class="bi bi-check-circle me-2"></i>Criar Cliente
                </button>
            </div>
        </form>
    </div>
@endsection

@push( 'scripts' )
    <!-- Scripts específicos da página -->

    <script>
        // ========================================
        // VALIDAÇÃO EM TEMPO REAL
        // ========================================

        /**
         * Valida campos obrigatórios
         */
        function validateRequiredField( input, fieldName ) {
            const value = input.value.trim();

            if ( !value ) {
                input.classList.add( 'is-invalid' );
                let errorDiv = input.nextElementSibling;
                if ( !errorDiv || !errorDiv.classList.contains( 'invalid-feedback' ) ) {
                    errorDiv = document.createElement( 'div' );
                    errorDiv.className = 'invalid-feedback';
                    input.parentNode.insertBefore( errorDiv, input.nextSibling );
                }
                errorDiv.textContent = `O ${fieldName} é obrigatório.`;
            } else {
                input.classList.remove( 'is-invalid' );
                const errorDiv = input.nextElementSibling;
                if ( errorDiv && errorDiv.classList.contains( 'invalid-feedback' ) ) {
                    errorDiv.textContent = '';
                }
            }
        }

        /**
         * Valida formato da data (DD/MM/YYYY)
         */
        function isValidDateFormat( value ) {
            const dateRegex = /^(\d{2})\/(\d{2})\/(\d{4})$/;
            return dateRegex.test( value );
        }

        /**
         * Valida se a data é válida, anterior a hoje e pessoa tem pelo menos 18 anos
         */
        function isValidBirthDate( value ) {
            if ( !isValidDateFormat( value ) ) return false;

            const parts = value.split( '/' );
            const day = parseInt( parts[0], 10 );
            const month = parseInt( parts[1], 10 ) - 1; // JavaScript months are 0-based
            const year = parseInt( parts[2], 10 );

            const birthDate = new Date( year, month, day );
            const today = new Date();

            // Verificar se a data é válida
            if ( birthDate.getDate() !== day || birthDate.getMonth() !== month || birthDate.getFullYear() !== year ) {
                return false;
            }

            // Verificar se é anterior a hoje
            if ( birthDate >= today ) {
                return false;
            }

            // Verificar se a pessoa tem pelo menos 18 anos
            let age = today.getFullYear() - birthDate.getFullYear();
            const monthDiff = today.getMonth() - birthDate.getMonth();

            // Ajustar idade se ainda não fez aniversário este ano
            if ( monthDiff < 0 || ( monthDiff === 0 && today.getDate() < birthDate.getDate() ) ) age--;

            return age >= 18;
        }

        /**
         * Mostra erro de validação
         */
        function showBirthDateError( input, message ) {
            input.classList.add( 'is-invalid' );
            const errorDiv = document.getElementById( 'birth_date_js_error' );
            if ( errorDiv ) {
                errorDiv.textContent = message;
                errorDiv.style.display = 'block';
            }
        }

        /**
         * Remove erro de validação
         */
        function clearBirthDateError( input ) {
            input.classList.remove( 'is-invalid' );
            const errorDiv = document.getElementById( 'birth_date_js_error' );
            if ( errorDiv ) {
                errorDiv.textContent = '';
                errorDiv.style.display = 'none';
            }
        }

        // ========================================
        // OUTRAS FUNCIONALIDADES
        // ========================================

        // Buscar CEP automático
        document.addEventListener( 'DOMContentLoaded', function () {
            // Formatar valores já carregados
            const cpfInput = document.getElementById( 'cpf' );
            const cnpjInput = document.getElementById( 'cnpj' );

            if ( cpfInput && cpfInput.value ) {
                cpfInput.value = formatCPF( cpfInput.value );
            }
            if ( cnpjInput && cnpjInput.value ) {
                cnpjInput.value = formatCNPJ( cnpjInput.value );
            }

            // Validação no submit
            const form = document.getElementById( 'createForm' );
            if ( form ) {
                form.addEventListener( 'submit', function ( e ) {
                    const birthDateInput = document.getElementById( 'birth_date' );
                    const value = birthDateInput.value.trim();

                    if ( value && !isValidBirthDate( value ) ) {
                        e.preventDefault();
                        if ( !isValidDateFormat( value ) ) {
                            showBirthDateError( birthDateInput, 'Formato inválido. Use DD/MM/YYYY' );
                        } else {
                            const parts = value.split( '/' );
                            const day = parseInt( parts[0], 10 );
                            const month = parseInt( parts[1], 10 ) - 1;
                            const year = parseInt( parts[2], 10 );
                            const birthDate = new Date( year, month, day );
                            const today = new Date();

                            if ( birthDate >= today ) {
                                showBirthDateError( birthDateInput, 'Data não pode ser futura' );
                            } else {
                                showBirthDateError( birthDateInput, 'É necessário ter pelo menos 18 anos' );
                            }
                        }
                        birthDateInput.focus();
                        return false;
                    }
                } );
            }

            // Aguardar um pouco para garantir que todos os elementos estão carregados
            setTimeout( function () {
                // Inicializar validação de campos obrigatórios
                const birthDateInput = document.getElementById( 'birth_date' );
                if ( birthDateInput ) {
                    birthDateInput.addEventListener( 'blur', function () {
                        const value = this.value.trim();
                        if ( value && !isValidBirthDate( value ) ) {
                            if ( !isValidDateFormat( value ) ) {
                                showBirthDateError( this, 'Formato inválido. Use DD/MM/YYYY' );
                            } else {
                                // Verificar se é data futura ou menor de 18 anos
                                const parts = value.split( '/' );
                                const day = parseInt( parts[0], 10 );
                                const month = parseInt( parts[1], 10 ) - 1;
                                const year = parseInt( parts[2], 10 );
                                const birthDate = new Date( year, month, day );
                                const today = new Date();

                                if ( birthDate >= today ) {
                                    showBirthDateError( this, 'Data não pode ser futura' );
                                } else {
                                    showBirthDateError( this, 'É necessário ter pelo menos 18 anos' );
                                }
                            }
                        } else {
                            clearBirthDateError( this );
                        }
                    } );

                    // Limpar erro quando começar a digitar novamente
                    birthDateInput.addEventListener( 'input', function () {
                        if ( this.classList.contains( 'is-invalid' ) ) {
                            clearBirthDateError( this );
                        }
                    } );
                }

                // Validação de campos obrigatórios
                const firstName = document.getElementById( 'first_name' );
                const lastName = document.getElementById( 'last_name' );

                if ( firstName ) {
                    firstName.addEventListener( 'blur', () => validateRequiredField( firstName, 'nome' ) );
                }

                if ( lastName ) {
                    lastName.addEventListener( 'blur', () => validateRequiredField( lastName, 'sobrenome' ) );
                }

                // Contador de caracteres para descrição
                const textarea = document.getElementById( 'description' );
                const charCount = document.getElementById( 'char-count-value' );

                if ( textarea && charCount ) {
                    // Inicializar contador com valor atual
                    const updateCharCount = () => {
                        const charsLeft = textarea.maxLength - textarea.value.length;
                        charCount.textContent = charsLeft;
                    };

                    // Atualizar contador inicial
                    updateCharCount();

                    // Atualizar contador em tempo real
                    textarea.addEventListener( 'input', updateCharCount );
                }
            }, 100 );

            const cepInput = document.getElementById( 'cep' );
            if ( cepInput ) {
                cepInput.addEventListener( 'blur', function () {
                    const cep = this.value.replace( /\D/g, '' );
                    if ( cep.length === 8 ) {
                        const xhr = new XMLHttpRequest();
                        xhr.open( 'GET', `https://viacep.com.br/ws/${cep}/json/`, true );
                        xhr.onload = function () {
                            if ( xhr.status === 200 ) {
                                const data = JSON.parse( xhr.responseText );
                                if ( !data.erro ) {
                                    document.getElementById( 'address' ).value = data.logradouro || '';
                                    document.getElementById( 'neighborhood' ).value = data.bairro || '';
                                    document.getElementById( 'city' ).value = data.localidade || '';
                                    document.getElementById( 'state' ).value = data.uf || '';
                                }
                            }
                        };
                        xhr.send();
                    }
                } );
            }
        } );
    </script>
@endpush
