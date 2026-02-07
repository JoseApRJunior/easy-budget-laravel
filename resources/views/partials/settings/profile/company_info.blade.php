@if ( auth()->check() )
    @php
        $provider   = auth()->user()->provider;
        // Prioriza PJ se houver CNPJ, senão usa o campo type do CommonData
        $personType = null;
        if ($provider?->commonData) {
            if ($provider->commonData->cnpj) {
                $personType = 'pj';
            } elseif ($provider->commonData->type === 'company') {
                $personType = 'pj';
            } elseif ($provider->commonData->type === 'individual' || $provider->commonData->cpf) {
                $personType = 'pf';
            }
        }
    @endphp
    {{-- Seção: Tipo de Pessoa --}}
    <div class="col-12">
        <h6 class="text-muted mb-3">
            <i class="bi bi-{{ $personType === 'pj' ? 'building' : 'person' }} me-2"></i>
            @if( $personType === 'pj' )
                Informações da Empresa
            @else
                Informações Pessoais
            @endif
        </h6>
    </div>
    <div class="col-12">
        <div class="mb-3">
            <label class="small text-muted">Tipo de Pessoa</label>
            <p class="mb-0 fw-semibold">
                @if( $personType === 'pf' )
                    <span class="badge bg-info">Pessoa Física</span>
                @elseif( $personType === 'pj' )
                    <span class="badge bg-success">Pessoa Jurídica</span>
                @else
                    <span class="badge bg-secondary">Não informado</span>
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
        <div class="col-md-6">
            <div class="mb-3">
                <label class="small text-muted">Razão Social</label>
                <p class="mb-0 fw-semibold">{{ $provider?->commonData?->company_name ?? 'Não informado' }}</p>
            </div>
        </div>
        @if($provider?->businessData?->fantasy_name)
        <div class="col-md-6">
            <div class="mb-3">
                <label class="small text-muted">Nome Fantasia</label>
                <p class="mb-0">{{ $provider->businessData->fantasy_name }}</p>
            </div>
        </div>
        @endif
        @if($provider?->businessData?->founding_date)
        <div class="col-md-6">
            <div class="mb-3">
                <label class="small text-muted">Data de Fundação</label>
                <p class="mb-0">{{ $provider->businessData->founding_date->format('d/m/Y') }}</p>
            </div>
        </div>
        @endif
        @if($provider?->businessData?->industry)
        <div class="col-md-6">
            <div class="mb-3">
                <label class="small text-muted">Setor</label>
                <p class="mb-0">{{ $provider->businessData->industry }}</p>
            </div>
        </div>
        @endif
        @if($provider?->businessData?->company_size)
        <div class="col-md-6">
            <div class="mb-3">
                <label class="small text-muted">Porte da Empresa</label>
                <p class="mb-0">{{ $provider->businessData->company_size }}</p>
            </div>
        </div>
        @endif
    @endif
    {{-- Seção: Endereço --}}
    <div class="col-12">
        <h6 class="text-muted mb-3 mt-4">
            <i class="bi bi-geo-alt me-2"></i>Endereço
        </h6>
    </div>
    @if($provider?->address)
        <div class="col-md-8">
            <div class="mb-3">
                <label class="small text-muted">Logradouro</label>
                <p class="mb-0">
                    {{ $provider->address->address ?? 'Não informado' }}
                    @if($provider->address->address_number)
                        , {{ $provider->address->address_number }}
                    @endif
                </p>
            </div>
        </div>
        <div class="col-md-4">
            <div class="mb-3">
                <label class="small text-muted">CEP</label>
                <p class="mb-0">{{ $provider->address->cep ?? 'Não informado' }}</p>
            </div>
        </div>
        <div class="col-md-6">
            <div class="mb-3">
                <label class="small text-muted">Bairro</label>
                <p class="mb-0">{{ $provider->address->neighborhood ?? 'Não informado' }}</p>
            </div>
        </div>
        <div class="col-md-6">
            <div class="mb-3">
                <label class="small text-muted">Cidade/Estado</label>
                <p class="mb-0">
                    {{ $provider->address->city ?? 'Não informado' }}
                    @if($provider->address->state)
                        / {{ $provider->address->state }}
                    @endif
                </p>
            </div>
        </div>
    @else
        <div class="col-12">
            <div class="mb-3">
                <p class="text-muted mb-0">Endereço não informado</p>
            </div>
        </div>
    @endif
@endif
