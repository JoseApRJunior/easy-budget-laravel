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

        <form id="createForm" action="{{ route( 'provider.customers.store' ) }}" method="POST"
            enctype="multipart/form-data">
            @csrf

            <div class="row g-4">
                <!-- Dados Básicos -->
                <div class="col-12 col-lg-6">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-transparent border-0">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-person me-2"></i>Dados Básicos
                            </h5>
                            <div class="alert alert-info py-2 mb-3">
                                <small class="mb-0">
                                    <i class="bi bi-info-circle-fill me-1"></i>
                                    <strong>Passo 1:</strong> Preencha os dados básicos e selecione o tipo de cliente.
                                </small>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <!-- Seletor de tipo de cliente -->
                                <div class="col-12">
                                    <label for="customer_type" class="form-label">Tipo de Cliente</label>
                                    <select name="customer_type" id="customer_type"
                                        class="form-select @error( 'customer_type' ) is-invalid @enderror" required>
                                        <option value="">Selecione o tipo</option>
                                        <option value="pf" {{ old( 'customer_type' ) == 'pf' ? 'selected' : '' }}>Pessoa
                                            Física
                                        </option>
                                        <option value="pj" {{ old( 'customer_type' ) == 'pj' ? 'selected' : '' }}>Pessoa
                                            Jurídica</option>
                                    </select>
                                    @error( 'customer_type' )
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Campos pessoais básicos -->
                                @include( 'partials.customer.basic_fields' )
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Campos Específicos (dinâmicos) -->
                <div class="col-12 col-lg-6">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-transparent border-0">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-briefcase me-2"></i>Dados Específicos
                            </h5>
                            <div class="alert alert-warning py-2 mb-3" id="type_warning" style="display: none;">
                                <small class="mb-0">
                                    <i class="bi bi-exclamation-triangle-fill me-1"></i>
                                    <strong>Atenção:</strong> Selecione o tipo de cliente para liberar os campos
                                    específicos.
                                </small>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <!-- Campos específicos PF -->
                                <div id="pf_fields" class="pf-specific" style="display: none;">
                                    @include( 'partials.customer.pf_specific_fields' )
                                </div>

                                <!-- Campos específicos PJ -->
                                <div id="pj_fields" class="pj-specific" style="display: none;">
                                    @include( 'partials.customer.pj_specific_fields' )
                                </div>

                                <!-- Campos comuns (endereço) -->
                                <div id="common_fields" class="common-fields" style="display: none;">
                                    @include( 'partials.customer.address_fields' )
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
                <button type="submit" class="btn btn-primary" id="createButton" disabled>
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
        // MÁSCARAS DE CAMPO
        // ========================================

        // Aguardar carregamento do VanillaMask e aplicar máscaras
        document.addEventListener( 'DOMContentLoaded', function () {
            // Verificar se VanillaMask está disponível
            if ( typeof VanillaMask !== 'undefined' ) {
                // Aplicar máscaras apenas se os elementos existirem
                if (document.getElementById('phone_personal')) {
                    const phoneMask = new VanillaMask( 'phone_personal', 'phone' );
                }
                if (document.getElementById('phone_business')) {
                    const phoneBusinessMask = new VanillaMask( 'phone_business', 'phone' );
                }
                if (document.getElementById('cpf')) {
                    const cpfMask = new VanillaMask( 'cpf', 'cpf' );
                }
                if (document.getElementById('cnpj')) {
                    const cnpjMask = new VanillaMask( 'cnpj', 'cnpj' );
                }
                if (document.getElementById('cep')) {
                    const cepMask = new VanillaMask( 'cep', 'cep' );
                }
            } else {
                console.warn( 'VanillaMask não está disponível. Máscaras não serão aplicadas.' );
            }
        } );

        // ========================================
        // CONTROLE DE FORMULÁRIO PROGRESSIVO
        // ========================================

        /**
         * Controla exibição dos campos específicos baseado no tipo de cliente
         */
        function toggleCustomerFields() {
            const customerType = document.getElementById( 'customer_type' ).value;
            const pfFields = document.getElementById( 'pf_fields' );
            const pjFields = document.getElementById( 'pj_fields' );
            const commonFields = document.getElementById( 'common_fields' );
            const typeWarning = document.getElementById( 'type_warning' );
            const createButton = document.getElementById( 'createButton' );

            if ( customerType === 'pf' ) {
                pfFields.style.display = 'block';
                pjFields.style.display = 'none';
                commonFields.style.display = 'block';
                typeWarning.style.display = 'none';
                createButton.disabled = false;
                createButton.textContent = 'Criar Cliente (Pessoa Física)';
            } else if ( customerType === 'pj' ) {
                pfFields.style.display = 'none';
                pjFields.style.display = 'block';
                commonFields.style.display = 'block';
                typeWarning.style.display = 'none';
                createButton.disabled = false;
                createButton.textContent = 'Criar Cliente (Pessoa Jurídica)';
            } else {
                pfFields.style.display = 'none';
                pjFields.style.display = 'none';
                commonFields.style.display = 'none';
                typeWarning.style.display = 'block';
                createButton.disabled = true;
                createButton.textContent = 'Selecione o tipo de cliente';
            }
        }

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

        // Inicializar formulário progressivo
        document.addEventListener( 'DOMContentLoaded', function () {
            // Configurar seletor de tipo de cliente
            const customerTypeSelect = document.getElementById( 'customer_type' );
            if ( customerTypeSelect ) {
                customerTypeSelect.addEventListener( 'change', toggleCustomerFields );

                // Inicializar estado baseado no valor atual (útil para edição)
                toggleCustomerFields();
            }

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
                    if ( birthDateInput ) {
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

                // CEP lookup já está em /public/assets/js/cep-lookup.js
            }, 100 );
        } );
    </script>
@endpush
