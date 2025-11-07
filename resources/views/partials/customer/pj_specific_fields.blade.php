<div class="alert alert-info py-2 mb-3">
  <small class="mb-0">
    <i class="bi bi-building-fill me-1"></i>
    <strong>Campos específicos para Pessoa Jurídica</strong>
  </small>
</div>

<div class="mb-3">
  <label for="company_name" class="form-label">Razão Social</label>
  <input type="text" class="form-control @error( 'company_name' ) is-invalid @enderror" id="company_name"
    name="company_name" value="{{ old( 'company_name', $customer->company_name ?? '' ) }}">
  @error( 'company_name' )
    <div class="invalid-feedback">{{ $message }}</div>
  @enderror
</div>

<div class="mb-3">
  <label for="fantasy_name" class="form-label">Nome Fantasia</label>
  <input type="text" class="form-control @error( 'fantasy_name' ) is-invalid @enderror" id="fantasy_name"
    name="fantasy_name" value="{{ old( 'fantasy_name', $customer->fantasy_name ?? '' ) }}">
  @error( 'fantasy_name' )
    <div class="invalid-feedback">{{ $message }}</div>
  @enderror
</div>

<div class="mb-3">
  <label for="cnpj" class="form-label">CNPJ</label>
  <input type="text" class="form-control @error( 'cnpj' ) is-invalid @enderror" id="cnpj" name="cnpj"
    value="{{ old( 'cnpj', $customer->commonData->cnpj ?? '' ) }}" placeholder="00.000.000/0000-00">
  @error( 'cnpj' )
    <div class="invalid-feedback">{{ $message }}</div>
  @enderror
</div>

<div class="mb-3">
  <label for="state_registration" class="form-label">Inscrição Estadual</label>
  <input type="text" class="form-control @error( 'state_registration' ) is-invalid @enderror" id="state_registration"
    name="state_registration" value="{{ old( 'state_registration', $customer->state_registration ?? '' ) }}">
  @error( 'state_registration' )
    <div class="invalid-feedback">{{ $message }}</div>
  @enderror
</div>

<div class="mb-3">
  <label for="municipal_registration" class="form-label">Inscrição Municipal</label>
  <input type="text" class="form-control @error( 'municipal_registration' ) is-invalid @enderror"
    id="municipal_registration" name="municipal_registration"
    value="{{ old( 'municipal_registration', $customer->municipal_registration ?? '' ) }}">
  @error( 'municipal_registration' )
    <div class="invalid-feedback">{{ $message }}</div>
  @enderror
</div>

<div class="mb-3">
  <label for="founding_date" class="form-label">Data de Fundação</label>
  <input type="text" class="form-control @error( 'founding_date' ) is-invalid @enderror" id="founding_date"
    name="founding_date" value="{{ old( 'founding_date', $customer->founding_date ?? '' ) }}" placeholder="DD/MM/AAAA">
  @error( 'founding_date' )
    <div class="invalid-feedback">{{ $message }}</div>
  @enderror
</div>

<div class="mb-3">
  <label for="email_business" class="form-label">Email Empresarial</label>
  <input type="email" class="form-control @error( 'email_business' ) is-invalid @enderror" id="email_business"
    name="email_business" value="{{ old( 'email_business', $customer->contact->email_business ?? '' ) }}">
  @error( 'email_business' )
    <div class="invalid-feedback">{{ $message }}</div>
  @enderror
</div>

<div class="mb-3">
  <label for="phone_business" class="form-label">Telefone Empresarial</label>
  <input type="tel" class="form-control @error( 'phone_business' ) is-invalid @enderror" id="phone_business"
    name="phone_business" value="{{ old( 'phone_business', $customer->contact->phone_business ?? '' ) }}">
  @error( 'phone_business' )
    <div class="invalid-feedback">{{ $message }}</div>
  @enderror
</div>

<div class="mb-3">
  <label for="company_website" class="form-label">Website</label>
  <input type="url" class="form-control @error( 'company_website' ) is-invalid @enderror" id="company_website"
    name="company_website" value="{{ old( 'company_website', $customer->company_website ?? '' ) }}">
  @error( 'company_website' )
    <div class="invalid-feedback">{{ $message }}</div>
  @enderror
</div>

<div class="mb-3">
  <label for="industry" class="form-label">Setor de Atuação</label>
  <input type="text" class="form-control @error( 'industry' ) is-invalid @enderror" id="industry" name="industry"
    value="{{ old( 'industry', $customer->industry ?? '' ) }}">
  @error( 'industry' )
    <div class="invalid-feedback">{{ $message }}</div>
  @enderror
</div>

<div class="mb-3">
  <label for="company_size" class="form-label">Porte da Empresa</label>
  <select name="company_size" class="form-select @error( 'company_size' ) is-invalid @enderror" id="company_size">
    <option value="">Selecione o porte</option>
    <option value="micro" {{ old( 'company_size' ) == 'micro' ? 'selected' : '' }}>Microempresa</option>
    <option value="pequena" {{ old( 'company_size' ) == 'pequena' ? 'selected' : '' }}>Pequena</option>
    <option value="media" {{ old( 'company_size' ) == 'media' ? 'selected' : '' }}>Média</option>
    <option value="grande" {{ old( 'company_size' ) == 'grande' ? 'selected' : '' }}>Grande</option>
  </select>
  @error( 'company_size' )
    <div class="invalid-feedback">{{ $message }}</div>
  @enderror
</div>

<div class="mb-3">
  <label for="notes" class="form-label">Observações</label>
  <textarea class="form-control @error( 'notes' ) is-invalid @enderror" id="notes" name="notes" rows="3"
    maxlength="1000"
    placeholder="Observações sobre a empresa...">{{ old( 'notes', $customer->notes ?? '' ) }}</textarea>
  @error( 'notes' )
    <div class="invalid-feedback">{{ $message }}</div>
  @enderror
</div>
