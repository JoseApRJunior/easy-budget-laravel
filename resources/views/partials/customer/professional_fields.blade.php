<div class="mb-3">
    <label for="company_name" class="form-label">Nome da Empresa</label>
    <input type="text" class="form-control @error( 'company_name' ) is-invalid @enderror" id="company_name"
        name="company_name" value="{{ old( 'company_name', $customer->company_name ?? '' ) }}">
    @error( 'company_name' )
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="email_business" class="form-label">Email Comercial</label>
    <input type="email" class="form-control @error( 'email_business' ) is-invalid @enderror" id="email_business"
        name="email_business" value="{{ old( 'email_business', $customer->contact->email_business ?? '' ) }}" required>
    @error( 'email_business' )
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="cnpj" class="form-label">CNPJ</label>
    <input type="text" class="form-control @error( 'cnpj' ) is-invalid @enderror" id="cnpj" name="cnpj"
        value="{{ old( 'cnpj', $customer->commonData->cnpj ?? '' ) }}">
    @error( 'cnpj' )
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="cpf" class="form-label">CPF</label>
    <input type="text" class="form-control @error( 'cpf' ) is-invalid @enderror" id="cpf" name="cpf"
        value="{{ old( 'cpf', $customer->commonData->cpf ?? '' ) }}">
    @error( 'cpf' )
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="area_of_activity_id" class="form-label">Área de Atuação</label>
    <select name="area_of_activity_id" class="form-select @error( 'area_of_activity_id' ) is-invalid @enderror"
        id="area_of_activity" required>
        <option value="">Selecione uma área</option>
        @foreach ( $areas_of_activity as $area )
            <option value="{{ $area->id }}" {{ old( 'area_of_activity_id', $customer->commonData->area_of_activity_id ?? '' ) == $area->id ? 'selected' : '' }}>
                {{ $area->name }}
            </option>
        @endforeach
    </select>
    @error( 'area_of_activity_id' )
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>


<div class="mb-3">
    <label for="profession_id" class="form-label">Profissão</label>
    <select name="profession_id" class="form-select @error( 'profession_id' ) is-invalid @enderror" id="profession"
        required>
        <option value="">Selecione uma profissão</option>
        @foreach ( $professions as $prof )
            <option value="{{ $prof->id }}" {{ old( 'profession_id', $customer->commonData->profession_id ?? '' ) == $prof->id ? 'selected' : '' }}>
                {{ $prof->name }}
            </option>
        @endforeach
    </select>
    @error( 'profession_id' )
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="description" class="form-label">Descrição Profissional</label>
    <textarea class="form-control @error( 'description' ) is-invalid @enderror" id="description" name="description"
        rows="4" maxlength="250"
        placeholder="Descreva sua experiência profissional...">{{ old( 'description', $customer->commonData->description ?? '' ) }}</textarea>
    @error( 'description' )
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="mb-3">
    <label for="website" class="form-label">Website</label>
    <input type="url" class="form-control @error( 'website' ) is-invalid @enderror" id="website" name="website"
        value="{{ old( 'website', $customer->contact->website ?? '' ) }}">
    @error( 'website' )
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
