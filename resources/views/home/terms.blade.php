@extends( 'layouts.guest' )

@section( 'title', 'Termos de Serviço - Easy Budget' )

@section( 'content' )
    <!-- Hero Section -->
    <section class="bg-gradient-to-br from-gray-600 via-gray-700 to-gray-800 text-white py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h1 class="text-4xl md:text-5xl font-bold mb-6">Termos de Serviço</h1>
                <p class="text-xl md:text-2xl text-gray-100 max-w-3xl mx-auto">
                    Conheça os termos e condições para uso da plataforma Easy Budget
                </p>
            </div>
        </div>
    </section>

    <!-- Terms Content -->
    <section class="py-20 bg-white">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="prose prose-lg max-w-none">
                <p class="text-gray-600 mb-8">Última atualização: {{ date( 'd/m/Y' ) }}</p>

                <h2 class="text-2xl font-bold text-gray-900 mb-4">1. Aceitação dos Termos</h2>
                <p class="text-gray-600 mb-6">
                    Ao acessar e usar o Easy Budget, você concorda em cumprir e estar vinculado aos seguintes termos e
                    condições de uso.
                    Se você não concordar com estes termos, não use este serviço.
                </p>

                <h2 class="text-2xl font-bold text-gray-900 mb-4">2. Descrição do Serviço</h2>
                <p class="text-gray-600 mb-6">
                    O Easy Budget é uma plataforma online que oferece ferramentas para gestão financeira, controle de
                    orçamentos,
                    gestão de clientes e geração de relatórios empresariais.
                </p>

                <h2 class="text-2xl font-bold text-gray-900 mb-4">3. Cadastro e Conta</h2>
                <p class="text-gray-600 mb-6">
                    Para utilizar nossos serviços, você deve se cadastrar e manter uma conta pessoal. Você é responsável por
                    fornecer informações precisas e manter a confidencialidade de sua senha.
                </p>

                <h2 class="text-2xl font-bold text-gray-900 mb-4">4. Planos e Pagamentos</h2>
                <p class="text-gray-600 mb-6">
                    Oferecemos diferentes planos de assinatura. Os pagamentos são processados de forma segura através de
                    cartões de crédito, boleto bancário e PIX.
                </p>

                <h2 class="text-2xl font-bold text-gray-900 mb-4">5. Uso Aceitável</h2>
                <p class="text-gray-600 mb-6">
                    Você concorda em usar o Easy Budget apenas para fins legais. Você não deve violar leis ou
                    transmitir vírus ou código malicioso.
                </p>

                <h2 class="text-2xl font-bold text-gray-900 mb-4">6. Propriedade Intelectual</h2>
                <p class="text-gray-600 mb-6">
                    O Easy Budget e todo o seu conteúdo são propriedade da Easy Budget Ltda. e são protegidos pelas
                    leis de direitos autorais.
                </p>

                <h2 class="text-2xl font-bold text-gray-900 mb-4">7. Limitação de Responsabilidade</h2>
                <p class="text-gray-600 mb-6">
                    O Easy Budget é fornecido "como está" sem garantias. Não garantimos que o serviço será ininterrupto
                    ou livre de erros.
                </p>

                <h2 class="text-2xl font-bold text-gray-900 mb-4">8. Modificações dos Termos</h2>
                <p class="text-gray-600 mb-6">
                    Reservamo-nos o direito de modificar estes termos a qualquer momento. O uso continuado do serviço
                    após as modificações constitui aceitação dos novos termos.
                </p>

                <h2 class="text-2xl font-bold text-gray-900 mb-4">9. Contato</h2>
                <p class="text-gray-600 mb-6">
                    Se você tiver dúvidas sobre estes Termos de Serviço, entre em contato conosco:
                </p>
                <ul class="text-gray-600 mb-8 list-disc list-inside">
                    <li>Email: legal@easybudget.com.br</li>
                    <li>Telefone: (11) 99999-9999</li>
                </ul>

                <div class="bg-blue-50 border-l-4 border-blue-400 p-4">
                    <p class="text-sm text-blue-700">
                        <strong>Nota:</strong> Estes termos estão em vigor a partir de {{ date( 'd/m/Y' ) }}.
                    </p>
                </div>
            </div>
        </div>
    </section>
@endsection
