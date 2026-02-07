{{-- Seção: Informações de Login --}}
<div class="col-12">
    <h6 class="text-muted mb-3">
        <i class="bi bi-person-circle me-2"></i>Informações de Login
    </h6>
</div>
<div class="col-md-6">
    <div class="mb-3">
        <label class="small text-muted">Nome Completo</label>
        <p class="mb-0 fw-semibold">{{ auth()->user()->name ?? 'Não informado' }}</p>
    </div>
</div>
<div class="col-md-6">
    <div class="mb-3">
        <label class="small text-muted">E-mail de Login</label>
        <p class="mb-0">{{ auth()->user()->email ?? 'Não informado' }}</p>
    </div>
</div>

{{-- Seção: Informações Pessoais --}}
<div class="col-12">
    <h6 class="text-muted mb-3 mt-4">
        <i class="bi bi-person-lines-fill me-2"></i>Informações Pessoais
    </h6>
</div>
<div class="col-md-6">
    <div class="mb-3">
        <label class="small text-muted">Primeiro Nome</label>
        <p class="mb-0">
            @if( auth()->user()->provider && auth()->user()->provider->commonData )
                {{ auth()->user()->provider->commonData->first_name ?? 'Não informado' }}
            @else
                Não informado
            @endif
        </p>
    </div>
</div>
<div class="col-md-6">
    <div class="mb-3">
        <label class="small text-muted">Sobrenome</label>
        <p class="mb-0">
            @if( auth()->user()->provider && auth()->user()->provider->commonData )
                {{ auth()->user()->provider->commonData->last_name ?? 'Não informado' }}
            @else
                Não informado
            @endif
        </p>
    </div>
</div>
<div class="col-md-6">
    <div class="mb-3">
        <label class="small text-muted">Data de Nascimento</label>
        <p class="mb-0">
            @if( auth()->user()->provider && auth()->user()->provider->commonData && auth()->user()->provider->commonData->birth_date )
                {{ auth()->user()->provider->commonData->birth_date->format( 'd/m/Y' ) }}
            @else
                Não informado
            @endif
        </p>
    </div>
</div>
<div class="col-md-6">
    <div class="mb-3">
        <label class="small text-muted">CPF</label>
        <p class="mb-0">
            @if( auth()->user()->provider && auth()->user()->provider->commonData )
                {{ auth()->user()->provider->commonData->cpf ? format_cpf( auth()->user()->provider->commonData->cpf ) : 'Não informado' }}
            @else
                Não informado
            @endif
        </p>
    </div>
</div>

{{-- Seção: Contato --}}
<div class="col-12">
    <h6 class="text-muted mb-3 mt-4">
        <i class="bi bi-telephone me-2"></i>Informações de Contato
    </h6>
</div>
<div class="col-md-6">
    <div class="mb-3">
        <label class="small text-muted">E-mail Pessoal</label>
        <p class="mb-0">
            @if( auth()->user()->provider && auth()->user()->provider->contact )
                {{ auth()->user()->provider->contact->email_personal ?? 'Não informado' }}
            @else
                Não informado
            @endif
        </p>
    </div>
</div>
<div class="col-md-6">
    <div class="mb-3">
        <label class="small text-muted">Telefone Pessoal</label>
        <p class="mb-0">
            @if( auth()->user()->provider && auth()->user()->provider->contact )
                {{ auth()->user()->provider->contact->phone_personal ? format_phone( auth()->user()->provider->contact->phone_personal ) : 'Não informado' }}
            @else
                Não informado
            @endif
        </p>
    </div>
</div>
<div class="col-md-6">
    <div class="mb-3">
        <label class="small text-muted">E-mail Comercial</label>
        <p class="mb-0">
            @if( auth()->user()->provider && auth()->user()->provider->contact )
                {{ auth()->user()->provider->contact->email_business ?? 'Não informado' }}
            @else
                Não informado
            @endif
        </p>
    </div>
</div>
<div class="col-md-6">
    <div class="mb-3">
        <label class="small text-muted">Telefone Comercial</label>
        <p class="mb-0">
            @if( auth()->user()->provider && auth()->user()->provider->contact )
                {{ auth()->user()->provider->contact->phone_business ? format_phone( auth()->user()->provider->contact->phone_business ) : 'Não informado' }}
            @else
                Não informado
            @endif
        </p>
    </div>
</div>
