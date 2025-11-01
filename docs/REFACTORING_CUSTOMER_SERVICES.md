# 🔄 Refatoração: Customer Services Architecture

## 📋 Contexto

O projeto possui CustomerService implementado mas com código duplicado que pode ser otimizado usando **EntityDataService** (já criado).

**Arquivo atual:** `app/Services/Domain/CustomerService.php`

## 🎯 Objetivo da Refatoração

Refatorar CustomerService para usar **EntityDataService** (Shared Layer), eliminando duplicação de código e seguindo os padrões do memory bank.

---

## 📊 Análise Atual: CustomerService

### ❌ Problemas Identificados

1. **Código Duplicado** - Criação manual de CommonData, Contact, Address
2. **Validações Básicas** - Não usa ValidationHelper
3. **Limpeza Manual** - Usa `clean_document_number()` mas não valida
4. **Sem Reutilização** - Não aproveita EntityDataService

### ✅ Pontos Positivos

1. **Transações DB** - Usa DB::transaction() corretamente
2. **Estrutura Correta** - Está na camada Domain (correto)
3. **ServiceResult** - Retorna ServiceResult padronizado

---

## 📝 Plano de Refatoração

### **PASSO 1: Injetar EntityDataService**

**Arquivo:** `app/Services/Domain/CustomerService.php`

**Adicionar dependência:**

```php
use App\Services\Shared\EntityDataService;

public function __construct(
    private CustomerRepository $customerRepository,
    private CustomerInteractionService $interactionService,
    private EntityDataService $entityDataService, // NOVO
) {}
```

### **PASSO 2: Refatorar createCustomer()**

**ANTES (código duplicado):**

```php
public function createCustomer(array $data): ServiceResult
{
    try {
        $validation = $this->validateCustomerData($data);
        if (!$validation->isSuccess()) {
            return $validation;
        }

        $customer = DB::transaction(function () use ($data) {
            // Criar CommonData manualmente
            $commonData = CommonData::create([
                'tenant_id' => Auth::user()->tenant_id,
                'first_name' => $data['first_name'] ?? null,
                'last_name' => $data['last_name'] ?? null,
                // ... mais campos
            ]);

            // Criar Contact manualmente
            $contact = Contact::create([
                'tenant_id' => Auth::user()->tenant_id,
                'email' => $data['email_personal'] ?? null,
                // ... mais campos
            ]);

            // Criar Address manualmente
            $address = Address::create([
                'tenant_id' => Auth::user()->tenant_id,
                'cep' => $data['cep'] ?? null,
                // ... mais campos
            ]);

            // Criar Customer
            $customer = Customer::create([
                'tenant_id' => Auth::user()->tenant_id,
                'common_data_id' => $commonData->id,
                'contact_id' => $contact->id,
                'address_id' => $address->id,
                'status' => 'active',
            ]);

            return $customer->load(['commonData', 'contact', 'address']);
        });

        return $this->success($customer, 'Cliente criado com sucesso');
    } catch (\Exception $e) {
        return $this->error('Erro ao criar cliente: ' . $e->getMessage());
    }
}
```

**DEPOIS (usando EntityDataService):**

```php
public function createCustomer(array $data): ServiceResult
{
    try {
        $validation = $this->validateCustomerData($data);
        if (!$validation->isSuccess()) {
            return $validation;
        }

        $tenantId = Auth::user()->tenant_id;

        $customer = DB::transaction(function () use ($data, $tenantId) {
            // Usar EntityDataService para criar dados compartilhados
            $entityData = $this->entityDataService->createCompleteEntityData($data, $tenantId);

            // Criar Customer com IDs gerados
            $customer = Customer::create([
                'tenant_id' => $tenantId,
                'common_data_id' => $entityData['common_data']->id,
                'contact_id' => $entityData['contact']->id,
                'address_id' => $entityData['address']->id,
                'status' => 'active',
            ]);

            return $customer->load(['commonData', 'contact', 'address']);
        });

        return $this->success($customer, 'Cliente criado com sucesso');
    } catch (\Exception $e) {
        return $this->error('Erro ao criar cliente: ' . $e->getMessage());
    }
}
```

### **PASSO 3: Refatorar updateCustomer()**

**Adicionar método:**

```php
public function updateCustomer(int $id, array $data): ServiceResult
{
    try {
        $customer = $this->customerRepository->findByIdAndTenantId($id, auth()->user()->tenant_id);
        if (!$customer) {
            return $this->error('Cliente não encontrado');
        }

        $validation = $this->validateCustomerData($data, $customer->id);
        if (!$validation->isSuccess()) {
            return $validation;
        }

        $updated = DB::transaction(function () use ($customer, $data) {
            // Carregar relacionamentos
            $customer->load(['commonData', 'contact', 'address']);

            // Usar EntityDataService para atualizar
            $this->entityDataService->updateCompleteEntityData(
                $customer->commonData,
                $customer->contact,
                $customer->address,
                $data
            );

            // Atualizar status do Customer se fornecido
            if (isset($data['status'])) {
                $customer->update(['status' => $data['status']]);
            }

            return $customer->fresh(['commonData', 'contact', 'address']);
        });

        return $this->success($updated, 'Cliente atualizado com sucesso');
    } catch (\Exception $e) {
        return $this->error('Erro ao atualizar cliente: ' . $e->getMessage());
    }
}
```

