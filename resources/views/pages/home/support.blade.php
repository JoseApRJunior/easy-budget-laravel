{{-- pages/home/support.blade.php --}}
@extends( 'layouts.app' )

@section( 'content' )
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <h1 class="mb-4">
                    <i class="bi bi-headset me-2"></i>
                    Suporte ao Cliente Easy Budget
                </h1>

                {{-- Card de Opções --}}
                <div class="card hover-card mb-4">
                    <div class="card-header text-white">
                        <h2 class="h5 mb-0">
                            <i class="bi bi-question-circle me-2"></i>
                            Como podemos ajudar?
                        </h2>
                    </div>
                    <div class="card-body">
                        <p class="lead">
                            Estamos aqui para ajudar com qualquer dúvida ou problema relacionado ao nosso sistema de
                            orçamentos. Por
                            favor, escolha uma das opções abaixo:
                        </p>
                        <div class="row mt-4">
                            <div class="col-md-6 mb-3">
                                <a href="#faq" class="btn btn-outline-primary w-100">
                                    <i class="bi bi-question-circle me-2"></i>
                                    Perguntas Frequentes
                                </a>
                            </div>
                            <div class="col-md-6 mb-3">
                                <a href="#contact" class="btn btn-outline-primary w-100">
                                    <i class="bi bi-envelope me-2"></i>
                                    Contato Direto
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- FAQ Section --}}
                <div id="faq" class="card hover-card mb-4">
                    <div class="card-header bg-info text-white">
                        <h2 class="h5 mb-0">
                            <i class="bi bi-question-diamond me-2"></i>
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
                <div id="contact" class="card hover-card mb-4">
                    <div class="card-header bg-success text-white">
                        <h2 class="h5 mb-0">
                            <i class="bi bi-envelope me-2"></i>
                            Entre em Contato
                        </h2>
                    </div>
                    <div class="card-body">
                        <form action="/support" method="POST" class="needs-validation" novalidate>
                            {{ csrf_field() }}

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="first_name" class="form-label">Nome</label>
                                    <input type="text" class="form-control" id="first_name" name="first_name" required>
                                    @error( 'first_name' ) <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="last_name" class="form-label">Sobrenome</label>
                                    <input type="text" class="form-control" id="last_name" name="last_name" required>
                                    @error( 'last_name' ) <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">E-mail</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                                @error( 'email' ) <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="mb-3">
                                <label for="subject" class="form-label">Assunto</label>
                                <select class="form-select" id="subject" name="subject" required>
                                    <option value="">Selecione um assunto</option>
                                    <option value="orçamento">Dúvida sobre orçamentos</option>
                                    <option value="pagamento">Questões de pagamento</option>
                                    <option value="técnico">Suporte técnico</option>
                                    <option value="outro">Outro assunto</option>
                                </select>
                                @error( 'subject' ) <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="mb-3">
                                <label for="message" class="form-label">Mensagem</label>
                                <textarea class="form-control" id="message" name="message" rows="4" required></textarea>
                                @error( 'message' ) <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-send me-2"></i>
                                Enviar Mensagem
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
