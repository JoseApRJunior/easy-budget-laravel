@extends( 'layouts.app' )
@section( 'content' )
    <!-- Banner Principal com gradiente e animação -->
    <section id="home" class="hero-section text-center position-relative mt-5">
        <div class="hero-overlay"></div>
        <div class="main-container position-relative">
            <!-- Alerta de teste -->
            <div class="alert alert-warning alert-dismissible fade show mb-4" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <strong>Ambiente de Testes!</strong> Os dados podem ser resetados a qualquer momento. Não utilize dados
                reais.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>

            <div class="hero-content">
                <h1 class="display-4 fw-bold mb-3">Bem-vindo ao Easy Budget</h1>
                <p class="lead mb-4">Transforme a gestão de seus serviços com nossas soluções diversificadas e inovadoras.
                </p>
                <button id="conhecaPlanos" class="btn btn-primary btn-lg pulse-button">
                    <i class="bi bi-arrow-down-circle me-2"></i>Conheça nossos planos
                </button>
            </div>
        </div>
    </section>

    <!-- Seção de Planos -->
    <section id="plans" class="py-5">
        <div class="main-container">
            <div class="section-header text-center mb-5">
                <h2 class="display-6 fw-bold">Escolha o Plano Perfeito para Você</h2>
                <p class="small-text">Selecione o plano que melhor atende às suas necessidades</p>
            </div>

            <div class="row row-cols-1 row-cols-md-3 g-4 mb-5">
                @foreach( $plans as $plan )
                    <div class="col">
                        <div class="card h-100 shadow-sm hover-card">
                            <div class="card-body d-flex flex-column">
                                <div class="text-center mb-4">
                                    @if( $plan[ 'slug' ] == 'free' )
                                        <i class="bi bi-rocket display-6 text-primary mb-2"></i>
                                    @elseif( $plan[ 'slug' ] == 'basic' )
                                        <i class="bi bi-star display-6 text-success mb-2"></i>
                                    @else
                                        <i class="bi bi-gem display-6 text-info mb-2"></i>
                                    @endif
                                    <h3 class="card-title h4">{{ $plan[ 'name' ] }}</h3>
                                    <div class="pricing-header">
                                        <span class="currency">R$</span>
                                        <span class="price">{{ number_format( $plan[ 'price' ], 0, '', '.' ) }}</span>
                                        <span class="period">/mês</span>
                                    </div>
                                </div>

                                <p class="card-text small-text mb-4">{{ $plan[ 'description' ] }}</p>

                                <ul class="feature-list list-unstyled mb-4">
                                    @foreach( $plan[ 'features' ] as $feature )
                                        <li class="mb-2">
                                            <i class="bi bi-check-circle-fill text-success me-2"></i>
                                            {{ $feature }}
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Call to Action Section -->
            <div class="row justify-content-center mt-5  ">
                <div class="col-lg-8 text-center">
                    <div class="cta-section card h-100 shadow-sm hover-card  rounded-3 p-5">
                        <h3 class="mb-3">Pronto para começar?</h3>
                        <p class="lead mb-4">
                            Junte-se a milhares de profissionais que já estão transformando seus negócios com o Easy Budget.
                        </p>
                        <div class="d-flex justify-content-center gap-3 flex-wrap">
                            <a href="{{ route( 'register' ) }}" class="btn btn-light btn-lg">
                                <i class="bi bi-person-plus me-2"></i>
                                Começar Agora
                            </a>
                            <a href="#plans" class="btn btn-outline-light btn-lg">
                                <i class="bi bi-info-circle me-2"></i>
                                Saiba Mais
                            </a>
                        </div>
                        <div class="mt-4">
                            <small class="opacity-75">
                                <i class="bi bi-shield-check me-1"></i>
                                Cadastro gratuito • Sem taxas ocultas • Cancele quando quiser
                            </small>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </section>

@endsection

@push( 'scripts' )
    <script type="module" src="{{ asset( 'assets/js/home.js' ) }}"></script>
@endpush
