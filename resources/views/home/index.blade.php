@extends( 'layouts.guest' )

@section( 'title', 'Easy Budget - Gestão de Serviços e Orçamentos' )

@section( 'content' )
    <!-- Alerta de Ambiente de Testes -->
    <div class="alert alert-warning alert-dismissible fade show mb-0" role="alert" style="border-radius: 0;">
        <div class="container">
            <i class="bi bi-exclamation-triangle-fill me-2"></i>
            <strong>Ambiente de Testes!</strong> Os dados podem ser resetados a qualquer momento. Não utilize dados reais.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div>

    <!-- Hero Section -->
    <section id="home" class="hero-section text-center position-relative"
        style="padding-top: 80px; min-height: 60vh; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
        <div class="hero-overlay"
            style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.3);"></div>
        <div class="main-container position-relative" style="max-width: 1200px; margin: 0 auto; padding: 0 20px;">
            <div class="hero-content" style="padding: 60px 0;">
                <h1 class="display-4 fw-bold mb-3" style="font-size: 3.5rem; font-weight: 700;">Bem-vindo ao Easy Budget
                </h1>
                <p class="lead mb-4" style="font-size: 1.5rem; margin-bottom: 2rem;">
                    Transforme a gestão de seus serviços com nossas soluções diversificadas e inovadoras.
                </p>
                <button id="conhecaPlanos" class="btn btn-primary btn-lg pulse-button"
                    style="padding: 15px 30px; font-size: 1.1rem; border-radius: 50px; background: #ffc107; border: none; color: #212529;">
                    <i class="bi bi-arrow-down-circle me-2"></i>Conheça nossos planos
                </button>
            </div>
        </div>
    </section>

    <!-- Seção de Planos -->
    <section id="plans" class="py-5">
        <div class="main-container" style="max-width: 1200px; margin: 0 auto; padding: 0 20px;">
            <div class="section-header text-center mb-5">
                <h2 class="display-6 fw-bold" style="font-size: 2.5rem; margin-bottom: 1rem;">Escolha o Plano Perfeito para
                    Você</h2>
                <p class="small-text" style="color: #6c757d;">Selecione o plano que melhor atende às suas necessidades</p>
            </div>

            <div class="row row-cols-1 row-cols-md-3 g-4 mb-5">
                @php
                    $plansData  = $plans instanceof \App\Support\ServiceResult ? $plans->getData() : $plans;
                    $plansCount = 0;
                    if ( is_array( $plansData ) ) {
                        $plansCount = count( $plansData );
                    } elseif ( $plansData instanceof \Illuminate\Support\Collection ) {
                        $plansCount = $plansData->count();
                    } elseif ( is_countable( $plansData ) ) {
                        $plansCount = count( $plansData );
                    }
                @endphp
                @if( $plansCount > 0 )
                    @foreach( $plansData as $plan )
                        @php
                            $planSlug        = is_array( $plan ) ? ( $plan[ 'slug' ] ?? '' ) : ( $plan->slug ?? '' );
                            $planName        = is_array( $plan ) ? ( $plan[ 'name' ] ?? '' ) : ( $plan->name ?? '' );
                            $planPrice       = is_array( $plan ) ? ( $plan[ 'price' ] ?? 0 ) : ( $plan->price ?? 0 );
                            $planDescription = is_array( $plan ) ? ( $plan[ 'description' ] ?? '' ) : ( $plan->description ?? '' );
                            $planFeatures    = is_array( $plan ) ? ( $plan[ 'features' ] ?? [] ) : ( $plan->features ?? [] );
                        @endphp
                        <div class="col">
                            <div class="card h-100 shadow-sm hover-card"
                                style="border-radius: 15px; transition: transform 0.3s ease;">
                                <div class="card-body d-flex flex-column text-center p-4">
                                    <div class="text-center mb-4">
                                        @if( $planSlug == 'basic' )
                                            <i class="bi bi-rocket display-6 text-primary mb-2"></i>
                                        @elseif( $planSlug == 'pro' )
                                            <i class="bi bi-star display-6 text-success mb-2"></i>
                                        @else
                                            <i class="bi bi-gem display-6 text-info mb-2"></i>
                                        @endif
                                        <h3 class="card-title h4">{{ $planName }}</h3>
                                        <div class="pricing-header">
                                            <span class="currency">R$</span>
                                            <span class="price">{{ number_format( $planPrice, 0, '', '.' ) }}</span>
                                            <span class="period">/mês</span>
                                        </div>
                                    </div>

                                    <p class="card-text small-text mb-4">{{ $planDescription }}</p>

                                    @if( $planFeatures && count( $planFeatures ) > 0 )
                                        <ul class="feature-list list-unstyled mb-4 text-start">
                                            @foreach( $planFeatures as $feature )
                                                <li class="mb-2">
                                                    <i class="bi bi-check-circle-fill text-success me-2"></i>
                                                    {{ is_array( $feature ) ? implode( ', ', $feature ) : $feature }}
                                                </li>
                                            @endforeach
                                        </ul>
                                    @endif

                                    <button type="button" class="btn btn-outline-primary btn-lg mt-auto w-100 select-plan"
                                        data-plan="{{ $planName }}" data-target="#preCadastroForm" style="border-radius: 25px;">
                                        <i class="bi bi-arrow-right-circle me-2"></i>Selecionar
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                @else
                    <!-- Plano Gratuito -->
                    <div class="col">
                        <div class="card h-100 shadow-sm hover-card"
                            style="border-radius: 15px; transition: transform 0.3s ease;">
                            <div class="card-body d-flex flex-column text-center p-4">
                                <div class="text-center mb-4">
                                    <i class="bi bi-rocket display-6 text-primary mb-2"></i>
                                    <h3 class="card-title h4">Plano Gratuito</h3>
                                    <div class="pricing-header">
                                        <span class="currency">R$</span>
                                        <span class="price">0</span>
                                        <span class="period">/mês</span>
                                    </div>
                                </div>
                                <p class="card-text small-text mb-4">Comece gratuitamente com recursos básicos</p>
                                <ul class="feature-list list-unstyled mb-4 text-start">
                                    <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Até 10
                                        orçamentos/mês</li>
                                    <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>1 usuário</li>
                                    <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Suporte básico
                                    </li>
                                    <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Relatórios simples
                                    </li>
                                </ul>
                                <button type="button" class="btn btn-outline-primary btn-lg mt-auto w-100 select-plan"
                                    data-plan="Plano Gratuito" data-target="#preCadastroForm" style="border-radius: 25px;">
                                    <i class="bi bi-arrow-right-circle me-2"></i>Selecionar
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Plano Básico -->
                    <div class="col">
                        <div class="card h-100 shadow-sm hover-card"
                            style="border-radius: 15px; transition: transform 0.3s ease; border: 2px solid #ffc107;">
                            <div class="card-body d-flex flex-column text-center p-4">
                                <div class="text-center mb-4">
                                    <span class="badge bg-warning text-dark mb-2">MAIS POPULAR</span>
                                    <i class="bi bi-star display-6 text-success mb-2"></i>
                                    <h3 class="card-title h4">Plano Básico</h3>
                                    <div class="pricing-header">
                                        <span class="currency">R$</span>
                                        <span class="price">49</span>
                                        <span class="period">/mês</span>
                                    </div>
                                </div>
                                <p class="card-text small-text mb-4">Ideal para pequenos negócios</p>
                                <ul class="feature-list list-unstyled mb-4 text-start">
                                    <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Até 100
                                        orçamentos/mês</li>
                                    <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Até 5 usuários
                                    </li>
                                    <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Suporte
                                        prioritário</li>
                                    <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Relatórios
                                        avançados</li>
                                    <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>API básica</li>
                                </ul>
                                <button type="button" class="btn btn-warning btn-lg mt-auto w-100 select-plan"
                                    data-plan="Plano Básico" data-target="#preCadastroForm" style="border-radius: 25px;">
                                    <i class="bi bi-arrow-right-circle me-2"></i>Selecionar
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Plano Profissional -->
                    <div class="col">
                        <div class="card h-100 shadow-sm hover-card"
                            style="border-radius: 15px; transition: transform 0.3s ease;">
                            <div class="card-body d-flex flex-column text-center p-4">
                                <div class="text-center mb-4">
                                    <i class="bi bi-gem display-6 text-info mb-2"></i>
                                    <h3 class="card-title h4">Plano Profissional</h3>
                                    <div class="pricing-header">
                                        <span class="currency">R$</span>
                                        <span class="price">149</span>
                                        <span class="period">/mês</span>
                                    </div>
                                </div>
                                <p class="card-text small-text mb-4">Para empresas em crescimento</p>
                                <ul class="feature-list list-unstyled mb-4 text-start">
                                    <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Orçamentos
                                        ilimitados</li>
                                    <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Usuários
                                        ilimitados</li>
                                    <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Suporte 24/7</li>
                                    <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Todos os recursos
                                    </li>
                                    <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>Integrações
                                        avançadas</li>
                                </ul>
                                <button type="button" class="btn btn-outline-primary btn-lg mt-auto w-100 select-plan"
                                    data-plan="Plano Profissional" data-target="#preCadastroForm" style="border-radius: 25px;">
                                    <i class="bi bi-arrow-right-circle me-2"></i>Selecionar
                                </button>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Formulário de Pré-Cadastro -->
            <div id="preCadastroForm" class="card shadow-lg border-0 rounded-3" style="display: none;">
                <div class="card-header py-3 bg-primary text-white">
                    <h5 class="card-title text-center mb-0">
                        <i class="bi bi-person-plus me-2"></i>Pré-Cadastro
                    </h5>
                </div>
                <div class="card-body p-4">
                    <form action="{{ route( 'register' ) }}" method="POST" id="preRegisterForm" class="needs-validation"
                        novalidate>
                        @csrf
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label for="plan" class="form-label">Plano Selecionado</label>
                                <select class="form-select" id="planSelect" name="plan" required>
                                    <option value="free" selected>Plano Gratuito - R$ 0,00</option>
                                    <option value="basic" disabled>Plano Básico - R$ 49,00 - em desenvolvimento</option>
                                    <option value="professional" disabled>Plano Profissional - R$ 149,00 - em
                                        desenvolvimento</option>
                                </select>
                                @error( 'plan' )
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-3">
                                <label for="first_name" class="form-label">Nome</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" required />
                                @error( 'first_name' )
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-3">
                                <label for="last_name" class="form-label">Sobrenome</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" required />
                                @error( 'last_name' )
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required />
                                @error( 'email' )
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-3">
                                <label for="phone" class="form-label">Telefone</label>
                                <input type="tel" class="form-control" id="phone" name="phone" required />
                                @error( 'phone' )
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-3">
                                <label for="password" class="form-label">Senha</label>
                                <div class="password-container position-relative">
                                    <input type="password" class="form-control" id="password" name="password" required />
                                    <button type="button"
                                        class="password-toggle position-absolute top-50 end-0 translate-middle-y me-3"
                                        data-input="password">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                                @error( 'password' )
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-3">
                                <label for="confirm_password" class="form-label">Confirmar Senha</label>
                                <div class="password-container position-relative">
                                    <input type="password" class="form-control" id="confirm_password"
                                        name="confirm_password" required />
                                    <button type="button"
                                        class="password-toggle position-absolute top-50 end-0 translate-middle-y me-3"
                                        data-input="confirm_password">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                </div>
                                @error( 'confirm_password' )
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-3">
                                <label class="form-label">&nbsp;</label>
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bi bi-send me-2"></i>Enviar Pré-Cadastro
                                </button>
                            </div>

                            <div class="mt-4 text-center">
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="terms_accepted"
                                        name="terms_accepted" required />
                                    <label class="form-check-label" for="terms_accepted">
                                        Eu li e aceito os <a href="/terms-of-service" target="_blank">Termos de Serviço</a>
                                        e a <a href="/privacy-policy" target="_blank">Política de Privacidade</a>.
                                    </label>
                                </div>
                                @error( 'terms_accepted' )
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                                <small class="small-text d-block mb-3 text-muted">
                                    Ao se cadastrar, você concorda em receber atualizações sobre nossos serviços por e-mail.
                                    Você pode cancelar a qualquer momento.
                                </small>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
