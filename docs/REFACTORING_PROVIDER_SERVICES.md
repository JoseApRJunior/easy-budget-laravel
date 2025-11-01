# 🔄 Refatoração: Provider Services Architecture

## 📋 Contexto

O projeto possui dois arquivos de serviço para Provider:
- `app/Services/Application/ProviderManagementService.php` (Application Layer) - **POPULADO**
- `app/Services/Domain/ProviderService.php` (Domain Layer) - **VAZIO**

Segundo a arquitetura documentada no memory bank, existe uma separação clara de responsabilidades entre camadas.

## 🏗️ Arquitetura de Serviços (Memory Bank)

```
Controllers → Services → Repositories → Models

Services divididos em 3 camadas:
├── Application/    - Orquestração, workflows complexos, múltiplos serviços
├── Domain/         - CRUD, regras de negócio da entidade, validações específicas
└── Infrastructure/ - APIs externas, e-mail, cache, PDF, pagamentos
```

## 🎯 Objetivo da Refatoração

Separar corretamente as responsabilidades entre **ProviderManagementService** (Application) e **ProviderService** (Domain), seguindo os padrões documentados no memory bank.

---

## 📊 Análise Atual: ProviderManagementService

### ✅ Métodos que DEVEM permanecer (Application Layer)

**Workflows Complexos:**
- `createProviderFromRegistration()` - Cria Tenant + User + Provider + Plan + Subscription
- `updateProvider()` - Orquestra atualização de User + CommonData + Contact + Address

**Orquestração de Múltiplos Serviços:**
- `getDashboardData()` - Agrega Budget + Activity + FinancialSummary
- `getFinancialReports()` - Agrega múltiplas queries financeiras
- `getBudgetReports()` - Relatórios de orçamentos
- `getServiceReports()` - Relatórios de serviços
- `getCustomerReports()` - Relatórios de clientes

**Lógica de Aplicação:**
- `changePassword()` - Workflow de mudança de senha + log de atividade
- `getProviderForUpdate()` - Prepara dados para formulário (inclui AreaOfActivity, Profession)

### 🔄 Métodos que DEVEM mover para Domain Layer

**CRUD Básico:**
- `getProviderByUserId()` - Busca simples com relacionamentos

**Validações de Domínio:**
- `isEmailAvailable()` - Validação de unicidade de email

**Métodos Auxiliares Privados:**
- `cleanDocumentNumber()` - **JÁ EXISTE como helper global** `clean_document_number()`
- `handleLogoUpload()` - Pode mover para FileUploadService (Infrastructure)

---

## 📝 Plano de Refatoração

### **PASSO 1: Criar ProviderService (Domain Layer)**

**Arquivo:** `app/Services/Domain/ProviderService.php`

**Implementar:**

```php
<?php

declare(strict_types=1);

namespace App\Services\Domain;

use App\Models\Provider;
use App\Repositories\ProviderRepository;
use App\Services\Core\Abstracts\AbstractBaseService;
use App\Support\ServiceResult;

/**
 * Service de domínio para operações CRUD do Provider.
 *
 * Responsável por:
 * - CRUD básico da entidade Provider
 * - Validações de domínio específicas
 * - Regras de negócio puras do Provider
 */
class ProviderService extends AbstractBaseService
{
    public function __construct(
        private ProviderRepository $providerRepository
    ) {
        parent::__construct($providerRepository);
    }

    /**
     * Busca Provider por user_id com relacionamentos.
     */
    public function getByUserId(int $userId, int $tenantId): ?Provider
    {
        return $this->providerRepository->findByUserId($userId, $tenantId);
    }

    /**
     * Verifica se email está disponível (não usado por outro usuário).
     */
    public function isEmailAvailable(string $email, int $excludeUserId, int $tenantId): bool
    {
        return $this->providerRepository->isEmailAvailable($email, $excludeUserId, $tenantId);
    }

    /**
     * Busca Provider com todos os relacionamentos carregados.
     */
    public function getWithRelations(int $providerId): ServiceResult
    {
        $provider = $this->providerRepository->findWithRelations($providerId, [
            'user', 'commonData', 'contact', 'address'
        ]);

        if (!$provider) {
            return $this->error('Provider não encontrado');
        }

        return $this->success($provider);
    }
}
```

### **PASSO 2: Adicionar métodos no ProviderRepository**

**Arquivo:** `app/Repositories/ProviderRepository.php`

**Adicionar:**

```php
/**
 * Busca Provider por user_id.
 */
public function findByUserId(int $userId, int $tenantId): ?Provider
{
    return Provider::where('user_id', $userId)
        ->where('tenant_id', $tenantId)
        ->with(['user', 'commonData', 'contact', 'address'])
        ->first();
}

/**
 * Verifica disponibilidade de email.
 */
public function isEmailAvailable(string $email, int $excludeUserId, int $tenantId): bool
{
    return !User::where('email', $email)
        ->where('tenant_id', $tenantId)
        ->where('id', '!=', $excludeUserId)
        ->exists();
}

/**
 * Busca Provider com relacionamentos específicos.
 */
public function findWithRelations(int $providerId, array $relations = []): ?Provider
{
    return Provider::with($relations)->find($providerId);
}
```

