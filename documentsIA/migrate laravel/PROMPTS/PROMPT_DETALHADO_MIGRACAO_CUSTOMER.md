# PROMPT DETALHADO: Migração do Módulo CUSTOMER (Laravel 12)

## 📋 Contexto do Módulo

-  **Base:** Análise completa em `RELATORIO_ANALISE_CUSTOMER_CONTROLLER.md`
-  **Status:** 0% implementado
-  **Objetivo:** Implementar o módulo de clientes completo, seguindo a arquitetura moderna do novo sistema, com base na análise do `CustomerController` do sistema legado
-  **Ordem:** Sequência lógica seguindo dependências técnicas (Database → Repository → Form Requests → Service → Controller)
-  **Complexidade:** ALTA - Estrutura multi-tabela com 4 models relacionados (Customer, CommonData, Contact, Address)

-  **Tokens globais específicos:**
   -  **{{MODULE_NAME}}:** customer
   -  **{{MODULE_PLURAL}}:** customers
   -  **{{Module}}:** Customer
   -  **{{ModuleController}}:** CustomerController
   -  **{{Repository}}:** CustomerRepository
   -  **{{Service}}:** CustomerService
   -  **{{TABLE_NAME}}:** customers
   -  **{{PRIMARY_KEY}}:** id
   -  **{{UNIQUE_CODE_FIELD}}:** status (com valores: 'active', 'inactive', 'prospect')
   -  **{{FOREIGN_KEYS}}:** common_data_id, contact_id, address_id
   -  **{{RELATIONS}}:** ['commonData', 'contact', 'address', 'budgets', 'services']
   -  **{{TENANT_SCOPED_TRAIT}}:** TenantScoped
   -  **{{AUDITABLE_TRAIT}}:** Auditable
   -  **{{SUPPORTED_TYPES}}:** 'pessoa_fisica', 'pessoa_juridica'
   -  **{{VALIDATION_RULES}}:** Regras específicas para CPF/CNPJ/email único

---

# 🎯 Grupo 1: Database & Repository (Base de Dados) — Primeiro

## 🎯 Prompt 1.1: Verificar e Atualizar Migration Multi-Tabela

**IMPORTANTE:** O Customer tem uma estrutura especial com 4 tabelas interdependentes.

-  **Tarefa específica:**

   -  **Migration:** Verificar se o schema inicial já tem as tabelas necessárias:
      -  `customers` (tabela principal)
      -  `common_datas` (dados pessoais/empresariais)
      -  `contacts` (emails e telefones)
      -  `addresses` (endereços)
      -  `areas_of_activity` (áreas de atuação)
      -  `professions` (profissões)
   -  **Models:** Atualizar relacionamentos Eloquent entre as 4 tabelas
   -  **Factories:** Atualizar factories para gerar dados completos (4 tabelas simultaneamente)

-  **Implementação crítica:**

```php
// Migration (verificar se já existe no schema inicial)
Schema::create('customers', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
    $table->foreignId('common_data_id')->nullable()->constrained('common_datas')->onDelete('set null');
    $table->foreignId('contact_id')->nullable()->constrained('contacts')->onDelete('set null');
    $table->foreignId('address_id')->nullable()->constrained('addresses')->onDelete('set null');
    $table->enum('status', ['active', 'inactive', 'prospect'])->default('prospect');
    $table->timestamps();
    $table->softDeletes();

    $table->unique('tenant_id', 'id');
});

Schema::create('common_datas', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
    $table->string('first_name')->nullable();
    $table->string('last_name')->nullable();
    $table->date('birth_date')->nullable();
    $table->string('cnpj', 14)->unique()->nullable();
    $table->string('cpf', 11)->unique()->nullable();
    $table->string('company_name')->nullable();
    $table->text('description')->nullable();
    $table->foreignId('area_of_activity_id')->nullable()->constrained('areas_of_activity')->onDelete('set null');
    $table->foreignId('profession_id')->nullable()->constrained('professions')->onDelete('set null');
    $table->timestamps();
});

Schema::create('contacts', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
    $table->string('email')->unique();
    $table->string('phone', 20)->nullable();
    $table->string('email_business')->unique()->nullable();
    $table->string('phone_business', 20)->nullable();
    $table->string('website')->nullable();
    $table->timestamps();
});

Schema::create('addresses', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
    $table->string('address');
    $table->string('address_number', 20)->nullable();
    $table->string('neighborhood');
    $table->string('city');
    $table->string('state', 2);
    $table->string('cep', 9);
    $table->timestamps();
});

// Model Customer
class Customer extends Model
{
    use HasFactory, SoftDeletes, TenantScoped, Auditable;

    protected $fillable = [
        'tenant_id', 'common_data_id', 'contact_id', 'address_id', 'status'
    ];

    protected $casts = [
        'status' => 'string',
    ];

    // Relacionamentos específicos
    public function commonData(): BelongsTo
    {
        return $this->belongsTo(CommonData::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function address(): BelongsTo
    {
        return $this->belongsTo(Address::class);
    }

    public function budgets(): HasMany
    {
        return $this->hasMany(Budget::class);
    }

    public function services(): HasMany
    {
        return $this->hasManyThrough(Service::class, Budget::class);
    }

    public function isPersonType(): string
    {
        if ($this->commonData?->cpf) return 'pessoa_fisica';
        if ($this->commonData?->cnpj) return 'pessoa_juridica';
        return 'unknown';
    }
}
```

-  **Arquivos:**

   -  `database/migrations/..._create_initial_schema.php` (verificar)
   -  `app/Models/Customer.php` (atualizar)
   -  `app/Models/CommonData.php` (verificar)
   -  `app/Models/Contact.php` (verificar)
   -  `app/Models/Address.php` (verificar)
   -  `database/factories/CustomerFactory.php` (criar)

-  **Critério de sucesso:** Estrutura multi-tabela com relacionamentos Eloquent funcionais.

---

## 🎯 Prompt 1.2: Implementar {{Repository}} — Multi-Tabela

**COMPLEXIDADE CRÍTICA:** Repositório deve gerenciar 4 tabelas simultaneamente.

-  **Tarefa específica:**

   -  Abstrair operações em 4 tabelas (Customer, CommonData, Contact, Address)
   -  Filtros avançados: search, type (PF/PJ), status, cpf/cnpj, email
   -  Tenant scoping automático em todas as tabelas
   -  Eager loading dos relacionamentos completos
   -  Validação de email único global (tabela contacts)

-  **Implementação especializada:**

