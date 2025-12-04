{{-- resources/views/pages/legal/privacy_policy.blade.php --}}
{{-- Página de Política de Privacidade --}}

@extends('layouts.app')

@section('content')
    {{-- Breadcrumbs --}}
@section('breadcrumbs')
    <li>
        <a href="{{ route('home') }}" class="text-gray-500 hover:text-primary-600">Início</a>
    </li>
    <li>
        <span class="text-gray-900 font-medium">Política de Privacidade</span>
    </li>
@endsection

<section class="py-20">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="card shadow-lg">
            <div class="card-header bg-primary-600 text-white">
                <h1 class="text-3xl font-bold py-1 text-center">
                    Política de Privacidade
                </h1>
            </div>

            <div class="card-body p-8">
                <div class="prose max-w-none">
                    <p class="text-lg text-gray-700 mb-6">
                        Última atualização: {{ date('d/m/Y') }}
                    </p>

                    <h2 class="text-2xl font-bold text-gray-900 mb-4">1. Informações Gerais</h2>
                    <p class="text-gray-700 mb-6">
                        A sua privacidade é importante para nós. Esta política de privacidade explica como coletamos,
                        usamos, armazenamos e protegemos suas informações pessoais quando você utiliza o Easy Budget.
                    </p>

                    <h2 class="text-2xl font-bold text-gray-900 mb-4">2. Dados Coletados</h2>
                    <p class="text-gray-700 mb-4">Coletamos os seguintes tipos de informações:</p>
                    <ul class="list-disc list-inside text-gray-700 mb-6 space-y-2">
                        <li><strong>Informações de cadastro:</strong> Nome, email, telefone e senha</li>
                        <li><strong>Dados de uso:</strong> Informações sobre como você utiliza nossa plataforma</li>
                        <li><strong>Dados financeiros:</strong> Informações relacionadas a orçamentos e serviços</li>
                        <li><strong>Comunicações:</strong> Mensagens trocadas através da plataforma</li>
                    </ul>

                    <h2 class="text-2xl font-bold text-gray-900 mb-4">3. Uso das Informações</h2>
                    <p class="text-gray-700 mb-6">
                        Utilizamos suas informações para:
                    </p>
                    <ul class="list-disc list-inside text-gray-700 mb-6 space-y-2">
                        <li>Fornecer e manter nossos serviços</li>
                        <li>Processar transações e gerenciar orçamentos</li>
                        <li>Comunicar sobre atualizações e novidades</li>
                        <li>Fornecer suporte ao cliente</li>
                        <li>Melhorar nossa plataforma</li>
                    </ul>

                    <h2 class="text-2xl font-bold text-gray-900 mb-4">4. Compartilhamento de Dados</h2>
                    <p class="text-gray-700 mb-6">
                        Não vendemos, alugamos ou comercializamos suas informações pessoais. Podemos compartilhar dados
                        apenas:
                    </p>
                    <ul class="list-disc list-inside text-gray-700 mb-6 space-y-2">
                        <li>Com prestadores de serviços necessários para operar a plataforma</li>
                        <li>Quando exigido por lei ou para proteger nossos direitos</li>
                        <li>Em caso de fusão, aquisição ou venda de ativos</li>
                    </ul>

                    <h2 class="text-2xl font-bold text-gray-900 mb-4">5. Segurança dos Dados</h2>
                    <p class="text-gray-700 mb-6">
                        Implementamos medidas de segurança técnicas e organizacionais apropriadas para proteger suas
                        informações pessoais contra acesso não autorizado, alteração, divulgação ou destruição.
                    </p>

                    <h2 class="text-2xl font-bold text-gray-900 mb-4">6. Retenção de Dados</h2>
                    <p class="text-gray-700 mb-6">
                        Mantemos suas informações pessoais apenas pelo tempo necessário para cumprir os propósitos
                        descritos nesta política, a menos que um período de retenção mais longo seja exigido por lei.
                    </p>

                    <h2 class="text-2xl font-bold text-gray-900 mb-4">7. Seus Direitos</h2>
                    <p class="text-gray-700 mb-6">
                        Você tem o direito de:
                    </p>
                    <ul class="list-disc list-inside text-gray-700 mb-6 space-y-2">
                        <li>Acessar seus dados pessoais</li>
                        <li>Corrigir dados incorretos ou incompletos</li>
                        <li>Solicitar a exclusão de seus dados</li>
                        <li>Opor-se ao processamento de seus dados</li>
                        <li>Solicitar a portabilidade dos dados</li>
                    </ul>

                    <h2 class="text-2xl font-bold text-gray-900 mb-4">8. Cookies</h2>
                    <p class="text-gray-700 mb-6">
                        Utilizamos cookies e tecnologias similares para melhorar sua experiência, analisar o uso da
                        plataforma
                        e personalizar conteúdo. Você pode gerenciar suas preferências de cookies através das
                        configurações do navegador.
                    </p>

                    <h2 class="text-2xl font-bold text-gray-900 mb-4">9. Alterações nesta Política</h2>
                    <p class="text-gray-700 mb-6">
                        Podemos atualizar esta política de privacidade periodicamente. Notificaremos sobre mudanças
                        significativas
                        através de aviso em nossa plataforma ou por email.
                    </p>

                    <h2 class="text-2xl font-bold text-gray-900 mb-4">10. Contato</h2>
                    <p class="text-gray-700 mb-6">
                        Para questões sobre esta política de privacidade ou sobre como tratamos seus dados, entre em
                        contato:
                    </p>
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <p class="text-gray-700">
                            <strong>Email:</strong>
                            <a href="mailto:jrwebdevelopment.2025@gmail.com"
                                class="text-primary-600 hover:text-primary-800 ml-2">
                                jrwebdevelopment.2025@gmail.com
                            </a>
                        </p>
                        <p class="text-gray-700 mt-2">
                            <strong>Telefone:</strong> (43) 99959-0945
                        </p>
                    </div>
                </div>

                <div class="text-center mt-8">
                    <a href="{{ route('home') }}" class="btn btn-primary">
                        Voltar ao Início
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
