{{--
Campos pessoais do cliente
Uso: @include('customer.personal-fields', ['customer' => $customer])
--}}

@props( [ 'customer' => null ] )

<div class="mb-3">
    <label for="first_name" class="form-label">Nome</label>
    <input type="text" class="form-control" id="first_name" name="first_name"
        value="{{ $customer->first_name ?? old( 'first_name' ) }}" required>
    @error( 'first_name' )
        <div class="text-danger">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="last_name" class="form-label">Sobrenome</label>
    <input type="text" class="form-control" id="last_name" name="last_name"
        value="{{ $customer->last_name ?? old( 'last_name' ) }}" required>
    @error( 'last_name' )
        <div class="text-danger">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="email" class="form-label">Email</label>
    <input type="email" class="form-control" id="email" name="email" value="{{ $customer->email ?? old( 'email' ) }}"
        required>
    @error( 'email' )
        <div class="text-danger">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="phone" class="form-label">Celular</label>
    <input type="tel" class="form-control" id="phone" name="phone" value="{{ $customer->phone ?? old( 'phone' ) }}"
        required>
    @error( 'phone' )
        <div class="text-danger">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="phone_business" class="form-label">Telefone Comercial</label>
    <input type="tel" class="form-control" id="phone_business" name="phone_business"
        value="{{ $customer->phone_business ?? old( 'phone_business' ) }}" required>
    @error( 'phone_business' )
        <div class="text-danger">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="birth_date" class="form-label">Data de Nascimento</label>
    <input type="date" class="form-control" id="birth_date" name="birth_date"
        value="{{ isset( $customer->birth_date ) ? \Carbon\Carbon::parse( $customer->birth_date )->format( 'Y-m-d' ) : old( 'birth_date' ) }}"
        required>
    @error( 'birth_date' )
        <div class="text-danger">{{ $message }}</div>
    @enderror
</div>
