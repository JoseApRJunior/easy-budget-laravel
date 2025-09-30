{{--
Campos pessoais do prestador
Uso: @include('provider.personal-fields', ['provider' => $provider])
--}}

@props( [ 'provider' => null ] )

<div class="mb-3">
    <label for="first_name" class="form-label">Nome</label>
    <input type="text" class="form-control" id="first_name" name="first_name"
        value="{{ $provider->first_name ?? old( 'first_name' ) }}" required>
    @error( 'first_name' )
        <div class="text-danger">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="last_name" class="form-label">Sobrenome</label>
    <input type="text" class="form-control" id="last_name" name="last_name"
        value="{{ $provider->last_name ?? old( 'last_name' ) }}" required>
    @error( 'last_name' )
        <div class="text-danger">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="email" class="form-label">Email</label>
    <input type="email" class="form-control" id="email" name="email" value="{{ $provider->email ?? old( 'email' ) }}"
        required>
    @error( 'email' )
        <div class="text-danger">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="phone" class="form-label">Celular</label>
    <input type="tel" class="form-control" id="phone" name="phone" value="{{ $provider->phone ?? old( 'phone' ) }}"
        required>
    @error( 'phone' )
        <div class="text-danger">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="phone_business" class="form-label">Telefone Comercial</label>
    <input type="tel" class="form-control" id="phone_business" name="phone_business"
        value="{{ $provider->phone_business ?? old( 'phone_business' ) }}" required>
    @error( 'phone_business' )
        <div class="text-danger">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="birth_date" class="form-label">Data de Nascimento</label>
    <input type="date" class="form-control" id="birth_date" name="birth_date"
        value="{{ isset( $provider->birth_date ) ? \Carbon\Carbon::parse( $provider->birth_date )->format( 'Y-m-d' ) : old( 'birth_date' ) }}"
        required>
    @error( 'birth_date' )
        <div class="text-danger">{{ $message }}</div>
    @enderror
</div>
