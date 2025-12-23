{{-- pages/home/support.blade.php --}}
@extends( 'layouts.app' )

@section( 'content' )
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-lg-10 col-xl-8">
                <div class="text-center mb-5">
                    <h1 class="display-5 fw-bold text-primary mb-3">
                        <i class="bi bi-headset me-3"></i>
                        Suporte ao Cliente Easy Budget
                    </h1>
                    <p class="lead ">
                        Estamos aqui para ajudar com qualquer dúvida ou problema relacionado ao nosso sistema de orçamentos.
                    </p>
                </div>

                {{-- Card de Opções --}}
                <div class="card border-0 shadow-sm mb-5">
                    <div class="card-body text-center p-5 ">
                        <div class="mb-4">
                            <i class="bi bi-question-circle " style="font-size: 3rem;"></i>
                        </div>
                        <h2 class="h3 mb-4 fw-bold">Como podemos ajudar?</h2>
                        <p class="lead  mb-4">
                            Escolha uma das opções abaixo para obter a ajuda que precisa
                        </p>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <a href="#faq" class="btn btn-primary btn-lg w-100 py-3 shadow-sm">
                                    <i class="bi bi-question-diamond me-2"></i>
                                    <strong>Perguntas Frequentes</strong>
                                    <br><small>Encontre respostas rápidas</small>
                                </a>
                            </div>
                            <div class="col-md-6">
                                <a href="#contact" class="btn btn-success btn-lg w-100 py-3 shadow-sm">
                                    <i class="bi bi-envelope-paper me-2"></i>
                                    <strong>Contato Direto</strong>
                                    <br><small>Fale com nossa equipe</small>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- FAQ Section --}}
                <div id="faq" class="card border-0 shadow-sm mb-5">
                    <div class="card-header bg-primary text-white border-0">
                        <h2 class="h4 mb-0 text-center py-2">
                            <i class="bi bi-question-diamond me-3"></i>
                            Perguntas Frequentes
                        </h2>
                    </div>
                    <div class="card-body">
                        <div class="accordion" id="faqAccordion">
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="faqHeading1">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                        data-bs-target="#faqCollapse1">
                                        <i class="bi bi-plus-circle me-2"></i>
                                        Como faço para criar um novo orçamento para um cliente?
                                    </button>
                                </h2>
                                <div id="faqCollapse1" class="accordion-collapse collapse" aria-labelledby="faqHeading1"
                                    data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        <p>Para criar um novo orçamento:</p>
                                        <ol>
                                            <li>Acesse a seção "Orçamentos" no painel principal</li>
                                            <li>Clique em "Criar Orçamento"</li>
                                            <li>Pesquise um cliente já cadastrado</li>
                                            <li>Defina a data de previsão de vencimento</li>
                                            <li>Adicione uma descrição (opcional)</li>
                                            <li>Selecione o orçamento e adicione serviços</li>
                                            <li>Clique em "Salvar Orçamento"</li>
                                        </ol>
                                    </div>
                                </div>
                            </div>

                            <div class="accordion-item">
                                <h2 class="accordion-header" id="faqHeading2">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                        data-bs-target="#faqCollapse2">
                                        <i class="bi bi-clipboard-data me-2"></i>
                                        Como posso acompanhar o status dos meus orçamentos?
                                    </button>
                                </h2>
                                <div id="faqCollapse2" class="accordion-collapse collapse" aria-labelledby="faqHeading2"
                                    data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        <p class="mb-3">
                                            Na seção "Meus Orçamentos", você encontrará uma lista de todos os seus
                                            orçamentos com seus
                                            respectivos status:
                                        </p>
                                        <ul class="list-group mb-4">
                                            <li class="list-group-item">
                                                <i class="bi bi-circle me-2 text-primary"></i>
                                                <strong>NOVO:</strong>
                                                Orçamento criado, mas ainda não foi iniciado.
                                            </li>
                                            <li class="list-group-item">
                                                <i class="bi bi-arrow-repeat me-2 text-info"></i>
                                                <strong>EM PROGRESSO:</strong>
                                                Orçamento em andamento, com serviços sendo adicionados ou atualizados.
                                            </li>
                                            <li class="list-group-item">
                                                <i class="bi bi-check-circle me-2 text-success"></i>
                                                <strong>COMPLETADO:</strong>
                                                Orçamento finalizado, com todos os serviços concluídos.
                                            </li>
                                            <li class="list-group-item">
                                                <i class="bi bi-x-circle me-2 text-danger"></i>
                                                <strong>CANCELADO:</strong>
                                                Orçamento cancelado, não será mais utilizado.
                                            </li>
                                        </ul>

                                        <p class="mb-3">
                                            Você pode clicar em cada orçamento para ver mais detalhes e atualizações,
                                            incluindo:
                                        </p>
                                        <ul class="list-group">
                                            <li class="list-group-item">
                                                <i class="bi bi-list-check me-2"></i>
                                                Lista de serviços associados ao orçamento
                                            </li>
                                            <li class="list-group-item">
                                                <i class="bi bi-toggle-on me-2"></i>
                                                Status de cada serviço (ativo, inativo, deletado)
                                            </li>
                                            <li class="list-group-item">
                                                <i class="bi bi-calendar-event me-2"></i>
                                                Data de previsão de vencimento do orçamento
                                            </li>
                                            <li class="list-group-item">
                                                <i class="bi bi-file-text me-2"></i>
                                                Descrição do orçamento
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>

                            <div class="accordion-item">
                                <h2 class="accordion-header" id="faqHeading3">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse"
                                        data-bs-target="#faqCollapse3">
                                        <i class="bi bi-graph-up me-2"></i>
                                        Como faço para gerar relatórios financeiros?
                                    </button>
                                </h2>
                                <div id="faqCollapse3" class="accordion-collapse collapse" aria-labelledby="faqHeading3"
                                    data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        <p class="mb-0">
                                            Acesse a seção "Relatórios" no menu principal. Lá você encontrará opções para
                                            gerar:
                                        </p>
                                        <ul class="list-group mt-3">
                                            <li class="list-group-item">
                                                <i class="bi bi-cash me-2"></i>
                                                Relatórios de faturamento
                                            </li>
                                            <li class="list-group-item">
                                                <i class="bi bi-check-square me-2"></i>
                                                Orçamentos aprovados
                                            </li>
                                            <li class="list-group-item">
                                                <i class="bi bi-star me-2"></i>
                                                Serviços mais solicitados
                                            </li>
                                        </ul>
                                        <p class="mt-3 mb-0">
                                            Você pode personalizar o período e os tipos de dados que deseja incluir em cada
                                            relatório.
                                        </p>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                {{-- Contact Form --}}
                <div id="contact" class="card border-0 shadow-sm mb-5">
                    <div class="card-header bg-success border-0">
                        <h2 class="h4 mb-0 text-center py-2">
                            <i class="bi bi-envelope-paper me-3"></i>
                            Entre em Contato
                        </h2>
                    </div>
                    <div class="card-body p-4">

                        <div class="text-center mb-4">
                            <p class="text-muted">Preencha o formulário abaixo e nossa equipe entrará em contato em breve.
                            </p>
                        </div>
                    </div>
                    <div class="card-body">

                        <form action="{{ route( 'support' ) }}" method="POST" class="needs-validation " novalidate>
                            @csrf

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="first_name" class="form-label fw-semibold">
                                        <i class="bi bi-person me-2"></i>Nome
                                    </label>
                                    <input type="text"
                                        class="form-control form-control-lg @error( 'first_name' ) is-invalid @enderror"
                                        id="first_name" name="first_name" value="{{ old( 'first_name' ) }}" required>
                                    @error( 'first_name' )
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-6">
                                    <label for="last_name" class="form-label fw-semibold">
                                        <i class="bi bi-person me-2"></i>Sobrenome
                                    </label>
                                    <input type="text"
                                        class="form-control form-control-lg @error( 'last_name' ) is-invalid @enderror"
                                        id="last_name" name="last_name" value="{{ old( 'last_name' ) }}" required>
                                    @error( 'last_name' )
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="email" class="form-label fw-semibold">
                                    <i class="bi bi-envelope me-2"></i>E-mail
                                </label>
                                <input type="email"
                                    class="form-control form-control-lg @error( 'email' ) is-invalid @enderror" id="email"
                                    name="email" value="{{ old( 'email' ) }}" required>
                                @error( 'email' )
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-4">
                                <label for="subject" class="form-label fw-semibold">
                                    <i class="bi bi-tag me-2"></i>Assunto
                                </label>
                                <select class="form-select form-select-lg @error( 'subject' ) is-invalid @enderror"
                                    id="subject" name="subject" required>
                                    <option value="">Selecione um assunto</option>
                                    <option value="orçamento" {{ old( 'subject' ) == 'orçamento' ? 'selected' : '' }}>
                                        Dúvida sobre orçamentos
                                    </option>
                                    <option value="pagamento" {{ old( 'subject' ) == 'pagamento' ? 'selected' : '' }}>
                                        Questões de pagamento
                                    </option>
                                    <option value="técnico" {{ old( 'subject' ) == 'técnico' ? 'selected' : '' }}>
                                        Suporte técnico
                                    </option>
                                    <option value="outro" {{ old( 'subject' ) == 'outro' ? 'selected' : '' }}>
                                        Outro assunto
                                    </option>
                                </select>
                                @error( 'subject' )
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-4">
                                <label for="message" class="form-label fw-semibold">
                                    <i class="bi bi-chat-text me-2"></i>Mensagem
                                    <small class="text-muted">(Mínimo 10 caracteres)</small>
                                </label>
                                <textarea class="form-control @error( 'message' ) is-invalid @enderror" id="message"
                                    name="message" rows="5" placeholder="Descreva sua dúvida ou problema em detalhes..."
                                    maxlength="2000" required>{{ old( 'message' ) }}</textarea>

                                <!-- Contador de caracteres -->
                                <div class="form-text">
                                    <small id="message-counter" class="text-muted">
                                        <span id="current-chars">0</span>/2000 caracteres
                                    </small>
                                </div>

                                @error( 'message' )
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="d-grid">
                                <button type="submit" id="submit-btn" class="btn btn-success btn-lg" disabled>
                                    <i class="bi bi-send me-2"></i>
                                    Enviar Mensagem
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    {{-- JavaScript para contador de caracteres --}}
    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const messageTextarea = document.getElementById('message');
        const counterElement = document.getElementById('current-chars');
        const counterContainer = document.getElementById('message-counter');
        const submitBtn = document.getElementById('submit-btn');

        function updateCounter() {
            const currentLength = messageTextarea.value.length;
            const maxLength = 2000;
            const minLength = 10;

            // Atualiza contador
            counterElement.textContent = currentLength;

            // Validação visual
            if (currentLength < minLength && currentLength > 0) {
                counterContainer.innerHTML = '<span class="text-warning">' + currentLength + '</span>/2000 caracteres (mínimo ' + minLength + ')';
                submitBtn.disabled = true;
                submitBtn.classList.remove('btn-success');
                submitBtn.classList.add('btn-secondary');
            } else if (currentLength >= minLength) {
                counterContainer.innerHTML = '<span class="text-success">' + currentLength + '</span>/2000 caracteres ✓';
                submitBtn.disabled = false;
                submitBtn.classList.remove('btn-secondary');
                submitBtn.classList.add('btn-success');
            } else {
                counterContainer.innerHTML = '<span id="current-chars">' + currentLength + '</span>/2000 caracteres';
                submitBtn.disabled = true;
                submitBtn.classList.remove('btn-success');
                submitBtn.classList.add('btn-secondary');
            }

            // Validação de limite máximo
            if (currentLength >= maxLength) {
                messageTextarea.value = messageTextarea.value.substring(0, maxLength);
                updateCounter();
            }
        }

        // Eventos
        messageTextarea.addEventListener('input', updateCounter);
        messageTextarea.addEventListener('keyup', updateCounter);
        messageTextarea.addEventListener('paste', function() {
            setTimeout(updateCounter, 0);
        });

        // Inicialização
        updateCounter();
    });
    </script>
    @endpush

    {{-- Informações de Contato Adicionais --}}
    <div class="card py-3 mt-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="text-center">
                        <h3 class="mb-4 fw-bold">Outras Formas de Contato</h3>
                        <div class="row g-4">
                            <div class="col-md-4">
                                <div class="text-center p-3">
                                    <i class="bi bi-envelope-at text-primary" style="font-size: 2.5rem;"></i>
                                    <h5 class="mt-3">E-mail</h5>
                                    <p class="mb-2">suporte@easybudget.net.br</p>
                                    <a href="mailto:suporte@easybudget.net.br" class="btn btn-outline-primary btn-sm">
                                        Enviar E-mail
                                    </a>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-center p-3">
                                    <i class="bi bi-clock text-success" style="font-size: 2.5rem;"></i>
                                    <h5 class="mt-3">Horário</h5>
                                    <p class="mb-2">Segunda a Sexta<br>08:00 às 18:00</p>
                                    <small>Horário de Brasília</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-center p-3">
                                    <i class="bi bi-telephone text-info" style="font-size: 2.5rem;"></i>
                                    <h5 class="mt-3">Telefone</h5>
                                    <p class="mb-2">(43) 99959-0945</p>
                                    <a href="tel:+5543999590945" class="btn btn-outline-info btn-sm">
                                        Ligar Agora
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
