<div class="tab-pane fade {{ $activeTab === 'provider' ? 'show active' : '' }}" id="provider">
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-transparent border-0">
            <h3 class="h5 mb-0">Informações do Provider</h3>
            <p class="text-muted small mb-0 mt-1">Dados da empresa ou consultório</p>
        </div>
        <div class="card-body">
            @if($tabs['provider']['data']['provider'])
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="card border">
                            <div class="card-body">
                                <h6 class="card-title text-primary">Dados da Empresa</h6>
                                <dl class="row mb-0">
                                    <dt class="col-sm-4">Nome:</dt>
                                    <dd class="col-sm-8">{{ $tabs['provider']['data']['provider']->name ?? 'Não informado' }}</dd>

                                    <dt class="col-sm-4">CNPJ:</dt>
                                    <dd class="col-sm-8">{{ $tabs['provider']['data']['provider']->cnpj ?? 'Não informado' }}</dd>

                                    <dt class="col-sm-4">Telefone:</dt>
                                    <dd class="col-sm-8">{{ $tabs['provider']['data']['provider']->phone ?? 'Não informado' }}</dd>

                                    <dt class="col-sm-4">Email:</dt>
                                    <dd class="col-sm-8">{{ $tabs['provider']['data']['provider']->email ?? 'Não informado' }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card border">
                            <div class="card-body">
                                <h6 class="card-title text-primary">Endereço</h6>
                                <dl class="row mb-0">
                                    <dt class="col-sm-4">Rua:</dt>
                                    <dd class="col-sm-8">{{ $tabs['provider']['data']['provider']->street ?? 'Não informado' }}</dd>

                                    <dt class="col-sm-4">Número:</dt>
                                    <dd class="col-sm-8">{{ $tabs['provider']['data']['provider']->number ?? 'Não informado' }}</dd>

                                    <dt class="col-sm-4">Bairro:</dt>
                                    <dd class="col-sm-8">{{ $tabs['provider']['data']['provider']->neighborhood ?? 'Não informado' }}</dd>

                                    <dt class="col-sm-4">Cidade:</dt>
                                    <dd class="col-sm-8">{{ $tabs['provider']['data']['provider']->city ?? 'Não informado' }}</dd>

                                    <dt class="col-sm-4">Estado:</dt>
                                    <dd class="col-sm-8">{{ $tabs['provider']['data']['provider']->state ?? 'Não informado' }}</dd>

                                    <dt class="col-sm-4">CEP:</dt>
                                    <dd class="col-sm-8">{{ $tabs['provider']['data']['provider']->zip_code ?? 'Não informado' }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-12">
                        <div class="card border">
                            <div class="card-body">
                                <h6 class="card-title text-primary">Informações Adicionais</h6>
                                <dl class="row mb-0">
                                    <dt class="col-sm-2">Descrição:</dt>
                                    <dd class="col-sm-10">{{ $tabs['provider']['data']['provider']->description ?? 'Não informado' }}</dd>

                                    <dt class="col-sm-2">Website:</dt>
                                    <dd class="col-sm-10">
                                        @if($tabs['provider']['data']['provider']->website)
                                            <a href="{{ $tabs['provider']['data']['provider']->website }}" target="_blank">
                                                {{ $tabs['provider']['data']['provider']->website }}
                                            </a>
                                        @else
                                            Não informado
                                        @endif
                                    </dd>

                                    <dt class="col-sm-2">Status:</dt>
                                    <dd class="col-sm-10">
                                        @if($tabs['provider']['data']['provider']->status === 'active')
                                            <span class="badge bg-success">Ativo</span>
                                        @elseif($tabs['provider']['data']['provider']->status === 'inactive')
                                            <span class="badge bg-warning">Inativo</span>
                                        @else
                                            <span class="badge bg-secondary">{{ $tabs['provider']['data']['provider']->status ?? 'Desconhecido' }}</span>
                                        @endif
                                    </dd>

                                    <dt class="col-sm-2">Criado em:</dt>
                                    <dd class="col-sm-10">{{ $tabs['provider']['data']['provider']->created_at ? $tabs['provider']['data']['provider']->created_at->format('d/m/Y H:i') : 'Não disponível' }}</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <a href="{{ route('provider.business.edit') }}" class="btn btn-primary">
                        <i class="bi bi-pencil me-2"></i>Editar Informações
                    </a>
                </div>
            @else
                <div class="text-center py-5">
                    <i class="bi bi-building text-muted" style="font-size: 3rem;"></i>
                    <p class="text-muted mt-3 mb-4">Nenhuma informação de provider disponível.</p>
                    <a href="{{ route('provider.business.edit') }}" class="btn btn-primary">
                        <i class="bi bi-plus me-2"></i>Cadastrar Empresa
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
