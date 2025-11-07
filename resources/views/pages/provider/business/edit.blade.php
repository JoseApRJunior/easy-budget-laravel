@extends( 'layouts.app' )

@section( 'title', 'Dados Empresariais' )

@section( 'content' )
    <div class="container-fluid py-4">
        <!-- Cabeçalho -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0">
                <i class="bi bi-building me-2"></i>Dados Empresariais
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="{{ route( 'provider.dashboard' ) }}">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="{{ route( 'settings.index' ) }}">Configurações</a></li>
                    <li class="breadcrumb-item active">Dados Empresariais</li>
                </ol>
            </nav>
        </div>

        <form action="{{ route( 'provider.business.update' ) }}" method="POST" enctype="multipart/form-data" id="businessForm">
            @csrf
            @method( 'PUT' )

            <div class="row g-4">
                <!-- Dados Pessoais -->
                <div class="col-lg-6">
                    <div class="card h-100">
                        <div class="card-header bg-transparent">
                            <h5 class="mb-0"><i class="bi bi-person me-2"></i>Dados Pessoais</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="first_name" class="form-label">Nome</label>
                                <input type="text" class="form-control @error('first_name') is-invalid @enderror"
                                       id="first_name" name="first_name"
                                       value="{{ old('first_name', $provider->commonData?->first_name ?? '') }}">
                                @error('first_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="last_name" class="form-label">Sobrenome</label>
                                <input type="text" class="form-control @error('last_name') is-invalid @enderror"
                                       id="last_name" name="last_name"
                                       value="{{ old('last_name', $provider->commonData?->last_name ?? '') }}">
                                @error('last_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="birth_date" class="form-label">Data de Nascimento</label>
                                <input type="text" class="form-control @error('birth_date') is-invalid @enderror"
                                       id="birth_date" name="birth_date"
                                       value="{{ old('birth_date', $provider->commonData?->birth_date ? \Carbon\Carbon::parse($provider->commonData->birth_date)->format('d/m/Y') : '') }}"
                                       placeholder="DD/MM/AAAA">
                                <div id="birth_date_js_error" class="text-danger small mt-1" style="display:none;"></div>
                                @error('birth_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="email_personal" class="form-label">Email Pessoal</label>
                                <input type="email" class="form-control @error('email_personal') is-invalid @enderror"
                                       id="email_personal" name="email_personal"
                                       value="{{ old('email_personal', $provider->contact?->email_personal ?? $provider->contact?->email ?? '') }}">
                                @error('email_personal')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="phone_personal" class="form-label">Telefone Pessoal</label>
                                <input type="tel" class="form-control @error('phone_personal') is-invalid @enderror"
                                       id="phone_personal" name="phone_personal"
                                       value="{{ old('phone_personal', $provider->contact?->phone_personal ?? $provider->contact?->phone ?? '') }}">
                                @error('phone_personal')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Dados Profissionais -->
                <div class="col-lg-6">
                    <div class="card h-100">
                        <div class="card-header bg-transparent">
                            <h5 class="mb-0"><i class="bi bi-briefcase me-2"></i>Dados Profissionais</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="person_type" class="form-label">Tipo de Pessoa</label>
                                <select name="person_type" id="person_type"
                                    class="form-select @error( 'person_type' ) is-invalid @enderror" required>
                                    <option value="">Selecione o tipo</option>
                                    @php
                                        $currentType = old('person_type', ($provider->commonData?->type === 'company' ? 'pj' : 'pf'));
                                    @endphp
                                    <option value="pf" {{ $currentType == 'pf' ? 'selected' : '' }}>Pessoa Física</option>
                                    <option value="pj" {{ $currentType == 'pj' ? 'selected' : '' }}>Pessoa Jurídica</option>
                                </select>
                                @error( 'person_type' )
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Campos PF -->
                            <div id="pf_fields">
                                <div class="mb-3">
                                    <label for="cpf" class="form-label">CPF</label>
                                    <input type="text" class="form-control @error( 'cpf' ) is-invalid @enderror"
                                           id="cpf" name="cpf"
                                           value="{{ old( 'cpf', $provider->commonData?->cpf ?? '' ) }}">
                                    @error( 'cpf' )
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- Campos PJ -->
                            <div id="pj_fields">
                                <div class="mb-3">
                                    <label for="company_name" class="form-label">Razão Social</label>
                                    <input type="text" class="form-control @error( 'company_name' ) is-invalid @enderror"
                                           id="company_name" name="company_name"
                                           value="{{ old( 'company_name', $provider->commonData?->company_name ?? '' ) }}">
                                    @error( 'company_name' )
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="cnpj" class="form-label">CNPJ</label>
                                    <input type="text" class="form-control @error( 'cnpj' ) is-invalid @enderror"
                                           id="cnpj" name="cnpj"
                                           value="{{ old( 'cnpj', $provider->commonData?->cnpj ?? '' ) }}">
                                    @error( 'cnpj' )
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="area_of_activity_id" class="form-label">Área de Atuação</label>
                                <select name="area_of_activity_id" class="form-select @error( 'area_of_activity_id' ) is-invalid @enderror"
                                        id="area_of_activity" required>
                                    <option value="">Selecione uma área</option>
                                    @foreach ( $areas_of_activity as $area )
                                        <option value="{{ $area->id }}"
                                            {{ old( 'area_of_activity_id', $provider->commonData?->area_of_activity_id ?? '' ) == $area->id ? 'selected' : '' }}>
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
                                <select name="profession_id" class="form-select @error( 'profession_id' ) is-invalid @enderror"
                                        id="profession" required>
                                    <option value="">Selecione uma profissão</option>
                                    @foreach ( $professions as $prof )
                                        <option value="{{ $prof->id }}"
                                            {{ old( 'profession_id', $provider->commonData?->profession_id ?? '' ) == $prof->id ? 'selected' : '' }}>
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
                                <textarea class="form-control @error( 'description' ) is-invalid @enderror"
                                          id="description" name="description" rows="3" maxlength="250"
                                          placeholder="Descreva sua experiência profissional...">{{ old( 'description', $provider->commonData?->description ?? '' ) }}</textarea>
                                @error( 'description' )
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Contato -->
                <div class="col-lg-6">
                    <div class="card h-100">
                        <div class="card-header bg-transparent">
                            <h5 class="mb-0"><i class="bi bi-envelope me-2"></i>Contato</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="email_business" class="form-label">Email Empresarial</label>
                                <input type="email" class="form-control @error( 'email_business' ) is-invalid @enderror"
                                       id="email_business" name="email_business"
                                       value="{{ old( 'email_business', $provider->contact?->email_business ?? '' ) }}">
                                @error( 'email_business' )
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="phone_business" class="form-label">Telefone Empresarial</label>
                                <input type="tel" class="form-control @error( 'phone_business' ) is-invalid @enderror"
                                       id="phone_business" name="phone_business"
                                       value="{{ old( 'phone_business', $provider->contact?->phone_business ?? '' ) }}">
                                @error( 'phone_business' )
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="website" class="form-label">Website</label>
                                <input type="url" class="form-control @error( 'website' ) is-invalid @enderror"
                                       id="website" name="website"
                                       value="{{ old( 'website', $provider->contact?->website ?? '' ) }}">
                                @error( 'website' )
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Endereço -->
                <div class="col-lg-6">
                    <div class="card h-100">
                        <div class="card-header bg-transparent">
                            <h5 class="mb-0"><i class="bi bi-geo-alt me-2"></i>Endereço</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="cep" class="form-label">CEP</label>
                                    <input type="text" class="form-control @error( 'cep' ) is-invalid @enderror"
                                           id="cep" name="cep" data-cep-lookup
                                           value="{{ old( 'cep', $provider->address?->cep ?? '' ) }}" required>
                                    @error( 'cep' )
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="address_number" class="form-label">Número</label>
                                    <input type="text" class="form-control @error( 'address_number' ) is-invalid @enderror"
                                           id="address_number" name="address_number"
                                           value="{{ old( 'address_number', $provider->address?->address_number ?? '' ) }}">
                                    @error( 'address_number' )
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="address" class="form-label">Endereço</label>
                                <input type="text" class="form-control @error( 'address' ) is-invalid @enderror"
                                       id="address" name="address"
                                       value="{{ old( 'address', $provider->address?->address ?? '' ) }}" required>
                                @error( 'address' )
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="neighborhood" class="form-label">Bairro</label>
                                <input type="text" class="form-control @error( 'neighborhood' ) is-invalid @enderror"
                                       id="neighborhood" name="neighborhood"
                                       value="{{ old( 'neighborhood', $provider->address?->neighborhood ?? '' ) }}" required>
                                @error( 'neighborhood' )
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="row">
                                <div class="col-md-8 mb-3">
                                    <label for="city" class="form-label">Cidade</label>
                                    <input type="text" class="form-control @error( 'city' ) is-invalid @enderror"
                                           id="city" name="city"
                                           value="{{ old( 'city', $provider->address?->city ?? '' ) }}" required>
                                    @error( 'city' )
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label for="state" class="form-label">Estado</label>
                                    <input type="text" class="form-control @error( 'state' ) is-invalid @enderror"
                                           id="state" name="state"
                                           value="{{ old( 'state', $provider->address?->state ?? '' ) }}" required>
                                    @error( 'state' )
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Dados Empresariais Adicionais (PJ) -->
                <div class="col-12" id="business-data-section" style="display: none;">
                    <div class="card">
                        <div class="card-header bg-transparent">
                            <h5 class="mb-0"><i class="bi bi-building-gear me-2"></i>Dados Empresariais Adicionais</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="fantasy_name" class="form-label">Nome Fantasia</label>
                                    <input type="text" class="form-control @error( 'fantasy_name' ) is-invalid @enderror"
                                           id="fantasy_name" name="fantasy_name"
                                           value="{{ old( 'fantasy_name', $provider->businessData?->fantasy_name ?? '' ) }}">
                                    @error( 'fantasy_name' )
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="founding_date" class="form-label">Data de Fundação</label>
                                    <input type="text" class="form-control @error( 'founding_date' ) is-invalid @enderror"
                                           id="founding_date" name="founding_date"
                                           value="{{ old( 'founding_date', $provider->businessData?->founding_date ? \Carbon\Carbon::parse($provider->businessData->founding_date)->format('d/m/Y') : '' ) }}"
                                           placeholder="DD/MM/AAAA">
                                    @error( 'founding_date' )
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="state_registration" class="form-label">Inscrição Estadual</label>
                                    <input type="text" class="form-control @error( 'state_registration' ) is-invalid @enderror"
                                           id="state_registration" name="state_registration"
                                           value="{{ old( 'state_registration', $provider->businessData?->state_registration ?? '' ) }}">
                                    @error( 'state_registration' )
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="municipal_registration" class="form-label">Inscrição Municipal</label>
                                    <input type="text" class="form-control @error( 'municipal_registration' ) is-invalid @enderror"
                                           id="municipal_registration" name="municipal_registration"
                                           value="{{ old( 'municipal_registration', $provider->businessData?->municipal_registration ?? '' ) }}">
                                    @error( 'municipal_registration' )
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="industry" class="form-label">Setor de Atuação</label>
                                    <input type="text" class="form-control @error( 'industry' ) is-invalid @enderror"
                                           id="industry" name="industry"
                                           value="{{ old( 'industry', $provider->businessData?->industry ?? '' ) }}">
                                    @error( 'industry' )
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="company_size" class="form-label">Porte da Empresa</label>
                                    <select name="company_size" class="form-select @error( 'company_size' ) is-invalid @enderror"
                                            id="company_size">
                                        <option value="">Selecione</option>
                                        <option value="micro" {{ old( 'company_size', $provider->businessData?->company_size ?? '' ) == 'micro' ? 'selected' : '' }}>Microempresa</option>
                                        <option value="pequena" {{ old( 'company_size', $provider->businessData?->company_size ?? '' ) == 'pequena' ? 'selected' : '' }}>Pequena Empresa</option>
                                        <option value="media" {{ old( 'company_size', $provider->businessData?->company_size ?? '' ) == 'media' ? 'selected' : '' }}>Média Empresa</option>
                                        <option value="grande" {{ old( 'company_size', $provider->businessData?->company_size ?? '' ) == 'grande' ? 'selected' : '' }}>Grande Empresa</option>
                                    </select>
                                    @error( 'company_size' )
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12 mb-3">
                                    <label for="notes" class="form-label">Observações</label>
                                    <textarea class="form-control @error( 'notes' ) is-invalid @enderror"
                                              id="notes" name="notes" rows="3"
                                              placeholder="Informações adicionais sobre a empresa...">{{ old( 'notes', $provider->businessData?->notes ?? '' ) }}</textarea>
                                    @error( 'notes' )
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Logo -->
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-transparent">
                            <h5 class="mb-0"><i class="bi bi-image me-2"></i>Logo da Empresa</h5>
                        </div>
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-md-3 text-center mb-3 mb-md-0">
                                    <img id="logo-preview"
                                          src="{{ $provider->user->logo ? asset( 'storage/' . $provider->user->logo ) : asset( 'assets/img/img_not_found.png' ) }}"
                                          alt="Logo" class="img-fluid rounded shadow-sm"
                                          style="max-width: 150px; max-height: 150px;">
                                </div>
                                <div class="col-md-9">
                                    <label for="logo" class="btn btn-outline-primary mb-2">
                                        <i class="bi bi-camera-fill me-2"></i>Alterar Logo
                                    </label>
                                    <input type="file" class="d-none" id="logo" name="logo"
                                            accept="image/png,image/jpeg,image/jpg">
                                    <p class="text-muted small mb-0">
                                        A logo será exibida em orçamentos, faturas e relatórios. Tamanho máximo: 5MB.
                                    </p>
                                    @error( 'logo' )
                                        <span class="text-danger small">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Botões -->
            <div class="d-flex justify-content-between mt-4">
                <div>
                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        <i class="bi bi-check-circle me-2"></i>Atualizar Dados
                    </button>
                    <a href="{{ route( 'settings.index' ) }}" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle me-2"></i>Cancelar
                    </a>
                </div>
                <a href="{{ route( 'settings.profile.edit' ) }}" class="btn btn-outline-info">
                    <i class="bi bi-person me-2"></i>Perfil Pessoal
                </a>
            </div>
        </form>
    </div>
@endsection

@push( 'scripts' )
<script>
document.addEventListener('DOMContentLoaded', function() {
    function togglePersonFields() {
        const type = document.getElementById('person_type').value;
        const pfFields = document.getElementById('pf_fields');
        const pjFields = document.getElementById('pj_fields');
        const businessSection = document.getElementById('business-data-section');

        pfFields.style.display = type === 'pf' ? 'block' : 'none';
        pjFields.style.display = type === 'pj' ? 'block' : 'none';
        businessSection.style.display = type === 'pj' ? 'block' : 'none';
    }

    togglePersonFields();

    setTimeout(() => {
        if (typeof VanillaMask !== 'undefined') {
            // Inicializar máscaras básicas
            new VanillaMask('phone_personal', 'phone');
            new VanillaMask('phone_business', 'phone');
            new VanillaMask('cep', 'cep');

            // Inicializar máscara baseada no tipo de pessoa atual
            const type = document.getElementById('person_type').value;
            if (type === 'pf') {
                new VanillaMask('cpf', 'cpf');
            } else if (type === 'pj') {
                new VanillaMask('cnpj', 'cnpj');
            }

            // Aplicar formatação aos valores existentes nos campos
            const cpfField = document.getElementById('cpf');
            const cnpjField = document.getElementById('cnpj');

            if (cpfField && cpfField.value && type === 'pf') {
                cpfField.value = window.formatCPF ? window.formatCPF(cpfField.value) : cpfField.value;
            }

            if (cnpjField && cnpjField.value && type === 'pj') {
                cnpjField.value = window.formatCNPJ ? window.formatCNPJ(cnpjField.value) : cnpjField.value;
            }
        }
    }, 500);

    document.getElementById('person_type')?.addEventListener('change', function() {
        togglePersonFields();
        setTimeout(() => {
            if (typeof VanillaMask !== 'undefined') {
                const type = this.value;
                if (type === 'pf') {
                    new VanillaMask('cpf', 'cpf');
                } else if (type === 'pj') {
                    new VanillaMask('cnpj', 'cnpj');
                }
            }
        }, 200);
    });

    document.getElementById('logo')?.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file && file.size <= 5242880) {
            const reader = new FileReader();
            reader.onload = e => document.getElementById('logo-preview').src = e.target.result;
            reader.readAsDataURL(file);
        } else if (file) {
            alert('Arquivo muito grande. Máximo: 5MB');
            this.value = '';
        }
    });
});
</script>
@endpush
