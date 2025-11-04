@if ( auth()->check() )
    @php
        $provider   = auth()->user()->provider;
        $personType = $provider?->commonData?->cpf ? 'pf' : ( $provider?->commonData?->cnpj ? 'pj' : null );
    @endphp
    <div class="col-12">
        <div class="mb-3">
            <label class="small text-muted">Tipo de Pessoa</label>
            <p class="mb-0">
                @if( $personType === 'pf' )
                    Pessoa Física
                @elseif( $personType === 'pj' )
                    Pessoa Jurídica
                @else
                    Não informado
                @endif
            </p>
        </div>
    </div>
    <div class="col-12">
        <div class="mb-3">
            <label class="small text-muted">
                @if( $personType === 'pf' )
                    CPF
                @elseif( $personType === 'pj' )
                    CNPJ
                @else
                    Documento
                @endif
            </label>
            <p class="mb-0">
                @if( $personType === 'pf' )
                    {{ $provider?->commonData?->cpf ? format_cpf( $provider->commonData->cpf ) : 'Não informado' }}
                @elseif( $personType === 'pj' )
                    {{ $provider?->commonData?->cnpj ? format_cnpj( $provider->commonData->cnpj ) : 'Não informado' }}
                @else
                    Não informado
                @endif
            </p>
        </div>
    </div>
    @if( $personType === 'pj' )
        <div class="col-12">
            <div class="mb-3">
                <label class="small text-muted">Empresa</label>
                <p class="mb-0">{{ $provider?->commonData?->company_name ?? 'Não informado' }}</p>
            </div>
        </div>
    @endif
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
