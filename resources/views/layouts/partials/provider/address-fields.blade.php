{{--
Campos de endereço do prestador
Uso: @include('provider.address-fields', ['provider' => $provider])
--}}

@props( [ 'provider' => null ] )

<div class="mb-3">
    <label for="cep" class="form-label">CEP</label>
    <input type="text" class="form-control" id="cep" name="cep" value="{{ $provider->cep ?? old( 'cep' ) }}" required>
    @error( 'cep' )
        <div class="text-danger">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="address" class="form-label">Endereço</label>
    <input type="text" class="form-control" id="address" name="address"
        value="{{ $provider->address ?? old( 'address' ) }}" required>
    @error( 'address' )
        <div class="text-danger">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="address_number" class="form-label">Número</label>
    <input type="text" class="form-control" id="address_number" name="address_number"
        value="{{ $provider->address_number ?? old( 'address_number' ) }}" required>
    @error( 'address_number' )
        <div class="text-danger">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="neighborhood" class="form-label">Bairro</label>
    <input type="text" class="form-control" id="neighborhood" name="neighborhood"
        value="{{ $provider->neighborhood ?? old( 'neighborhood' ) }}" required>
    @error( 'neighborhood' )
        <div class="text-danger">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="city" class="form-label">Cidade</label>
    <input type="text" class="form-control" id="city" name="city" value="{{ $provider->city ?? old( 'city' ) }}" required>
    @error( 'city' )
        <div class="text-danger">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="state" class="form-label">Estado</label>
    <input type="text" class="form-control" id="state" name="state" value="{{ $provider->state ?? old( 'state' ) }}"
        required>
    @error( 'state' )
        <div class="text-danger">{{ $message }}</div>
    @enderror
</div>