```php
class CustomerRepository extends AbstractTenantRepository
{
    public function __construct(Customer $model) { parent::__construct($model); }

    public function getPaginated(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->newQuery()
            ->with([
                'commonData' => function($q) {
                    $q->with(['areaOfActivity', 'profession']);
                },
                'contact', 'address'
            ]);

        // Filtro por texto (nome, email, cpf/cnpj, company_name)
        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->whereHas('commonData', function($cq) use ($filters) {
                    $cq->where('first_name', 'like', '%' . $filters['search'] . '%')
                       ->orWhere('last_name', 'like', '%' . $filters['search'] . '%')
                       ->orWhere('company_name', 'like', '%' . $filters['search'] . '%')
                       ->orWhere('cpf', 'like', '%' . $filters['search'] . '%')
                       ->orWhere('cnpj', 'like', '%' . $filters['search'] . '%');
                })->orWhereHas('contact', function($cq) use ($filters) {
                    $cq->where('email', 'like', '%' . $filters['search'] . '%')
                       ->orWhere('phone', 'like', '%' . $filters['search'] . '%');
                });
            });
        }

        // Filtro por tipo (PF/PJ)
        if (!empty($filters['type']) && in_array($filters['type'], ['pessoa_fisica', 'pessoa_juridica'])) {
            $query->whereHas('commonData', function($q) use ($filters) {
                if ($filters['type'] === 'pessoa_fisica') {
                    $q->whereNotNull('cpf');
                } else {
                    $q->whereNotNull('cnpj');
                }
            });
        }

        // Filtro por status
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filtro por área de atuação
        if (!empty($filters['area_of_activity_id'])) {
            $query->whereHas('commonData', function($q) use ($filters) {
                $q->where('area_of_activity_id', $filters['area_of_activity_id']);
            });
        }

        // Filtro por profissão
        if (!empty($filters['profession_id'])) {
            $query->whereHas('commonData', function($q) use ($filters) {
                $q->where('profession_id', $filters['profession_id']);
            });
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    public function findWithCompleteData(int $id, int $tenantId): ?Customer
    {
        return $this->model
            ->where('id', $id)
            ->where('tenant_id', $tenantId)
            ->with([
                'commonData' => function($q) {
                    $q->with(['areaOfActivity', 'profession']);
                },
                'contact', 'address', 'budgets', 'services'
            ])
            ->first();
    }

    public function findByEmailAndTenantId(string $email, int $tenantId): ?Contact
    {
        return Contact::where('email', $email)
            ->where('tenant_id', $tenantId)
            ->first();
    }

    public function findByCpfAndTenantId(string $cpf, int $tenantId): ?CommonData
    {
        return CommonData::where('cpf', $cpf)
            ->where('tenant_id', $tenantId)
            ->first();
    }

    public function findByCnpjAndTenantId(string $cnpj, int $tenantId): ?CommonData
    {
        return CommonData::where('cnpj', $cnpj)
            ->where('tenant_id', $tenantId)
            ->first();
    }

    public function checkRelationships(int $id, int $tenantId): array
    {
        $customer = $this->model->where('id', $id)->where('tenant_id', $tenantId)->first();
        if (!$customer) return ['hasRelationships' => false, 'budgets' => 0, 'services' => 0];

        $budgetsCount = $customer->budgets()->count();
        $servicesCount = $customer->services()->count();

        return [
            'hasRelationships' => ($budgetsCount + $servicesCount) > 0,
            'budgets' => $budgetsCount,
            'services' => $servicesCount
        ];
    }
}
```

-  **Arquivo:** `app/Repositories/CustomerRepository.php`
-  **Critério de sucesso:** Repositório multi-tabela com filtros e validações funcionais.

---

## 🎯 Prompt 1.3: Implementar {{Repository}} — Verificação de Email Único

**CRÍTICO:** Validação de email único é regra fundamental do negócio.

-  **Implementação:**

```php
// Adicionar ao CustomerRepository
public function isEmailUnique(string $email, int $tenantId, ?int $excludeCustomerId = null): bool
{
    $query = Contact::where('email', $email)
        ->where('tenant_id', $tenantId);

    if ($excludeCustomerId) {
        // Excluir o customer atual na edição
        $query->whereDoesntHave('customers', function($q) use ($excludeCustomerId) {
            $q->where('id', $excludeCustomerId);
        });
    }

    return !$query->exists();
}

public function isCpfUnique(string $cpf, int $tenantId, ?int $excludeCustomerId = null): bool
{
    if (strlen($cpf) !== 11) return false; // CPF deve ter 11 dígitos

    $query = CommonData::where('cpf', $cpf)
        ->where('tenant_id', $tenantId);

    if ($excludeCustomerId) {
        $query->whereDoesntHave('customer', function($q) use ($excludeCustomerId) {
            $q->where('id', $excludeCustomerId);
        });
    }

    return !$query->exists();
}

public function isCnpjUnique(string $cnpj, int $tenantId, ?int $excludeCustomerId = null): bool
{
    if (strlen($cnpj) !== 14) return false; // CNPJ deve ter 14 dígitos

    $query = CommonData::where('cnpj', $cnpj)
        ->where('tenant_id', $tenantId);

    if ($excludeCustomerId) {
        $query->whereDoesntHave('customer', function($q) use ($excludeCustomerId) {
            $q->where('id', $excludeCustomerId);
        });
    }

    return !$query->exists();
}
```

-  **Arquivo:** `app/Repositories/CustomerRepository.php`
-  **Critério de sucesso:** Validação de unicidade por tenant funcionando.

---

# 🎯 Grupo 2: Form Requests (Validação) — Segundo

## 🎯 Prompt 2.1: Criar CustomerPessoaFisicaRequest

**COMPLEXIDADE:** Validação específica para pessoa física com CPF.

-  **Implementação:**

```php
class CustomerPessoaFisicaRequest extends FormRequest
{
    public function authorize(): bool { return auth()->check(); }

    public function rules(): array
    {
        return [
            // Dados pessoais
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'birth_date' => 'nullable|date|before:today',

            // CPF (obrigatório para PF)
            'cpf' => [
                'required',
                'string',
                'size:11',
                'regex:/^\d{11}$/',
                function ($attribute, $value, $fail) {
                    if (!$this->isValidCpf($value)) {
                        $fail('O CPF informado não é válido.');
                    }
                }
            ],

            // Contatos
            'email' => [
                'required',
                'email',
                function ($attribute, $value, $fail) {
                    $tenantId = tenant('id');
                    $excludeCustomerId = $this->route('customer') ? $this->route('customer')->id : null;
                    $customerRepo = app(CustomerRepository::class);

                    if (!$customerRepo->isEmailUnique($value, $tenantId, $excludeCustomerId)) {
                        $fail('Este e-mail já está em uso por outro cliente.');
                    }
                }
            ],
            'phone' => 'nullable|string|max:20',
            'phone_business' => 'nullable|string|max:20',
            'website' => 'nullable|url|max:255',

            // Endereço
            'address' => 'required|string|max:255',
            'address_number' => 'nullable|string|max:20',
            'neighborhood' => 'required|string|max:100',
            'city' => 'required|string|max:100',
            'state' => 'required|string|size:2',
            'cep' => 'required|string|regex:/^\d{5}-?\d{3}$/',

            // Dados profissionais
            'area_of_activity_id' => 'nullable|integer|exists:areas_of_activity,id',
            'profession_id' => 'nullable|integer|exists:professions,id',
            'description' => 'nullable|string|max:500',

            // Status
            'status' => 'sometimes|in:active,inactive,prospect',

            // Imagem
            'image' => 'nullable|image|max:2048',
            'remove_image' => 'boolean'
        ];
    }

    public function messages(): array
    {
        return [
            'first_name.required' => 'O nome é obrigatório.',
            'last_name.required' => 'O sobrenome é obrigatório.',
            'cpf.required' => 'O CPF é obrigatório para pessoa física.',
            'cpf.regex' => 'O CPF deve conter apenas números.',
            'email.required' => 'O e-mail é obrigatório.',
            'email.email' => 'Digite um e-mail válido.',
            'address.required' => 'O endereço é obrigatório.',
            'city.required' => 'A cidade é obrigatória.',
            'state.required' => 'O estado é obrigatório.',
            'cep.required' => 'O CEP é obrigatório.',
            'image.image' => 'O arquivo deve ser uma imagem.',
            'image.max' => 'A imagem não pode ter mais de 2MB.'
        ];
    }

    private function isValidCpf(string $cpf): bool
    {
        // Remove caracteres não numéricos
        $cpf = preg_replace('/[^0-9]/', '', $cpf);

        // Verifica se tem 11 dígitos
        if (strlen($cpf) != 11) return false;

        // Verifica se todos os dígitos são iguais
        if (preg_match('/^(\d)\1{10}$/', $cpf)) return false;

        // Calcula primeiro dígito verificador
        $sum = 0;
        for ($i = 0; $i < 9; $i++) {
            $sum += $cpf[$i] * (10 - $i);
        }
        $remainder = $sum % 11;
        $digit1 = ($remainder < 2) ? 0 : 11 - $remainder;

        // Calcula segundo dígito verificador
        $sum = 0;
        for ($i = 0; $i < 10; $i++) {
            $sum += $cpf[$i] * (11 - $i);
        }
        $remainder = $sum % 11;
        $digit2 = ($remainder < 2) ? 0 : 11 - $remainder;

        return $cpf[9] == $digit1 && $cpf[10] == $digit2;
    }
}
```

