{{-- pages/home/support.blade.php --}}
@extends( 'layouts.app' )

@section( 'content' )
    <x-layout.page-container padding="py-4">
        <x-layout.page-header
            title="Suporte ao Cliente"
            icon="headset"
            description="Estamos aqui para ajudar com qualquer dúvida ou problema relacionado ao Easy Budget."
        />

        {{-- Card de Opções Rápidas --}}
        <x-ui.card class="mb-4 mt-4">
            <x-layout.v-stack gap="3" class="py-4 align-items-center">
                <x-ui.icon-box icon="question-circle" variant="primary" size="lg" />
                <h2 class="h4 fw-bold mb-0">Como podemos ajudar hoje?</h2>
                <p class="text-muted mb-1">
                    Escolha uma das opções abaixo para obter a ajuda que precisa
                </p>
                <x-layout.grid-row class="justify-content-center w-100 mb-0">
                    <x-layout.grid-col size="col-md-5 col-lg-4">
                        <x-ui.button href="#faq" variant="outline-primary" class="w-100 py-3 shadow-sm">
                            <x-layout.v-stack gap="1" class="align-items-center">
                                <i class="bi bi-question-diamond fs-4"></i>
                                <span class="fw-bold">Perguntas Frequentes</span>
                                <small class="opacity-75">Respostas rápidas</small>
                            </x-layout.v-stack>
                        </x-ui.button>
                    </x-layout.grid-col>
                    <x-layout.grid-col size="col-md-5 col-lg-4">
                        <x-ui.button href="#contact" variant="primary" class="w-100 py-3 shadow-sm">
                            <x-layout.v-stack gap="1" class="align-items-center">
                                <i class="bi bi-envelope-paper fs-4"></i>
                                <span class="fw-bold">Contato Direto</span>
                                <small class="opacity-75">Fale com nossa equipe</small>
                            </x-layout.v-stack>
                        </x-ui.button>
                    </x-layout.grid-col>
                </x-layout.grid-row>
            </x-layout.v-stack>
        </x-ui.card>

        <x-layout.grid-row class="mb-4">
            {{-- FAQ Section --}}
            <x-layout.grid-col size="col-lg-7" id="faq">
                <x-ui.card>
                    <x-slot:header>
                        <x-layout.h-stack gap="2">
                            <i class="bi bi-question-diamond fs-5 text-primary"></i>
                            <h2 class="h5 mb-0 text-dark fw-bold">Perguntas Frequentes</h2>
                        </x-layout.h-stack>
                    </x-slot:header>

                    <x-ui.accordion
                        flush
                        id="faqAccordion"
                        :items="[
                            [
                                'title' => 'Como faço para criar um novo orçamento?',
                                'content' => 'Para criar um novo orçamento, acesse a seção <strong>Orçamentos</strong> no menu lateral, clique em <strong>Novo Orçamento</strong> e siga os passos para selecionar o cliente e adicionar os serviços desejados.'
                            ],
                            [
                                'title' => 'Como acompanho o status dos orçamentos?',
                                'content' => 'Na listagem de orçamentos, você verá etiquetas coloridas indicando se o orçamento é <strong>Novo</strong>, está <strong>Em Aberto</strong>, foi <strong>Aprovado</strong> ou <strong>Finalizado</strong>.'
                            ],
                            [
                                'title' => 'Como gerar relatórios financeiros?',
                                'content' => 'No seu <strong>Dashboard</strong>, você tem uma visão geral em tempo real. Para relatórios detalhados, utilize o menu <strong>Relatórios</strong> onde poderá filtrar por período e categoria.'
                            ]
                        ]"
                    />
                </x-ui.card>
            </x-layout.grid-col>

            {{-- Contact Form --}}
            <x-layout.grid-col size="col-lg-5" id="contact">
                <x-ui.card>
                    <x-slot:header>
                        <x-layout.h-stack gap="2">
                            <i class="bi bi-envelope-paper fs-5 text-success"></i>
                            <h2 class="h5 mb-0 text-dark fw-bold">Envie uma Mensagem</h2>
                        </x-layout.h-stack>
                    </x-slot:header>

                    <x-ui.form.form action="{{ route('support.store') }}" class="needs-validation" novalidate>
                        <x-ui.form.row gap="3" class="mb-3">
                            <x-slot:left>
                                <x-ui.form.input
                                    name="first_name"
                                    label="Nome"
                                    placeholder="Seu nome"
                                    required
                                    :value="old('first_name')"
                                />
                            </x-slot:left>
                            <x-slot:right>
                                <x-ui.form.input
                                    name="last_name"
                                    label="Sobrenome"
                                    placeholder="Seu sobrenome"
                                    required
                                    :value="old('last_name')"
                                />
                            </x-slot:right>
                        </x-ui.form.row>

                        <x-ui.form.row cols="1" class="mb-3">
                            <x-ui.form.input
                                type="email"
                                name="email"
                                label="E-mail"
                                placeholder="seu@email.com"
                                required
                                :value="old('email')"
                            />
                        </x-ui.form.row>

                        <x-ui.form.row cols="1" class="mb-3">
                            <x-ui.form.select
                                name="subject"
                                label="Assunto"
                                placeholder="Selecione um assunto"
                                required
                            >
                                <x-ui.form.option value="orçamento" :selected="old('subject') == 'orçamento'">Dúvida sobre orçamentos</x-ui.form.option>
                                <x-ui.form.option value="pagamento" :selected="old('subject') == 'pagamento'">Questões de pagamento</x-ui.form.option>
                                <x-ui.form.option value="técnico" :selected="old('subject') == 'técnico'">Suporte técnico</x-ui.form.option>
                                <x-ui.form.option value="outro" :selected="old('subject') == 'outro'">Outro assunto</x-ui.form.option>
                            </x-ui.form.select>
                        </x-ui.form.row>

                        <x-ui.form.row cols="1" class="mb-4">
                            <x-ui.form.textarea
                                name="message"
                                label="Mensagem"
                                rows="5"
                                placeholder="Descreva sua dúvida ou problema..."
                                required
                                maxlength="2000"
                            >{{ old('message') }}</x-ui.form.textarea>
                            <x-layout.h-stack gap="2" justify="between" class="form-text">
                                <small class="text-muted">Mínimo 10 caracteres</small>
                                <small id="message-counter" class="text-muted"><span id="current-chars">0</span>/2000</small>
                            </x-layout.h-stack>
                        </x-ui.form.row>

                        <x-ui.button type="submit" variant="success" class="w-100 py-2" id="submit-btn" disabled>
                            <i class="bi bi-send me-2"></i> Enviar Mensagem
                        </x-ui.button>
                    </x-ui.form.form>
                </x-ui.card>
            </x-layout.grid-col>
        </x-layout.grid-row>

        {{-- Nova Row para Formas de Contato Adicionais --}}
        <x-layout.grid-row >
            <x-layout.grid-col size="col-md-6">
                <x-ui.card class="h-100">
                    <x-layout.h-stack gap="3" align="center">
                        <x-ui.icon-box icon="envelope-at" variant="primary" size="md" />
                        <x-layout.v-stack gap="0">
                            <h3 class="h6 fw-bold mb-1">E-mail</h3>
                            <p class="small text-muted mb-0">suporte@easybudget.net.br</p>
                        </x-layout.v-stack>
                    </x-layout.h-stack>
                </x-ui.card>
            </x-layout.grid-col>
            <x-layout.grid-col size="col-md-6">
                <x-ui.card class="h-100">
                    <x-layout.h-stack gap="3" align="center">
                        <x-ui.icon-box icon="telephone" variant="success" size="md" />
                        <x-layout.v-stack gap="0">
                            <h3 class="h6 fw-bold mb-1">Telefone / Whatsapp</h3>
                            <p class="small text-muted mb-0">(43) 99959-0945</p>
                        </x-layout.v-stack>
                    </x-layout.h-stack>
                </x-ui.card>
            </x-layout.grid-col>
        </x-layout.grid-row>
    </x-layout.page-container>

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const messageTextarea = document.querySelector('textarea[name="message"]');
            const counterElement = document.getElementById('current-chars');
            const submitBtn = document.getElementById('submit-btn');

            function updateCounter() {
                const currentLength = messageTextarea.value.length;
                const minLength = 10;

                counterElement.textContent = currentLength;

                if (currentLength >= minLength) {
                    submitBtn.disabled = false;
                    counterElement.classList.remove('text-danger');
                    counterElement.classList.add('text-success');
                } else {
                    submitBtn.disabled = true;
                    if (currentLength > 0) {
                        counterElement.classList.add('text-danger');
                    }
                }
            }

            messageTextarea.addEventListener('input', updateCounter);
            updateCounter();
        });
    </script>
    @endpush
@endsection
