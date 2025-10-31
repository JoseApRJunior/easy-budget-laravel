<div class="mb-3">
    <label for="cep" class="form-label">CEP</label>
    <input type="text" class="form-control @error( 'cep' ) is-invalid @enderror" id="cep" name="cep"
        value="{{ old( 'cep', $customer->address->cep ?? '' ) }}" required>
    @error( 'cep' )
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="address" class="form-label">Endereço</label>
    <input type="text" class="form-control @error( 'address' ) is-invalid @enderror" id="address" name="address"
        value="{{ old( 'address', $customer->address->address ?? '' ) }}" required>
    @error( 'address' )
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="address_number" class="form-label">Número</label>
    <input type="text" class="form-control @error( 'address_number' ) is-invalid @enderror" id="address_number"
        name="address_number" value="{{ old( 'address_number', $customer->address->address_number ?? '' ) }}">
    @error( 'address_number' )
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="neighborhood" class="form-label">Bairro</label>
    <input type="text" class="form-control @error( 'neighborhood' ) is-invalid @enderror" id="neighborhood"
        name="neighborhood" value="{{ old( 'neighborhood', $customer->address->neighborhood ?? '' ) }}" required>
    @error( 'neighborhood' )
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="city" class="form-label">Cidade</label>
    <input type="text" class="form-control @error( 'city' ) is-invalid @enderror" id="city" name="city"
        value="{{ old( 'city', $customer->address->city ?? '' ) }}" required>
    @error( 'city' )
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="state" class="form-label">Estado</label>
    <input type="text" class="form-control @error( 'state' ) is-invalid @enderror" id="state" name="state"
        value="{{ old( 'state', $customer->address->state ?? '' ) }}" required>
    @error( 'state' )
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