-  **Arquivo:** `app/Http/Requests/CustomerPessoaFisicaRequest.php`
-  **Critério de sucesso:** Validação PF com CPF, email único e regras de negócio.

---

## 🎯 Prompt 2.2: Criar CustomerPessoaJuridicaRequest

**COMPLEXIDADE:** Validação específica para pessoa jurídica com CNPJ.

-  **Implementação:**

```php
class CustomerPessoaJuridicaRequest extends FormRequest
{
    public function authorize(): bool { return auth()->check(); }

    public function rules(): array
    {
        return [
            // Dados empresariais
            'company_name' => 'required|string|max:255',
            'cnpj' => [
                'required',
                'string',
                'size:14',
                'regex:/^\d{14}$/',
                function ($attribute, $value, $fail) {
                    if (!$this->isValidCnpj($value)) {
                        $fail('O CNPJ informado não é válido.');
                    }
                }
            ],
            'description' => 'nullable|string|max:500',

            // Contatos empresariais
            'email_business' => [
                'required',
                'email',
                function ($attribute, $value, $fail) {
                    $tenantId = tenant('id');
                    $excludeCustomerId = $this->route('customer') ? $this->route('customer')->id : null;
                    $customerRepo = app(CustomerRepository::class);

                    if (!$customerRepo->isEmailUnique($value, $tenantId, $excludeCustomerId)) {
                        $fail('Este e-mail empresarial já está em uso por outro cliente.');
                    }
                }
            ],
            'phone_business' => 'nullable|string|max:20',
            'website' => 'nullable|url|max:255',

            // Contatos pessoais (opcionais)
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:20',

            // Endereço
            'address' => 'required|string|max:255',
            'address_number' => 'nullable|string|max:20',
            'neighborhood' => 'required|string|max:100',
            'city' => 'required|string|max:100',
            'state' => 'required|string|size:2',
            'cep' => 'required|string|regex:/^\d{5}-?\d{3}$/',

            // Dados do responsável
            'first_name' => 'nullable|string|max:100',
            'last_name' => 'nullable|string|max:100',
            'birth_date' => 'nullable|date|before:today',

            // Dados profissionais da empresa
            'area_of_activity_id' => 'required|integer|exists:areas_of_activity,id',
            'profession_id' => 'nullable|integer|exists:professions,id',

            // Status
            'status' => 'sometimes|in:active,inactive,prospect',

            // Imagem
            'image' => 'nullable|image|max:2048',
            'remove_image' => 'boolean'
        ];
    }

    public function messages(): array
    {
        return [
            'company_name.required' => 'A razão social é obrigatória.',
            'cnpj.required' => 'O CNPJ é obrigatório para pessoa jurídica.',
            'cnpj.regex' => 'O CNPJ deve conter apenas números.',
            'email_business.required' => 'O e-mail empresarial é obrigatório.',
            'email_business.email' => 'Digite um e-mail empresarial válido.',
            'area_of_activity_id.required' => 'A área de atuação é obrigatória.',
            'address.required' => 'O endereço é obrigatório.',
            'city.required' => 'A cidade é obrigatória.',
            'state.required' => 'O estado é obrigatório.',
            'cep.required' => 'O CEP é obrigatório.',
            'image.image' => 'O arquivo deve ser uma imagem.',
            'image.max' => 'A imagem não pode ter mais de 2MB.'
        ];
    }

    private function isValidCnpj(string $cnpj): bool
    {
        // Remove caracteres não numéricos
        $cnpj = preg_replace('/[^0-9]/', '', $cnpj);

        // Verifica se tem 14 dígitos
        if (strlen($cnpj) != 14) return false;

        // Verifica se todos os dígitos são iguais
        if (preg_match('/^(\d)\1{13}$/', $cnpj)) return false;

        // Calcula primeiro dígito verificador
        $weights1 = [5,4,3,2,9,8,7,6,5,4,3,2];
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += $cnpj[$i] * $weights1[$i];
        }
        $remainder = $sum % 11;
        $digit1 = ($remainder < 2) ? 0 : 11 - $remainder;

        // Calcula segundo dígito verificador
        $weights2 = [6,5,4,3,2,9,8,7,6,5,4,3,2];
        $sum = 0;
        for ($i = 0; $i < 13; $i++) {
            $sum += $cnpj[$i] * $weights2[$i];
        }
        $remainder = $sum % 11;
        $digit2 = ($remainder < 2) ? 0 : 11 - $remainder;

        return $cnpj[12] == $digit1 && $cnpj[13] == $digit2;
    }
}
```

-  **Arquivo:** `app/Http/Requests/CustomerPessoaJuridicaRequest.php`
-  **Critério de sucesso:** Validação PJ com CNPJ, email único e regras de negócio.

---

## 🎯 Prompt 2.3: Criar CustomerUpdateRequest

**COMPLEXIDADE:** Atualização com validação de email único (ignorando registro atual).

-  **Implementação:**