@endsection

@push( 'styles' )
    <style>
        /* Hero section com gradiente */
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            position: relative;
            overflow: hidden;
        }

        .hero-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.3);
        }

        /* Cards com hover effect */
        .hover-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .hover-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15) !important;
        }

        /* Botão pulsante */
        @keyframes pulse {
            0% {
                transform: scale(1);
            }

            50% {
                transform: scale(1.05);
            }

            100% {
                transform: scale(1);
            }
        }

        .pulse-button {
            animation: pulse 2s infinite;
        }

        /* Container principal */
        .main-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        /* Pricing header */
        .pricing-header {
            margin-bottom: 1rem;
        }

        .currency {
            font-size: 1.5rem;
            vertical-align: top;
            margin-right: 0.25rem;
        }

        .price {
            font-size: 3rem;
            font-weight: 700;
        }

        .period {
            font-size: 1rem;
            color: #6c757d;
        }

        /* Password container */
        .password-container {
            position: relative;
        }

        .password-toggle {
            background: none;
            border: none;
            color: #6c757d;
            cursor: pointer;
        }

        .password-toggle:hover {
            color: #495057;
        }

        /* Formulário responsivo */
        @media (max-width: 768px) {
            .hero-content h1 {
                font-size: 2.5rem;
            }

            .hero-content .lead {
                font-size: 1.25rem;
            }

            .pricing-header .price {
                font-size: 2.5rem;
            }
        }

        /* Animações */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fade-in-up {
            animation: fadeInUp 0.6s ease-out;
        }

        /* Melhorar contraste e acessibilidade */
        .card {
            border: 1px solid rgba(0, 0, 0, .125);
        }

        .btn-primary {
            background-color: #ffc107;
            border-color: #ffc107;
            color: #212529;
        }

        .btn-primary:hover {
            background-color: #e0a800;
            border-color: #d39e00;
            color: #212529;
        }

        .btn-outline-primary {
            color: #ffc107;
            border-color: #ffc107;
        }

        .btn-outline-primary:hover {
            background-color: #ffc107;
            border-color: #ffc107;
            color: #212529;
        }

        /* Estados de foco para acessibilidade */
        .form-control:focus,
        .form-select:focus,
        .btn:focus {
            box-shadow: 0 0 0 0.2rem rgba(255, 193, 7, 0.25);
        }

        /* Badge personalizado */
        .badge {
            font-size: 0.75rem;
            padding: 0.5rem 1rem;
        }
    </style>
