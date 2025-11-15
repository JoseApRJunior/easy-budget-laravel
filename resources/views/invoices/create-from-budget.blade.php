@extends('layouts.app')

@section('content')
<div class="container py-4">
    <h1 class="h4 mb-3">Criar Fatura a partir do Orçamento</h1>

    <div class="card mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div><strong>Orçamento:</strong> {{ $budget->code }}</div>
                    <div><strong>Cliente:</strong> {{ $budget->customer->name ?? '-' }}</div>
                </div>
                <div class="col-md-6 text-md-end">
                    <div><strong>Total do Orçamento:</strong> R$ {{ number_format($budget->total, 2, ',', '.') }}</div>
                </div>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('invoices.store.from-budget', $budget) }}">
        @csrf
        <input type="hidden" name="budget_code" value="{{ $budget->code }}" />

        <div class="card mb-4">
            <div class="card-header">Itens do Orçamento</div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm table-striped">
                        <thead>
                            <tr>
                                <th style="width: 40px"></th>
                                <th>Serviço</th>
                                <th>Produto</th>
                                <th class="text-end">Qtd</th>
                                <th class="text-end">Valor Unit.</th>
                                <th class="text-end">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($budget->services as $service)
                                @foreach($service->serviceItems as $item)
                                    <tr>
                                        <td>
                                            <input type="checkbox" class="form-check-input" name="items[{{ $item->id }}][selected]" value="1" />
                                            <input type="hidden" name="items[{{ $item->id }}][service_item_id]" value="{{ $item->id }}" />
                                        </td>
                                        <td>{{ $service->description }}</td>
                                        <td>{{ $item->product->name ?? ('#'.$item->product_id) }}</td>
                                        <td>
                                            <input type="number" step="0.01" min="0.01" class="form-control form-control-sm text-end" name="items[{{ $item->id }}][quantity]" value="{{ $item->quantity }}" />
                                        </td>
                                        <td>
                                            <input type="number" step="0.01" min="0.01" class="form-control form-control-sm text-end" name="items[{{ $item->id }}][unit_value]" value="{{ $item->unit_value }}" />
                                        </td>
                                        <td class="text-end">R$ {{ number_format($item->total, 2, ',', '.') }}</td>
                                    </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">Dados da Fatura</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Serviço</label>
                        <select class="form-select" name="service_id" required>
                            @foreach($budget->services as $service)
                                <option value="{{ $service->id }}">{{ $service->code }} — {{ $service->description }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Vencimento</label>
                        <input type="date" class="form-control" name="due_date" value="{{ now()->addDays(7)->format('Y-m-d') }}" required />
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status" required>
                            @foreach($statusOptions as $st)
                                <option value="{{ $st->value }}">{{ $st->getDescription() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Desconto</label>
                        <input type="number" step="0.01" min="0" class="form-control" name="discount" value="0" />
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">Criar Fatura</button>
            <a href="{{ route('invoices.index') }}" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>
@endsection