@extends('layouts.app')

@section('title', 'Novo Cliente')

@section('content')
    <div class="container-fluid py-4">
        <x-page-header
            title="Novo Cliente"
            icon="person-plus"
            :breadcrumb-items="[
                'Dashboard' => route('provider.dashboard'),
                'Clientes' => route('provider.customers.dashboard'),
                'Novo' => '#'
            ]">
            <p class="text-muted mb-0 small">Cadastre um novo cliente no sistema</p>
        </x-page-header>

        <form action="{{ route('provider.customers.store') }}" method="POST" id="customerForm">
            @csrf

            <div class="row g-4">
                <!-- Dados Pessoais -->
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-transparent border-0 py-3">
                            <h5 class="mb-0 fw-bold "><i class="bi bi-person me-2 "></i>Dados Pessoais</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="first_name" class="text-uppercase small fw-bold text-muted mb-2">Nome</label>
                                <input type="text" class="form-control @error('first_name') is-invalid @enderror"
                                    id="first_name" name="first_name" value="{{ old('first_name') }}" required>
                                @error('first_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="last_name" class="text-uppercase small fw-bold text-muted mb-2">Sobrenome</label>
                                <input type="text" class="form-control @error('last_name') is-invalid @enderror"
                                    id="last_name" name="last_name" value="{{ old('last_name') }}" required>
                                @error('last_name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="birth_date" class="text-uppercase small fw-bold text-muted mb-2">Data de Nascimento</label>
                                <input type="text" class="form-control @error('birth_date') is-invalid @enderror"
                                    id="birth_date" name="birth_date" value="{{ old('birth_date') }}"
                                    placeholder="DD/MM/AAAA" data-mask="00/00/0000">
                                <div id="birth_date_js_error" class="text-danger small mt-1" style="display:none;"></div>
                                @error('birth_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="email_personal" class="text-uppercase small fw-bold text-muted mb-2">Email Pessoal</label>
                                <input type="email" class="form-control @error('email_personal') is-invalid @enderror"
                                    id="email_personal" name="email_personal" value="{{ old('email_personal') }}" required>
                                @error('email_personal')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="phone_personal" class="text-uppercase small fw-bold text-muted mb-2">Telefone Pessoal</label>
                                <input type="tel" class="form-control @error('phone_personal') is-invalid @enderror"
                                    id="phone_personal" name="phone_personal" value="{{ old('phone_personal') }}" required
                                    data-mask="(00) 00000-0000">
                                @error('phone_personal')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Dados Profissionais -->
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-transparent border-0 py-3">
                            <h5 class="mb-0 fw-bold "><i class="bi bi-briefcase me-2 "></i>Dados Profissionais</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="person_type" class="text-uppercase small fw-bold text-muted mb-2">Tipo de Pessoa</label>
                                <select name="person_type" id="person_type"
                                    class="form-select @error('person_type') is-invalid @enderror" required>
                                    <option value="">Selecione o tipo</option>
                                    <option value="pf" {{ old('person_type') == 'pf' ? 'selected' : '' }}>Pessoa Física
                                    </option>
                                    <option value="pj" {{ old('person_type') == 'pj' ? 'selected' : '' }}>Pessoa
                                        Jurídica</option>
                                </select>
                                @error('person_type')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Campos PF -->
                            <div id="pf_fields">
                                <div class="mb-3">
                                    <label for="cpf" class="text-uppercase small fw-bold text-muted mb-2">CPF</label>
                                    <input type="text" class="form-control @error('cpf') is-invalid @enderror"
                                        id="cpf" name="cpf" value="{{ old('cpf') }}" data-mask="000.000.000-00">
                                    @error('cpf')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <!-- Campos PJ -->
                            <div id="pj_fields">
                                <div class="mb-3">
                                    <label for="company_name" class="text-uppercase small fw-bold text-muted mb-2">Razão Social</label>
                                    <input type="text" class="form-control @error('company_name') is-invalid @enderror"
                                        id="company_name" name="company_name" value="{{ old('company_name') }}">
                                    @error('company_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="cnpj" class="text-uppercase small fw-bold text-muted mb-2">CNPJ</label>
                                    <input type="text" class="form-control @error('cnpj') is-invalid @enderror"
                                        id="cnpj" name="cnpj" value="{{ old('cnpj') }}" data-mask="00.000.000/0000-00">
                                    @error('cnpj')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="area_of_activity_slug" class="text-uppercase small fw-bold text-muted mb-2">Área de Atuação</label>
                                <select name="area_of_activity_slug"
                                    class="form-select @error('area_of_activity_slug') is-invalid @enderror"
                                    id="area_of_activity_slug">
                                    <option value="">Selecione uma área</option>
                                    @foreach ($areas_of_activity ?? [] as $area)
                                        <option value="{{ $area->slug }}"
                                            {{ old('area_of_activity_slug') == $area->slug ? 'selected' : '' }}>
                                            {{ $area->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('area_of_activity_slug')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="profession_id" class="text-uppercase small fw-bold text-muted mb-2">Profissão</label>
                                <select name="profession_id"
                                    class="form-select @error('profession_id') is-invalid @enderror" id="profession_id">
                                    <option value="">Selecione uma profissão</option>
                                    @foreach ($professions ?? [] as $prof)
                                        <option value="{{ $prof->id }}"
                                            {{ old('profession_id') == $prof->id ? 'selected' : '' }}>
                                            {{ $prof->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('profession_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="description" class="text-uppercase small fw-bold text-muted mb-2">Descrição</label>
                                <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description"
                                    rows="3" maxlength="250" placeholder="Descrição do cliente...">{{ old('description') }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Contato -->
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-transparent border-0 py-3">
                            <h5 class="mb-0 fw-bold "><i class="bi bi-envelope me-2 "></i>Contato</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="email_business" class="text-uppercase small fw-bold text-muted mb-2">Email Empresarial</label>
                                <input type="email" class="form-control @error('email_business') is-invalid @enderror"
                                    id="email_business" name="email_business" value="{{ old('email_business') }}">
                                @error('email_business')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="phone_business" class="text-uppercase small fw-bold text-muted mb-2">Telefone Empresarial</label>
                                <input type="tel" class="form-control @error('phone_business') is-invalid @enderror"
                                    id="phone_business" name="phone_business" value="{{ old('phone_business') }}"
                                    data-mask="(00) 00000-0000">
                                @error('phone_business')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="website" class="text-uppercase small fw-bold text-muted mb-2">Website</label>
                                <input type="text" class="form-control @error('website') is-invalid @enderror"
                                    id="website" name="website" value="{{ old('website') }}" placeholder="ex: www.site.com.br">
                                @error('website')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Endereço -->
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-transparent border-0 py-3">
                            <h5 class="mb-0 fw-bold "><i class="bi bi-geo-alt me-2 "></i>Endereço</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="cep" class="text-uppercase small fw-bold text-muted mb-2">CEP</label>
                                    <input type="text" class="form-control @error('cep') is-invalid @enderror"
                                        id="cep" name="cep" data-cep-lookup value="{{ old('cep') }}"
                                        required>
                                    @error('cep')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="address_number" class="text-uppercase small fw-bold text-muted mb-2">Número</label>
                                    <input type="text"
                                        class="form-control @error('address_number') is-invalid @enderror"
                                        id="address_number" name="address_number" value="{{ old('address_number') }}">
                                    @error('address_number')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="address" class="text-uppercase small fw-bold text-muted mb-2">Endereço</label>
                                <input type="text" class="form-control @error('address') is-invalid @enderror"
                                    id="address" name="address" value="{{ old('address') }}" required>
                                @error('address')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="neighborhood" class="text-uppercase small fw-bold text-muted mb-2">Bairro</label>
                                <input type="text" class="form-control @error('neighborhood') is-invalid @enderror"
                                    id="neighborhood" name="neighborhood" value="{{ old('neighborhood') }}" required>
                                @error('neighborhood')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="row">
                                <div class="col-md-8 mb-3">
                                    <label for="city" class="text-uppercase small fw-bold text-muted mb-2">Cidade</label>
                                    <input type="text" class="form-control @error('city') is-invalid @enderror"
                                        id="city" name="city" value="{{ old('city') }}" required>
                                    @error('city')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label for="state" class="text-uppercase small fw-bold text-muted mb-2">Estado</label>
                                    <input type="text" class="form-control @error('state') is-invalid @enderror"
                                        id="state" name="state" value="{{ old('state') }}" required>
                                    @error('state')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Dados Empresariais Adicionais (PJ) -->
                <div class="col-12" id="business-data-section" style="display: none;">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-transparent border-0 py-3">
                            <h5 class="mb-0 fw-bold "><i class="bi bi-building-gear me-2 "></i>Dados Empresariais Adicionais</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="fantasy_name" class="text-uppercase small fw-bold text-muted mb-2">Nome Fantasia</label>
                                    <input type="text"
                                        class="form-control @error('fantasy_name') is-invalid @enderror"
                                        id="fantasy_name" name="fantasy_name" value="{{ old('fantasy_name') }}">
                                    @error('fantasy_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="founding_date" class="text-uppercase small fw-bold text-muted mb-2">Data de Fundação</label>
                                    <input type="text"
                                        class="form-control @error('founding_date') is-invalid @enderror"
                                        id="founding_date" name="founding_date" value="{{ old('founding_date') }}"
                                        placeholder="DD/MM/AAAA">
                                    @error('founding_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="state_registration" class="text-uppercase small fw-bold text-muted mb-2">Inscrição Estadual</label>
                                    <input type="text"
                                        class="form-control @error('state_registration') is-invalid @enderror"
                                        id="state_registration" name="state_registration"
                                        value="{{ old('state_registration') }}">
                                    @error('state_registration')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="municipal_registration" class="text-uppercase small fw-bold text-muted mb-2">Inscrição Municipal</label>
                                    <input type="text"
                                        class="form-control @error('municipal_registration') is-invalid @enderror"
                                        id="municipal_registration" name="municipal_registration"
                                        value="{{ old('municipal_registration') }}">
                                    @error('municipal_registration')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="industry" class="text-uppercase small fw-bold text-muted mb-2">Setor de Atuação</label>
                                    <input type="text" class="form-control @error('industry') is-invalid @enderror"
                                        id="industry" name="industry" value="{{ old('industry') }}">
                                    @error('industry')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="company_size" class="text-uppercase small fw-bold text-muted mb-2">Porte da Empresa</label>
                                    <select name="company_size"
                                        class="form-select @error('company_size') is-invalid @enderror"
                                        id="company_size">
                                        <option value="">Selecione</option>
                                        <option value="micro" {{ old('company_size') == 'micro' ? 'selected' : '' }}>
                                            Microempresa</option>
                                        <option value="pequena"
                                            {{ old('company_size') == 'pequena' ? 'selected' : '' }}>Pequena Empresa
                                        </option>
                                        <option value="media" {{ old('company_size') == 'media' ? 'selected' : '' }}>
                                            Média Empresa</option>
                                        <option value="grande" {{ old('company_size') == 'grande' ? 'selected' : '' }}>
                                            Grande Empresa</option>
                                    </select>
                                    @error('company_size')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-12 mb-3">
                                    <label for="notes" class="text-uppercase small fw-bold text-muted mb-2">Observações</label>
                                    <textarea class="form-control @error('notes') is-invalid @enderror" id="notes" name="notes" rows="3"
                                        placeholder="Informações adicionais sobre o cliente...">{{ old('notes') }}</textarea>
                                    @error('notes')
                                        <div class="invalid-feedback">{{ $message }}</div>
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
                    <a href="{{ url()->previous(route('provider.customers.index')) }}" class="btn btn-outline-secondary">
                        <i class="bi bi-arrow-left me-2"></i>Cancelar
                    </a>
                </div>
                <div>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle me-2"></i>Criar
                    </button>
                </div>
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
                    ['phone_personal', 'phone_business', 'cep', 'cpf', 'cnpj', 'birth_date', 'founding_date'].forEach(id => {
                        const el = document.getElementById(id);
                        if (el) {
                            let maskType = id;
                            if (id === 'phone_personal' || id === 'phone_business') maskType = 'phone';
                            if (id === 'birth_date' || id === 'founding_date') maskType = 'date';
                            new VanillaMask(id, maskType);
                        }
                    });
                }
            }, 100);

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