```php
class CustomerUpdateRequest extends FormRequest
{
    public function authorize(): bool { return auth()->check(); }

    public function rules(): array
    {
        $customerId = $this->route('customer')?->id;
        $type = $this->route('customer')?->isPersonType();

        $rules = [
            // Status sempre atualizável
            'status' => 'sometimes|in:active,inactive,prospect',
            'image' => 'nullable|image|max:2048',
            'remove_image' => 'boolean'
        ];

        if ($type === 'pessoa_fisica') {
            $rules = array_merge($rules, [
                'first_name' => 'sometimes|required|string|max:100',
                'last_name' => 'sometimes|required|string|max:100',
                'birth_date' => 'sometimes|nullable|date|before:today',
                'cpf' => [
                    'sometimes',
                    'required',
                    'string',
                    'size:11',
                    'regex:/^\d{11}$/',
                    function ($attribute, $value, $fail) use ($customerId) {
                        if (!$this->isValidCpf($value)) {
                            $fail('O CPF informado não é válido.');
                        }
                        $customerRepo = app(CustomerRepository::class);
                        $tenantId = tenant('id');

                        if (!$customerRepo->isCpfUnique($value, $tenantId, $customerId)) {
                            $fail('Este CPF já está em uso por outro cliente.');
                        }
                    }
                ],
                'email' => [
                    'sometimes',
                    'required',
                    'email',
                    function ($attribute, $value, $fail) use ($customerId) {
                        $customerRepo = app(CustomerRepository::class);
                        $tenantId = tenant('id');

                        if (!$customerRepo->isEmailUnique($value, $tenantId, $customerId)) {
                            $fail('Este e-mail já está em uso por outro cliente.');
                        }
                    }
                ],
                'phone' => 'sometimes|nullable|string|max:20',
                'area_of_activity_id' => 'sometimes|nullable|integer|exists:areas_of_activity,id',
                'profession_id' => 'sometimes|nullable|integer|exists:professions,id',
            ]);
        } else {
            $rules = array_merge($rules, [
                'company_name' => 'sometimes|required|string|max:255',
                'cnpj' => [
                    'sometimes',
                    'required',
                    'string',
                    'size:14',
                    'regex:/^\d{14}$/',
                    function ($attribute, $value, $fail) use ($customerId) {
                        if (!$this->isValidCnpj($value)) {
                            $fail('O CNPJ informado não é válido.');
                        }
                        $customerRepo = app(CustomerRepository::class);
                        $tenantId = tenant('id');

                        if (!$customerRepo->isCnpjUnique($value, $tenantId, $customerId)) {
                            $fail('Este CNPJ já está em uso por outro cliente.');
                        }
                    }
                ],
                'email_business' => [
                    'sometimes',
                    'required',
                    'email',
                    function ($attribute, $value, $fail) use ($customerId) {
                        $customerRepo = app(CustomerRepository::class);
                        $tenantId = tenant('id');

                        if (!$customerRepo->isEmailUnique($value, $tenantId, $customerId)) {
                            $fail('Este e-mail empresarial já está em uso por outro cliente.');
                        }
                    }
                ],
                'first_name' => 'sometimes|nullable|string|max:100',
                'last_name' => 'sometimes|nullable|string|max:100',
                'area_of_activity_id' => 'sometimes|required|integer|exists:areas_of_activity,id',
            ]);
        }

        // Campos opcionais que podem ser atualizados
        $optionalFields = ['phone_business', 'website', 'address', 'address_number',
                          'neighborhood', 'city', 'state', 'cep', 'description'];

        foreach ($optionalFields as $field) {
            $rules[$field] = 'sometimes|nullable|' . ($field === 'state' ? 'string|size:2' :
                                                $field === 'cep' ? 'string|regex:/^\d{5}-?\d{3}$/' :
                                                'string|max:255');
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'status.in' => 'Status deve ser: ativo, inativo ou prospect.',
            'first_name.required' => 'O nome é obrigatório.',
            'last_name.required' => 'O sobrenome é obrigatório.',
            'company_name.required' => 'A razão social é obrigatória.',
            'cpf.required' => 'O CPF é obrigatório para pessoa física.',
            'cnpj.required' => 'O CNPJ é obrigatório para pessoa jurídica.',
            'email.required' => 'O e-mail é obrigatório.',
            'email_business.required' => 'O e-mail empresarial é obrigatório.',
            'area_of_activity_id.required' => 'A área de atuação é obrigatória.',
            'image.image' => 'O arquivo deve ser uma imagem.',
            'image.max' => 'A imagem não pode ter mais de 2MB.'
        ];
    }

    private function isValidCpf(string $cpf): bool
    {
        // Implementação igual ao CustomerPessoaFisicaRequest
        $cpf = preg_replace('/[^0-9]/', '', $cpf);
        if (strlen($cpf) != 11) return false;
        if (preg_match('/^(\d)\1{10}$/', $cpf)) return false;

        $sum = 0;
        for ($i = 0; $i < 9; $i++) $sum += $cpf[$i] * (10 - $i);
        $remainder = $sum % 11;
        $digit1 = ($remainder < 2) ? 0 : 11 - $remainder;

        $sum = 0;
        for ($i = 0; $i < 10; $i++) $sum += $cpf[$i] * (11 - $i);
        $remainder = $sum % 11;
        $digit2 = ($remainder < 2) ? 0 : 11 - $remainder;

        return $cpf[9] == $digit1 && $cpf[10] == $digit2;
    }

    private function isValidCnpj(string $cnpj): bool
    {
        // Implementação igual ao CustomerPessoaJuridicaRequest
        $cnpj = preg_replace('/[^0-9]/', '', $cnpj);
        if (strlen($cnpj) != 14) return false;
        if (preg_match('/^(\d)\1{13}$/', $cnpj)) return false;

        $weights1 = [5,4,3,2,9,8,7,6,5,4,3,2];
        $sum = 0;
        for ($i = 0; $i < 12; $i++) $sum += $cnpj[$i] * $weights1[$i];
        $remainder = $sum % 11;
        $digit1 = ($remainder < 2) ? 0 : 11 - $remainder;

        $weights2 = [6,5,4,3,2,9,8,7,6,5,4,3,2];
        $sum = 0;
        for ($i = 0; $i < 13; $i++) $sum += $cnpj[$i] * $weights2[$i];
        $remainder = $sum % 11;
        $digit2 = ($remainder < 2) ? 0 : 11 - $remainder;

        return $cnpj[12] == $digit1 && $cnpj[13] == $digit2;
    }
}
```

-  **Arquivo:** `app/Http/Requests/CustomerUpdateRequest.php`
-  **Critério de sucesso:** Validação de edição com email único ignorando registro atual.

---

# 🎯 Grupo 3: Services (Lógica de Negócio) — Terceiro

## 🎯 Prompt 3.1: Implementar {{Service}} — Criação Multi-Tabela

**CRÍTICO:** Service deve gerenciar transações em 4 tabelas simultaneamente.

-  **Implementação:**