### **PASSO 3: Refatorar ProviderManagementService**

**Arquivo:** `app/Services/Application/ProviderManagementService.php`

**Mudanças:**

1. **Injetar ProviderService e EntityDataService:**

```php
public function __construct(
    private ProviderService $providerService,
    private EntityDataService $entityDataService,
    private FinancialSummary $financialSummary,
    private ActivityService $activityService,
    // ... outros serviços
) {}
```

2. **Substituir chamadas diretas:**

```php
// ANTES
$provider = Provider::where('user_id', $userId)
    ->where('tenant_id', $tenantId)
    ->with(['user', 'commonData', 'contact', 'address'])
    ->first();

// DEPOIS
$provider = $this->providerService->getByUserId($userId, $tenantId);
```

3. **Usar EntityDataService para atualização:**

```php
// ANTES (código duplicado)
if ($provider->commonData) {
    $commonDataUpdate = [
        'company_name' => $data['company_name'] ?? $provider->commonData->company_name,
        'cnpj' => $this->cleanDocumentNumber($data['cnpj']),
        // ... mais campos
    ];
    $provider->commonData->update($commonDataUpdate);
}
// Repetir para Contact e Address...

// DEPOIS (usando EntityDataService)
$this->entityDataService->updateCompleteEntityData(
    $provider->commonData,
    $provider->contact,
    $provider->address,
    $data
);
```

4. **Remover métodos privados duplicados:**

```php
// REMOVER (já existe como helper global)
private function cleanDocumentNumber(?string $documentNumber): ?string
{
    // Use clean_document_number() helper
}

// MOVER para FileUploadService (Infrastructure)
private function handleLogoUpload(UploadedFile $file, ?string $currentLogo): ?string
{
    // Mover para app/Services/Infrastructure/FileUploadService.php
}
```

### **PASSO 4: Atualizar ProviderBusinessController**

**Arquivo:** `app/Http/Controllers/ProviderBusinessController.php`

**Mudanças:**

```php
// REMOVER método privado duplicado
private function cleanDocumentNumber(?string $documentNumber): ?string
{
    // Usar clean_document_number() helper diretamente
}

// No método update(), usar helper:
if (!empty($validated['cnpj'])) {
    $validated['document'] = clean_document_number($validated['cnpj']);
} elseif (!empty($validated['cpf'])) {
    $validated['document'] = clean_document_number($validated['cpf']);
}
```

---

## 📋 Checklist de Implementação

### ✅ Fase 1: Criar Estrutura Base
- [ ] Criar `ProviderService.php` em `app/Services/Domain/`
- [ ] Adicionar métodos no `ProviderRepository.php`
- [ ] Escrever testes unitários para ProviderService

### ✅ Fase 2: Refatorar ProviderManagementService
- [ ] Injetar `ProviderService` e `EntityDataService`
- [ ] Substituir queries diretas por chamadas ao ProviderService
- [ ] Substituir atualizações manuais por EntityDataService
- [ ] Remover método `cleanDocumentNumber()` (usar helper)
- [ ] Mover `handleLogoUpload()` para FileUploadService

### ✅ Fase 3: Refatorar Controllers
- [ ] Remover `cleanDocumentNumber()` de ProviderBusinessController
- [ ] Usar helpers globais diretamente

### ✅ Fase 4: Testes
- [ ] Executar testes existentes
- [ ] Adicionar testes para novos métodos
- [ ] Validar integração entre camadas

---

## 🎯 Resultado Esperado

### **Separação Clara de Responsabilidades:**

```
ProviderBusinessController
    ↓
ProviderManagementService (Application)
    ├── Orquestra workflows complexos
    ├── Integra múltiplos serviços
    ├── Gera relatórios agregados
    └── Usa ↓
        ├── ProviderService (Domain) - CRUD básico
        ├── EntityDataService (Shared) - CommonData/Contact/Address
        ├── FileUploadService (Infrastructure) - Upload de arquivos
        └── ActivityService (Domain) - Logs de atividade
```

### **Benefícios:**

✅ **Código mais limpo** - Cada service tem responsabilidade única
✅ **Reutilização** - ProviderService pode ser usado em outros lugares
✅ **Testabilidade** - Testes unitários mais simples e focados
✅ **Manutenibilidade** - Mudanças isoladas por camada
✅ **Consistência** - Segue padrões do memory bank

---

## 📚 Referências

- **Memory Bank**: `.amazonq/rules/memory-bank/structure.md`
- **Service Patterns**: `app/DesignPatterns/Services/`
- **EntityDataService**: `docs/ENTITY_DATA_SERVICE_USAGE.md`
- **Helpers**: `app/Support/helpers.php`
