@if ( auth()->check() )
    <div class="col-12">
        <div class="mb-3">
            <label class="small text-muted">Empresa</label>
            <p class="mb-0">{{ auth()->user()->company_name ?? 'Não informado' }}</p>
        </div>
    </div>
    <div class="col-12">
        <div class="mb-3">
            <label class="small text-muted">Endereço</label>
            <p class="mb-0">
                {{ auth()->user()->address ?? '' }}, {{ auth()->user()->address_number ?? '' }}<br>
                {{ auth()->user()->neighborhood ?? '' }} -
                {{ auth()->user()->city ?? '' }}/{{ auth()->user()->state ?? '' }}<br>
                CEP: {{ auth()->user()->cep ?? '' }}
            </p>
        </div>
    </div>
@endif