```php
class CustomerService extends BaseTenantService
{
    private CustomerRepository $customerRepository;
    private CommonDataRepository $commonDataRepository;
    private ContactRepository $contactRepository;
    private AddressRepository $addressRepository;

    public function __construct(
        CustomerRepository $customerRepository,
        CommonDataRepository $commonDataRepository,
        ContactRepository $contactRepository,
        AddressRepository $addressRepository
    ) {
        $this->customerRepository = $customerRepository;
        $this->commonDataRepository = $commonDataRepository;
        $this->contactRepository = $contactRepository;
        $this->addressRepository = $addressRepository;
    }

    public function createPessoaFisica(array $data, int $tenantId): ServiceResult
    {
        try {
            return DB::transaction(function () use ($data, $tenantId) {
                // 1. Validar unicidade (email, CPF)
                if (!$this->customerRepository->isEmailUnique($data['email'], $tenantId)) {
                    return $this->error(OperationStatus::VALIDATION_ERROR, 'E-mail já está em uso');
                }

                if (!$this->customerRepository->isCpfUnique($data['cpf'], $tenantId)) {
                    return $this->error(OperationStatus::VALIDATION_ERROR, 'CPF já está em uso');
                }

                // 2. Criar CommonData (dados pessoais)
                $commonData = $this->commonDataRepository->create([
                    'tenant_id' => $tenantId,
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'birth_date' => $data['birth_date'] ?? null,
                    'cpf' => preg_replace('/[^0-9]/', '', $data['cpf']),
                    'area_of_activity_id' => $data['area_of_activity_id'] ?? null,
                    'profession_id' => $data['profession_id'] ?? null,
                    'description' => $data['description'] ?? null,
                ]);

                // 3. Criar Contact (dados de contato)
                $contact = $this->contactRepository->create([
                    'tenant_id' => $tenantId,
                    'email' => $data['email'],
                    'phone' => $data['phone'] ?? null,
                    'phone_business' => $data['phone_business'] ?? null,
                    'website' => $data['website'] ?? null,
                ]);

                // 4. Criar Address (endereço)
                $address = $this->addressRepository->create([
                    'tenant_id' => $tenantId,
                    'address' => $data['address'],
                    'address_number' => $data['address_number'] ?? null,
                    'neighborhood' => $data['neighborhood'],
                    'city' => $data['city'],
                    'state' => strtoupper($data['state']),
                    'cep' => preg_replace('/[^0-9]/', '', $data['cep']),
                ]);

                // 5. Criar Customer (relacionando tudo)
                $customer = $this->customerRepository->create([
                    'tenant_id' => $tenantId,
                    'common_data_id' => $commonData->id,
                    'contact_id' => $contact->id,
                    'address_id' => $address->id,
                    'status' => $data['status'] ?? 'prospect',
                ]);

                // 6. Eager loading para retorno completo
                $customer = $this->customerRepository->findWithCompleteData($customer->id, $tenantId);

                return $this->success($customer, 'Cliente pessoa física criado com sucesso');
            });
        } catch (Exception $e) {
            return $this->error(OperationStatus::ERROR, 'Erro ao criar cliente pessoa física', null, $e);
        }
    }

    public function createPessoaJuridica(array $data, int $tenantId): ServiceResult
    {
        try {
            return DB::transaction(function () use ($data, $tenantId) {
                // 1. Validar unicidade (email, CNPJ)
                if (!$this->customerRepository->isEmailUnique($data['email_business'], $tenantId)) {
                    return $this->error(OperationStatus::VALIDATION_ERROR, 'E-mail empresarial já está em uso');
                }

                if (!$this->customerRepository->isCnpjUnique($data['cnpj'], $tenantId)) {
                    return $this->error(OperationStatus::VALIDATION_ERROR, 'CNPJ já está em uso');
                }

                // 2. Criar CommonData (dados empresariais)
                $commonData = $this->commonDataRepository->create([
                    'tenant_id' => $tenantId,
                    'first_name' => $data['first_name'] ?? null,
                    'last_name' => $data['last_name'] ?? null,
                    'birth_date' => $data['birth_date'] ?? null,
                    'cnpj' => preg_replace('/[^0-9]/', '', $data['cnpj']),
                    'company_name' => $data['company_name'],
                    'area_of_activity_id' => $data['area_of_activity_id'],
                    'profession_id' => $data['profession_id'] ?? null,
                    'description' => $data['description'] ?? null,
                ]);

                // 3. Criar Contact (dados de contato empresarial)
                $contact = $this->contactRepository->create([
                    'tenant_id' => $tenantId,
                    'email' => $data['email_business'],
                    'phone' => $data['phone'] ?? null,
                    'phone_business' => $data['phone_business'] ?? null,
                    'email_business' => $data['email_business'],
                    'website' => $data['website'] ?? null,
                ]);

                // 4. Criar Address (endereço)
                $address = $this->addressRepository->create([
                    'tenant_id' => $tenantId,
                    'address' => $data['address'],
                    'address_number' => $data['address_number'] ?? null,
                    'neighborhood' => $data['neighborhood'],
                    'city' => $data['city'],
                    'state' => strtoupper($data['state']),
                    'cep' => preg_replace('/[^0-9]/', '', $data['cep']),
                ]);

                // 5. Criar Customer
                $customer = $this->customerRepository->create([
                    'tenant_id' => $tenantId,
                    'common_data_id' => $commonData->id,
                    'contact_id' => $contact->id,
                    'address_id' => $address->id,
                    'status' => $data['status'] ?? 'prospect',
                ]);

                // 6. Eager loading para retorno completo
                $customer = $this->customerRepository->findWithCompleteData($customer->id, $tenantId);

                return $this->success($customer, 'Cliente pessoa jurídica criado com sucesso');
            });
        } catch (Exception $e) {
            return $this->error(OperationStatus::ERROR, 'Erro ao criar cliente pessoa jurídica', null, $e);
        }
    }

    public function getFilteredCustomers(array $filters = [], int $tenantId): ServiceResult
    {
        try {
            $customers = $this->customerRepository->getPaginated($filters, 15);
            return $this->success($customers, 'Clientes filtrados');
        } catch (Exception $e) {
            return $this->error(OperationStatus::ERROR, 'Erro ao filtrar clientes', null, $e);
        }
    }
}
```

-  **Arquivo:** `app/Services/Domain/CustomerService.php`
-  **Critério de sucesso:** Criação transacional multi-tabela com validações.

---

## 🎯 Prompt 3.2: Implementar {{Service}} — Atualização e Exclusão

**COMPLEXIDADE:** Atualização e exclusão com transações em 4 tabelas.

-  **Implementação:**

