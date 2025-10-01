@extends( 'layouts.guest' )

@section( 'title', 'Política de Privacidade - Easy Budget' )

@section( 'content' )
    <!-- Hero Section -->
    <section class="bg-gradient-to-br from-purple-600 via-purple-700 to-indigo-800 text-white py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center">
                <h1 class="text-4xl md:text-5xl font-bold mb-6">Política de Privacidade</h1>
                <p class="text-xl md:text-2xl text-purple-100 max-w-3xl mx-auto">
                    Sua privacidade é nossa prioridade. Conheça como protegemos seus dados.
                </p>
            </div>
        </div>
    </section>

    <!-- Privacy Content -->
    <section class="py-20 bg-white">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="prose prose-lg max-w-none">
                <p class="text-gray-600 mb-8">Última atualização: {{ date( 'd/m/Y' ) }}</p>

                <h2 class="text-2xl font-bold text-gray-900 mb-4">1. Informações Gerais</h2>
                <p class="text-gray-600 mb-6">
                    A presente Política de Privacidade descreve como o Easy Budget coleta, usa, armazena e protege suas
                    informações pessoais.
                    Ao utilizar nossos serviços, você concorda com a coleta e uso de informações de acordo com esta
                    política.
                </p>

                <h2 class="text-2xl font-bold text-gray-900 mb-4">2. Informações que Coletamos</h2>
                <p class="text-gray-600 mb-4">
                    Coletamos os seguintes tipos de informações:
                </p>
                <ul class="text-gray-600 mb-6 list-disc list-inside">
                    <li><strong>Informações de cadastro:</strong> Nome, email, telefone e senha</li>
                    <li><strong>Informações da empresa:</strong> Razão social, CNPJ, endereço</li>
                    <li><strong>Dados de uso:</strong> Como você interage com nossa plataforma</li>
                    <li><strong>Informações técnicas:</strong> Endereço IP, tipo de navegador, cookies</li>
                    <li><strong>Dados financeiros:</strong> Informações de orçamentos, faturas e pagamentos</li>
                </ul>

                <h2 class="text-2xl font-bold text-gray-900 mb-4">3. Como Usamos suas Informações</h2>
                <p class="text-gray-600 mb-4">
                    Utilizamos suas informações para:
                </p>
                <ul class="text-gray-600 mb-6 list-disc list-inside">
                    <li>Fornecer e manter nossos serviços</li>
                    <li>Processar transações e gerenciar sua conta</li>
                    <li>Enviar comunicações importantes sobre o serviço</li>
                    <li>Melhorar nossa plataforma e desenvolver novos recursos</li>
                    <li>Fornecer suporte ao cliente</li>
                    <li>Cumprir obrigações legais</li>
                </ul>

                <h2 class="text-2xl font-bold text-gray-900 mb-4">4. Compartilhamento de Informações</h2>
                <p class="text-gray-600 mb-6">
                    Não vendemos, comercializamos ou transferimos suas informações pessoais para terceiros, exceto:
                </p>
                <ul class="text-gray-600 mb-6 list-disc list-inside">
                    <li>Com seu consentimento explícito</li>
                    <li>Para processadores de pagamento (ex: Mercado Pago, Stripe)</li>
                    <li>Para provedores de serviços em nuvem (ex: AWS, Google Cloud)</li>
                    <li>Quando exigido por lei ou para proteger nossos direitos</li>
                    <li>Em caso de fusão, aquisição ou venda de ativos</li>
                </ul>

                <h2 class="text-2xl font-bold text-gray-900 mb-4">5. Segurança dos Dados</h2>
                <p class="text-gray-600 mb-6">
                    Implementamos medidas de segurança técnicas, administrativas e físicas apropriadas para proteger suas
                    informações pessoais contra acesso não autorizado, alteração, divulgação ou destruição. Utilizamos
                    criptografia SSL/TLS para proteger dados em trânsito e em repouso.
                </p>

                <h2 class="text-2xl font-bold text-gray-900 mb-4">6. Cookies e Tecnologias Similares</h2>
                <p class="text-gray-600 mb-6">
                    Utilizamos cookies e tecnologias similares para melhorar sua experiência, analisar o uso do site e
                    personalizar conteúdo. Você pode controlar o uso de cookies através das configurações do seu navegador.
                </p>

                <h2 class="text-2xl font-bold text-gray-900 mb-4">7. Retenção de Dados</h2>
                <p class="text-gray-600 mb-6">
                    Mantemos suas informações pessoais apenas pelo tempo necessário para cumprir os propósitos descritos
                    nesta política, a menos que um período de retenção mais longo seja exigido ou permitido por lei.
                </p>

                <h2 class="text-2xl font-bold text-gray-900 mb-4">8. Seus Direitos</h2>
                <p class="text-gray-600 mb-4">
                    De acordo com a LGPD (Lei Geral de Proteção de Dados), você tem os seguintes direitos:
                </p>
                <ul class="text-gray-600 mb-6 list-disc list-inside">
                    <li>Confirmação da existência de tratamento de dados</li>
                    <li>Acesso aos seus dados</li>
                    <li>Correção de dados incompletos, inexatos ou desatualizados</li>
                    <li>Anonimização, bloqueio ou eliminação de dados desnecessários</li>
                    <li>Portabilidade dos dados</li>
                    <li>Eliminação dos dados tratados com consentimento</li>
                    <li>Revogação do consentimento</li>
                </ul>

                <h2 class="text-2xl font-bold text-gray-900 mb-4">9. Menores de Idade</h2>
                <p class="text-gray-600 mb-6">
                    Nossos serviços não são direcionados a menores de 18 anos. Não coletamos intencionalmente informações
                    pessoais de menores de idade. Se você é pai/mãe ou responsável e acredita que seu filho nos forneceu
                    informações pessoais, entre em contato conosco.
                </p>

                <h2 class="text-2xl font-bold text-gray-900 mb-4">10. Alterações nesta Política</h2>
                <p class="text-gray-600 mb-6">
                    Podemos atualizar nossa Política de Privacidade periodicamente. Notificaremos você sobre qualquer
                    mudança
                    publicando a nova política nesta página e atualizando a data de "última atualização".
                </p>

                <h2 class="text-2xl font-bold text-gray-900 mb-4">11. Contato</h2>
                <p class="text-gray-600 mb-6">
                    Se você tiver dúvidas sobre esta Política de Privacidade ou sobre nossas práticas de dados, entre em
                    contato:
                </p>
                <ul class="text-gray-600 mb-8 list-disc list-inside">
                    <li>Email: privacidade@easybudget.com.br</li>
                    <li>Telefone: (11) 99999-9999</li>
                    <li>Endereço: São Paulo/SP - Brasil</li>
                </ul>

                <div class="bg-green-50 border-l-4 border-green-400 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <i class="bi bi-shield-check text-green-400"></i>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-green-700">
                                <strong>Compromisso:</strong> Estamos comprometidos em proteger sua privacidade e garantir
                                a segurança dos seus dados pessoais.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
