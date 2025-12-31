@extends('layouts.app')

@section('title', 'Relatório de Clientes')

@section('content')
    <div class="container-fluid py-1">
        <!-- Cabeçalho -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h3 mb-0">
                    <i class="bi bi-people me-2"></i>
                    Relatório de Clientes
                </h1>
                <p class="text-muted">Visualize e analise todos os clientes cadastrados no sistema</p>
            </div>
            <nav aria-label="breadcrumb" class="d-none d-md-block">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route('provider.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('provider.reports.index') }}">Relatórios</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Clientes</li>
                </ol>
            </nav>
        </div>

        <!-- Filtros de Busca -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-filter me-1"></i> Filtros de Busca</h5>
            </div>
            <div class="card-body">
                <form id="filtersFormCustomers" method="GET" action="{{ route('provider.reports.customers') }}">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="name">Nome do Cliente</label>
                                <input type="text" class="form-control" id="name" name="name"
                                    value="{{ request('name') ?? '' }}" placeholder="Digite o nome">
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="document">CPF ou CNPJ</label>
                                <input type="text" class="form-control" id="document" name="document"
                                    value="{{ request('document') ?? '' }}" placeholder="Digite CPF ou CNPJ">
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="start_date">Data de Cadastro Inicial</label>
                                <input type="text" class="form-control" id="start_date" name="start_date"
                                    value="{{ request('start_date') ?? '' }}" placeholder="DD/MM/AAAA"
                                    data-mask="00/00/0000">
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="end_date">Data de Cadastro Final</label>
                                <input type="text" class="form-control" id="end_date" name="end_date"
                                    value="{{ request('end_date') ?? '' }}" placeholder="DD/MM/AAAA"
                                    data-mask="00/00/0000">
                            </div>
                        </div>

                        <div class="col-12">
                            <div class="d-flex gap-2">
                                <x-button type="submit" variant="primary" icon="search" label="Filtrar" class="flex-grow-1" id="btnFilterCustomers" />
                                <x-button type="link" :href="route('provider.reports.customers')" variant="outline-secondary" icon="x" label="Limpar" />
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        {{-- Empty State Inicial --}}
        @if (!request()->hasAny(['name', 'document', 'start_date', 'end_date']))
            <div class="card border-0 shadow-sm text-center py-4">
                <div class="card-body">
                    <i class="bi bi-funnel-fill text-primary mb-3" style="font-size: 3rem;"></i>
                    <h5 class="text-gray-800 mb-3">Utilize os filtros acima para gerar o relatório</h5>
                    <p class="text-muted mb-3">
                        Configure os critérios desejados e clique em "Filtrar" para visualizar os resultados
                    </p>
                    <a href="{{ route('provider.customers.create') }}" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Criar Primeiro Cliente
                    </a>
                </div>
            </div>
        @else
            <!-- Resultados -->
            <div class="card">
                <div class="card-header">
                    <div class="row align-items-center">
                        <div class="col-12 col-lg-8 mb-2 mb-lg-0">
                            <h5 class="mb-0 d-flex align-items-center flex-wrap">
                                <span class="me-2">
                                    <i class="bi bi-list-ul me-1"></i>
                                    <span class="d-none d-sm-inline">Lista de Clientes</span>
                                    <span class="d-sm-none">Clientes</span>
                                </span>
                                <span class="text-muted" style="font-size: 0.875rem;">
                                    @if (isset($customers) && $customers instanceof \Illuminate\Pagination\LengthAwarePaginator)
                                        ({{ $customers->total() }})
                                    @elseif (isset($customers))
                                        ({{ $customers->count() }})
                                    @endif
                                </span>
                            </h5>
                        </div>
                        <div class="col-12 col-lg-4 mt-2 mt-lg-0">
                            <div class="d-flex justify-content-start justify-content-lg-end">
                                <div class="btn-group" role="group">
                                    <button type="button" class="btn btn-outline-primary btn-sm" title="Exportar PDF"
                                        id="export-pdf">
                                        <i class="bi bi-file-earmark-pdf me-1"></i>PDF
                                    </button>
                                    <button type="button" class="btn btn-outline-success btn-sm" title="Exportar Excel"
                                        id="export-excel">
                                        <i class="bi bi-file-earmark-excel me-1"></i>Excel
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">

                    <!-- Mobile View -->
                    <div class="mobile-view">
                        <div class="list-group list-group-flush">
                            @forelse($customers ?? [] as $customer)
                                <a href="{{ route('provider.customers.show', $customer) }}"
                                    class="list-group-item list-group-item-action py-3">
                                    <div class="d-flex align-items-start">
                                        <i class="bi bi-person-circle text-muted me-3 mt-1"
                                            style="font-size: 1.5rem;"></i>
                                        <div class="flex-grow-1">
                                            <div class="fw-semibold mb-1">{{ $customer->name ?? 'Nome não informado' }}
                                            </div>
                                            <p class="text-muted small mb-2">
                                                {{ $customer->email ?? 'E-mail não informado' }}</p>
                                            <small class="text-muted">
                                                @if ($customer->document)
                                                    {{ preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $customer->document) }}
                                                @else
                                                    CPF/CNPJ não informado
                                                @endif
                                                • {{ $customer->created_at->format('d/m/Y') }}
                                            </small>
                                        </div>
                                        <i class="bi bi-chevron-right text-muted ms-2"></i>
                                    </div>
                                </a>
                            @empty
                                <div class="p-4 text-center text-muted">
                                    <i class="bi bi-inbox mb-2" style="font-size: 2rem;"></i>
                                    <br>
                                    <strong>Nenhum cliente encontrado</strong>
                                    <br>
                                    <small>Ajuste os filtros ou <a
                                            href="{{ route('provider.customers.create') }}">cadastre um novo
                                            cliente</a></small>
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <!-- Desktop View -->
                    <div class="desktop-view">
                        <div class="table-responsive">
                            <table class="modern-table table mb-0">
                                <thead>
                                    <tr>
                                        <th width="50"><i class="bi bi-person" aria-hidden="true"></i></th>
                                        <th>Nome</th>
                                        <th>Email</th>
                                        <th>Telefone</th>
                                        <th>CPF/CNPJ</th>
                                        <th width="120">Data Cadastro</th>
                                        <th width="150" class="text-center">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($customers ?? [] as $customer)
                                        <tr>
                                            <td>
                                                <div class="item-icon">
                                                    <i class="bi bi-person-circle"></i>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="item-name-cell">
                                                    {{ $customer->name ?? 'Nome não informado' }}
                                                </div>
                                            </td>
                                            <td>{{ $customer->email ?? 'E-mail não informado' }}</td>
                                            <td>{{ $customer->phone ?? 'Telefone não informado' }}</td>
                                            <td>
                                                @if ($customer->document)
                                                    <span
                                                        class="text-code">{{ preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $customer->document) }}</span>
                                                @else
                                                    <span class="text-muted">Não informado</span>
                                                @endif
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    {{ $customer->created_at?->format('d/m/Y H:i') ?? '—' }}
                                                </small>
                                            </td>
                                            <td>
                                                <div class="d-flex justify-content-center gap-1">
                                                    <x-button type="link" :href="route('provider.customers.show', $customer)" variant="info" size="sm" icon="eye" title="Visualizar" />
                                                    <x-button type="link" :href="route('provider.customers.edit', $customer)" variant="primary" size="sm" icon="pencil-square" title="Editar" />
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center text-muted">
                                                <i class="bi bi-inbox mb-2" style="font-size: 2rem;"></i>
                                                <br>
                                                <strong>Nenhum cliente encontrado</strong>
                                                <br>
                                                <small>Ajuste os filtros ou <a
                                                        href="{{ route('provider.customers.create') }}">cadastre um novo
                                                        cliente</a></small>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    @if ($customers instanceof \Illuminate\Pagination\LengthAwarePaginator && $customers->hasPages())
                        @include('partials.components.paginator', [
                            'p' => $customers->appends(request()->query()),
                            'show_info' => true,
                        ])
                    @endif
                </div>
        @endif
    </div>