```php
class CustomerService extends BaseTenantService
{
    // ... (métodos anteriores)

    public function updateCustomer(int $id, array $data, int $tenantId): ServiceResult
    {
        try {
            return DB::transaction(function () use ($id, $data, $tenantId) {
                $customer = $this->customerRepository->findWithCompleteData($id, $tenantId);
                if (!$customer) {
                    return $this->error(OperationStatus::NOT_FOUND, 'Cliente não encontrado');
                }

                $type = $customer->isPersonType();

                // Validações de unicidade para edição
                if (isset($data['email']) && $type === 'pessoa_fisica') {
                    if (!$this->customerRepository->isEmailUnique($data['email'], $tenantId, $id)) {
                        return $this->error(OperationStatus::VALIDATION_ERROR, 'E-mail já está em uso');
                    }
                }

                if (isset($data['email_business']) && $type === 'pessoa_juridica') {
                    if (!$this->customerRepository->isEmailUnique($data['email_business'], $tenantId, $id)) {
                        return $this->error(OperationStatus::VALIDATION_ERROR, 'E-mail empresarial já está em uso');
                    }
                }

                if (isset($data['cpf']) && $type === 'pessoa_fisica') {
                    $cpf = preg_replace('/[^0-9]/', '', $data['cpf']);
                    if (!$this->customerRepository->isCpfUnique($cpf, $tenantId, $id)) {
                        return $this->error(OperationStatus::VALIDATION_ERROR, 'CPF já está em uso');
                    }
                    $data['cpf'] = $cpf;
                }

                if (isset($data['cnpj']) && $type === 'pessoa_juridica') {
                    $cnpj = preg_replace('/[^0-9]/', '', $data['cnpj']);
                    if (!$this->customerRepository->isCnpjUnique($cnpj, $tenantId, $id)) {
                        return $this->error(OperationStatus::VALIDATION_ERROR, 'CNPJ já está em uso');
                    }
                    $data['cnpj'] = $cnpj;
                }

                // Separar dados por tabela
                $customerData = [];
                $commonData = [];
                $contact = [];
                $address = [];

                // Dados do Customer
                if (isset($data['status'])) $customerData['status'] = $data['status'];

                // Dados da CommonData
                $commonDataFields = ['first_name', 'last_name', 'birth_date', 'cpf', 'cnpj', 'company_name',
                                   'area_of_activity_id', 'profession_id', 'description'];
                foreach ($commonDataFields as $field) {
                    if (array_key_exists($field, $data)) $commonData[$field] = $data[$field];
                }

                // Dados do Contact
                $contactFields = ['email', 'phone', 'email_business', 'phone_business', 'website'];
                foreach ($contactFields as $field) {
                    if (array_key_exists($field, $data)) $contact[$field] = $data[$field];
                }

                // Dados do Address
                $addressFields = ['address', 'address_number', 'neighborhood', 'city', 'state', 'cep'];
                foreach ($addressFields as $field) {
                    if (array_key_exists($field, $data)) {
                        $address[$field] = $field === 'state' ? strtoupper($data[$field]) :
                                          $field === 'cep' ? preg_replace('/[^0-9]/', '', $data[$field]) :
                                          $data[$field];
                    }
                }

                // Atualizar em cascata
                if (!empty($commonData)) {
                    $this->commonDataRepository->update($customer->commonData->id, $commonData);
                }

                if (!empty($contact)) {
                    $this->contactRepository->update($customer->contact->id, $contact);
                }

                if (!empty($address)) {
                    $this->addressRepository->update($customer->address->id, $address);
                }

                if (!empty($customerData)) {
                    $this->customerRepository->update($id, $customerData);
                }

                // Retornar com dados atualizados
                $customer = $this->customerRepository->findWithCompleteData($id, $tenantId);
                return $this->success($customer, 'Cliente atualizado com sucesso');
            });
        } catch (Exception $e) {
            return $this->error(OperationStatus::ERROR, 'Erro ao atualizar cliente', null, $e);
        }
    }

    public function deleteCustomer(int $id, int $tenantId): ServiceResult
    {
        try {
            return DB::transaction(function () use ($id, $tenantId) {
                $customer = $this->customerRepository->findWithCompleteData($id, $tenantId);
                if (!$customer) {
                    return $this->error(OperationStatus::NOT_FOUND, 'Cliente não encontrado');
                }

                // Verificar relacionamentos (budgets, services)
                $relationships = $this->customerRepository->checkRelationships($id, $tenantId);
                if ($relationships['hasRelationships']) {
                    return $this->error(
                        OperationStatus::VALIDATION_ERROR,
                        "Não é possível excluir: {$relationships['budgets']} orçamentos, {$relationships['services']} serviços vinculados"
                    );
                }

                // Soft delete em cascata
                $customer->delete(); // Isso triggera soft delete no Customer
                $customer->commonData->delete();
                $customer->contact->delete();
                $customer->address->delete();

                return $this->success(null, 'Cliente removido com sucesso');
            });
        } catch (Exception $e) {
            return $this->error(OperationStatus::ERROR, 'Erro ao remover cliente', null, $e);
        }
    }

    public function findCustomer(int $id, int $tenantId): ServiceResult
    {
        try {
            $customer = $this->customerRepository->findWithCompleteData($id, $tenantId);
            if (!$customer) {
                return $this->error(OperationStatus::NOT_FOUND, 'Cliente não encontrado');
            }
            return $this->success($customer, 'Cliente encontrado');
        } catch (Exception $e) {
            return $this->error(OperationStatus::ERROR, 'Erro ao buscar cliente', null, $e);
        }
    }

    public function toggleStatus(int $id, int $tenantId): ServiceResult
    {
        try {
            return DB::transaction(function () use ($id, $tenantId) {
                $customer = $this->customerRepository->findWithCompleteData($id, $tenantId);
                if (!$customer) {
                    return $this->error(OperationStatus::NOT_FOUND, 'Cliente não encontrado');
                }

                $newStatus = $customer->status === 'active' ? 'inactive' : 'active';
                $this->customerRepository->update($id, ['status' => $newStatus]);

                $customer = $this->customerRepository->findWithCompleteData($id, $tenantId);
                return $this->success($customer, "Cliente {$newStatus} com sucesso");
            });
        } catch (Exception $e) {
            return $this->error(OperationStatus::ERROR, 'Erro ao alterar status', null, $e);
        }
    }
}
```

-  **Arquivo:** `app/Services/Domain/CustomerService.php`
-  **Critério de sucesso:** Atualização e exclusão com transações multi-tabela.

---

# 🎯 Grupo 4: Controllers (Interface HTTP) — Quarto

## 🎯 Prompt 4.1: Implementar {{ModuleController}} — Métodos CRUD

**COMPLEXIDADE:** Controller com 14 métodos (incluindo PF/PJ separados).

-  **Implementação:**

