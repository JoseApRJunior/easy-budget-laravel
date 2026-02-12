@extends('layouts.app')

@section('title', 'Novo Cliente')

@section('content')
<x-layout.page-container>
    <x-layout.page-header
        title="Novo Cliente"
        icon="person-plus"
        :breadcrumb-items="[
            'Dashboard' => route('provider.dashboard'),
            'Clientes' => route('provider.customers.dashboard'),
            'Novo' => '#'
        ]">
        <p class="text-muted mb-0 small">Cadastre um novo cliente no sistema</p>
    </x-layout.page-header>

    <form action="{{ route('provider.customers.store') }}" method="POST" id="customerForm">
        @csrf

        <div class="row g-4">
            <!-- Dados Pessoais -->
            <div class="col-lg-6">
                <x-ui.card class="h-100">
                    <x-slot:header>
                        <h5 class="mb-0 fw-bold text-primary"><i class="bi bi-person me-2"></i>Dados Pessoais</h5>
                    </x-slot:header>
                    
                    <div class="p-2">
                        <x-ui.form.input 
                            name="first_name" 
                            label="Nome" 
                            required 
                            :value="old('first_name')" 
                        />

                        <x-ui.form.input 
                            name="last_name" 
                            label="Sobrenome" 
                            required 
                            :value="old('last_name')" 
                        />

                        <div class="mb-3">
                            <label for="birth_date" class="form-label fw-bold small text-muted text-uppercase">Data de Nascimento</label>
                            <input type="text" class="form-control @error('birth_date') is-invalid @enderror"
                                id="birth_date" name="birth_date" value="{{ old('birth_date') }}"
                                placeholder="DD/MM/AAAA" data-mask="00/00/0000">
                            <div id="birth_date_js_error" class="text-danger small mt-1" style="display:none;"></div>
                            @error('birth_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <x-ui.form.input 
                            type="email" 
                            name="email_personal" 
                            label="Email Pessoal" 
                            required 
                            :value="old('email_personal')" 
                        />

                        <div class="mb-3">
                            <label for="phone_personal" class="form-label fw-bold small text-muted text-uppercase">Telefone Pessoal <span class="text-danger">*</span></label>
                            <input type="tel" class="form-control @error('phone_personal') is-invalid @enderror"
                                id="phone_personal" name="phone_personal" value="{{ old('phone_personal') }}" required
                                data-mask="(00) 00000-0000">
                            @error('phone_personal')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </x-ui.card>
            </div>

            <!-- Dados Profissionais -->
            <div class="col-lg-6">
                <x-ui.card class="h-100">
                    <x-slot:header>
                        <h5 class="mb-0 fw-bold text-primary"><i class="bi bi-briefcase me-2"></i>Dados Profissionais</h5>
                    </x-slot:header>

                    <div class="p-2">
                        <x-ui.form.select 
                            name="person_type" 
                            label="Tipo de Pessoa" 
                            required 
                            id="person_type"
                            :selected="old('person_type', 'pf')"
                        >
                            <option value="pf">Pessoa Física</option>
                            <option value="pj">Pessoa Jurídica</option>
                        </x-ui.form.select>

                        <!-- Campos PF -->
                        <div id="pf_fields">
                            <div class="mb-3">
                                <label for="cpf" class="form-label fw-bold small text-muted text-uppercase">CPF</label>
                                <input type="text" class="form-control @error('cpf') is-invalid @enderror"
                                    id="cpf" name="cpf" value="{{ \App\Helpers\DocumentHelper::formatCpf(old('cpf')) }}" data-mask="000.000.000-00">
                                @error('cpf')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Campos PJ -->
                        <div id="pj_fields" style="display: none;">
                            <x-ui.form.input 
                                name="company_name" 
                                label="Razão Social" 
                                :value="old('company_name')" 
                            />

                            <div class="mb-3">
                                <label for="cnpj" class="form-label fw-bold small text-muted text-uppercase">CNPJ</label>
                                <input type="text" class="form-control @error('cnpj') is-invalid @enderror"
                                    id="cnpj" name="cnpj" value="{{ \App\Helpers\DocumentHelper::formatCnpj(old('cnpj')) }}" data-mask="00.000.000/0000-00">
                                @error('cnpj')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <x-ui.form.select 
                            name="area_of_activity_slug" 
                            label="Área de Atuação" 
                            id="area_of_activity_slug"
                            :selected="old('area_of_activity_slug')"
                        >
                            <option value="">Selecione uma área</option>
                            @foreach ($areas_of_activity ?? [] as $area)
                                <option value="{{ $area->slug }}">{{ $area->name }}</option>
                            @endforeach
                        </x-ui.form.select>

                        <x-ui.form.select 
                            name="profession_id" 
                            label="Profissão" 
                            id="profession_id"
                            :selected="old('profession_id')"
                        >
                            <option value="">Selecione uma profissão</option>
                            @foreach ($professions ?? [] as $prof)
                                <option value="{{ $prof->id }}">{{ $prof->name }}</option>
                            @endforeach
                        </x-ui.form.select>

                        <x-ui.form.textarea 
                            name="description" 
                            label="Descrição" 
                            rows="3" 
                            maxlength="250" 
                            placeholder="Descrição do cliente..."
                        >{{ old('description') }}</x-ui.form.textarea>
                    </div>
                </x-ui.card>
            </div>

            <!-- Contato -->
            <div class="col-lg-6">
                <x-ui.card class="h-100">
                    <x-slot:header>
                        <h5 class="mb-0 fw-bold text-primary"><i class="bi bi-envelope me-2"></i>Contato</h5>
                    </x-slot:header>

                    <div class="p-2">
                        <x-ui.form.input 
                            type="email" 
                            name="email_business" 
                            label="Email Empresarial" 
                            :value="old('email_business')" 
                        />

                        <div class="mb-3">
                            <label for="phone_business" class="form-label fw-bold small text-muted text-uppercase">Telefone Empresarial</label>
                            <input type="tel" class="form-control @error('phone_business') is-invalid @enderror"
                                id="phone_business" name="phone_business" value="{{ old('phone_business') }}"
                                data-mask="(00) 00000-0000">
                            @error('phone_business')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <x-ui.form.input 
                            name="website" 
                            label="Website" 
                            :value="old('website')" 
                            placeholder="ex: www.site.com.br"
                        />
                    </div>
                </x-ui.card>
            </div>

            <!-- Endereço -->
            <div class="col-lg-6">
                <x-ui.card class="h-100">
                    <x-slot:header>
                        <h5 class="mb-0 fw-bold text-primary"><i class="bi bi-geo-alt me-2"></i>Endereço</h5>
                    </x-slot:header>

                    <div class="p-2">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="cep" class="form-label fw-bold small text-muted text-uppercase">CEP <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('cep') is-invalid @enderror"
                                    id="cep" name="cep" data-cep-lookup
                                    value="{{ old('cep') }}" required data-mask="00000-000">
                                @error('cep')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <x-ui.form.input 
                                    name="address_number" 
                                    label="Número" 
                                    :value="old('address_number')" 
                                />
                            </div>
                        </div>

                        <x-ui.form.input 
                            name="address" 
                            label="Endereço" 
                            required 
                            :value="old('address')" 
                        />

                        <x-ui.form.input 
                            name="neighborhood" 
                            label="Bairro" 
                            required 
                            :value="old('neighborhood')" 
                        />

                        <div class="row">
                            <div class="col-md-8">
                                <x-ui.form.input 
                                    name="city" 
                                    label="Cidade" 
                                    required 
                                    :value="old('city')" 
                                />
                            </div>
                            <div class="col-md-4">
                                <x-ui.form.input 
                                    name="state" 
                                    label="Estado" 
                                    required 
                                    :value="old('state')" 
                                />
                            </div>
                        </div>
                    </div>
                </x-ui.card>
            </div>

            <!-- Dados Empresariais Adicionais (PJ) -->
            <div class="col-12" id="business-data-section" style="display: none;">
                <x-ui.card>
                    <x-slot:header>
                        <h5 class="mb-0 fw-bold text-primary"><i class="bi bi-building-gear me-2"></i>Dados Empresariais Adicionais</h5>
                    </x-slot:header>

                    <div class="p-2">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <x-ui.form.input 
                                    name="fantasy_name" 
                                    label="Nome Fantasia" 
                                    :value="old('fantasy_name')" 
                                />
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="founding_date" class="form-label fw-bold small text-muted text-uppercase">Data de Fundação</label>
                                <input type="text"
                                    class="form-control @error('founding_date') is-invalid @enderror"
                                    id="founding_date" name="founding_date" value="{{ old('founding_date') }}"
                                    placeholder="DD/MM/AAAA" data-mask="00/00/0000">
                                @error('founding_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6">
                                <x-ui.form.input 
                                    name="state_registration" 
                                    label="Inscrição Estadual" 
                                    :value="old('state_registration')" 
                                />
                            </div>

                            <div class="col-md-6">
                                <x-ui.form.input 
                                    name="municipal_registration" 
                                    label="Inscrição Municipal" 
                                    :value="old('municipal_registration')" 
                                />
                            </div>

                            <div class="col-md-6">
                                <x-ui.form.input 
                                    name="industry" 
                                    label="Setor de Atuação" 
                                    :value="old('industry')" 
                                />
                            </div>

                            <div class="col-md-6">
                                <x-ui.form.select 
                                    name="company_size" 
                                    label="Porte da Empresa" 
                                    id="company_size"
                                    :selected="old('company_size')"
                                >
                                    <option value="">Selecione</option>
                                    <option value="micro">Microempresa</option>
                                    <option value="pequena">Pequena Empresa</option>
                                    <option value="media">Média Empresa</option>
                                    <option value="grande">Grande Empresa</option>
                                </x-ui.form.select>
                            </div>

                            <div class="col-12">
                                <x-ui.form.textarea 
                                    name="notes" 
                                    label="Observações" 
                                    rows="3" 
                                    placeholder="Informações adicionais sobre o cliente..."
                                >{{ old('notes') }}</x-ui.form.textarea>
                            </div>
                        </div>
                    </div>
                </x-ui.card>
            </div>

            <div class="col-12">
                <div class="form-check form-switch">
                    <input type="hidden" name="status" value="inactive">
                    <input class="form-check-input" type="checkbox" id="status" name="status" value="active"
                        {{ old('status', 'active') === 'active' ? 'checked' : '' }}>
                    <label class="form-check-label fw-bold" for="status">Cliente Ativo</label>
                </div>
            </div>

            <!-- Botões -->
            <div class="d-flex justify-content-between align-items-center mt-4 mb-5">
                <x-ui.back-button index-route="provider.customers.index" label="Cancelar" />
                <x-ui.button type="submit" variant="primary" icon="check-circle" label="Cadastrar Cliente" feature="customers" />
            </div>
        </div>
    </form>
</x-layout.page-container>
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
                    ['phone_personal', 'phone_business', 'cep', 'cpf', 'cnpj', 'birth_date', 'founding_date'].forEach(id => {
                        const el = document.getElementById(id);
                        if (el) {
                            let maskType = id;
                            if (id === 'phone_personal' || id === 'phone_business') maskType = 'phone';
                            if (id === 'birth_date' || id === 'founding_date') maskType = 'date';
                        const options = (id === 'cpf' || id === 'cnpj') ? { clearIfNotMatch: false } : {};
                        new VanillaMask(id, maskType, options);
                    }
                });

                // Aplicar formatação aos valores existentes nos campos (caso venha do old input)
                const type = document.getElementById('person_type').value;
                const cpfField = document.getElementById('cpf');
                const cnpjField = document.getElementById('cnpj');

                if (cpfField && cpfField.value && type === 'pf') {
                    cpfField.value = window.formatCPF ? window.formatCPF(cpfField.value) : cpfField.value;
                }

                if (cnpjField && cnpjField.value && type === 'pj') {
                    cnpjField.value = window.formatCNPJ ? window.formatCNPJ(cnpjField.value) : cnpjField.value;
                }
            }
        }, 100);

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

            // Validação de data de nascimento
            function isValidBirthDate(value) {
                if (!/^\d{2}\/\d{2}\/\d{4}$/.test(value)) return false;

                const parts = value.split('/');
                const birthDate = new Date(parts[2], parts[1] - 1, parts[0]);
                const today = new Date();

                let age = today.getFullYear() - birthDate.getFullYear();
                const monthDiff = today.getMonth() - birthDate.getMonth();

                if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
                    age--;
                }

                return age >= 18 && birthDate < today;
            }

            document.getElementById('birth_date')?.addEventListener('blur', function() {
                const value = this.value.trim();
                if (value && !isValidBirthDate(value)) {
                    this.classList.add('is-invalid');
                    document.getElementById('birth_date_js_error').textContent =
                        'Data inválida ou menor de 18 anos';
                    document.getElementById('birth_date_js_error').style.display = 'block';
                } else {
                    this.classList.remove('is-invalid');
                    document.getElementById('birth_date_js_error').style.display = 'none';
                }
            });
        });
    </script>
@endpush
