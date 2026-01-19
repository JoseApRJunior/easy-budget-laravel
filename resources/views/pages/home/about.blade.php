@extends( 'layouts.app' )

@section( 'content' )
    <x-layout.page-container>
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="text-center mb-4">
                    <x-layout.page-header
                        title="Sobre o Easy Budget"
                        icon="building"
                    />
                </div>

                {{-- Missão --}}
                <x-ui.card class="hover-card mb-4">
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
                </x-ui.card>

                {{-- Quem Somos e Nossa Visão --}}
                <x-layout.grid-row>
                    <x-layout.grid-col size="col-md-6">
                        <x-ui.card class="hover-card h-100">
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
                        </x-ui.card>
                    </x-layout.grid-col>
                    <x-layout.grid-col size="col-md-6">
                        <x-ui.card class="hover-card h-100">
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
                        </x-ui.card>
                    </x-layout.grid-col>
                </x-layout.grid-row>

                {{-- Recursos --}}
                <x-ui.card class="hover-card mb-4">
                    <h2 class="h4 mb-3">
                        <i class="bi bi-gear me-2"></i>
                        Nossos Recursos
                    </h2>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item px-0">
                            <i class="bi bi-file-earmark-text me-2 text-primary"></i>
                            <strong>Criação de Orçamentos:</strong>
                            Ferramentas intuitivas para prestadores criarem orçamentos detalhados.
                        </li>
                        <li class="list-group-item px-0">
                            <i class="bi bi-kanban me-2 text-primary"></i>
                            <strong>Gestão de Projetos:</strong>
                            Acompanhamento em tempo real do progresso dos serviços.
                        </li>
                        <li class="list-group-item px-0">
                            <i class="bi bi-chat-dots me-2 text-primary"></i>
                            <strong>Comunicação Integrada:</strong>
                            Chat interno para facilitar a comunicação entre prestadores e clientes.
                        </li>
                        <li class="list-group-item px-0">
                            <i class="bi bi-graph-up me-2 text-primary"></i>
                            <strong>Análise Financeira:</strong>
                            Relatórios e dashboards para análise de desempenho e lucratividade.
                        </li>
                        <li class="list-group-item px-0">
                            <i class="bi bi-people-fill me-2 text-primary"></i>
                            <strong>Múltiplos Perfis:</strong>
                            Suporte para pessoas físicas e jurídicas com necessidades distintas.
                        </li>
                    </ul>
                </x-ui.card>

                {{-- Valores --}}
                <x-ui.card class="hover-card mb-4">
                    <h2 class="h4 mb-3">
                        <i class="bi bi-star me-2"></i>
                        Nossos Valores
                    </h2>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item px-0">
                            <i class="bi bi-lightning me-2 text-warning"></i>
                            <strong>Eficiência:</strong>
                            Otimizamos o processo de orçamentação e gestão de serviços.
                        </li>
                        <li class="list-group-item px-0">
                            <i class="bi bi-brightness-high me-2 text-warning"></i>
                            <strong>Transparência:</strong>
                            Promovemos clareza em todas as etapas do processo de serviço.
                        </li>
                        <li class="list-group-item px-0">
                            <i class="bi bi-lightbulb me-2 text-warning"></i>
                            <strong>Inovação:</strong>
                            Buscamos constantemente novas soluções para melhorar nossa plataforma.
                        </li>
                        <li class="list-group-item px-0">
                            <i class="bi bi-shield-check me-2 text-warning"></i>
                            <strong>Segurança:</strong>
                            Protegemos os dados e informações de nossos usuários com máxima prioridade.
                        </li>
                        <li class="list-group-item px-0">
                            <i class="bi bi-people me-2 text-warning"></i>
                            <strong>Colaboração:</strong>
                            Facilitamos a conexão e cooperação entre prestadores e clientes.
                        </li>
                    </ul>
                </x-ui.card>

                {{-- Contato --}}
                <x-ui.card class="hover-card mb-4 text-center">
                    <h2 class="h4 mb-3">
                        <i class="bi bi-envelope me-2"></i>
                        Entre em Contato
                    </h2>
                    <p class="mb-4">
                        Queremos ouvir você! Se você tem alguma dúvida, sugestão ou deseja saber mais sobre como o Easy
                        Budget pode
                        ajudar seu negócio, não hesite em nos contatar.
                    </p>
                    <x-ui.button href="/support" variant="primary" icon="headset" label="Ir para a página de Suporte" />
                </x-ui.card>
            </div>
        </div>
    </x-layout.page-container>
@endsection