### **PASSO 4: Refatorar validateCustomerData()**

**ANTES (validações básicas):**

```php
private function validateCustomerData(array $data): ServiceResult
{
    if (empty($data['first_name']) || empty($data['last_name'])) {
        return $this->error('Nome e sobrenome são obrigatórios');
    }

    if (empty($data['email_personal'])) {
        return $this->error('Email pessoal é obrigatório');
    }

    // Validação manual de CPF/CNPJ
    if ($hasCpf && strlen(preg_replace('/\D/', '', $data['cpf'])) !== 11) {
        return $this->error('CPF deve ter 11 dígitos');
    }

    return $this->success();
}
```

**DEPOIS (usando ValidationHelper):**

```php
use App\Helpers\ValidationHelper;

private function validateCustomerData(array $data, ?int $excludeCustomerId = null): ServiceResult
{
    // Validar campos obrigatórios
    if (empty($data['first_name']) || empty($data['last_name'])) {
        return $this->error('Nome e sobrenome são obrigatórios');
    }

    // Validar email usando helper
    if (empty($data['email_personal']) || !validate_email($data['email_personal'])) {
        return $this->error('Email pessoal válido é obrigatório');
    }

    // Validar telefone usando helper
    if (empty($data['phone_personal']) || !validate_phone($data['phone_personal'])) {
        return $this->error('Telefone pessoal válido é obrigatório');
    }

    // Verificar se tem pelo menos um documento
    $hasCpf = !empty($data['cpf']);
    $hasCnpj = !empty($data['cnpj']);

    if (!$hasCpf && !$hasCnpj) {
        return $this->error('CPF ou CNPJ é obrigatório');
    }

    // Validar CPF usando helper
    if ($hasCpf && !validate_cpf($data['cpf'])) {
        return $this->error('CPF inválido');
    }

    // Validar CNPJ usando helper
    if ($hasCnpj && !validate_cnpj($data['cnpj'])) {
        return $this->error('CNPJ inválido');
    }

    // Validar CEP usando helper
    if (!empty($data['cep']) && !validate_cep($data['cep'])) {
        return $this->error('CEP inválido');
    }

    // Validar data de nascimento se fornecida
    if (!empty($data['birth_date'])) {
        if (!ValidationHelper::isValidBirthDate($data['birth_date'], 18)) {
            return $this->error('Data de nascimento inválida ou cliente menor de 18 anos');
        }
    }

    // Validar endereço completo
    $requiredAddressFields = ['cep', 'address', 'neighborhood', 'city', 'state'];
    foreach ($requiredAddressFields as $field) {
        if (empty($data[$field])) {
            return $this->error('Endereço completo é obrigatório');
        }
    }

    // Validar unicidade de email no tenant
    if (!$this->isEmailUniqueInTenant($data['email_personal'], $excludeCustomerId)) {
        return $this->error('Email já cadastrado para outro cliente');
    }

    return $this->success();
}

/**
 * Verifica se email é único no tenant.
 */
private function isEmailUniqueInTenant(string $email, ?int $excludeCustomerId = null): bool
{
    $query = Contact::where('tenant_id', auth()->user()->tenant_id)
        ->where('email_personal', $email);

    if ($excludeCustomerId) {
        $query->whereHas('customer', function ($q) use ($excludeCustomerId) {
            $q->where('id', '!=', $excludeCustomerId);
        });
    }

    return !$query->exists();
}
```

### **PASSO 5: Adicionar métodos auxiliares**

