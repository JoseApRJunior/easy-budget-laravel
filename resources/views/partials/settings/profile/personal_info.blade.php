<div class="col-md-6">
    <div class="mb-3">
        <label class="small text-muted">Nome</label>
        <p class="mb-0 fw-semibold">
            @if( auth()->user()->provider && auth()->user()->provider->commonData )
                {{ auth()->user()->provider->commonData->first_name ?? 'Não informado' }}
                {{ auth()->user()->provider->commonData->last_name ?? '' }}
            @else
                {{ auth()->user()->name ?? 'Não informado' }}
            @endif
        </p>
    </div>
</div>
<div class="col-md-6">
    <div class="mb-3">
        <label class="small text-muted">E-mail</label>
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
        <label class="small text-muted">Telefone</label>
        <p class="mb-0">
            @if( auth()->user()->provider && auth()->user()->provider->contact )
                {{ auth()->user()->provider->contact->phone_personal ?? 'Não informado' }}
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