@endpush

@push( 'scripts' )
    <script>
        // Seleção de plano
        document.querySelectorAll( '.select-plan' ).forEach( button => {
            button.addEventListener( 'click', function () {
                const planName = this.getAttribute( 'data-plan' );
                const targetForm = this.getAttribute( 'data-target' );

                // Atualizar seleção do plano
                document.querySelectorAll( '.select-plan' ).forEach( btn => {
                    btn.classList.remove( 'active' );
                } );
                this.classList.add( 'active' );

                // Selecionar opção no select
                const planSelect = document.getElementById( 'planSelect' );
                if ( planSelect ) {
                    const option = Array.from( planSelect.options ).find( opt =>
                        opt.text.includes( planName )
                    );
                    if ( option ) {
                        option.selected = true;
                    }
                }

                // Mostrar formulário
                const form = document.querySelector( targetForm );
                if ( form ) {
                    form.style.display = 'block';
                    form.scrollIntoView( { behavior: 'smooth', block: 'center' } );
                }
            } );
        } );

        // Toggle de visibilidade da senha
        document.querySelectorAll( '.password-toggle' ).forEach( button => {
            button.addEventListener( 'click', function () {
                const inputId = this.getAttribute( 'data-input' );
                const input = document.getElementById( inputId );
                const icon = this.querySelector( 'i' );

                if ( input.type === 'password' ) {
                    input.type = 'text';
                    icon.classList.remove( 'bi-eye' );
                    icon.classList.add( 'bi-eye-slash' );
                } else {
                    input.type = 'password';
                    icon.classList.remove( 'bi-eye-slash' );
                    icon.classList.add( 'bi-eye' );
                }
            } );
        } );

        // Validação de senha em tempo real
        document.getElementById( 'confirm_password' )?.addEventListener( 'input', function () {
            const password = document.getElementById( 'password' ).value;
            const confirmPassword = this.value;
            const errorIcon = document.querySelector( '.password-toggle[data-input="confirm_password"] .error-icon' );

            if ( password !== confirmPassword ) {
                this.classList.add( 'is-invalid' );
                if ( errorIcon ) errorIcon.classList.remove( 'd-none' );
            } else {
                this.classList.remove( 'is-invalid' );
                if ( errorIcon ) errorIcon.classList.add( 'd-none' );
            }
        } );

        // Scroll suave para planos
        document.getElementById( 'conhecaPlanos' )?.addEventListener( 'click', function () {
            document.getElementById( 'plans' )?.scrollIntoView( {
                behavior: 'smooth',
                block: 'start'
            } );
        } );

        // Animação de entrada para elementos
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver( ( entries ) => {
            entries.forEach( entry => {
                if ( entry.isIntersecting ) {
                    entry.target.classList.add( 'animate-fade-in-up' );
                }
            } );
        }, observerOptions );

        // Observar elementos para animação
        document.addEventListener( 'DOMContentLoaded', () => {
            document.querySelectorAll( '.card, .section-header' ).forEach( el => {
                observer.observe( el );
            } );
        } );
    </script>
@endpush
