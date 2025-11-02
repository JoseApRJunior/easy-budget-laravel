@if ( auth()->check() )
    @php
        $provider = auth()->user()->provider;
    @endphp
    <div class="col-12">
        <div class="mb-3">
            <label class="small text-muted">Empresa</label>
            <p class="mb-0">{{ $provider?->commonData?->company_name ?? 'Não informado' }}</p>
        </div>
    </div>
    <div class="col-12">
        <div class="mb-3">
            <label class="small text-muted">Endereço</label>
            <p class="mb-0">
                {{ $provider?->address?->address ?? '' }}, {{ $provider?->address?->address_number ?? '' }}<br>
                {{ $provider?->address?->neighborhood ?? '' }} -
                {{ $provider?->address?->city ?? '' }}/{{ $provider?->address?->state ?? '' }}<br>
                CEP: {{ $provider?->address?->cep ?? '' }}
            </p>
        </div>
    </div>
@endif
