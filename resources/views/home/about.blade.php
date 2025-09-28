@extends( 'layouts.app' )

@section( 'title', 'Sobre - Easy Budget' )
@section( 'description', 'Conheça mais sobre o Easy Budget, nossa missão e valores' )

@section( 'content' )
    <section class="py-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto">
                    <div class="text-center mb-5">
                        <h1 class="display-5 fw-bold mb-3">
                            <i class="bi bi-info-circle text-primary me-2"></i>Sobre o Easy Budget
                        </h1>
                        <p class="lead text-muted">
                            Conheça nossa história, missão e compromisso com a excelência em gestão de orçamentos
                        </p>
                    </div>

                    <div class="card shadow-sm mb-4">
                        <div class="card-body p-5">
                            <h2 class="h3 mb-4">
                                <i class="bi bi-star text-warning me-2"></i>Nossa Missão
                            </h2>
                            <p class="mb-4">
                                Revolucionar a gestão de orçamentos e serviços, oferecendo uma plataforma intuitiva,
                                segura e eficiente que simplifica processos complexos e impulsiona o crescimento dos nossos
                                usuários.
                            </p>

                            <h2 class="h3 mb-4">
                                <i class="bi bi-eye text-info me-2"></i>Nossa Visão
                            </h2>
                            <p class="mb-4">
                                Ser a principal referência em sistemas de gestão de orçamentos no Brasil,
                                reconhecida pela inovação, qualidade e pelo impacto positivo na produtividade de empresas e
                                profissionais.
                            </p>

                            <h2 class="h3 mb-4">
                                <i class="bi bi-heart text-danger me-2"></i>Nossos Valores
                            </h2>
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <div class="d-flex align-items-start">
                                        <i class="bi bi-check-circle-fill text-success me-3 mt-1"></i>
                                        <div>
                                            <h6 class="fw-bold">Inovação</h6>
                                            <p class="text-muted small mb-0">
                                                Buscamos constantemente novas soluções e tecnologias para melhorar a
                                                experiência dos usuários.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="d-flex align-items-start">
                                        <i class="bi bi-check-circle-fill text-success me-3 mt-1"></i>
                                        <div>
                                            <h6 class="fw-bold">Qualidade</h6>
                                            <p class="text-muted small mb-0">
                                                Comprometimento com a excelência em todos os aspectos do nosso produto e
                                                atendimento.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="d-flex align-items-start">
                                        <i class="bi bi-check-circle-fill text-success me-3 mt-1"></i>
                                        <div>
                                            <h6 class="fw-bold">Transparência</h6>
                                            <p class="text-muted small mb-0">
                                                Comunicação clara e honesta com nossos usuários e parceiros.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="d-flex align-items-start">
                                        <i class="bi bi-check-circle-fill text-success me-3 mt-1"></i>
                                        <div>
                                            <h6 class="fw-bold">Suporte</h6>
                                            <p class="text-muted small mb-0">
                                                Atendimento excepcional e suporte técnico de qualidade para todos os
                                                usuários.
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card shadow-sm mb-4">
                        <div class="card-body p-5">
                            <h2 class="h3 mb-4">
                                <i class="bi bi-graph-up text-primary me-2"></i>Nossa História
                            </h2>
                            <p class="mb-4">
                                O Easy Budget nasceu da necessidade de profissionais autônomos e pequenas empresas
                                terem uma ferramenta simples e poderosa para gerenciar seus orçamentos e serviços.
                            </p>
                            <p class="mb-4">
                                Desde o início, focamos em criar uma solução que unisse tecnologia de ponta com
                                usabilidade excepcional, resultando em uma plataforma que realmente faz a diferença
                                no dia a dia dos nossos usuários.
                            </p>
                            <p class="mb-0">
                                Hoje, orgulhamo-nos de servir centenas de profissionais e empresas,
                                contribuindo para o crescimento e organização de seus negócios.
                            </p>
                        </div>
                    </div>

                    <div class="text-center">
                        <h3 class="h4 mb-3">Junte-se a nós nessa jornada!</h3>
                        <p class="text-muted mb-4">
                            Descubra como o Easy Budget pode transformar a gestão do seu negócio.
                        </p>
                        <a href="{{ route( 'home' ) }}" class="btn btn-primary btn-lg">
                            <i class="bi bi-arrow-left me-2"></i>Voltar ao Início
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
