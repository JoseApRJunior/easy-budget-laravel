@extends( 'layouts.app' )

@section( 'content' )
    <x-layout.page-container>
        <x-layout.page-header title="Sobre o Easy Budget" icon="building"  />

        {{-- Missão --}}
        <x-ui.card class="hover-card transition-all mb-3 mt-3">
            <x-slot:header>
                <x-layout.h-stack gap="2">
                    <i class="bi bi-flag fs-5 text-primary"></i>
                    <h2 class="h5 mb-0 text-dark fw-bold">Nossa Missão</h2>
                </x-layout.h-stack>
            </x-slot:header>
            <p class="lead mb-0">
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
        <x-layout.grid-row class="mb-3">
            <x-layout.grid-col size="col-md-6">
                <x-ui.card class="hover-card transition-all h-100">
                    <x-slot:header>
                        <x-layout.h-stack gap="2">
                            <i class="bi bi-people fs-5 text-primary"></i>
                            <h2 class="h5 mb-0 text-dark fw-bold">Quem Somos</h2>
                        </x-layout.h-stack>
                    </x-slot:header>
                    <p class="lead mb-0">
                        Somos uma equipe de profissionais apaixonados por tecnologia e gestão de projetos.
                        Fundada em 2024,
                        nossa plataforma tem crescido rapidamente, atendendo a uma ampla gama de setores e
                        facilitando a conexão
                        entre prestadores de serviços e clientes.
                    </p>
                </x-ui.card>
            </x-layout.grid-col>
            <x-layout.grid-col size="col-md-6">
                <x-ui.card class="hover-card transition-all h-100">
                    <x-slot:header>
                        <x-layout.h-stack gap="2">
                            <i class="bi bi-eye fs-5 text-primary"></i>
                            <h2 class="h5 mb-0 text-dark fw-bold">Nossa Visão</h2>
                        </x-layout.h-stack>
                    </x-slot:header>
                    <p class="lead mb-0">
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
        <x-ui.card class="hover-card transition-all mb-3">
            <x-slot:header>
                <x-layout.h-stack gap="2">
                    <i class="bi bi-gear fs-5 text-primary"></i>
                    <h2 class="h5 mb-0 text-dark fw-bold">Nossos Recursos</h2>
                </x-layout.h-stack>
            </x-slot:header>
            <div class="list-group list-group-flush border-0">
                <x-ui.list-item padding="2" :hover="true" class="border-bottom">
                    <x-resource.resource-info
                        icon="file-earmark-text"
                        title="Criação de Orçamentos:"
                        subtitle="Ferramentas intuitivas para prestadores criarem orçamentos detalhados."
                        icon-class="text-primary fs-5"
                        title-class="fw-bold d-block"
                    />
                </x-ui.list-item>
                <x-ui.list-item padding="2" :hover="true" class="border-bottom">
                    <x-resource.resource-info
                        icon="kanban"
                        title="Gestão de Projetos:"
                        subtitle="Acompanhamento em tempo real do progresso dos serviços."
                        icon-class="text-primary fs-5"
                        title-class="fw-bold d-block"
                    />
                </x-ui.list-item>
                <x-ui.list-item padding="2" :hover="true" class="border-bottom">
                    <x-resource.resource-info
                        icon="chat-dots"
                        title="Comunicação Integrada:"
                        subtitle="Chat interno para facilitar a comunicação entre prestadores e clientes."
                        icon-class="text-primary fs-5"
                        title-class="fw-bold d-block"
                    />
                </x-ui.list-item>
                <x-ui.list-item padding="2" :hover="true" class="border-bottom">
                    <x-resource.resource-info
                        icon="graph-up"
                        title="Análise Financeira:"
                        subtitle="Relatórios e dashboards para análise de desempenho e lucratividade."
                        icon-class="text-primary fs-5"
                        title-class="fw-bold d-block"
                    />
                </x-ui.list-item>
                <x-ui.list-item padding="2" :hover="true">
                    <x-resource.resource-info
                        icon="people-fill"
                        title="Múltiplos Perfis:"
                        subtitle="Suporte para pessoas físicas e jurídicas com necessidades distintas."
                        icon-class="text-primary fs-5"
                        title-class="fw-bold d-block"
                    />
                </x-ui.list-item>
            </div>
        </x-ui.card>

        {{-- Valores --}}
        <x-ui.card class="hover-card transition-all mb-3">
            <x-slot:header>
                <x-layout.h-stack gap="2">
                    <i class="bi bi-star fs-5 text-primary"></i>
                    <h2 class="h5 mb-0 text-dark fw-bold">Nossos Valores</h2>
                </x-layout.h-stack>
            </x-slot:header>
            <div class="row g-3 p-2">
                <div class="col-md-4">
                    <x-ui.list-item padding="3" :hover="true" class="h-100 bg-light border-0">
                        <x-resource.resource-info
                            icon="lightning"
                            title="Eficiência"
                            subtitle="Otimização de processos e gestão."
                            class="flex-column text-center w-100"
                            icon-class="text-warning fs-4 d-block mb-2"
                            title-class="fw-bold d-block"
                        />
                    </x-ui.list-item>
                </div>
                <div class="col-md-4">
                    <x-ui.list-item padding="3" :hover="true" class="h-100 bg-light border-0">
                        <x-resource.resource-info
                            icon="brightness-high"
                            title="Transparência"
                            subtitle="Clareza em todas as etapas."
                            class="flex-column text-center w-100"
                            icon-class="text-warning fs-4 d-block mb-2"
                            title-class="fw-bold d-block"
                        />
                    </x-ui.list-item>
                </div>
                <div class="col-md-4">
                    <x-ui.list-item padding="3" :hover="true" class="h-100 bg-light border-0">
                        <x-resource.resource-info
                            icon="lightbulb"
                            title="Inovação"
                            subtitle="Novas soluções constantes."
                            class="flex-column text-center w-100"
                            icon-class="text-warning fs-4 d-block mb-2"
                            title-class="fw-bold d-block"
                        />
                    </x-ui.list-item>
                </div>
                <div class="col-md-6">
                    <x-ui.list-item padding="3" :hover="true" class="h-100 bg-light border-0">
                        <x-resource.resource-info
                            icon="heart"
                            title="Foco no Cliente"
                            subtitle="Suas necessidades são nossa prioridade."
                            class="flex-column text-center w-100"
                            icon-class="text-warning fs-4 d-block mb-2"
                            title-class="fw-bold d-block"
                        />
                    </x-ui.list-item>
                </div>
                <div class="col-md-6">
                    <x-ui.list-item padding="3" :hover="true" class="h-100 bg-light border-0">
                        <x-resource.resource-info
                            icon="people"
                            title="Colaboração"
                            subtitle="Conexão entre prestadores e clientes."
                            class="flex-column text-center w-100"
                            icon-class="text-warning fs-4 d-block mb-2"
                            title-class="fw-bold d-block"
                        />
                    </x-ui.list-item>
                </div>
            </div>
        </x-ui.card>

        {{-- Contato --}}
        <x-ui.card class="hover-card transition-all text-center mb-3">
            <x-slot:header>
                <x-layout.h-stack gap="2" justify="center">
                    <i class="bi bi-envelope fs-5 text-primary"></i>
                    <h2 class="h5 mb-0 text-dark fw-bold">Entre em Contato</h2>
                </x-layout.h-stack>
            </x-slot:header>
            <p class="mb-4">
                Queremos ouvir você! Se você tem alguma dúvida, sugestão ou deseja saber mais sobre como o Easy
                Budget pode ajudar seu negócio, não hesite em nos contatar.
            </p>
            <x-ui.button href="/support" variant="primary" icon="headset" label="Ir para a página de Suporte" />
        </x-ui.card>
    </x-layout.page-container>
@endsection
