@extends( 'layouts.app' )

@section( 'title', 'Suporte - Easy Budget' )
@section( 'description', 'Entre em contato conosco para obter suporte técnico e esclarecer dúvidas' )

@section( 'content' )
    <section class="py-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto">
                    <div class="text-center mb-5">
                        <h1 class="display-5 fw-bold mb-3">
                            <i class="bi bi-headset text-primary me-2"></i>Central de Suporte
                        </h1>
                        <p class="lead text-muted">
                            Nossa equipe está pronta para ajudar você a aproveitar ao máximo o Easy Budget
                        </p>
                    </div>

                    <div class="row g-4 mb-5">
                        <div class="col-md-4">
                            <div class="card h-100 text-center border-0 shadow-sm hover-shadow">
                                <div class="card-body p-4">
                                    <div class="mb-3">
                                        <i class="bi bi-envelope-at display-4 text-primary"></i>
                                    </div>
                                    <h5 class="card-title">Email</h5>
                                    <p class="card-text text-muted">
                                        Envie sua dúvida ou problema detalhado
                                    </p>
                                    <a href="mailto:suporte@easybudget.com.br" class="btn btn-outline-primary">
                                        <i class="bi bi-envelope me-2"></i>Enviar Email
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="card h-100 text-center border-0 shadow-sm hover-shadow">
                                <div class="card-body p-4">
                                    <div class="mb-3">
                                        <i class="bi bi-chat-dots display-4 text-success"></i>
                                    </div>
                                    <h5 class="card-title">Chat Online</h5>
                                    <p class="card-text text-muted">
                                        Converse conosco em tempo real
                                    </p>
                                    <button class="btn btn-outline-success"
                                        onclick="alert('Chat será implementado em breve!')">
                                        <i class="bi bi-chat me-2"></i>Iniciar Chat
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="card h-100 text-center border-0 shadow-sm hover-shadow">
                                <div class="card-body p-4">
                                    <div class="mb-3">
                                        <i class="bi bi-question-circle display-4 text-info"></i>
                                    </div>
                                    <h5 class="card-title">Central de Ajuda</h5>
                                    <p class="card-text text-muted">
                                        Encontre respostas para perguntas frequentes
                                    </p>
                                    <a href="#" class="btn btn-outline-info">
                                        <i class="bi bi-book me-2"></i>Ver Ajuda
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-primary text-white">
                            <h4 class="mb-0">
                                <i class="bi bi-envelope-paper me-2"></i>Fale Conosco
                            </h4>
                        </div>
                        <div class="card-body p-4">
                            <form action="{{ route( 'home.contact' ) }}" method="POST">
                                @csrf
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="name" class="form-label">Nome Completo</label>
                                        <input type="text" class="form-control" id="name" name="name"
                                            value="{{ old( 'name' ) }}" required>
                                        @error( 'name' )
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-md-6">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="email" name="email"
                                            value="{{ old( 'email' ) }}" required>
                                        @error( 'email' )
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-12">
                                        <label for="subject" class="form-label">Assunto</label>
                                        <select class="form-select" id="subject" name="subject" required>
                                            <option value="">Selecione um assunto</option>
                                            <option value="duvida" {{ old( 'subject' ) == 'duvida' ? 'selected' : '' }}>
                                                Dúvida sobre o sistema
                                            </option>
                                            <option value="problema" {{ old( 'subject' ) == 'problema' ? 'selected' : '' }}>
                                                Reportar um problema
                                            </option>
                                            <option value="sugestao" {{ old( 'subject' ) == 'sugestao' ? 'selected' : '' }}>
                                                Sugestão de melhoria
                                            </option>
                                            <option value="comercial" {{ old( 'subject' ) == 'comercial' ? 'selected' : '' }}>
                                                Informações comerciais
                                            </option>
                                            <option value="outros" {{ old( 'subject' ) == 'outros' ? 'selected' : '' }}>
                                                Outros
                                            </option>
                                        </select>
                                        @error( 'subject' )
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-12">
                                        <label for="message" class="form-label">Mensagem</label>
                                        <textarea class="form-control" id="message" name="message" rows="5"
                                            required>{{ old( 'message' ) }}</textarea>
                                        @error( 'message' )
                                            <div class="text-danger small mt-1">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="col-12">
                                        <button type="submit" class="btn btn-primary btn-lg">
                                            <i class="bi bi-send me-2"></i>Enviar Mensagem
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="card shadow-sm">
                        <div class="card-body p-4">
                            <h5 class="card-title mb-3">
                                <i class="bi bi-info-circle text-info me-2"></i>Informações de Atendimento
                            </h5>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-clock text-muted me-3"></i>
                                        <div>
                                            <strong>Horário de Atendimento</strong><br>
                                            <span class="text-muted">Segunda a Sexta: 8h às 18h<br>Sábado: 8h às 12h</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-telephone text-muted me-3"></i>
                                        <div>
                                            <strong>Telefone</strong><br>
                                            <span class="text-muted">(11) 9999-9999</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
