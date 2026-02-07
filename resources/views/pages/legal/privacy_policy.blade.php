@extends('layouts.app')

@section('title', 'Política de Privacidade')

@section('content')
    <x-layout.page-container>
        <x-layout.page-header
            title="Política de Privacidade"
            icon="shield-lock"
            :breadcrumb-items="[
                'Início' => route('home'),
                'Política de Privacidade' => '#'
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

                        <h4 class="fw-bold text-dark mb-3">1. Informações Gerais</h4>
                        <p class="text-secondary mb-4">
                            A sua privacidade é importante para nós. Esta política de privacidade explica como coletamos,
                            usamos, armazenamos e protegemos suas informações pessoais quando você utiliza o Easy Budget.
                        </p>

                        <h4 class="fw-bold text-dark mb-3">2. Dados Coletados</h4>
                        <p class="text-secondary mb-3">Coletamos os seguintes tipos de informações:</p>
                        <ul class="list-unstyled mb-4 ps-3">
                            <li class="mb-2"><i class="bi bi-check2-circle text-primary me-2"></i><strong>Informações de cadastro:</strong> Nome, email, telefone e senha</li>
                            <li class="mb-2"><i class="bi bi-check2-circle text-primary me-2"></i><strong>Dados de uso:</strong> Informações sobre como você utiliza nossa plataforma</li>
                            <li class="mb-2"><i class="bi bi-check2-circle text-primary me-2"></i><strong>Dados financeiros:</strong> Informações relacionadas a orçamentos e serviços</li>
                            <li class="mb-2"><i class="bi bi-check2-circle text-primary me-2"></i><strong>Comunicações:</strong> Mensagens trocadas através da plataforma</li>
                        </ul>

                        <h4 class="fw-bold text-dark mb-3">3. Uso das Informações</h4>
                        <p class="text-secondary mb-3">Utilizamos suas informações para:</p>
                        <ul class="list-unstyled mb-4 ps-3">
                            <li class="mb-2"><i class="bi bi-dot text-primary me-2"></i>Fornecer e manter nossos serviços</li>
                            <li class="mb-2"><i class="bi bi-dot text-primary me-2"></i>Processar transações e gerenciar orçamentos</li>
                            <li class="mb-2"><i class="bi bi-dot text-primary me-2"></i>Comunicar sobre atualizações e novidades</li>
                            <li class="mb-2"><i class="bi bi-dot text-primary me-2"></i>Fornecer suporte ao cliente</li>
                            <li class="mb-2"><i class="bi bi-dot text-primary me-2"></i>Melhorar nossa plataforma</li>
                        </ul>

                        <h4 class="fw-bold text-dark mb-3">4. Compartilhamento de Dados</h4>
                        <p class="text-secondary mb-3">
                            Não vendemos, alugamos ou comercializamos suas informações pessoais. Podemos compartilhar dados apenas:
                        </p>
                        <ul class="list-unstyled mb-4 ps-3">
                            <li class="mb-2"><i class="bi bi-dash text-secondary me-2"></i>Com prestadores de serviços necessários para operar a plataforma</li>
                            <li class="mb-2"><i class="bi bi-dash text-secondary me-2"></i>Quando exigido por lei ou para proteger nossos direitos</li>
                            <li class="mb-2"><i class="bi bi-dash text-secondary me-2"></i>Em caso de fusão, aquisição ou venda de ativos</li>
                        </ul>

                        <h4 class="fw-bold text-dark mb-3">5. Segurança dos Dados</h4>
                        <p class="text-secondary mb-4">
                            Implementamos medidas de segurança técnicas e organizacionais apropriadas para proteger suas
                            informações pessoais contra acesso não autorizado, alteração, divulgação ou destruição.
                        </p>

                        <h4 class="fw-bold text-dark mb-3">6. Retenção de Dados</h4>
                        <p class="text-secondary mb-4">
                            Mantemos suas informações pessoais apenas pelo tempo necessário para cumprir os propósitos
                            descritos nesta política, a menos que um período de retenção mais longo seja exigido por lei.
                        </p>

                        <h4 class="fw-bold text-dark mb-3">7. Seus Direitos</h4>
                        <p class="text-secondary mb-3">Você tem o direito de:</p>
                        <ul class="list-unstyled mb-4 ps-3">
                            <li class="mb-2"><i class="bi bi-check-lg text-success me-2"></i>Acessar seus dados pessoais</li>
                            <li class="mb-2"><i class="bi bi-check-lg text-success me-2"></i>Corrigir dados incorretos ou incompletos</li>
                            <li class="mb-2"><i class="bi bi-check-lg text-success me-2"></i>Solicitar a exclusão de seus dados</li>
                            <li class="mb-2"><i class="bi bi-check-lg text-success me-2"></i>Opor-se ao processamento de seus dados</li>
                            <li class="mb-2"><i class="bi bi-check-lg text-success me-2"></i>Solicitar a portabilidade dos dados</li>
                        </ul>

                        <h4 class="fw-bold text-dark mb-3">8. Cookies</h4>
                        <p class="text-secondary mb-4">
                            Utilizamos cookies e tecnologias similares para melhorar sua experiência, analisar o uso da plataforma
                            e personalizar conteúdo. Você pode gerenciar suas preferências de cookies através das
                            configurações do navegador.
                        </p>

                        <h4 class="fw-bold text-dark mb-3">9. Alterações nesta Política</h4>
                        <p class="text-secondary mb-4">
                            Podemos atualizar esta política de privacidade periodicamente. Notificaremos sobre mudanças significativas
                            através de aviso em nossa plataforma ou por email.
                        </p>

                        <h4 class="fw-bold text-dark mb-3">10. Contato</h4>
                        <p class="text-secondary mb-3">
                            Para questões sobre esta política de privacidade ou sobre como tratamos seus dados, entre em contato:
                        </p>
                        <div class="alert alert-light border">
                            <p class="mb-2">
                                <i class="bi bi-envelope me-2"></i><strong>Email:</strong>
                                <a href="mailto:jrwebdevelopment.2025@gmail.com" class="text-decoration-none ms-1">
                                    jrwebdevelopment.2025@gmail.com
                                </a>
                            </p>
                            <p class="mb-0">
                                <i class="bi bi-whatsapp me-2"></i><strong>Telefone:</strong> (43) 99959-0945
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