```php
/**
 * Busca clientes com filtros avançados.
 */
public function searchCustomers(array $filters = []): ServiceResult
{
    try {
        $customers = $this->customerRepository->searchWithFilters($filters);
        return $this->success($customers, 'Busca realizada com sucesso');
    } catch (\Exception $e) {
        return $this->error('Erro na busca: ' . $e->getMessage());
    }
}

/**
 * Verifica se cliente tem relacionamentos (budgets, invoices).
 */
public function hasRelationships(int $customerId): ServiceResult
{
    try {
        $customer = $this->customerRepository->findByIdAndTenantId(
            $customerId, 
            auth()->user()->tenant_id
        );

        if (!$customer) {
            return $this->error('Cliente não encontrado');
        }

        $budgetsCount = $customer->budgets()->count();
        $invoicesCount = $customer->invoices()->count();

        $hasRelationships = ($budgetsCount + $invoicesCount) > 0;

        return $this->success([
            'has_relationships' => $hasRelationships,
            'budgets_count' => $budgetsCount,
            'invoices_count' => $invoicesCount,
        ]);
    } catch (\Exception $e) {
        return $this->error('Erro ao verificar relacionamentos: ' . $e->getMessage());
    }
}

/**
 * Duplica cliente existente.
 */
public function duplicateCustomer(int $customerId): ServiceResult
{
    try {
        $original = $this->customerRepository->findByIdAndTenantId(
            $customerId, 
            auth()->user()->tenant_id
        );

        if (!$original) {
            return $this->error('Cliente não encontrado');
        }

        $original->load(['commonData', 'contact', 'address']);

        $tenantId = auth()->user()->tenant_id;

        $duplicate = DB::transaction(function () use ($original, $tenantId) {
            // Duplicar dados usando EntityDataService
            $data = [
                'first_name' => $original->commonData->first_name . ' (Cópia)',
                'last_name' => $original->commonData->last_name,
                'birth_date' => $original->commonData->birth_date?->format('d/m/Y'),
                'cpf' => $original->commonData->cpf,
                'cnpj' => $original->commonData->cnpj,
                'company_name' => $original->commonData->company_name,
                'description' => $original->commonData->description,
                'area_of_activity_id' => $original->commonData->area_of_activity_id,
                'profession_id' => $original->commonData->profession_id,
                'email_personal' => null, // Email deve ser único
                'phone_personal' => $original->contact->phone_personal,
                'email_business' => $original->contact->email_business,
                'phone_business' => $original->contact->phone_business,
                'website' => $original->contact->website,
                'cep' => $original->address->cep,
                'address' => $original->address->address,
                'address_number' => $original->address->address_number,
                'neighborhood' => $original->address->neighborhood,
                'city' => $original->address->city,
                'state' => $original->address->state,
            ];

            $entityData = $this->entityDataService->createCompleteEntityData($data, $tenantId);

            return Customer::create([
                'tenant_id' => $tenantId,
                'common_data_id' => $entityData['common_data']->id,
                'contact_id' => $entityData['contact']->id,
                'address_id' => $entityData['address']->id,
                'status' => 'active',
            ])->load(['commonData', 'contact', 'address']);
        });

        return $this->success($duplicate, 'Cliente duplicado com sucesso');
    } catch (\Exception $e) {
        return $this->error('Erro ao duplicar cliente: ' . $e->getMessage());
    }
}
```

---

## 📋 Checklist de Implementação

### ✅ Fase 1: Preparação
- [ ] Verificar se EntityDataService está funcionando
- [ ] Verificar se ValidationHelper está disponível
- [ ] Backup do CustomerService atual

### ✅ Fase 2: Refatoração
- [ ] Injetar EntityDataService no construtor
- [ ] Refatorar `createCustomer()` para usar EntityDataService
- [ ] Refatorar `updateCustomer()` para usar EntityDataService
- [ ] Refatorar `validateCustomerData()` para usar ValidationHelper
- [ ] Adicionar método `isEmailUniqueInTenant()`
- [ ] Adicionar método `hasRelationships()`
- [ ] Adicionar método `duplicateCustomer()`

### ✅ Fase 3: Testes
- [ ] Testar criação de cliente
- [ ] Testar atualização de cliente
- [ ] Testar validações (CPF, CNPJ, email, telefone)
- [ ] Testar duplicação de cliente
- [ ] Testar verificação de relacionamentos

### ✅ Fase 4: Limpeza
- [ ] Remover código comentado
- [ ] Atualizar documentação
- [ ] Executar testes automatizados

---

## 🎯 Resultado Esperado

### **Código Mais Limpo:**

```php
// ANTES: 50+ linhas para criar cliente
$commonData = CommonData::create([...]);
$contact = Contact::create([...]);
$address = Address::create([...]);
$customer = Customer::create([...]);

// DEPOIS: 3 linhas
$entityData = $this->entityDataService->createCompleteEntityData($data, $tenantId);
$customer = Customer::create([...]);
```

### **Validações Robustas:**

```php
// ANTES: Validação manual
if (strlen(preg_replace('/\D/', '', $data['cpf'])) !== 11) {
    return $this->error('CPF deve ter 11 dígitos');
}

// DEPOIS: Validação com helper
if (!validate_cpf($data['cpf'])) {
    return $this->error('CPF inválido');
}
```

### **Benefícios:**

✅ **-60% de código** - Redução significativa de linhas
✅ **Reutilização** - EntityDataService compartilhado com Provider
✅ **Validações robustas** - ValidationHelper com algoritmos corretos
✅ **Manutenibilidade** - Mudanças em um único lugar
✅ **Testabilidade** - Mais fácil de testar

---

## 📚 Referências

- **EntityDataService**: `app/Services/Shared/EntityDataService.php`
- **ValidationHelper**: `app/Helpers/ValidationHelper.php`
- **Helpers**: `app/Support/helpers.php`
- **Guia de Uso**: `docs/ENTITY_DATA_SERVICE_USAGE.md`
