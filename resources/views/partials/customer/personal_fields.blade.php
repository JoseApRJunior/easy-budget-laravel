<div class="mb-3">
    <label for="first_name" class="form-label">Nome</label>
    <input type="text" class="form-control @error( 'first_name' ) is-invalid @enderror" id="first_name"
        name="first_name" value="{{ old( 'first_name', $customer->commonData->first_name ?? '' ) }}" required>
    @error( 'first_name' )
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="last_name" class="form-label">Sobrenome</label>
    <input type="text" class="form-control @error( 'last_name' ) is-invalid @enderror" id="last_name" name="last_name"
        value="{{ old( 'last_name', $customer->commonData->last_name ?? '' ) }}" required>
    @error( 'last_name' )
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="email_personal" class="form-label">Email Pessoal</label>
    <input type="email" class="form-control @error( 'email_personal' ) is-invalid @enderror" id="email_personal"
        name="email_personal" value="{{ old( 'email_personal', $customer->contact->email ?? '' ) }}" required>
    @error( 'email_personal' )
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="phone_personal" class="form-label">Telefone Pessoal</label>
    <input type="tel" class="form-control @error( 'phone_personal' ) is-invalid @enderror" id="phone_personal" name="phone_personal"
        value="{{ old( 'phone_personal', $customer->contact->phone ?? '' ) }}" required>
    @error( 'phone_personal' )
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="phone_business" class="form-label">Telefone Comercial</label>
    <input type="tel" class="form-control @error( 'phone_business' ) is-invalid @enderror" id="phone_business"
        name="phone_business" value="{{ old( 'phone_business', $customer->contact->phone_business ?? '' ) }}">
    @error( 'phone_business' )
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="birth_date" class="form-label">Data de Nascimento</label>
    <input type="text" class="form-control @error( 'birth_date' ) is-invalid @enderror" id="birth_date"
        name="birth_date"
        value="{{ old( 'birth_date', $customer->birth_date ? \Carbon\Carbon::parse( $customer->birth_date )->format( 'd/m/Y' ) : '' ) }}"
        placeholder="DD/MM/AAAA" required>
    <div id="birth_date_js_error" class="text-danger small mt-1" style="display:none;"></div>
    @error( 'birth_date' )
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
