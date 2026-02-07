@extends('layouts.app')

@section('title', 'Dados Empresariais')

@section('content')
    <div class="container-fluid py-4">
        <x-layout.page-header
            title="Dados Empresariais"
            icon="building"
            :breadcrumb-items="[
                'Dashboard' => route('provider.dashboard'),
                'Configurações' => route('settings.index'),
                'Dados Empresariais' => '#'
            ]">
            <p class="text-muted mb-0 small">Gerencie as informações da sua empresa</p>
        </x-layout.page-header>

        <form action="{{ route('provider.business.update') }}" method="POST" enctype="multipart/form-data"
            id="businessForm">
            @csrf
            @method('PUT')

            <div class="row g-4">
                <!-- Dados Pessoais -->
                <div class="col-lg-6">
                    <x-ui.card title="Dados Pessoais" icon="person" class="h-100">
                        <x-ui.form.input 
                            label="Nome" 
                            name="first_name" 
                            id="first_name"
                            :value="old('first_name', $provider->commonData?->first_name ?? '')" 
                            :error="$errors->first('first_name')" 
                        />

                        <x-ui.form.input 
                            label="Sobrenome" 
                            name="last_name" 
                            id="last_name"
                            :value="old('last_name', $provider->commonData?->last_name ?? '')" 
                            :error="$errors->first('last_name')" 
                        />

                        <x-ui.form.input 
                            label="Data de Nascimento" 
                            name="birth_date" 
                            id="birth_date"
                            :value="old('birth_date', $provider->commonData?->birth_date ? \Carbon\Carbon::parse($provider->commonData->birth_date)->format('d/m/Y') : '')" 
                            placeholder="DD/MM/AAAA"
                            :error="$errors->first('birth_date')" 
                        />
                        <div id="birth_date_js_error" class="text-danger small mt-1" style="display:none;"></div>

                        <x-ui.form.input 
                            type="email"
                            label="Email Pessoal" 
                            name="email_personal" 
                            id="email_personal"
                            :value="old('email_personal', $provider->contact?->email_personal ?? ($provider->contact?->email ?? ''))" 
                            :error="$errors->first('email_personal')" 
                        />

                        <x-ui.form.input 
                            type="tel"
                            label="Telefone Pessoal" 
                            name="phone_personal" 
                            id="phone_personal"
                            :value="old('phone_personal', $provider->contact?->phone_personal ?? ($provider->contact?->phone ?? ''))" 
                            :error="$errors->first('phone_personal')" 
                        />
                    </x-ui.card>
                </div>

                <!-- Dados Profissionais -->
                <div class="col-lg-6">
                    <x-ui.card title="Dados Profissionais" icon="briefcase" class="h-100">
                        <x-ui.form.select 
                            label="Tipo de Pessoa" 
                            name="person_type" 
                            id="person_type" 
                            required
                            :error="$errors->first('person_type')">
                            <option value="">Selecione o tipo</option>
                            @php
                                $currentType = old(
                                    'person_type',
                                    $provider->commonData?->type === 'company' ? 'pj' : 'pf',
                                );
                            @endphp
                            <option value="pf" {{ $currentType == 'pf' ? 'selected' : '' }}>Pessoa Física</option>
                            <option value="pj" {{ $currentType == 'pj' ? 'selected' : '' }}>Pessoa Jurídica</option>
                        </x-ui.form.select>

                        <!-- Campos PF -->
                        <div id="pf_fields">
                            <x-ui.form.input 
                                label="CPF" 
                                name="cpf" 
                                id="cpf"
                                :value="format_cpf(old('cpf', $provider->commonData?->cpf ?? ''))" 
                                :error="$errors->first('cpf')" 
                            />
                        </div>

                        <!-- Campos PJ -->
                        <div id="pj_fields">
                            <x-ui.form.input 
                                label="Razão Social" 
                                name="company_name" 
                                id="company_name"
                                :value="old('company_name', $provider->commonData?->company_name ?? '')" 
                                :error="$errors->first('company_name')" 
                            />

                            <x-ui.form.input 
                                label="CNPJ" 
                                name="cnpj" 
                                id="cnpj"
                                :value="format_cnpj(old('cnpj', $provider->commonData?->cnpj ?? ''))" 
                                :error="$errors->first('cnpj')" 
                            />
                        </div>

                        <x-ui.form.select 
                            label="Área de Atuação" 
                            name="area_of_activity_id" 
                            id="area_of_activity" 
                            required
                            :error="$errors->first('area_of_activity_id')">
                            <option value="">Selecione uma área</option>
                            @foreach ($areas_of_activity as $area)
                                <option value="{{ $area->id }}"
                                    {{ old('area_of_activity_id', $provider->commonData?->area_of_activity_id ?? '') == $area->id ? 'selected' : '' }}>
                                    {{ $area->name }}
                                </option>
                            @endforeach
                        </x-ui.form.select>

                        <x-ui.form.select 
                            label="Profissão" 
                            name="profession_id" 
                            id="profession" 
                            required
                            :error="$errors->first('profession_id')">
                            <option value="">Selecione uma profissão</option>
                            @foreach ($professions as $prof)
                                <option value="{{ $prof->id }}"
                                    {{ old('profession_id', $provider->commonData?->profession_id ?? '') == $prof->id ? 'selected' : '' }}>
                                    {{ $prof->name }}
                                </option>
                            @endforeach
                        </x-ui.form.select>

                        <x-ui.form.textarea 
                            label="Descrição Profissional" 
                            name="description" 
                            id="description" 
                            rows="3" 
                            maxlength="250" 
                            placeholder="Descreva sua experiência profissional..."
                            :value="old('description', $provider->commonData?->description ?? '')"
                            :error="$errors->first('description')" 
                        />
                    </x-ui.card>
                </div>

                <!-- Contato -->
                <div class="col-lg-6">
                    <x-ui.card title="Contato" icon="envelope" class="h-100">
                        <x-ui.form.input 
                            type="email"
                            label="Email Empresarial" 
                            name="email_business" 
                            id="email_business"
                            :value="old('email_business', $provider->contact?->email_business ?? '')" 
                            :error="$errors->first('email_business')" 
                        />

                        <x-ui.form.input 
                            type="tel"
                            label="Telefone Empresarial" 
                            name="phone_business" 
                            id="phone_business"
                            :value="old('phone_business', $provider->contact?->phone_business ?? '')" 
                            :error="$errors->first('phone_business')" 
                        />

                        <x-ui.form.input 
                            label="Website" 
                            name="website" 
                            id="website"
                            :value="old('website', $provider->contact?->website ?? '')" 
                            placeholder="ex: www.site.com.br"
                            :error="$errors->first('website')" 
                        />
                    </x-ui.card>
                </div>

                <!-- Endereço -->
                <div class="col-lg-6">
                    <x-ui.card title="Endereço" icon="geo-alt" class="h-100">
                        <div class="row">
                            <div class="col-md-6">
                                <x-ui.form.input 
                                    label="CEP" 
                                    name="cep" 
                                    id="cep"
                                    :value="old('cep', $provider->address?->cep ?? '')" 
                                    required
                                    data-cep-lookup
                                    :error="$errors->first('cep')" 
                                    wrapper-class="mb-3"
                                />
                            </div>
                            <div class="col-md-6">
                                <x-ui.form.input 
                                    label="Número" 
                                    name="address_number" 
                                    id="address_number"
                                    :value="old('address_number', $provider->address?->address_number ?? '')" 
                                    :error="$errors->first('address_number')" 
                                    wrapper-class="mb-3"
                                />
                            </div>
                        </div>

                        <x-ui.form.input 
                            label="Endereço" 
                            name="address" 
                            id="address"
                            :value="old('address', $provider->address?->address ?? '')" 
                            required
                            :error="$errors->first('address')" 
                        />

                        <x-ui.form.input 
                            label="Bairro" 
                            name="neighborhood" 
                            id="neighborhood"
                            :value="old('neighborhood', $provider->address?->neighborhood ?? '')" 
                            required
                            :error="$errors->first('neighborhood')" 
                        />

                        <div class="row">
                            <div class="col-md-8">
                                <x-ui.form.input 
                                    label="Cidade" 
                                    name="city" 
                                    id="city"
                                    :value="old('city', $provider->address?->city ?? '')" 
                                    required
                                    :error="$errors->first('city')" 
                                    wrapper-class="mb-3"
                                />
                            </div>
                            <div class="col-md-4">
                                <x-ui.form.input 
                                    label="Estado" 
                                    name="state" 
                                    id="state"
                                    :value="old('state', $provider->address?->state ?? '')" 
                                    required
                                    :error="$errors->first('state')" 
                                    wrapper-class="mb-3"
                                />
                            </div>
                        </div>
                    </x-ui.card>
                </div>

                <!-- Dados Empresariais Adicionais (PJ) -->
                <div class="col-12" id="business-data-section" style="display: none;">
                    <x-ui.card title="Dados Empresariais Adicionais" icon="building-gear">
                        <div class="row">
                            <div class="col-md-6">
                                <x-ui.form.input 
                                    label="Nome Fantasia" 
                                    name="fantasy_name" 
                                    id="fantasy_name"
                                    :value="old('fantasy_name', $provider->businessData?->fantasy_name ?? '')" 
                                    :error="$errors->first('fantasy_name')" 
                                    wrapper-class="mb-3"
                                />
                            </div>

                            <div class="col-md-6">
                                <x-ui.form.input 
                                    label="Data de Fundação" 
                                    name="founding_date" 
                                    id="founding_date"
                                    :value="old('founding_date', $provider->businessData?->founding_date ? \Carbon\Carbon::parse($provider->businessData->founding_date)->format('d/m/Y') : '')" 
                                    placeholder="DD/MM/AAAA"
                                    :error="$errors->first('founding_date')" 
                                    wrapper-class="mb-3"
                                />
                            </div>

                            <div class="col-md-6">
                                <x-ui.form.input 
                                    label="Inscrição Estadual" 
                                    name="state_registration" 
                                    id="state_registration"
                                    :value="old('state_registration', $provider->businessData?->state_registration ?? '')" 
                                    :error="$errors->first('state_registration')" 
                                    wrapper-class="mb-3"
                                />
                            </div>

                            <div class="col-md-6">
                                <x-ui.form.input 
                                    label="Inscrição Municipal" 
                                    name="municipal_registration" 
                                    id="municipal_registration"
                                    :value="old('municipal_registration', $provider->businessData?->municipal_registration ?? '')" 
                                    :error="$errors->first('municipal_registration')" 
                                    wrapper-class="mb-3"
                                />
                            </div>

                            <div class="col-md-6">
                                <x-ui.form.input 
                                    label="Setor de Atuação" 
                                    name="industry" 
                                    id="industry"
                                    :value="old('industry', $provider->businessData?->industry ?? '')" 
                                    :error="$errors->first('industry')" 
                                    wrapper-class="mb-3"
                                />
                            </div>

                            <div class="col-md-6">
                                <x-ui.form.select 
                                    label="Porte da Empresa" 
                                    name="company_size" 
                                    id="company_size"
                                    :error="$errors->first('company_size')"
                                    wrapper-class="mb-3">
                                    <option value="">Selecione</option>
                                    <option value="micro" {{ old('company_size', $provider->businessData?->company_size ?? '') == 'micro' ? 'selected' : '' }}>Microempresa</option>
                                    <option value="pequena" {{ old('company_size', $provider->businessData?->company_size ?? '') == 'pequena' ? 'selected' : '' }}>Pequena Empresa</option>
                                    <option value="media" {{ old('company_size', $provider->businessData?->company_size ?? '') == 'media' ? 'selected' : '' }}>Média Empresa</option>
                                    <option value="grande" {{ old('company_size', $provider->businessData?->company_size ?? '') == 'grande' ? 'selected' : '' }}>Grande Empresa</option>
                                </x-ui.form.select>
                            </div>

                            <div class="col-12">
                                <x-ui.form.textarea 
                                    label="Observações" 
                                    name="notes" 
                                    id="notes" 
                                    rows="3" 
                                    placeholder="Informações adicionais sobre a empresa..."
                                    :value="old('notes', $provider->businessData?->notes ?? '')"
                                    :error="$errors->first('notes')" 
                                    wrapper-class="mb-3"
                                />
                            </div>
                        </div>
                    </x-ui.card>
                </div>

                <!-- Logo -->
                <div class="col-12">
                    <x-ui.card title="Logo da Empresa" icon="image">
                        <div class="row align-items-center">
                            <div class="col-md-3 text-center mb-3 mb-md-0">
                                <img id="logo-preview"
                                    src="{{ $provider->user->logo ? asset('storage/' . $provider->user->logo) : asset('assets/img/img_not_found.png') }}"
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
                                @error('logo')
                                    <span class="text-danger small">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </x-ui.card>
                </div>
            </div>

            <!-- Botões -->
            <div class="d-flex justify-content-between mt-4">
                <div class="d-flex gap-2">
                    <x-ui.button 
                        type="submit" 
                        variant="primary"
                        id="submitBtn"
                        icon="bi bi-check-circle">
                        Atualizar Dados
                    </x-ui.button>
                    <x-ui.button 
                        href="{{ route('settings.index') }}" 
                        variant="outline-secondary"
                        icon="bi bi-x-circle">
                        Cancelar
                    </x-ui.button>
                </div>
                <x-ui.button 
                    href="{{ route('settings.profile.edit') }}" 
                    variant="outline-info"
                    icon="bi bi-person">
                    Perfil Pessoal
                </x-ui.button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
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
                    new VanillaMask('birth_date', 'date');
                    new VanillaMask('founding_date', 'date');

                    // Inicializar máscara baseada no tipo de pessoa atual
                    const type = document.getElementById('person_type').value;
                    if (type === 'pf') {
                        new VanillaMask('cpf', 'cpf', { clearIfNotMatch: false });
                    } else if (type === 'pj') {
                        new VanillaMask('cnpj', 'cnpj', { clearIfNotMatch: false });
                    }

                    // Aplicar formatação aos valores existentes nos campos
                    const cpfField = document.getElementById('cpf');
                    const cnpjField = document.getElementById('cnpj');

                    if (cpfField && cpfField.value && type === 'pf') {
                        cpfField.value = window.formatCPF ? window.formatCPF(cpfField.value) : cpfField
                            .value;
                    }

                    if (cnpjField && cnpjField.value && type === 'pj') {
                        cnpjField.value = window.formatCNPJ ? window.formatCNPJ(cnpjField.value) : cnpjField
                            .value;
                    }
                }
            }, 500);

            document.getElementById('person_type')?.addEventListener('change', function() {
                togglePersonFields();
                setTimeout(() => {
                    if (typeof VanillaMask !== 'undefined') {
                        const type = this.value;
                        if (type === 'pf') {
                            new VanillaMask('cpf', 'cpf', { clearIfNotMatch: false });
                        } else if (type === 'pj') {
                            new VanillaMask('cnpj', 'cnpj', { clearIfNotMatch: false });
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
