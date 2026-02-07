@extends('layouts.app')

@section('title', 'Clientes')

@section('content')
    <div class="container-fluid py-4">
        <x-layout.page-header
            title="Lista de Clientes"
            icon="people"
            :breadcrumb-items="[
                'Dashboard' => route('provider.dashboard'),
                'Clientes' => route('provider.customers.dashboard'),
                'Lista' => '#'
            ]">
            <p class="text-muted mb-0 small">Lista de todos os clientes registrados no sistema</p>
        </x-layout.page-header>

        <div class="row">
            <div class="col-12">
                <!-- Filtros de Busca -->
                <x-form.filter-form
                    id="filtersFormCustomers"
                    :route="route('provider.customers.index')"
                    :filters="$filters"
                >
                    <x-form.filter-field
                        col="col-md-4"
                        name="search"
                        label="Buscar"
                        placeholder="Nome, e-mail ou documento"
                        :filters="$filters"
                    />

                    <x-form.filter-field
                        type="select"
                        col="col-md-2"
                        name="active"
                        label="Status"
                        :filters="$filters"
                        :options="['active' => 'Ativo', 'inactive' => 'Inativo', 'all' => 'Todos']"
                    />

                    <x-form.filter-field
                        type="select"
                        col="col-md-2"
                        name="type"
                        label="Tipo"
                        :filters="$filters"
                        :options="['' => 'Todos os tipos', 'individual' => 'Pessoa Física', 'company' => 'Pessoa Jurídica']"
                    />

                    <x-form.filter-field
                        type="select"
                        col="col-md-2"
                        name="area_of_activity"
                        label="Área de Atuação"
                        :filters="$filters"
                        :options="['' => 'Todas as áreas'] + (isset($areas_of_activity) ? $areas_of_activity->pluck('name', 'slug')->toArray() : [])"
                    />

                    <x-form.filter-field
                        type="select"
                        col="col-md-2"
                        name="deleted"
                        label="Status de Exclusão"
                        :filters="$filters"
                        :options="['current' => 'Atuais', 'only' => 'Deletados', 'all' => 'Todos']"
                    />

                    <x-form.filter-field
                        col="col-md-2"
                        name="cep"
                        label="CEP"
                        placeholder="00000-000"
                        data-mask="00000-000"
                        :filters="$filters"
                    />

                    <x-form.filter-field
                        col="col-md-2"
                        name="cpf"
                        label="CPF"
                        placeholder="000.000.000-00"
                        data-mask="000.000.000-00"
                        :filters="$filters"
                    />

                    <x-form.filter-field
                        col="col-md-2"
                        name="cnpj"
                        label="CNPJ"
                        placeholder="00.000.000/0000-00"
                        data-mask="00.000.000/0000-00"
                        :filters="$filters"
                    />

                    <x-form.filter-field
                        col="col-md-2"
                        name="phone"
                        label="Telefone"
                        placeholder="(00) 00000-0000"
                        data-mask="(00) 00000-0000"
                        :filters="$filters"
                    />

                    <x-form.filter-field
                        type="date"
                        col="col-md-2"
                        name="start_date"
                        label="Cadastro Inicial"
                        :filters="$filters"
                    />

                    <x-form.filter-field
                        type="date"
                        col="col-md-2"
                        name="end_date"
                        label="Cadastro Final"
                        :filters="$filters"
                    />

                    <x-form.filter-field
                        type="select"
                        col="col-md-2"
                        name="per_page"
                        label="Por página"
                        :filters="$filters"
                        :options="[10 => '10', 20 => '20', 50 => '50']"
                    />
                </x-form.filter-form>



        <!-- Card de Tabela -->
        <x-resource.resource-list-card
            title="Lista de Clientes"
            mobileTitle="Clientes"
            icon="people"
            :total="$customers instanceof \Illuminate\Pagination\LengthAwarePaginator ? $customers->total() : $customers->count()"
            padding="p-0"
        >
            <x-slot:headerActions>
                <div class="d-flex justify-content-end gap-2">
                    <div class="dropdown">
                        <x-ui.button variant="outline-secondary" size="sm" icon="download" label="Exportar"
                            class="dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" id="exportDropdown" />
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="exportDropdown">
                            <li>
                                <a class="dropdown-item"
                                    href="{{ route('provider.customers.export', array_merge(request()->query(), ['format' => 'xlsx'])) }}">
                                    <i class="bi bi-file-earmark-excel me-2 text-success"></i> Excel (.xlsx)
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item"
                                    href="{{ route('provider.customers.export', array_merge(request()->query(), ['format' => 'pdf'])) }}">
                                    <i class="bi bi-file-earmark-pdf me-2 text-danger"></i> PDF (.pdf)
                                </a>
                            </li>
                        </ul>
                    </div>
                    <x-ui.button type="link" :href="route('provider.customers.create')" size="sm" icon="plus" label="Novo" />
                </div>
            </x-slot:headerActions>

            <x-slot:desktop>
                <x-resource.resource-table>
                    <x-slot:thead>
                        <tr>
                            <th><i class="bi bi-person" aria-hidden="true"></i></th>
                            <th>Cliente</th>
                            <th>Documento</th>
                            <th class="text-nowrap">E-mail</th>
                            <th class="text-nowrap">Telefone</th>
                            <th class="text-nowrap">Cadastro</th>
                            <th class="text-nowrap">Status</th>
                            <th class="text-center">Ações</th>
                        </tr>
                    </x-slot:thead>
                    @forelse($customers as $customer)
                        <tr>
                            <td>
                                <div class="item-icon">
                                    <i class="bi bi-person-fill"></i>
                                </div>
                            </td>
                            <td>
                                @if ($customer->commonData)
                                    @if ($customer->commonData->isCompany())
                                        <strong>{{ $customer->commonData->company_name }}</strong>
                                        @if ($customer->commonData->fantasy_name)
                                            <br><small class="text-muted">{{ $customer->commonData->fantasy_name }}</small>
                                        @endif
                                    @else
                                        <strong>{{ $customer->commonData->first_name }} {{ $customer->commonData->last_name }}</strong>
                                    @endif
                                @else
                                    <span class="text-muted">Nome não informado</span>
                                @endif
                            </td>
                            <td>
                                @if ($customer->commonData)
                                    @if ($customer->commonData->isCompany())
                                        <span class="text-code">{{ \App\Helpers\MaskHelper::formatCNPJ($customer->commonData->cnpj) }}</span>
                                    @else
                                        <span class="text-code">{{ \App\Helpers\MaskHelper::formatCPF($customer->commonData->cpf) }}</span>
                                    @endif
                                @else
                                    <span class="text-muted">N/A</span>
                                @endif
                            </td>
                            <td>
                                @if ($customer->contact)
                                    {{ $customer->contact->email_personal ?? ($customer->contact->email_business ?? 'N/A') }}
                                @else
                                    <span class="text-muted">N/A</span>
                                @endif
                            </td>
                            <td>
                                @if ($customer->contact)
                                    {{ \App\Helpers\MaskHelper::formatPhone($customer->contact->phone_personal ?? $customer->contact->phone_business) }}
                                @else
                                    <span class="text-muted">N/A</span>
                                @endif
                            </td>
                            <td>{{ $customer->created_at->format('d/m/Y') }}</td>
                            <td class="text-nowrap">
                                <span class="modern-badge {{ $customer->status === 'active' ? 'badge-active' : 'badge-inactive' }}">
                                    {{ $customer->status === 'active' ? 'Ativo' : 'Inativo' }}
                                </span>
                            </td>
                            <td class="text-center">
                                <x-resource.action-buttons
                                    :item="$customer"
                                    resource="customers"
                                    size="sm"
                                    :showDelete="!$customer->deleted_at"
                                />
                                @if ($customer->deleted_at)
                                    <x-ui.button variant="success" icon="arrow-counterclockwise"
                                        data-bs-toggle="modal"
                                        data-bs-target="#restoreModal"
                                        data-restore-url="{{ route('provider.customers.restore', $customer->id) }}"
                                        data-name="{{ $customer->commonData ? ($customer->commonData->isCompany() ? $customer->commonData->company_name : $customer->commonData->first_name . ' ' . $customer->commonData->last_name) : 'Cliente' }}"
                                        title="Restaurar" size="sm" />
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                <x-resource.empty-state
                                    title="Nenhum cliente encontrado"
                                    description="Não encontramos clientes com os filtros aplicados."
                                    icon="people"
                                />
                            </td>
                        </tr>
                    @endforelse
                </x-resource.resource-table>
            </x-slot:desktop>

            <x-slot:mobile>
                @forelse($customers as $customer)
                    <x-resource.resource-mobile-item
                        icon="person"
                        :href="route('provider.customers.show', $customer->id)"
                    >
                        <x-resource.resource-mobile-header
                            :title="$customer->commonData ? ($customer->commonData->isCompany() ? $customer->commonData->company_name : $customer->commonData->first_name . ' ' . $customer->commonData->last_name) : 'Nome não informado'"
                            :subtitle="$customer->created_at->format('d/m/Y')"
                        />

                        <x-slot:description>
                            <div class="d-flex gap-2 flex-wrap mb-2">
                                @if ($customer->status === 'active')
                                    <span class="badge bg-success-subtle text-success">Ativo</span>
                                @else
                                    <span class="badge bg-danger-subtle text-danger">Inativo</span>
                                @endif
                            </div>
                        </x-slot:description>

                        <x-slot:actions>
                            <x-resource.action-buttons
                                :item="$customer"
                                resource="customers"
                                size="sm"
                                :showDelete="!$customer->deleted_at"
                            />
                        </x-slot:actions>
                    </x-resource.resource-mobile-item>
                @empty
                    <div class="py-5">
                        <x-resource.empty-state
                            title="Nenhum cliente encontrado"
                            description="Não encontramos clientes com os filtros aplicados."
                            icon="people"
                        />
                    </div>
                @endforelse
            </x-slot:mobile>

            @if ($customers instanceof \Illuminate\Pagination\LengthAwarePaginator && $customers->hasPages())
                <x-slot:footer>
                    @include('partials.components.paginator', ['p' => $customers->appends(request()->query()), 'show_info' => true])
                </x-slot:footer>
            @endif
        </x-resource.resource-list-card>
                </div>
            </div>
            <!-- Modal de Confirmação -->
            <x-ui.confirm-modal
                id="deleteModal"
                title="Confirmar Exclusão"
                message="Tem certeza de que deseja excluir o cliente <strong id='deleteCustomerName'></strong>?"
                submessage="Esta ação não pode ser desfeita."
                confirmLabel="Excluir"
                variant="danger"
                type="delete"
                resource="cliente"
            />

            <!-- Modal de Restauração -->
            <x-ui.confirm-modal
                id="restoreModal"
                title="Confirmar Restauração"
                message="Tem certeza de que deseja restaurar o cliente <strong id='restoreCustomerName'></strong>?"
                confirmLabel="Restaurar"
                variant="success"
                type="restore"
                resource="cliente"
            />

            <div class="modal fade" id="confirmAllCustomersModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Listar todos os clientes?</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                aria-label="Fechar"></button>
                        </div>
                        <div class="modal-body">
                            <p>Você não aplicou filtros. Listar todos pode retornar muitos registros.</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="button" class="btn btn-primary btn-confirm-all-customers">Listar todos</button>
                        </div>
                    </div>
                </div>
            </div>
        @endsection

        @push('scripts')
    <script src="{{ asset('assets/js/customer.js') }}?v={{ time() }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const startDate = document.getElementById('start_date');
            const endDate = document.getElementById('end_date');
            const form = document.getElementById('filtersFormCustomers');

            if (!form || !startDate || !endDate) return;

            const parseDate = (str) => {
                if (!str) return null;
                const parts = str.split('/');
                if (parts.length === 3) {
                    const d = new Date(parts[2], parts[1] - 1, parts[0]);
                    return isNaN(d.getTime()) ? null : d;
                }
                return null;
            };

            const validateDates = () => {
                if (!startDate.value || !endDate.value) return true;

                const start = parseDate(startDate.value);
                const end = parseDate(endDate.value);

                if (start && end && start > end) {
                    const message = 'A data inicial não pode ser maior que a data final.';
                    if (window.easyAlert) {
                        window.easyAlert.warning(message);
                    } else {
                        alert(message);
                    }
                    return false;
                }
                return true;
            };

            form.addEventListener('submit', function(e) {
                if (!validateDates()) {
                    e.preventDefault();
                    return;
                }

                if (startDate.value && !endDate.value) {
                    e.preventDefault();
                    const message = 'Para filtrar por período, informe as datas inicial e final.';
                    if (window.easyAlert) {
                        window.easyAlert.error(message);
                    } else {
                        alert(message);
                    }
                    endDate.focus();
                } else if (!startDate.value && endDate.value) {
                    e.preventDefault();
                    const message = 'Para filtrar por período, informe as datas inicial e final.';
                    if (window.easyAlert) {
                        window.easyAlert.error(message);
                    } else {
                        alert(message);
                    }
                    startDate.focus();
                }
            });
        });
    </script>
@endpush
