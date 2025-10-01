@extends( 'layouts.guest' )

@section( 'title', 'Suporte - Easy Budget' )

@section( 'content' )
    <!-- Hero Section -->
    <section class="bg-gradient-to-br from-green-600 via-green-700 to-teal-800 text-white py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h1 class="text-4xl md:text-5xl font-bold mb-6">Central de Suporte</h1>
                <p class="text-xl md:text-2xl text-green-100 max-w-3xl mx-auto">
                    Nossa equipe está pronta para ajudar você a obter o máximo do Easy Budget
                </p>
            </div>
        </div>
    </section>

    <!-- Support Options -->
    <section class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Como Podemos Ajudar?</h2>
                <p class="text-xl text-gray-600 max-w-2xl mx-auto">
                    Escolha a opção de suporte que melhor se adequa à sua necessidade
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Documentação -->
                <div class="bg-gray-50 rounded-xl p-8 text-center hover:shadow-lg transition-shadow duration-300">
                    <div class="w-16 h-16 bg-blue-100 rounded-lg flex items-center justify-center mx-auto mb-6">
                        <i class="bi bi-book text-2xl text-blue-600"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-4">Documentação</h3>
                    <p class="text-gray-600 mb-6">
                        Guias completos, tutoriais em vídeo e documentação técnica detalhada para você aprender a usar todos
                        os recursos.
                    </p>
                    <a href="#"
                        class="inline-flex items-center px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition-colors duration-200">
                        <i class="bi bi-book mr-2"></i>
                        Acessar Documentação
                    </a>
                </div>

                <!-- Suporte por Email -->
                <div class="bg-gray-50 rounded-xl p-8 text-center hover:shadow-lg transition-shadow duration-300">
                    <div class="w-16 h-16 bg-green-100 rounded-lg flex items-center justify-center mx-auto mb-6">
                        <i class="bi bi-envelope text-2xl text-green-600"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-4">Suporte por Email</h3>
                    <p class="text-gray-600 mb-6">
                        Nossa equipe responde em até 24 horas. Ideal para questões técnicas e problemas que não são
                        urgentes.
                    </p>
                    <a href="mailto:suporte@easybudget.com.br"
                        class="inline-flex items-center px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition-colors duration-200">
                        <i class="bi bi-envelope mr-2"></i>
                        Enviar Email
                    </a>
                </div>

                <!-- Suporte Prioritário -->
                <div class="bg-gray-50 rounded-xl p-8 text-center hover:shadow-lg transition-shadow duration-300">
                    <div class="w-16 h-16 bg-purple-100 rounded-lg flex items-center justify-center mx-auto mb-6">
                        <i class="bi bi-headset text-2xl text-purple-600"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-4">Suporte Prioritário</h3>
                    <p class="text-gray-600 mb-6">
                        Para clientes dos planos Professional e Enterprise. Atendimento em até 4 horas com especialistas
                        dedicados.
                    </p>
                    <a href="#"
                        class="inline-flex items-center px-6 py-3 bg-purple-600 hover:bg-purple-700 text-white font-semibold rounded-lg transition-colors duration-200">
                        <i class="bi bi-headset mr-2"></i>
                        Suporte Prioritário
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- FAQ Section -->
    <section class="py-20 bg-gray-50">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Perguntas Frequentes</h2>
                <p class="text-xl text-gray-600">
                    Respostas para as dúvidas mais comuns sobre o Easy Budget
                </p>
            </div>

            <div class="space-y-6">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Como funciona o teste gratuito?</h3>
                    <p class="text-gray-600">
                        Oferecemos 14 dias de teste gratuito em todos os planos. Durante esse período, você tem acesso
                        completo a todos os recursos sem nenhuma limitação.
                    </p>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Posso migrar meus dados de outro sistema?</h3>
                    <p class="text-gray-600">
                        Sim! Oferecemos ferramentas de importação para diversos formatos (Excel, CSV) e também podemos
                        ajudar com migrações mais complexas.
                    </p>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Quais formas de pagamento são aceitas?</h3>
                    <p class="text-gray-600">
                        Aceitamos cartões de crédito, boleto bancário e PIX. Para planos anuais, oferecemos desconto de 20%.
                    </p>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Como funciona o backup dos dados?</h3>
                    <p class="text-gray-600">
                        Realizamos backups automáticos diários de todos os dados. Além disso, você pode fazer backup manual
                        a qualquer momento e baixar seus dados.
                    </p>
                </div>

                <div class="bg-white rounded-lg shadow-md p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Preciso de conhecimento técnico para usar o
                        sistema?</h3>
                    <p class="text-gray-600">
                        Não! Nossa interface é intuitiva e amigável. Oferecemos treinamento inicial e suporte contínuo para
                        garantir que você aproveite ao máximo a plataforma.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Form -->
    <section class="py-20 bg-white">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Entre em Contato</h2>
                <p class="text-xl text-gray-600">
                    Nossa equipe está pronta para ajudar você
                </p>
            </div>

            <div class="bg-gray-50 rounded-lg p-8">
                <form method="POST" action="{{ route( 'home.contact' ) }}" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    @csrf

                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Nome</label>
                        <input type="text" id="name" name="name" required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                        <input type="email" id="email" name="email" required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                    </div>

                    <div class="md:col-span-2">
                        <label for="subject" class="block text-sm font-medium text-gray-700 mb-2">Assunto</label>
                        <input type="text" id="subject" name="subject" required
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                    </div>

                    <div class="md:col-span-2">
                        <label for="message" class="block text-sm font-medium text-gray-700 mb-2">Mensagem</label>
                        <textarea id="message" name="message" rows="6" required
                            placeholder="Descreva sua dúvida ou problema..."
                            class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"></textarea>
                    </div>

                    <div class="md:col-span-2">
                        <button type="submit"
                            class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-6 rounded-lg transition-colors duration-200">
                            Enviar Mensagem
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <!-- Contact Info -->
    <section class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Outras Formas de Contato</h2>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="text-center">
                    <div class="w-16 h-16 bg-blue-100 rounded-lg flex items-center justify-center mx-auto mb-4">
                        <i class="bi bi-envelope text-2xl text-blue-600"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Email</h3>
                    <p class="text-gray-600 mb-2">suporte@easybudget.com.br</p>
                    <p class="text-sm text-gray-500">Resposta em até 24 horas</p>
                </div>

                <div class="text-center">
                    <div class="w-16 h-16 bg-green-100 rounded-lg flex items-center justify-center mx-auto mb-4">
                        <i class="bi bi-telephone text-2xl text-green-600"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Telefone</h3>
                    <p class="text-gray-600 mb-2">(11) 99999-9999</p>
                    <p class="text-sm text-gray-500">Segunda a Sexta, 9h às 18h</p>
                </div>

                <div class="text-center">
                    <div class="w-16 h-16 bg-purple-100 rounded-lg flex items-center justify-center mx-auto mb-4">
                        <i class="bi bi-chat-dots text-2xl text-purple-600"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Chat Online</h3>
                    <p class="text-gray-600 mb-2">Disponível 24/7</p>
                    <p class="text-sm text-gray-500">Para clientes dos planos pagos</p>
                </div>
            </div>
        </div>
    </section>
@endsection
