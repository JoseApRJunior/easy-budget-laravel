<div class="alert alert-info py-2 mb-3">
  <small class="mb-0">
    <i class="bi bi-person-fill me-1"></i>
    <strong>Campos específicos para Pessoa Física</strong>
  </small>
</div>

<div class="mb-3">
  <label for="cpf" class="form-label">CPF</label>
  <input type="text" class="form-control @error( 'cpf' ) is-invalid @enderror" id="cpf" name="cpf"
    value="{{ old( 'cpf', $customer->commonData->cpf ?? '' ) }}" placeholder="000.000.000-00">
  @error( 'cpf' )
    <div class="invalid-feedback">{{ $message }}</div>
  @enderror
</div>

<div class="mb-3">
  <label for="birth_date" class="form-label">Data de Nascimento</label>
  <input type="text" class="form-control @error( 'birth_date' ) is-invalid @enderror" id="birth_date" name="birth_date"
    value="{{ old( 'birth_date', $customer->birth_date ? \Carbon\Carbon::parse( $customer->birth_date )->format( 'd/m/Y' ) : '' ) }}"
    placeholder="DD/MM/AAAA">
  <div id="birth_date_js_error" class="text-danger small mt-1" style="display:none;"></div>
  @error( 'birth_date' )
    <div class="invalid-feedback">{{ $message }}</div>
  @enderror
</div>

<div class="mb-3">
  <label for="area_of_activity_id" class="form-label">Área de Atuação</label>
  <select name="area_of_activity_id" class="form-select @error( 'area_of_activity_id' ) is-invalid @enderror"
    id="area_of_activity">
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
  <select name="profession_id" class="form-select @error( 'profession_id' ) is-invalid @enderror" id="profession">
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
    rows="3" maxlength="250"
    placeholder="Descreva sua experiência profissional...">{{ old( 'description', $customer->commonData->description ?? '' ) }}</textarea>
  <div class="d-flex justify-content-end mt-2">
    <small class="text-muted">
      <span id="char-count-value">250</span> caracteres restantes
    </small>
  </div>
  @error( 'description' )
    <div class="invalid-feedback">{{ $message }}</div>
  @enderror
</div>

<div class="mb-3">
  <label for="website" class="form-label">Website</label>
  <input type="text" class="form-control @error( 'website' ) is-invalid @enderror" id="website" name="website"
    value="{{ old( 'website', $customer->contact->website ?? '' ) }}" placeholder="ex: www.site.com.br">
  @error( 'website' )
    <div class="invalid-feedback">{{ $message }}</div>
  @enderror
</div>
