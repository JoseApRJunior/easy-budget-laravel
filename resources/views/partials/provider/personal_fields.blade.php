<div class="mb-3">
    <label for="first_name" class="form-label">Nome</label>
    <input type="text" class="form-control @error( 'first_name' ) is-invalid @enderror" id="first_name" name="first_name"
        value="{{ old( 'first_name', $provider->first_name ?? '' ) }}" required>
    @error( 'first_name' )
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="last_name" class="form-label">Sobrenome</label>
    <input type="text" class="form-control @error( 'last_name' ) is-invalid @enderror" id="last_name" name="last_name"
        value="{{ old( 'last_name', $provider->last_name ?? '' ) }}" required>
    @error( 'last_name' )
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="email" class="form-label">Email</label>
    <input type="email" class="form-control @error( 'email' ) is-invalid @enderror" id="email" name="email"
        value="{{ old( 'email', $provider->email ?? '' ) }}" required>
    @error( 'email' )
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="phone" class="form-label">Celular</label>
    <input type="tel" class="form-control @error( 'phone' ) is-invalid @enderror" id="phone" name="phone"
        value="{{ old( 'phone', $provider->phone ?? '' ) }}" required>
    @error( 'phone' )
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="phone_business" class="form-label">Telefone Comercial</label>
    <input type="tel" class="form-control @error( 'phone_business' ) is-invalid @enderror" id="phone_business"
        name="phone_business" value="{{ old( 'phone_business', $provider->phone_business ?? '' ) }}" required>
    @error( 'phone_business' )
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="birth_date" class="form-label">Data de Nascimento</label>
    <input type="date" class="form-control @error( 'birth_date' ) is-invalid @enderror" id="birth_date" name="birth_date"
        value="{{ old( 'birth_date', $provider->birth_date ?? '' ) }}" required>
    @error( 'birth_date' )
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
