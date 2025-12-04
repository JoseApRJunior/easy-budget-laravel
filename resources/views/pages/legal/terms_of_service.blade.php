{{-- resources/views/pages/legal/terms_of_service.blade.php --}}
{{-- Página de Termos de Serviço --}}

@extends('layouts.app')

@section('content')
    {{-- Breadcrumbs --}}
@section('breadcrumbs')
    <li>
        <a href="{{ route('home') }}" class="text-gray-500 hover:text-primary-600">Início</a>
    </li>
    <li>
        <span class="text-gray-900 font-medium">Termos de Serviço</span>
    </li>
@endsection

<section class="py-20">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="card shadow-lg">
            <div class="card-header bg-primary-600 text-white">
                <h1 class="text-3xl font-bold py-1 text-center">
                    Termos de Serviço
                </h1>
            </div>

            <div class="card-body p-8">
                <div class="prose max-w-none">
                    <p class="text-lg text-gray-700 mb-6">
                        Última atualização: {{ date('d/m/Y') }}
                    </p>

                    <h2 class="text-2xl font-bold text-gray-900 mb-4">1. Aceitação dos Termos</h2>
                    <p class="text-gray-700 mb-6">
                        Ao acessar e usar o Easy Budget, você aceita e concorda em cumprir os termos e condições de uso
                        aqui descritos. Se você não concordar com estes termos, por favor, não use este serviço.
                    </p>

                    <h2 class="text-2xl font-bold text-gray-900 mb-4">2. Uso do Serviço</h2>
                    <p class="text-gray-700 mb-6">
                        O Easy Budget é uma plataforma destinada a facilitar a gestão financeira e orçamentação de
                        serviços.
                        Você concorda em usar o serviço apenas para fins legítimos e de acordo com todas as leis
                        aplicáveis.
                    </p>

                    <h2 class="text-2xl font-bold text-gray-900 mb-4">3. Contas de Usuário</h2>
                    <p class="text-gray-700 mb-6">
                        Para utilizar determinados recursos do serviço, você precisará criar uma conta. Você é
                        responsável
                        por manter a confidencialidade de suas credenciais de login e por todas as atividades que
                        ocorrerem
                        em sua conta.
                    </p>

                    <h2 class="text-2xl font-bold text-gray-900 mb-4">4. Propriedade Intelectual</h2>
                    <p class="text-gray-700 mb-6">
                        Todo o conteúdo, recursos e funcionalidades do Easy Budget são propriedade da JR Tech e estão
                        protegidos por leis de direitos autorais, marcas registradas e outras leis de propriedade
                        intelectual.
                    </p>

                    <h2 class="text-2xl font-bold text-gray-900 mb-4">5. Limitação de Responsabilidade</h2>
                    <p class="text-gray-700 mb-6">
                        O Easy Budget é fornecido "como está" sem garantias de qualquer tipo. Não nos responsabilizamos
                        por qualquer dano direto, indireto, incidental ou consequencial resultante do uso do serviço.
                    </p>

                    <h2 class="text-2xl font-bold text-gray-900 mb-4">6. Modificações dos Termos</h2>
                    <p class="text-gray-700 mb-6">
                        Reservamo-nos o direito de modificar estes termos a qualquer momento. As alterações entrarão em
                        vigor
                        imediatamente após sua publicação. É sua responsabilidade verificar periodicamente eventuais
                        mudanças.
                    </p>

                    <h2 class="text-2xl font-bold text-gray-900 mb-4">7. Contato</h2>
                    <p class="text-gray-700 mb-6">
                        Para dúvidas sobre estes termos, entre em contato conosco através do email:
                        <a href="mailto:jrwebdevelopment.2025@gmail.com"
                            class="text-primary-600 hover:text-primary-800">
                            jrwebdevelopment.2025@gmail.com
                        </a>
                    </p>
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
