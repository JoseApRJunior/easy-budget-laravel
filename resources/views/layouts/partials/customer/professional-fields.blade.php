{{--
Campos profissionais do cliente
Uso: @include('customer.professional-fields', ['customer' => $customer, 'areas_of_activity' => $areas_of_activity,
'professions' => $professions])
--}}

@props( [ 'customer' => null, 'areas_of_activity' => [], 'professions' => [] ] )

<div class="mb-3">
    <label for="company_name" class="form-label">Nome da Empresa</label>
    <input type="text" class="form-control" id="company_name" name="company_name"
        value="{{ $customer->company_name ?? old( 'company_name' ) }}">
    @error( 'company_name' )
                <div class="text-danger">{{ $message }}
        </div>
    @enderror
</div> <div class="mb-3">
<label for="email_business" class="form-label">Email Comercial</label>
<input type="email" class="form-control" id="email_business" name="email_business"
    value="{{ $customer->email_business ?? old( 'email_business' ) }}" required>
@error( 'email_business' )
    <div class="text-danger">{{ $message }}</div>
@enderror
</div>

<div class="mb-3">
    <label for="cnpj" class="form-label">CNPJ</label>
    <input type="text" class="form-control" id="cnpj" name="cnpj" value="{{ $customer->cnpj ?? old( 'cnpj' ) }}">
    @error( 'cnpj' )
        <div class="text-danger">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="cpf" class="form-label">CPF</label>
    <input type="text" class="form-control" id="cpf" name="cpf" value="{{ $customer->cpf ?? old( 'cpf' ) }}">
    @error( 'cpf' )
        <div class="text-danger">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="area_of_activity_id" class="form-label">Área de Atuação</label>
    <select name="area_of_activity_id" class="form-control" id="area_of_activity" required>
        @foreach( $areas_of_activity as $area )
            <option value="{{ $area->id }}" {{ ( $customer->area_of_activity_id ?? old( 'area_of_activity_id' ) ) == $area->id ? 'selected' : '' }}>
                {{ $area->name }}
            </option>
        @endforeach
    </select>
    @error( 'area_of_activity_id' )
        <div class="text-danger">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="profession_id" class="form-label">Profissão</label>
    <select name="profession_id" class="form-control" id="profession" required>
        @foreach( $professions as $prof )
            <option value="{{ $prof->id }}" {{ ( $customer->profession_id ?? old( 'profession_id' ) ) == $prof->id ? 'selected' : '' }}>
                {{ $prof->name }}
            </option>
        @endforeach
    </select>
    @error( 'profession_id' )
        <div class="text-danger">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="website" class="form-label">Website</label>
    <input type="url" class="form-control" id="website" name="website"
        value="{{ $customer->website ?? old( 'website' ) }}">
    @error( 'website' )
        <div class="text-danger">{{ $message }}</div>
    @enderror
</div>