@endsection

@push('scripts')
    <!-- Adicione a biblioteca SheetJS -->
    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
    <script src="{{ asset('assets/js/modules/table-paginator.js') }}"></script>
    <script src="{{ asset('assets/js/customer_report.js') }}?v={{ time() }}"></script>

    <script>
        function updatePerPage(value) {
            const url = new URL(window.location);
            url.searchParams.set('per_page', value);
            window.location.href = url.toString();
        }

        document.addEventListener('DOMContentLoaded', function() {
            const startDate = document.getElementById('start_date');
            const endDate = document.getElementById('end_date');
            const form = document.getElementById('filtersFormCustomers');
            const documentInput = document.getElementById('document');

            if (!form || !startDate || !endDate) return;

            if (typeof VanillaMask !== 'undefined') {
                new VanillaMask('start_date', 'date');
                new VanillaMask('end_date', 'date');
            }

            if (documentInput) {
                documentInput.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/\D/g, '');
                    if (value.length <= 11) {
                        value = value.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
                    } else {
                        value = value.replace(/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/, '$1.$2.$3/$4-$5');
                    }
                    e.target.value = value;
                });
            }

            const parseDate = (str) => {
                if (!str) return null;
                const parts = str.split('/');
                if (parts.length === 3) {
                    const d = new Date(parts[2], parts[1] - 1, parts[0]);
                    return isNaN(d.getTime()) ? null : d;
                }
                return null;
            };

            const validateDates = (input) => {
                if (!startDate.value || !endDate.value) return true;

                const start = parseDate(startDate.value);
                const end = parseDate(endDate.value);

                if (start && end && start > end) {
                    if (window.easyAlert) {
                        window.easyAlert.warning('A data inicial não pode ser maior que a data final.');
                    } else {
                        alert('A data inicial não pode ser maior que a data final.');
                    }
                    if (input) input.value = '';
                    return false;
                }
                return true;
            };

            startDate.addEventListener('change', function() {
                validateDates(this);
            });
            endDate.addEventListener('change', function() {
                validateDates(this);
            });

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
