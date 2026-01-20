@extends('layouts.app')

@section('title', 'Termos de Serviço')

@section('content')
    <x-layout.page-container>
        <x-layout.page-header
            title="Termos de Serviço"
            icon="file-earmark-text"
            :breadcrumb-items="[
                'Início' => route('home'),
                'Termos de Serviço' => '#'
            ]">
            <x-slot:actions>
                <x-ui.button :href="route('home')" variant="secondary" outline icon="arrow-left" label="Voltar ao Início" />
            </x-slot:actions>
        </x-layout.page-header>

        <x-layout.grid-row>
            <div class="col-12 col-lg-10 offset-lg-1">
                <x-ui.card>
                    <div class="p-3">
                        <p class="text-muted mb-4">
                            Última atualização: {{ date('d/m/Y') }}
                        </p>

                        <h4 class="fw-bold text-dark mb-3">1. Aceitação dos Termos</h4>
                        <p class="text-secondary mb-4">
                            Ao acessar e usar o Easy Budget, você aceita e concorda em cumprir os termos e condições de uso
                            aqui descritos. Se você não concordar com estes termos, por favor, não use este serviço.
                        </p>

                        <h4 class="fw-bold text-dark mb-3">2. Uso do Serviço</h4>
                        <p class="text-secondary mb-4">
                            O Easy Budget é uma plataforma destinada a facilitar a gestão financeira e orçamentação de serviços.
                            Você concorda em usar o serviço apenas para fins legítimos e de acordo com todas as leis aplicáveis.
                        </p>

                        <h4 class="fw-bold text-dark mb-3">3. Contas de Usuário</h4>
                        <p class="text-secondary mb-4">
                            Para utilizar determinados recursos do serviço, você precisará criar uma conta. Você é responsável
                            por manter a confidencialidade de suas credenciais de login e por todas as atividades que ocorrerem
                            em sua conta.
                        </p>

                        <h4 class="fw-bold text-dark mb-3">4. Propriedade Intelectual</h4>
                        <p class="text-secondary mb-4">
                            Todo o conteúdo, recursos e funcionalidades do Easy Budget são propriedade da JR Tech e estão
                            protegidos por leis de direitos autorais, marcas registradas e outras leis de propriedade intelectual.
                        </p>

                        <h4 class="fw-bold text-dark mb-3">5. Limitação de Responsabilidade</h4>
                        <p class="text-secondary mb-4">
                            O Easy Budget é fornecido "como está" sem garantias de qualquer tipo. Não nos responsabilizamos
                            por qualquer dano direto, indireto, incidental ou consequencial resultante do uso do serviço.
                        </p>

                        <h4 class="fw-bold text-dark mb-3">6. Modificações dos Termos</h4>
                        <p class="text-secondary mb-4">
                            Reservamo-nos o direito de modificar estes termos a qualquer momento. As alterações entrarão em vigor
                            imediatamente após sua publicação. É sua responsabilidade verificar periodicamente eventuais mudanças.
                        </p>

                        <h4 class="fw-bold text-dark mb-3">7. Contato</h4>
                        <p class="text-secondary mb-3">
                            Para dúvidas sobre estes termos, entre em contato conosco através do email:
                        </p>
                        <div class="alert alert-light border">
                            <p class="mb-0">
                                <i class="bi bi-envelope me-2"></i><strong>Email:</strong>
                                <a href="mailto:jrwebdevelopment.2025@gmail.com" class="text-decoration-none ms-1">
                                    jrwebdevelopment.2025@gmail.com
                                </a>
                            </p>
                        </div>
                    </div>

                    <div class="text-center mt-4">
                        <x-ui.button :href="route('home')" variant="primary" label="Voltar ao Início" />
                    </div>
                </x-ui.card>
            </div>
        </x-layout.grid-row>
    </x-layout.page-container>
@endsection
