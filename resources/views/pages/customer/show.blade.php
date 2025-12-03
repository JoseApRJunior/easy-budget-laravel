@extends( 'layouts.app' )

@section( 'content' )
    <div class="container-fluid py-1">
        <!-- Cabeçalho -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0 d-flex align-items-center">
                <i class="bi bi-person-badge-fill me-2 "></i>
                <span>Detalhes do Cliente</span>
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route( 'provider.dashboard' ) }}"
                            class="text-decoration-none">Dashboard</a>
                    </li>
                    <li class="breadcrumb-item"><a href="{{ route( 'provider.customers.index' ) }}"
                            class="text-decoration-none">Clientes</a></li>
                    <li class="breadcrumb-item active">{{ $customer->commonData?->first_name }}
                        {{ $customer->commonData?->last_name }}
                    </li>
                </ol>
            </nav>
        </div>

        <div class="row g-4">
            <!-- Informações Pessoais -->
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100 card-hover">
                    <div class="card-header bg-transparent border-0 py-1">
                        <h5 class="card-title mb-0 d-flex align-items-center">
                            <i class="bi bi-person-fill me-2 "></i>
                            <span>Informações Pessoais</span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="info-item mb-3 ">
                            <label class="text-muted small d-block mb-1">Nome Completo</label>
                            <p class="mb-0 d-flex align-items-center fw-bold">{{ $customer->commonData?->first_name }}
                                {{ $customer->commonData?->last_name }}
                            </p>
                            @if ( $customer->status === 'active' )
                                <span class="badge bg-success mt-2">
                                    <i class="bi bi-check-circle-fill me-1"></i>Ativo
                                </span>
                            @endif
                            <div class="decoration-line"></div>
                        </div>

                        @php
                            $personal_info = [
                                'Email'              => [ 'icon' => 'envelope-fill', 'value' => $customer->contact?->email_personal ],
                                'Email Comercial'    => [ 'icon' => 'envelope-fill', 'value' => $customer->contact?->email_business ],
                                'Telefone'           => [ 'icon' => 'phone-fill', 'value' => $customer->contact?->phone_personal ],
                                'Telefone Comercial' => [ 'icon' => 'telephone-fill', 'value' => $customer->contact?->phone_business ],
                            ];
                        @endphp

                        @foreach ( $personal_info as $key => $info )
                            @if( $info[ 'value' ] )
                                <div class="info-item mb-3">
                                    <label class="text-muted small d-block mb-1">{{ str_replace( '_', ' ', $key ) }}</label>
                                    <p class="mb-0 d-flex align-items-center">
                                        <i class="bi bi-{{ $info[ 'icon' ] }} me-2 "></i>
                                        @if( $key === 'Email' || $key === 'Email Comercial' )
                                            <a href="mailto:{{ $info[ 'value' ] }}"
                                                class="text-decoration-none">{{ $info[ 'value' ] }}</a>
                                        @elseif( $key === 'Telefone' || $key === 'Telefone Comercial' )
                                            <a href="tel:{{ preg_replace( '/\D/', '', $info[ 'value' ] ) }}"
                                                class="text-decoration-none">{{ $info[ 'value' ] }}</a>
                                        @else
                                            <span>{{ $info[ 'value' ] }}</span>
                                        @endif
                                    </p>
                                </div>
                            @endif
                        @endforeach

                        <!-- Data de cadastro -->
                        <div class="info-item mb-3">
                            <label class="text-muted small d-block mb-1">Cadastrado em</label>
                            <p class="mb-0 d-flex align-items-center">
                                <i class="bi bi-calendar-plus me-2"></i>
                                <span>{{ \Carbon\Carbon::parse( $customer->created_at )->format( 'd/m/Y H:i' ) }}</span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Informações Específicas -->
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100 card-hover">
                    <div class="card-header bg-transparent border-0 py-1">
                        <h5 class="card-title mb-0 d-flex align-items-center">
                            <i class="bi bi-briefcase-fill me-2 "></i>
                            <span>Informações Específicas</span>
                        </h5>
                    </div>
                    <div class="card-body">
                        @if ( $customer->isCompany() )
                            <!-- Campos PJ -->
                            @php
                                $specific_info = [
                                    'Razão Social'         => [ 'icon' => 'building', 'value' => $customer->commonData?->company_name ],
                                    'Nome Fantasia'        => [ 'icon' => 'building-fill', 'value' => $customer->commonData?->fantasy_name ],
                                    'CNPJ'                 => [ 'icon' => 'card-text', 'value' => format_cnpj( $customer->commonData?->cnpj ) ],
                                    'Inscrição Estadual'   => [ 'icon' => 'file-earmark-text', 'value' => $customer->commonData?->state_registration ],
                                    'Inscrição Municipal'  => [ 'icon' => 'file-earmark-text-fill', 'value' => $customer->commonData?->municipal_registration ],
                                    'Data de Fundação'     => [ 'icon' => 'calendar-event', 'value' => $customer->commonData?->founding_date ? \Carbon\Carbon::parse( $customer->commonData->founding_date )->format( 'd/m/Y' ) : '' ],
                                    'Email Empresarial'    => [ 'icon' => 'envelope-at', 'value' => $customer->contact?->email_business ],
                                    'Telefone Empresarial' => [ 'icon' => 'telephone', 'value' => $customer->contact?->phone_business ],
                                    'Website'              => [ 'icon' => 'globe', 'value' => $customer->contact?->website ],
                                    'Setor de Atuação'     => [ 'icon' => 'diagram-3', 'value' => $customer->commonData?->industry ],
                                    'Porte da Empresa'     => [ 'icon' => 'building-gear', 'value' => $customer->commonData?->company_size ? ucfirst( $customer->commonData->company_size ) : '' ],
                                ];
                            @endphp
                        @else
                            <!-- Campos PF -->
                            @php
                                $specific_info = [
                                    'CPF'                => [ 'icon' => 'person-badge', 'value' => format_cpf( $customer->commonData?->cpf ) ],
                                    'Data de Nascimento' => [ 'icon' => 'calendar-fill', 'value' => $customer->commonData?->birth_date ? \Carbon\Carbon::parse( $customer->commonData->birth_date )->format( 'd/m/Y' ) : '' ],
                                    'Área de Atuação'    => [ 'icon' => 'diagram-3', 'value' => $customer->commonData?->areaOfActivity?->name ],
                                    'Profissão'          => [ 'icon' => 'person-workspace', 'value' => $customer->commonData?->profession?->name ],
                                    'Website'            => [ 'icon' => 'globe', 'value' => $customer->contact?->website ],
                                ];
                            @endphp
                        @endif

                        @foreach ( $specific_info as $key => $info )
                            @if ( $info[ 'value' ] )
                                <div class="info-item mb-3">
                                    <label class="text-muted small d-block mb-1">{{ str_replace( '_', ' ', $key ) }}</label>
                                    <p class="mb-0 d-flex align-items-center">
                                        <i class="bi bi-{{ $info[ 'icon' ] }} me-2 "></i>
                                        @if ( $key == 'Website' && $info[ 'value' ] )
                                            <a href="{{ $info[ 'value' ] }}" target="_blank"
                                                class="text-decoration-none">{{ $info[ 'value' ] }}</a>
                                        @else
                                            <span>{{ $info[ 'value' ] }}</span>
                                        @endif
                                    </p>
                                </div>
                            @endif
                        @endforeach

                        @if( $customer->commonData?->description )
                            <div class="info-item mb-3">
                                <label class="text-muted small d-block mb-1">Descrição</label>
                                <p class="mb-0">
                                    <i class="bi bi-info-circle me-2"></i>
                                    {{ $customer->commonData->description }}
                                </p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Endereço -->
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100 card-hover">
                    <div class="card-header bg-transparent border-0 py-1">
                        <h5 class="card-title mb-0 d-flex align-items-center">
                            <i class="bi bi-geo-alt-fill me-2 "></i>
                            <span>Endereço</span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="info-item mb-3">
                            <label class="text-muted small d-block mb-1">CEP</label>
                            <p class="mb-0 d-flex align-items-center">
                                <i class="bi bi-mailbox me-2 "></i>
                                <span>{{ $customer->address?->cep }}</span>
                            </p>
                        </div>

                        <div class="info-item">
                            <label class="text-muted small d-block mb-1">Endereço Completo</label>
                            <p class="mb-0 d-flex align-items-start">
                                <i class="bi bi-pin-map-fill me-2 "></i>
                                <span>
                                    {{ $customer->address?->address }}, {{ $customer->address?->address_number }}<br>
                                    {{ $customer->address?->neighborhood }}<br>
                                    {{ $customer->address?->city }} - {{ $customer->address?->state }}
                                </span>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Descrição -->
            @if ( $customer->isCompany() && $customer->commonData?->notes )
                <div class="col-12">
                    <div class="card border-0 shadow-sm card-hover">
                        <div class="card-header bg-transparent border-0 py-1">
                            <h5 class="card-title mb-0 d-flex align-items-center">
                                <i class="bi bi-info-circle-fill me-2 "></i>
                                <span>Observações</span>
                            </h5>
                        </div>
                        <div class="card-body">
                            <p class="mb-0">{{ $customer->commonData->notes }}</p>
                        </div>
                    </div>
                </div>
            @elseif ( !$customer->isCompany() && $customer->commonData?->description )
                <div class="col-12">
                    <div class="card border-0 shadow-sm card-hover">
                        <div class="card-header bg-transparent border-0 py-1">
                            <h5 class="card-title mb-0 d-flex align-items-center">
                                <i class="bi bi-info-circle-fill me-2 "></i>
                                <span>Descrição Profissional</span>
                            </h5>
                        </div>
                        <div class="card-body">
                            <p class="mb-0">{{ $customer->commonData->description }}</p>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <!-- Resumo Financeiro -->
        <div class="row g-4 mt-2">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-transparent">
                        <h5 class="mb-0"><i class="bi bi-graph-up me-2"></i>Resumo Financeiro</h5>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <div class="text-center p-3 bg-light rounded">
                                    <i class="bi bi-file-earmark-text text-primary mb-2" style="font-size: 2rem;"></i>
                                    <h6 class="mb-1">Orçamentos</h6>
                                    <h4 class="text-primary mb-0">{{ $customer->budgets->count() }}</h4>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center p-3 bg-light rounded">
                                    <i class="bi bi-receipt text-success mb-2" style="font-size: 2rem;"></i>
                                    <h6 class="mb-1">Faturas</h6>
                                    <h4 class="text-success mb-0">{{ $customer->invoices->count() }}</h4>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center p-3 bg-light rounded">
                                    <i class="bi bi-currency-dollar text-warning mb-2" style="font-size: 2rem;"></i>
                                    <h6 class="mb-1">Total Faturado</h6>
                                    <h4 class="text-warning mb-0">
                                        {{ money( $customer->budgets->where( 'status', 'approved' )->sum( 'total' ) ) }}
                                    </h4>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="text-center p-3 bg-light rounded">
                                    <i class="bi bi-calendar-check text-info mb-2" style="font-size: 2rem;"></i>
                                    <h6 class="mb-1">Cliente desde</h6>
                                    <h4 class="text-info mb-0">
                                        {{ \Carbon\Carbon::parse( $customer->created_at )->format( 'm/Y' ) }}
                                    </h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Orçamentos Recentes -->
        @if( $customer->budgets->count() > 0 )
            <div class="row g-4 mt-2">
                <div class="col-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-transparent d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="bi bi-file-earmark-text me-2"></i>Orçamentos Recentes</h5>
                            <a href="{{ route( 'provider.budgets.index', [ 'customer_id' => $customer->id ] ) }}"
                                class="btn btn-sm btn-outline-primary">
                                Ver todos
                            </a>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Código</th>
                                            <th>Data</th>
                                            <th>Status</th>
                                            <th>Valor</th>
                                            <th>Ações</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach( $customer->budgets->take( 5 ) as $budget )
                                            <tr>
                                                <td><span class="text-code">{{ $budget->code }}</span></td>
                                                <td>{{ \Carbon\Carbon::parse( $budget->created_at )->format( 'd/m/Y' ) }}</td>
                                                <td>
                                                    <span
                                                        class="badge bg-{{ $budget->status->value === 'approved' ? 'success' : ( $budget->status->value === 'pending' ? 'warning' : 'secondary' ) }}">
                                                        {{ ucfirst( $budget->status->value ) }}
                                                    </span>
                                                </td>
                                                <td>{{ money( $budget->total ) }}</td>
                                                <td>
                                                    <a href="{{ route( 'provider.budgets.show', $budget->code ) }}"
                                                        class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Botões de Ação -->
        <div class="d-flex justify-content-between align-items-center mt-4">
            <div class="d-flex gap-2">
                <a href="{{ route( 'provider.customers.index' ) }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-2"></i>Voltar
                </a>
                <a href="{{ route( 'provider.budgets.create', [ 'customer_id' => $customer->id ] ) }}"
                    class="btn btn-success">
                    <i class="bi bi-plus-circle me-2"></i>Novo Orçamento
                </a>
            </div>
            <small class="text-muted">
                Última atualização: {{ \Carbon\Carbon::parse( $customer->updated_at )->format( 'd/m/Y H:i' ) }}
            </small>
            <div class="d-flex gap-2">
                <a href="{{ route( 'provider.customers.edit', $customer ) }}" class="btn btn-primary">
                    <i class="bi bi-pencil-fill me-2"></i>Editar
                </a>
                <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal">
                    <i class="bi bi-trash-fill me-2"></i>Excluir
                </button>
            </div>
        </div>
    </div>

    <!-- Modal de Confirmação de Exclusão -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title" id="deleteModalLabel">Confirmar Exclusão</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Tem certeza que deseja excluir o cliente <strong>{{ $customer->commonData?->first_name }}
                            {{ $customer->commonData?->last_name }}</strong>?</p>
                    <div class="alert alert-warning" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <strong>Atenção:</strong> Esta ação não pode ser desfeita e pode afetar orçamentos e faturas
                        associados.
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <form action="{{ route( 'provider.customers.destroy', $customer ) }}" method="POST" class="d-inline">
                        @csrf
                        @method( 'DELETE' )
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-trash me-2"></i>Confirmar Exclusão
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
