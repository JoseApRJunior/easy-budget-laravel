@extends( 'layouts.guest' )

@section( 'title', 'Sobre - Easy Budget' )

@section( 'content' )
    <!-- Hero Section -->
    <section class="bg-gradient-to-br from-blue-600 via-blue-700 to-indigo-800 text-white py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h1 class="text-4xl md:text-5xl font-bold mb-6">Sobre o Easy Budget</h1>
                <p class="text-xl md:text-2xl text-blue-100 max-w-3xl mx-auto">
                    Revolucionando a gestão financeira e de orçamentos para empresas de todos os portes
                </p>
            </div>
        </div>
    </section>

    <!-- About Content -->
    <section class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                <div>
                    <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-6">Nossa Missão</h2>
                    <p class="text-lg text-gray-600 mb-6">
                        O Easy Budget nasceu com o objetivo de simplificar e otimizar a gestão financeira de empresas,
                        oferecendo ferramentas poderosas e intuitivas para controle de orçamentos, clientes e relatórios.
                    </p>
                    <p class="text-lg text-gray-600 mb-6">
                        Acreditamos que uma gestão financeira eficiente é a base para o sucesso de qualquer negócio.
                        Por isso, desenvolvemos uma plataforma completa que se adapta às necessidades de cada empresa.
                    </p>
                    <p class="text-lg text-gray-600">
                        Com tecnologia de ponta e interface amigável, transformamos dados complexos em insights acionáveis,
                        ajudando nossos clientes a tomar decisões mais inteligentes e crescer de forma sustentável.
                    </p>
                </div>

                <div class="bg-gray-100 rounded-lg p-8">
                    <h3 class="text-2xl font-semibold text-gray-900 mb-6">Nossos Valores</h3>
                    <ul class="space-y-4">
                        <li class="flex items-start">
                            <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center mr-4 mt-1">
                                <i class="bi bi-lightbulb text-blue-600"></i>
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-900">Inovação</h4>
                                <p class="text-gray-600">Buscamos constantemente novas tecnologias e soluções para melhorar
                                    a experiência dos nossos usuários.</p>
                            </div>
                        </li>
                        <li class="flex items-start">
                            <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center mr-4 mt-1">
                                <i class="bi bi-shield-check text-green-600"></i>
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-900">Segurança</h4>
                                <p class="text-gray-600">Protegemos os dados dos nossos clientes com os mais altos padrões
                                    de segurança e privacidade.</p>
                            </div>
                        </li>
                        <li class="flex items-start">
                            <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center mr-4 mt-1">
                                <i class="bi bi-hand-thumbs-up text-purple-600"></i>
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-900">Excelência</h4>
                                <p class="text-gray-600">Entregamos produtos e serviços de alta qualidade, superando as
                                    expectativas dos nossos clientes.</p>
                            </div>
                        </li>
                        <li class="flex items-start">
                            <div class="w-8 h-8 bg-yellow-100 rounded-full flex items-center justify-center mr-4 mt-1">
                                <i class="bi bi-people text-yellow-600"></i>
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-900">Colaboração</h4>
                                <p class="text-gray-600">Trabalhamos em parceria com nossos clientes para entender e atender
                                    suas necessidades específicas.</p>
                            </div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- Team Section -->
    <section class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Nossa Equipe</h2>
                <p class="text-xl text-gray-600 max-w-2xl mx-auto">
                    Profissionais dedicados e experientes trabalhando para oferecer a melhor solução
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="bg-white rounded-lg shadow-lg p-8 text-center">
                    <div class="w-24 h-24 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="bi bi-code-slash text-3xl text-blue-600"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Desenvolvimento</h3>
                    <p class="text-gray-600">
                        Equipe técnica especializada em Laravel, Vue.js e tecnologias modernas para entregar produtos de
                        alta qualidade.
                    </p>
                </div>

                <div class="bg-white rounded-lg shadow-lg p-8 text-center">
                    <div class="w-24 h-24 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="bi bi-palette text-3xl text-green-600"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Design</h3>
                    <p class="text-gray-600">
                        Designers UX/UI focados em criar experiências intuitivas e visualmente atraentes para todos os
                        usuários.
                    </p>
                </div>

                <div class="bg-white rounded-lg shadow-lg p-8 text-center">
                    <div class="w-24 h-24 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="bi bi-headset text-3xl text-purple-600"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Suporte</h3>
                    <p class="text-gray-600">
                        Equipe de suporte dedicada para ajudar nossos clientes a obterem o máximo da plataforma.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-20 bg-blue-600 text-white">
        <div class="max-w-4xl mx-auto text-center px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl md:text-4xl font-bold mb-6">
                Pronto para conhecer nossa plataforma?
            </h2>
            <p class="text-xl mb-8 text-blue-100">
                Experimente todos os recursos do Easy Budget gratuitamente por 14 dias
            </p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ route( 'login' ) }}"
                    class="inline-flex items-center px-8 py-4 bg-yellow-500 hover:bg-yellow-400 text-gray-900 font-semibold rounded-lg transition-colors duration-200 text-lg">
                    <i class="bi bi-rocket mr-2"></i>
                    Começar Teste Grátis
                </a>
                <a href="{{ route( 'home.support' ) }}"
                    class="inline-flex items-center px-8 py-4 bg-white bg-opacity-10 hover:bg-opacity-20 text-white font-semibold rounded-lg border-2 border-white border-opacity-30 transition-all duration-200 text-lg">
                    <i class="bi bi-chat-dots mr-2"></i>
                    Falar com Especialista
                </a>
            </div>
        </div>
    </section>
@endsection
