@extends( 'layouts.app' )

@section( 'content' )
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <h1 class="mb-4 text-center">
                    <i class="bi bi-building me-2"></i>
                    Sobre o Easy Budget
                </h1>

                {{-- Missão --}}
                <div class="card hover-card mb-4">
                    <div class="card-body">
                        <h2 class="h4 mb-3">
                            <i class="bi bi-flag me-2"></i>
                            Nossa Missão
                        </h2>
                        <p class="lead">
                            No Easy Budget, nossa missão é simplificar e otimizar o processo de orçamentação de serviços
                            diversos,
                            conectando prestadores de serviços e clientes de forma eficiente e transparente. Oferecemos
                            ferramentas
                            intuitivas para ajudar tanto pessoas físicas quanto jurídicas a gerenciar seus orçamentos e
                            projetos com
                            facilidade.
                        </p>
                    </div>
                </div>

                {{-- Quem Somos e Nossa Visão --}}
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="card hover-card h-100">
                            <div class="card-body">
                                <h2 class="h4 mb-3">
                                    <i class="bi bi-people me-2"></i>
                                    Quem Somos
                                </h2>
                                <p>
                                    Somos uma equipe de profissionais apaixonados por tecnologia e gestão de projetos.
                                    Fundada em 2024,
                                    nossa plataforma tem crescido rapidamente, atendendo a uma ampla gama de setores e
                                    facilitando a conexão
                                    entre prestadores de serviços e clientes.
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card hover-card h-100">
                            <div class="card-body">
                                <h2 class="h4 mb-3">
                                    <i class="bi bi-eye me-2"></i>
                                    Nossa Visão
                                </h2>
                                <p>
                                    Imaginamos um mercado de serviços mais eficiente e transparente, onde prestadores e
                                    clientes possam
                                    colaborar de forma harmoniosa. Estamos comprometidos em continuar inovando e expandindo
                                    nossas
                                    funcionalidades para atender às necessidades em constante evolução do mercado de
                                    serviços.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Recursos --}}
                <div class="card hover-card mb-4">
                    <div class="card-body">
                        <h2 class="h4 mb-3">
                            <i class="bi bi-gear me-2"></i>
                            Nossos Recursos
                        </h2>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item">
                                <i class="bi bi-file-earmark-text me-2"></i>
                                <strong>Criação de Orçamentos:</strong>
                                Ferramentas intuitivas para prestadores criarem orçamentos detalhados.
                            </li>
                            <li class="list-group-item">
                                <i class="bi bi-kanban me-2"></i>
                                <strong>Gestão de Projetos:</strong>
                                Acompanhamento em tempo real do progresso dos serviços.
                            </li>
                            <li class="list-group-item">
                                <i class="bi bi-chat-dots me-2"></i>
                                <strong>Comunicação Integrada:</strong>
                                Chat interno para facilitar a comunicação entre prestadores e clientes.
                            </li>
                            <li class="list-group-item">
                                <i class="bi bi-graph-up me-2"></i>
                                <strong>Análise Financeira:</strong>
                                Relatórios e dashboards para análise de desempenho e lucratividade.
                            </li>
                            <li class="list-group-item">
                                <i class="bi bi-people-fill me-2"></i>
                                <strong>Múltiplos Perfis:</strong>
                                Suporte para pessoas físicas e jurídicas com necessidades distintas.
                            </li>
                        </ul>
                    </div>
                </div>

                {{-- Valores --}}
                <div class="card hover-card mb-4">
                    <div class="card-body">
                        <h2 class="h4 mb-3">
                            <i class="bi bi-star me-2"></i>
                            Nossos Valores
                        </h2>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item">
                                <i class="bi bi-lightning me-2"></i>
                                <strong>Eficiência:</strong>
                                Otimizamos o processo de orçamentação e gestão de serviços.
                            </li>
                            <li class="list-group-item">
                                <i class="bi bi-brightness-high me-2"></i>
                                <strong>Transparência:</strong>
                                Promovemos clareza em todas as etapas do processo de serviço.
                            </li>
                            <li class="list-group-item">
                                <i class="bi bi-lightbulb me-2"></i>
                                <strong>Inovação:</strong>
                                Buscamos constantemente novas soluções para melhorar nossa plataforma.
                            </li>
                            <li class="list-group-item">
                                <i class="bi bi-shield-check me-2"></i>
                                <strong>Segurança:</strong>
                                Protegemos os dados e informações de nossos usuários com máxima prioridade.
                            </li>
                            <li class="list-group-item">
                                <i class="bi bi-people me-2"></i>
                                <strong>Colaboração:</strong>
                                Facilitamos a conexão e cooperação entre prestadores e clientes.
                            </li>
                        </ul>
                    </div>
                </div>

                {{-- Contato --}}
                <div class="card hover-card mb-4">
                    <div class="card-body text-center">
                        <h2 class="h4 mb-3">
                            <i class="bi bi-envelope me-2"></i>
                            Entre em Contato
                        </h2>
                        <p class="mb-4">
                            Queremos ouvir você! Se você tem alguma dúvida, sugestão ou deseja saber mais sobre como o Easy
                            Budget pode
                            ajudar seu negócio, não hesite em nos contatar.
                        </p>
                        <a href="/support" class="btn btn-primary">
                            <i class="bi bi-headset me-2"></i>
                            Ir para a página de Suporte
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