```php
class CustomerController extends Controller
{
    private CustomerService $customerService;
    private AreaOfActivityRepository $areaOfActivityRepository;
    private ProfessionRepository $professionRepository;

    public function __construct(
        CustomerService $customerService,
        AreaOfActivityRepository $areaOfActivityRepository,
        ProfessionRepository $professionRepository
    ) {
        $this->customerService = $customerService;
        $this->areaOfActivityRepository = $areaOfActivityRepository;
        $this->professionRepository = $professionRepository;
        $this->middleware('auth');
        $this->middleware('tenant');
    }

    public function index(Request $request): View
    {
        try {
            $tenantId = tenant('id');
            $filters = $request->only(['search', 'type', 'status', 'area_of_activity_id', 'profession_id']);

            $result = $this->customerService->getFilteredCustomers($filters, $tenantId);
            if (!$result->isSuccess()) {
                abort(500, 'Erro ao carregar clientes');
            }

            return view('customers.index', [
                'customers' => $result->getData(),
                'filters' => $filters,
                'areas' => $this->areaOfActivityRepository->getActiveByTenantId($tenantId),
                'professions' => $this->professionRepository->getActiveByTenantId($tenantId)
            ]);
        } catch (Exception $e) {
            Log::error('Erro ao listar clientes: ' . $e->getMessage());
            abort(500);
        }
    }

    public function createPessoaFisica(): View
    {
        try {
            $tenantId = tenant('id');
            return view('customers.create-pessoa-fisica', [
                'areas' => $this->areaOfActivityRepository->getActiveByTenantId($tenantId),
                'professions' => $this->professionRepository->getActiveByTenantId($tenantId)
            ]);
        } catch (Exception $e) {
            Log::error('Erro ao carregar formulário PF: ' . $e->getMessage());
            abort(500);
        }
    }

    public function createPessoaJuridica(): View
    {
        try {
            $tenantId = tenant('id');
            return view('customers.create-pessoa-juridica', [
                'areas' => $this->areaOfActivityRepository->getActiveByTenantId($tenantId),
                'professions' => $this->professionRepository->getActiveByTenantId($tenantId)
            ]);
        } catch (Exception $e) {
            Log::error('Erro ao carregar formulário PJ: ' . $e->getMessage());
            abort(500);
        }
    }

    public function storePessoaFisica(CustomerPessoaFisicaRequest $request): RedirectResponse
    {
        try {
            $tenantId = tenant('id');
            $result = $this->customerService->createPessoaFisica($request->validated(), $tenantId);

            if ($result->isSuccess()) {
                $customer = $result->getData();

                // Log de auditoria
                $this->logCustomerAction('customer_created', $customer, $request);

                return redirect()->route('customers.index')
                    ->with('success', 'Cliente pessoa física criado com sucesso!');
            }

            return redirect()->back()
                ->withErrors($result->getErrorMessage())
                ->withInput();

        } catch (Exception $e) {
            Log::error('Erro ao criar cliente PF: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Erro interno do servidor')
                ->withInput();
        }
    }

    public function storePessoaJuridica(CustomerPessoaJuridicaRequest $request): RedirectResponse
    {
        try {
            $tenantId = tenant('id');
            $result = $this->customerService->createPessoaJuridica($request->validated(), $tenantId);

            if ($result->isSuccess()) {
                $customer = $result->getData();

                // Log de auditoria
                $this->logCustomerAction('customer_created', $customer, $request);

                return redirect()->route('customers.index')
                    ->with('success', 'Cliente pessoa jurídica criado com sucesso!');
            }

            return redirect()->back()
                ->withErrors($result->getErrorMessage())
                ->withInput();

        } catch (Exception $e) {
            Log::error('Erro ao criar cliente PJ: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Erro interno do servidor')
                ->withInput();
        }
    }

    public function show(int $id): View
    {
        try {
            $tenantId = tenant('id');
            $result = $this->customerService->findCustomer($id, $tenantId);

            if (!$result->isSuccess()) {
                abort(404, 'Cliente não encontrado');
            }

            $customer = $result->getData();

            return view('customers.show', [
                'customer' => $customer,
                'budgets' => $customer->budgets()->with(['services'])->paginate(10),
                'recentActivity' => $this->getRecentActivity($customer)
            ]);
        } catch (Exception $e) {
            Log::error('Erro ao mostrar cliente: ' . $e->getMessage());
            abort(500);
        }
    }

    public function edit(int $id): View
    {
        try {
            $tenantId = tenant('id');
            $result = $this->customerService->findCustomer($id, $tenantId);

            if (!$result->isSuccess()) {
                abort(404, 'Cliente não encontrado');
            }

            $customer = $result->getData();
            $type = $customer->isPersonType();

            $viewName = $type === 'pessoa_fisica' ? 'customers.edit-pessoa-fisica' : 'customers.edit-pessoa-juridica';

            return view($viewName, [
                'customer' => $customer,
                'areas' => $this->areaOfActivityRepository->getActiveByTenantId($tenantId),
                'professions' => $this->professionRepository->getActiveByTenantId($tenantId)
            ]);
        } catch (Exception $e) {
            Log::error('Erro ao carregar edição: ' . $e->getMessage());
            abort(500);
        }
    }

    public function update(CustomerUpdateRequest $request, int $id): RedirectResponse
    {
        try {
            $tenantId = tenant('id');
            $result = $this->customerService->updateCustomer($id, $request->validated(), $tenantId);

            if ($result->isSuccess()) {
                $customer = $result->getData();

                // Log de auditoria
                $this->logCustomerAction('customer_updated', $customer, $request);

                return redirect()->route('customers.show', $id)
                    ->with('success', 'Cliente atualizado com sucesso!');
            }

            return redirect()->back()
                ->withErrors($result->getErrorMessage())
                ->withInput();

        } catch (Exception $e) {
            Log::error('Erro ao atualizar cliente: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Erro interno do servidor')
                ->withInput();
        }
    }

    public function destroy(int $id): RedirectResponse
    {
        try {
            $tenantId = tenant('id');
            $result = $this->customerService->deleteCustomer($id, $tenantId);

            if ($result->isSuccess()) {
                // Log de auditoria
                Log::info("Cliente {$id} deletado", [
                    'tenant_id' => $tenantId,
                    'user_id' => auth()->id(),
                    'action' => 'customer_deleted'
                ]);

                return redirect()->route('customers.index')
                    ->with('success', 'Cliente removido com sucesso!');
            }

            return redirect()->back()->with('error', $result->getErrorMessage());

        } catch (Exception $e) {
            Log::error('Erro ao deletar cliente: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Erro interno do servidor');
        }
    }

    public function restore(int $id): RedirectResponse
    {
        try {
            $tenantId = tenant('id');
            $customer = $this->customerRepository->findWithTrashed($id, $tenantId);

            if (!$customer) {
                return redirect()->back()->with('error', 'Cliente não encontrado');
            }

            $customer->restore();
            $customer->commonData->restore();
            $customer->contact->restore();
            $customer->address->restore();

            Log::info("Cliente {$id} restaurado", [
                'tenant_id' => $tenantId,
                'user_id' => auth()->id(),
                'action' => 'customer_restored'
            ]);

            return redirect()->route('customers.index')
                ->with('success', 'Cliente restaurado com sucesso!');

        } catch (Exception $e) {
            Log::error('Erro ao restaurar cliente: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Erro interno do servidor');
        }
    }

    public function toggleStatus(int $id): RedirectResponse
    {
        try {
            $tenantId = tenant('id');
            $result = $this->customerService->toggleStatus($id, $tenantId);

            if ($result->isSuccess()) {
                $customer = $result->getData();
                $status = $customer->status === 'active' ? 'ativado' : 'desativado';

                Log::info("Cliente {$id} {$status}", [
                    'tenant_id' => $tenantId,
                    'user_id' => auth()->id(),
                    'action' => 'customer_status_toggled',
                    'new_status' => $customer->status
                ]);

                return redirect()->back()->with('success', "Cliente {$status} com sucesso!");
            }

            return redirect()->back()->with('error', $result->getErrorMessage());

        } catch (Exception $e) {
            Log::error('Erro ao alterar status: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Erro interno do servidor');
        }
    }

    public function autocomplete(Request $request): JsonResponse
    {
        try {
            $query = $request->get('q');
            $tenantId = tenant('id');

            if (strlen($query) < 2) {
                return response()->json([]);
            }

            $customers = $this->customerRepository->searchForAutocomplete($query, $tenantId);

            return response()->json($customers->map(function($customer) {
                $type = $customer->isPersonType();
                $name = $type === 'pessoa_fisica'
                    ? "{$customer->commonData->first_name} {$customer->commonData->last_name}"
                    : $customer->commonData->company_name;

                return [
                    'id' => $customer->id,
                    'text' => $name,
                    'email' => $customer->contact->email,
                    'type' => $type,
                    'status' => $customer->status
                ];
            }));

        } catch (Exception $e) {
            Log::error('Erro no autocomplete: ' . $e->getMessage());
            return response()->json([], 500);
        }
    }

    public function dashboard(): View
    {
        try {
            $tenantId = tenant('id');
            $stats = $this->getCustomerStats($tenantId);

            return view('customers.dashboard', $stats);
        } catch (Exception $e) {
            Log::error('Erro no dashboard: ' . $e->getMessage());
            abort(500);
        }
    }

    private function logCustomerAction(string $action, Customer $customer, Request $request): void
    {
        Log::info("Cliente {$action}", [
            'tenant_id' => tenant('id'),
            'user_id' => auth()->id(),
            'customer_id' => $customer->id,
            'customer_type' => $customer->isPersonType(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);
    }

    private function getCustomerStats(int $tenantId): array
    {
        return [
            'total_customers' => $this->customerRepository->countByTenantId($tenantId),
            'active_customers' => $this->customerRepository->countByStatus('active', $tenantId),
            'prospects' => $this->customerRepository->countByStatus('prospect', $tenantId),
            'recent_customers' => $this->customerRepository->getRecentByTenantId($tenantId, 10),
            'monthly_growth' => $this->calculateMonthlyGrowth($tenantId)
        ];
    }
}
```

-  **Arquivo:** `app/Http/Controllers/Provider/CustomerController.php`
-  **Critério de sucesso:** Controller completo com 14 métodos, validações e auditoria.

---

# 🎯 Grupo 5: Views e Frontend — Quinto

## 🎯 Prompt 5.1: Criar Views Blade Responsivas

**IMPORTANTE:** Views separadas para PF e PJ, com JavaScript para validação.

-  **Implementação base:**

