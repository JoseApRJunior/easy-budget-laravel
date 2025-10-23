<div class="col-md-6">
    <div class="mb-3">
        <label class="small text-muted">Nome</label>
        <p class="mb-0 fw-semibold">{{ auth()->user()->provider()->commonData()->first_name }}
            {{ auth()->user()->last_name }}</p>
    </div>
</div>
<div class="col-md-6">
    <div class="mb-3">
        <label class="small text-muted">E-mail</label>
        <p class="mb-0">{{ auth()->user()->email }}</p>
    </div>
</div>
<div class="col-md-6">
    <div class="mb-3">
        <label class="small text-muted">E-mail Comercial</label>
        <p class="mb-0">{{ auth()->user()->email_business }}</p>
    </div>
</div>
<div class="col-md-6">
    <div class="mb-3">
        <label class="small text-muted">Telefone</label>
        <p class="mb-0">{{ auth()->user()->phone ?? 'Não informado' }}</p>
    </div>
</div>
<div class="col-md-6">
    <div class="mb-3">
        <label class="small text-muted">Data de Nascimento</label>
        <p class="mb-0">
            {{ auth()->user()->birth_date ? auth()->user()->birth_date->format( 'd/m/Y' ) : 'Não informado' }}
        </p>
    </div>
</div>
