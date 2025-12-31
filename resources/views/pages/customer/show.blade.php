@extends('layouts.app')

@section('title', 'Detalhes do Cliente')

@section('content')
    <div class="container-fluid py-1 d-flex flex-column" style="min-height: calc(100vh - 200px);">
        <div class="flex-grow-1">
            <x-page-header
                title="Detalhes do Cliente"
                icon="person"
                :breadcrumb-items="[
                    'Clientes' => route('provider.customers.index'),
                    ($customer->commonData ? ($customer->commonData->isCompany() ? $customer->commonData->company_name : $customer->commonData->first_name . ' ' . $customer->commonData->last_name) : 'Cliente #' . $customer->id) => '#'
                ]">
                <p class="text-muted mb-0">Visualize as informações completas do cliente</p>
            </x-page-header>

            <div class="row g-4">
                <!-- Informações Pessoais e Status -->
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body text-center">
                            <div class="avatar-circle mx-auto mb-3 bg-light d-flex align-items-center justify-content-center fw-bold shadow-sm" style="width: 80px; height: 80px; font-size: 2rem;">
                                @if ($customer->commonData)
                                    @if ($customer->commonData->isCompany())
                                        {{ substr($customer->commonData->company_name, 0, 1) }}
                                    @else
                                        {{ substr($customer->commonData->first_name, 0, 1) }}{{ substr($customer->commonData->last_name, 0, 1) }}
                                    @endif
                                @else
                                    C
                                @endif
                            </div>

                            <h4 class="fw-bold text-dark mb-1">
                                @if ($customer->commonData)
                                    @if ($customer->commonData->isCompany())
                                        {{ $customer->commonData->company_name }}
                                    @else
                                        {{ $customer->commonData->first_name }} {{ $customer->commonData->last_name }}
                                    @endif
                                @else
                                    Cliente #{{ $customer->id }}
                                @endif
                            </h4>

                            @if ($customer->isIndividual() && $customer->age)
                                <p class="text-muted small mb-2">{{ $customer->age }} anos</p>
                            @endif

                            <div class="mb-4">
                                <span class="modern-badge {{ $customer->status === 'active' ? 'badge-active' : 'badge-inactive' }}">
                                    {{ $customer->status === 'active' ? 'Ativo' : 'Inativo' }}
                                </span>
                            </div>

                            <div class="text-start">
                                <label class="text-muted small text-uppercase fw-bold d-block mb-2 border-bottom pb-1">Contato</label>
                                @php
                                    $personal_info = [
                                        'Email' => ['icon' => 'envelope', 'value' => $customer->contact?->email_personal],
                                        'Email Comercial' => ['icon' => 'envelope-at', 'value' => $customer->contact?->email_business],
                                        'Telefone' => ['icon' => 'phone', 'value' => $customer->contact?->phone_personal],
                                        'Telefone Comercial' => ['icon' => 'telephone', 'value' => $customer->contact?->phone_business],
                                    ];
                                @endphp

                                @foreach ($personal_info as $key => $info)
                                    @if ($info['value'])
                                        <div class="mb-3">
                                            <small class="text-muted d-block">{{ $key }}</small>
                                            <div class="d-flex align-items-center">
                                                <i class="bi bi-{{ $info['icon'] }} me-2 text-muted"></i>
                                                @if (str_contains($key, 'Email'))
                                                    <a href="mailto:{{ $info['value'] }}" class="text-decoration-none text-dark text-break">{{ $info['value'] }}</a>
                                                @else
                                                    <a href="tel:{{ preg_replace('/\D/', '', $info['value']) }}" class="text-decoration-none text-dark">{{ \App\Helpers\MaskHelper::formatPhone($info['value']) }}</a>
                                                @endif
                                            </div>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Informações Detalhadas -->
                <div class="col-md-8">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header p-0 border-bottom-0">
                            <ul class="nav nav-tabs" id="customerTabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="details-tab" data-bs-toggle="tab"
                                        data-bs-target="#details" type="button" role="tab" aria-selected="true">
                                        <i class="bi bi-info-circle me-1"></i> Dados do Cliente
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="address-tab" data-bs-toggle="tab" data-bs-target="#address"
                                        type="button" role="tab" aria-selected="false">
                                        <i class="bi bi-geo-alt me-1"></i> Endereço
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="finance-tab" data-bs-toggle="tab" data-bs-target="#finance"
                                        type="button" role="tab" aria-selected="false">
                                        <i class="bi bi-cash-stack me-1"></i> Financeiro
                                    </button>
                                </li>
                            </ul>
                        </div>
                        <div class="card-body">
                            <div class="tab-content" id="customerTabsContent">
                                <!-- Aba Detalhes -->
                                <div class="tab-pane fade show active" id="details" role="tabpanel">
                                    <div class="row g-4">
                                        @if ($customer->isCompany())
                                            @php
                                                $specific_info = [
                                                    'Razão Social' => ['icon' => 'building', 'value' => $customer->commonData?->company_name],
                                                    'Nome Fantasia' => ['icon' => 'building', 'value' => $customer->commonData?->fantasy_name],
                                                    'CNPJ' => ['icon' => 'card-text', 'value' => format_cnpj($customer->commonData?->cnpj)],
                                                    'Inscrição Estadual' => ['icon' => 'file-earmark-text', 'value' => $customer->commonData?->state_registration],
                                                    'Inscrição Municipal' => ['icon' => 'file-earmark-text', 'value' => $customer->commonData?->municipal_registration],
                                                    'Data de Fundação' => ['icon' => 'calendar-event', 'value' => $customer->commonData?->founding_date ? \Carbon\Carbon::parse($customer->commonData->founding_date)->format('d/m/Y') : ''],
                                                    'Setor de Atuação' => ['icon' => 'diagram-3', 'value' => $customer->commonData?->industry],
                                                    'Porte da Empresa' => ['icon' => 'building-gear', 'value' => $customer->commonData?->company_size ? ucfirst($customer->commonData->company_size) : ''],
                                                    'Website' => ['icon' => 'globe', 'value' => $customer->contact?->website],
                                                ];
                                            @endphp
                                        @else
                                            @php
                                                $specific_info = [
                                                    'CPF' => ['icon' => 'person-badge', 'value' => format_cpf($customer->commonData?->cpf)],
                                                    'Data de Nascimento' => ['icon' => 'calendar-event', 'value' => $customer->commonData?->birth_date ? \Carbon\Carbon::parse($customer->commonData->birth_date)->format('d/m/Y') : ''],
                                                    'Área de Atuação' => ['icon' => 'diagram-3', 'value' => $customer->commonData?->areaOfActivity?->name],
                                                    'Profissão' => ['icon' => 'person-workspace', 'value' => $customer->commonData?->profession?->name],
                                                    'Website' => ['icon' => 'globe', 'value' => $customer->contact?->website],
                                                ];
                                            @endphp
                                        @endif

                                        @foreach ($specific_info as $key => $info)
                                            @if ($info['value'])
                                                <div class="col-md-6">
                                                    <label class="text-muted small text-uppercase fw-bold d-block mb-1">{{ $key }}</label>
                                                    <div class="d-flex align-items-center">
                                                        <i class="bi bi-{{ $info['icon'] }} me-2 text-muted"></i>
                                                        @if ($key == 'Website')
                                                            <a href="{{ $info['value'] }}" target="_blank" class="text-decoration-none text-dark">{{ $info['value'] }}</a>
                                                        @else
                                                            <span class="text-dark">{{ $info['value'] }}</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            @endif
                                        @endforeach

                                        <div class="col-12 mt-4">
                                            <label class="text-muted small text-uppercase fw-bold d-block mb-1">Observações / Descrição</label>
                                            <div class="p-3 rounded border bg-light">
                                                {{ ($customer->isCompany() ? $customer->businessData?->notes : $customer->commonData?->description) ?: 'Nenhuma observação informada.' }}
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Aba Endereço -->
                                <div class="tab-pane fade" id="address" role="tabpanel">
                                    <div class="row g-4">
                                        <div class="col-md-4">
                                            <label class="text-muted small text-uppercase fw-bold d-block mb-1">CEP</label>
                                            <p class="h5 text-dark"><i class="bi bi-mailbox me-2 text-muted"></i>{{ $customer->address?->cep ?: 'N/A' }}</p>
                                        </div>
                                        <div class="col-md-8">
                                            <label class="text-muted small text-uppercase fw-bold d-block mb-1">Logradouro</label>
                                            <p class="h5 text-dark"><i class="bi bi-geo-alt me-2 text-muted"></i>{{ $customer->address?->address ?: 'N/A' }}, {{ $customer->address?->address_number }}</p>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="text-muted small text-uppercase fw-bold d-block mb-1">Bairro</label>
                                            <p class="text-dark">{{ $customer->address?->neighborhood ?: 'N/A' }}</p>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="text-muted small text-uppercase fw-bold d-block mb-1">Cidade</label>
                                            <p class="text-dark">{{ $customer->address?->city ?: 'N/A' }}</p>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="text-muted small text-uppercase fw-bold d-block mb-1">Estado</label>
                                            <p class="text-dark">{{ $customer->address?->state ?: 'N/A' }}</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Aba Financeiro -->
                                <div class="tab-pane fade" id="finance" role="tabpanel">
                                    <div class="row g-4">
                                        <div class="col-md-6">
                                            <div class="card border bg-light shadow-none">
                                                <div class="card-body">
                                                    <h6 class="text-muted text-uppercase small fw-bold mb-3">Orçamentos</h6>
                                                    <div class="d-flex align-items-center justify-content-between">
                                                        <div>
                                                            <h3 class="mb-0 fw-bold">{{ $customer->budgets->count() }}</h3>
                                                            <small class="text-muted">Total gerado</small>
                                                        </div>
                                                        <i class="bi bi-file-earmark-text fs-1 text-muted opacity-50"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="card border bg-light shadow-none">
                                                <div class="card-body">
                                                    <h6 class="text-muted text-uppercase small fw-bold mb-3">Faturas</h6>
                                                    <div class="d-flex align-items-center justify-content-between">
                                                        <div>
                                                            <h3 class="mb-0 fw-bold">{{ $customer->invoices->count() }}</h3>
                                                            <small class="text-muted">Total faturado</small>
                                                        </div>
                                                        <i class="bi bi-receipt fs-1 text-muted opacity-50"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        @if($customer->budgets->count() > 0)
                                            <div class="col-12 mt-4">
                                                <h6 class="text-muted text-uppercase small fw-bold mb-3">Últimos Orçamentos</h6>
                                                <div class="table-responsive">
                                                    <table class="table table-sm table-hover border-top">
                                                        <thead>
                                                            <tr>
                                                                <th>Código</th>
                                                                <th>Data</th>
                                                                <th>Total</th>
                                                                <th>Status</th>
                                                                <th class="text-end">Ação</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach($customer->budgets()->latest()->take(5)->get() as $budget)
                                                                <tr>
                                                                    <td class="fw-bold">{{ $budget->code }}</td>
                                                                    <td>{{ $budget->created_at->format('d/m/Y') }}</td>
                                                                    <td>R$ {{ number_format($budget->total, 2, ',', '.') }}</td>
                                                                    <td>
                                                                        <span class="badge rounded-pill bg-light text-dark border">
                                                                            {{ ucfirst($budget->status) }}
                                                                        </span>
                                                                    </td>
                                                                    <td class="text-end">
                                                                <x-button type="link" :href="route('provider.budgets.show', $budget->id)" variant="info" size="sm" icon="eye" />
                                                            </td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer com Ações -->
        <div class="mt-auto pt-4 pb-2">
            <div class="row align-items-center g-3">
                <div class="col-12 col-md-auto order-2 order-md-1">
                    <x-back-button index-route="provider.customers.index" class="w-100 w-md-auto px-md-3" />
                </div>

                <div class="col-12 col-md text-center d-none d-md-block order-md-2">
                    <small class="text-muted">
                        Cadastrado em: {{ $customer->created_at->format('d/m/Y H:i') }} |
                        Última atualização: {{ $customer->updated_at?->format('d/m/Y H:i') }}
                    </small>
                </div>

                <div class="col-12 col-md-auto order-1 order-md-3">
                    <div class="d-grid d-md-flex gap-2">
                        <x-button type="link" :href="route('provider.customers.edit', $customer->id)" style="min-width: 120px;" icon="pencil-fill" label="Editar" />

                        <x-button :variant="$customer->status === 'active' ? 'warning' : 'success'" style="min-width: 120px;"
                            data-bs-toggle="modal" data-bs-target="#toggleModal"
                            data-action="{{ $customer->status === 'active' ? 'Desativar' : 'Ativar' }}"
                            :icon="$customer->status === 'active' ? 'slash-circle' : 'check-lg'"
                            :label="$customer->status === 'active' ? 'Desativar' : 'Ativar'" />

                        <x-button variant="danger" style="min-width: 120px;" data-bs-toggle="modal" data-bs-target="#deleteModal"
                            icon="trash-fill" label="Excluir" />
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Confirmação de Status -->
    <div class="modal fade" id="toggleModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title">Confirmar Alteração de Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Deseja realmente {{ $customer->status === 'active' ? 'desativar' : 'ativar' }} o cliente <strong>{{ $customer->commonData ? ($customer->commonData->isCompany() ? $customer->commonData->company_name : $customer->commonData->first_name . ' ' . $customer->commonData->last_name) : 'este cliente' }}</strong>?</p>
                </div>
                <div class="modal-footer border-0">
                    <x-button variant="secondary" data-bs-dismiss="modal" label="Cancelar" />
                    <form action="{{ route('provider.customers.toggle-status', $customer->id) }}" method="POST" class="d-inline">
                        @csrf
                        @method('PATCH')
                        <x-button type="submit" :variant="$customer->status === 'active' ? 'warning' : 'success'" :label="$customer->status === 'active' ? 'Desativar' : 'Ativar'" />
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Confirmação de Exclusão -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-0">
                    <h5 class="modal-title">Confirmar Exclusão</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Tem certeza que deseja excluir o cliente <strong>{{ $customer->commonData ? ($customer->commonData->isCompany() ? $customer->commonData->company_name : $customer->commonData->first_name . ' ' . $customer->commonData->last_name) : 'este cliente' }}</strong>?</p>
                    <div class="alert alert-warning" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <strong>Atenção:</strong> Esta ação não pode ser desfeita e pode afetar orçamentos e faturas associados.
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <x-button variant="secondary" data-bs-dismiss="modal" label="Cancelar" />
                    <form action="{{ route('provider.customers.destroy', $customer->id) }}" method="POST" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <x-button type="submit" variant="danger" icon="trash" label="Confirmar Exclusão" />
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