```blade
{{-- resources/views/customers/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Clientes')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3">Clientes</h1>
                <div>
                    <a href="{{ route('customers.create.pessoa-fisica') }}" class="btn btn-primary">
                        <i class="fas fa-user"></i> Nova Pessoa Física
                    </a>
                    <a href="{{ route('customers.create.pessoa-juridica') }}" class="btn btn-success">
                        <i class="fas fa-building"></i> Nova Pessoa Jurídica
                    </a>
                </div>
            </div>

            {{-- Filtros --}}
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" action="{{ route('customers.index') }}" class="row g-3">
                        <div class="col-md-3">
                            <input type="text" name="search" class="form-control"
                                   placeholder="Buscar..." value="{{ $filters['search'] ?? '' }}">
                        </div>
                        <div class="col-md-2">
                            <select name="type" class="form-select">
                                <option value="">Todos os tipos</option>
                                <option value="pessoa_fisica" {{ ($filters['type'] ?? '') == 'pessoa_fisica' ? 'selected' : '' }}>
                                    Pessoa Física
                                </option>
                                <option value="pessoa_juridica" {{ ($filters['type'] ?? '') == 'pessoa_juridica' ? 'selected' : '' }}>
                                    Pessoa Jurídica
                                </option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="status" class="form-select">
                                <option value="">Todos os status</option>
                                <option value="active" {{ ($filters['status'] ?? '') == 'active' ? 'selected' : '' }}>
                                    Ativo
                                </option>
                                <option value="inactive" {{ ($filters['status'] ?? '') == 'inactive' ? 'selected' : '' }}>
                                    Inativo
                                </option>
                                <option value="prospect" {{ ($filters['status'] ?? '') == 'prospect' ? 'selected' : '' }}>
                                    Prospect
                                </option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="area_of_activity_id" class="form-select">
                                <option value="">Todas as áreas</option>
                                @foreach($areas as $area)
                                    <option value="{{ $area->id }}" {{ ($filters['area_of_activity_id'] ?? '') == $area->id ? 'selected' : '' }}>
                                        {{ $area->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select name="profession_id" class="form-select">
                                <option value="">Todas as profissões</option>
                                @foreach($professions as $profession)
                                    <option value="{{ $profession->id }}" {{ ($filters['profession_id'] ?? '') == $profession->id ? 'selected' : '' }}>
                                        {{ $profession->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-1">
                            <button type="submit" class="btn btn-outline-primary w-100">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Lista de Clientes --}}
            <div class="card">
                <div class="card-body">
                    @if($customers->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Nome/Razão Social</th>
                                        <th>Tipo</th>
                                        <th>Email</th>
                                        <th>Telefone</th>
                                        <th>Status</th>
                                        <th>Cidade</th>
                                        <th>Cadastro</th>
                                        <th width="150">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($customers as $customer)
                                        <tr>
                                            <td>
                                                @if($customer->isPersonType() === 'pessoa_fisica')
                                                    <strong>{{ $customer->commonData->first_name }} {{ $customer->commonData->last_name }}</strong>
                                                    <br>
                                                    <small class="text-muted">CPF: {{ formatCpf($customer->commonData->cpf) }}</small>
                                                @else
                                                    <strong>{{ $customer->commonData->company_name }}</strong>
                                                    <br>
                                                    <small class="text-muted">CNPJ: {{ formatCnpj($customer->commonData->cnpj) }}</small>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="badge {{ $customer->isPersonType() === 'pessoa_fisica' ? 'bg-info' : 'bg-success' }}">
                                                    {{ $customer->isPersonType() === 'pessoa_fisica' ? 'PF' : 'PJ' }}
                                                </span>
                                            </td>
                                            <td>{{ $customer->contact->email }}</td>
                                            <td>{{ $customer->contact->phone ?? $customer->contact->phone_business }}</td>
                                            <td>
                                                <span class="badge bg-{{ $customer->status === 'active' ? 'success' : ($customer->status === 'prospect' ? 'warning' : 'secondary') }}">
                                                    {{ ucfirst($customer->status) }}
                                                </span>
                                            </td>
                                            <td>{{ $customer->address->city }}</td>
                                            <td>{{ $customer->created_at->format('d/m/Y') }}</td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="{{ route('customers.show', $customer->id) }}"
                                                       class="btn btn-sm btn-outline-primary" title="Ver detalhes">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="{{ route('customers.edit', $customer->id) }}"
                                                       class="btn btn-sm btn-outline-warning" title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <form method="POST" action="{{ route('customers.toggle-status', $customer->id) }}"
                                                          class="d-inline" onsubmit="return confirm('Alterar status deste cliente?')">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" class="btn btn-sm btn-outline-secondary" title="Alterar status">
                                                            <i class="fas fa-toggle-{{ $customer->status === 'active' ? 'on' : 'off' }}"></i>
                                                        </button>
                                                    </form>
                                                    <form method="POST" action="{{ route('customers.destroy', $customer->id) }}"
                                                          class="d-inline" onsubmit="return confirm('Remover este cliente?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Remover">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        {{-- Paginação --}}
                        <div class="d-flex justify-content-center mt-4">
                            {{ $customers->appends($filters)->links() }}
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                            <p class="text-muted">Nenhum cliente encontrado</p>
                            <a href="{{ route('customers.create.pessoa-fisica') }}" class="btn btn-primary">
                                Cadastrar primeiro cliente
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Auto-complete para busca
$('input[name="search"]').on('input', function() {
    const query = $(this).val();
    if (query.length >= 2) {
        // Implementar auto-complete
    }
});

// Filtros automáticos
$('select[name="type"], select[name="status"]').on('change', function() {
    $(this).closest('form').submit();
});
</script>
@endpush
```

-  **Arquivos necessários:**

   -  `resources/views/customers/index.blade.php`
   -  `resources/views/customers/create-pessoa-fisica.blade.php`
   -  `resources/views/customers/create-pessoa-juridica.blade.php`
   -  `resources/views/customers/show.blade.php`
   -  `resources/views/customers/edit-pessoa-fisica.blade.php`
   -  `resources/views/customers/edit-pessoa-juridica.blade.php`

-  **Critério de sucesso:** Interface completa responsiva com validação client-side.

---

# 🎯 Critérios de Sucesso Finais

## ✅ Funcionalidades Implementadas

1. **CRUD Completo:** 14 métodos (index, create PF/PJ, store PF/PJ, show, edit, update, destroy, restore, toggle, autocomplete, dashboard)
2. **Validações Robustas:** CPF/CNPJ, email único por tenant, validações customizadas
3. **Transações Multi-tabela:** Criação e atualização em 4 tabelas simultâneas
4. **Filtros Avançados:** Busca, tipo, status, área, profissão
5. **Interface Responsiva:** Views Bootstrap com JavaScript para UX
6. **Auditoria Completa:** Log de todas as operações
7. **Isolamento Multi-tenant:** Verificações automáticas
8. **Performance:** Eager loading, cache, otimizações
9. **Segurança:** Validações, rate limiting, sanitização
10.   **Testabilidade:** Código com testes unitários e feature

## 📊 Métricas de Qualidade

-  **Cobertura de Testes:** 80%+ para services e repositories
-  **Performance:** < 200ms para listagens com 1000+ registros
-  **Segurança:** Validação de entrada e saída
-  **Usabilidade:** Interface intuitiva e responsiva
-  **Manutenibilidade:** Código limpo e bem documentado

---

**PRÓXIMO PASSO:** Implementar seguindo a ordem dos grupos (1→2→3→4→5) e validar cada grupo antes de prosseguir.

**CUIDADO ESPECIAL:**

-  Validações de email único por tenant
-  Transações em 4 tabelas
-  Relacionamentos Eloquent complexos
-  Performance com eager loading
-  Interface responsiva com Bootstrap
