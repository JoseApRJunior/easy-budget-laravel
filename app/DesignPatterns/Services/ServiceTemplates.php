<?php

declare(strict_types=1);

namespace App\DesignPatterns\Services;

/**
 * Templates Práticos para Services
 *
 * Fornece templates prontos para uso imediato no desenvolvimento,
 * seguindo o padrão unificado definido em ServicePattern.
 */
class ServiceTemplates
{
    /**
     * TEMPLATE COMPLETO - Service Nível 1 (Básico)
     */
    public function basicServiceTemplate(): string
    {
        return '<?php

declare(strict_types=1);

namespace App\Services\Domain;

use App\Enums\OperationStatus;
use App\Models\{Module};
use App\Repositories\{Module}Repository;
use App\Services\Core\Abstracts\AbstractBaseService;
use App\Support\ServiceResult;
use Illuminate\Support\Facades\Validator;

/**
 * Serviço básico para {Module} - Apenas operações CRUD
 *
 * Implementa operações básicas sem lógica de negócio complexa.
 */
class {Module}Service extends AbstractBaseService
{
    /**
     * Construtor com injeção de dependência
     */
    public function __construct({Module}Repository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Retorna lista de filtros suportados
     */
    protected function getSupportedFilters(): array
    {
        return [
            \'id\',
            \'name\',
            \'status\',
            \'active\',
            \'created_at\',
            \'updated_at\',
        ];
    }

    /**
     * Valida dados para operações de {module}
     */
    public function validate(array $data, bool $isUpdate = false): ServiceResult
    {
        $rules = $this->getValidationRules($isUpdate);

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            $messages = implode(\', \', $validator->errors()->all());
            return $this->error(OperationStatus::INVALID_DATA, $messages);
        }

        return $this->success($data);
    }

    /**
     * Regras de validação para {module}
     */
    protected function getValidationRules(bool $isUpdate = false): array
    {
        return [
            \'name\' => \'required|string|max:255\',
            \'description\' => \'nullable|string|max:1000\',
            \'status\' => \'required|in:active,inactive\',
            \'active\' => \'boolean\',
        ];
    }

    /**
     * Cria novo {module} com validação
     */
    public function create(array $data): ServiceResult
    {
        $validation = $this->validate($data);
        if (!$validation->isSuccess()) {
            return $validation;
        }

        return parent::create($data);
    }

    /**
     * Atualiza {module} com validação
     */
    public function update(int $id, array $data): ServiceResult
    {
        $validation = $this->validate($data, true);
        if (!$validation->isSuccess()) {
            return $validation;
        }

        return parent::update($id, $data);
    }

    /**
     * Busca {module} por nome
     */
    public function findByName(string $name): ServiceResult
    {
        return $this->findOneBy([\'name\' => $name]);
    }

    /**
     * Lista {module} ativos
     */
    public function findActive(): ServiceResult
    {
        return $this->list([\'active\' => true]);
    }

    /**
     * Conta total de {module}
     */
    public function getTotalCount(): ServiceResult
    {
        return $this->count();
    }
}';
    }

    /**
     * TEMPLATE COMPLETO - Service Nível 2 (Intermediário)
     */
    public function intermediateServiceTemplate(): string
    {
        return '<?php

declare(strict_types=1);

namespace App\Services\Domain;

use App\Enums\OperationStatus;
use App\Models\{Module};
use App\Repositories\{Module}Repository;
use App\Services\Core\Abstracts\AbstractBaseService;
use App\Support\ServiceResult;
use Illuminate\Support\Facades\Validator;

/**
 * Serviço intermediário para {Module} - Com lógica de negócio
 *
 * Implementa regras de negócio específicas e validações avançadas.
 */
class {Module}Service extends AbstractBaseService
{
    /**
     * Construtor com injeção de dependência
     */
    public function __construct({Module}Repository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Retorna lista de filtros suportados
     */
    protected function getSupportedFilters(): array
    {
        return [
            \'id\',
            \'name\',
            \'slug\',
            \'status\',
            \'category_id\',
            \'price\',
            \'active\',
            \'created_at\',
            \'updated_at\',
        ];
    }

    /**
     * Valida dados para operações de {module}
     */
    public function validate(array $data, bool $isUpdate = false): ServiceResult
    {
        // Validação de regras de negócio primeiro
        $businessValidation = $this->validateBusinessRules($data, $isUpdate);
        if (!$businessValidation->isSuccess()) {
            return $businessValidation;
        }

        $rules = $this->getValidationRules($isUpdate);

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            $messages = implode(\', \', $validator->errors()->all());
            return $this->error(OperationStatus::INVALID_DATA, $messages);
        }

        return $this->success($data);
    }

    /**
     * Valida regras específicas de negócio
     */
    protected function validateBusinessRules(array $data, bool $isUpdate = false): ServiceResult
    {
        // Exemplo: validação específica de negócio
        if (isset($data[\'price\']) && $data[\'price\'] < 0) {
            return $this->error(OperationStatus::INVALID_DATA, \'Preço deve ser positivo\');
        }

        // Exemplo: validação de dependências
        if (isset($data[\'category_id\'])) {
            $categoryValidation = $this->validateCategory($data[\'category_id\']);
            if (!$categoryValidation->isSuccess()) {
                return $categoryValidation;
            }
        }

        return $this->success($data);
    }

    /**
     * Valida se categoria existe e está ativa
     */
    protected function validateCategory(int $categoryId): ServiceResult
    {
        // Implementação específica de validação de categoria
        return $this->success(true);
    }

    /**
     * Regras de validação para {module}
     */
    protected function getValidationRules(bool $isUpdate = false): array
    {
        return [
            \'name\' => \'required|string|max:255\',
            \'slug\' => \'required|string|max:100|unique:{module},slug\' . ($isUpdate && isset($data[\'id\']) ? \',\' . $data[\'id\'] : \'\'),
            \'category_id\' => \'required|exists:categories,id\',
            \'price\' => \'required|numeric|min:0\',
            \'status\' => \'required|in:active,inactive,suspended\',
        ];
    }

    /**
     * Cria novo {module} com validação completa
     */
    public function create(array $data): ServiceResult
    {
        $validation = $this->validate($data);
        if (!$validation->isSuccess()) {
            return $validation;
        }

        return parent::create($data);
    }

    /**
     * Atualiza {module} com validação completa
     */
    public function update(int $id, array $data): ServiceResult
    {
        $validation = $this->validate($data, true);
        if (!$validation->isSuccess()) {
            return $validation;
        }

        return parent::update($id, $data);
    }

    /**
     * Busca {module} por categoria
     */
    public function findByCategory(int $categoryId): ServiceResult
    {
        return $this->list([\'category_id\' => $categoryId]);
    }

    /**
     * Busca {module} por faixa de preço
     */
    public function findByPriceRange(float $minPrice, float $maxPrice): ServiceResult
    {
        return $this->list([
            \'price\' => [
                \'operator\' => \'between\',
                \'value\' => [$minPrice, $maxPrice]
            ]
        ]);
    }

    /**
     * Ativa {module}
     */
    public function activate(int $id): ServiceResult
    {
        return $this->update($id, [\'status\' => \'active\']);
    }

    /**
     * Desativa {module}
     */
    public function deactivate(int $id): ServiceResult
    {
        return $this->update($id, [\'status\' => \'inactive\']);
    }

    /**
     * Obtém estatísticas de {module}
     */
    public function getStats(): ServiceResult
    {
        return $this->getStats([]);
    }

    /**
     * Duplica {module}
     */
    public function duplicate(int $id, array $overrides = []): ServiceResult
    {
        $originalResult = $this->findById($id);
        if (!$originalResult->isSuccess()) {
            return $originalResult;
        }

        $original = $originalResult->getData();
        $data = $original->toArray();

        // Remove campos que não devem ser duplicados
        unset($data[\'id\'], $data[\'created_at\'], $data[\'updated_at\']);

        // Aplica modificações
        $data = array_merge($data, $overrides);

        // Garante unicidade
        if (!isset($overrides[\'name\'])) {
            $data[\'name\'] = $data[\'name\'] . \' (Cópia)\';
        }

        return $this->create($data);
    }
}';
    }

    /**
     * TEMPLATE COMPLETO - Service Nível 3 (Avançado)
     */
    public function advancedServiceTemplate(): string
    {
        return '<?php

declare(strict_types=1);

namespace App\Services\Domain;

use App\Enums\OperationStatus;
use App\Models\{Module};
use App\Repositories\{Module}Repository;
use App\Services\Core\Abstracts\AbstractBaseService;
use App\Services\Infrastructure\ExternalApiService;
use App\Services\Infrastructure\CacheService;
use App\Services\Infrastructure\NotificationService;
use App\Support\ServiceResult;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

/**
 * Serviço avançado para {Module} - Com integrações externas
 *
 * Implementa operações complexas com APIs externas, cache e notificações.
 */
class {Module}Service extends AbstractBaseService
{
    private ExternalApiService $externalApi;
    private CacheService $cache;
    private NotificationService $notification;

    /**
     * Construtor com injeção de dependências
     */
    public function __construct(
        {Module}Repository $repository,
        ExternalApiService $externalApi,
        CacheService $cache,
        NotificationService $notification
    ) {
        parent::__construct($repository);
        $this->externalApi = $externalApi;
        $this->cache = $cache;
        $this->notification = $notification;
    }

    /**
     * Retorna lista de filtros suportados
     */
    protected function getSupportedFilters(): array
    {
        return [
            \'id\',
            \'name\',
            \'status\',
            \'type\',
            \'external_id\',
            \'sync_status\',
            \'created_at\',
            \'updated_at\',
            \'synced_at\',
        ];
    }

    /**
     * Valida dados para operações de {module}
     */
    public function validate(array $data, bool $isUpdate = false): ServiceResult
    {
        // Validação básica
        $basicValidation = $this->validateBasicRules($data, $isUpdate);
        if (!$basicValidation->isSuccess()) {
            return $basicValidation;
        }

        // Validação de negócio
        $businessValidation = $this->validateBusinessRules($data);
        if (!$businessValidation->isSuccess()) {
            return $businessValidation;
        }

        // Validação externa
        $externalValidation = $this->validateWithExternalApi($data);
        if (!$externalValidation->isSuccess()) {
            return $externalValidation;
        }

        return $this->success($data);
    }

    /**
     * Valida regras básicas
     */
    protected function validateBasicRules(array $data, bool $isUpdate): ServiceResult
    {
        $rules = [
            \'name\' => \'required|string|max:255\',
            \'type\' => \'required|in:type1,type2,type3\',
            \'status\' => \'required|in:active,inactive,pending\',
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            $messages = implode(\', \', $validator->errors()->all());
            return $this->error(OperationStatus::INVALID_DATA, $messages);
        }

        return $this->success($data);
    }

    /**
     * Valida regras específicas de negócio
     */
    protected function validateBusinessRules(array $data): ServiceResult
    {
        // Validações complexas de negócio
        if (isset($data[\'type\']) && $data[\'type\'] === \'type1\') {
            if (!isset($data[\'external_id\'])) {
                return $this->error(OperationStatus::INVALID_DATA, \'External ID obrigatório para tipo 1\');
            }
        }

        return $this->success($data);
    }

    /**
     * Valida com API externa
     */
    protected function validateWithExternalApi(array $data): ServiceResult
    {
        if (isset($data[\'external_id\'])) {
            $apiValidation = $this->externalApi->validateExternalId($data[\'external_id\']);
            if (!$apiValidation->isSuccess()) {
                return $apiValidation;
            }
        }

        return $this->success($data);
    }

    /**
     * Cria {module} com integração externa
     */
    public function create(array $data): ServiceResult
    {
        $validation = $this->validate($data);
        if (!$validation->isSuccess()) {
            return $validation;
        }

        // Cria localmente primeiro
        $createResult = parent::create($data);
        if (!$createResult->isSuccess()) {
            return $createResult;
        }

        $module = $createResult->getData();

        // Sincroniza com API externa
        $syncResult = $this->syncWithExternalApi($module);
        if (!$syncResult->isSuccess()) {
            // Log erro mas não falha a criação
            $this->logSyncError($module, $syncResult);
        }

        return $createResult;
    }

    /**
     * Sincroniza com API externa
     */
    public function syncWithExternalApi($module): ServiceResult
    {
        try {
            $syncData = [
                \'id\' => $module->id,
                \'name\' => $module->name,
                \'type\' => $module->type,
            ];

            $syncResult = $this->externalApi->sync($syncData);

            if ($syncResult->isSuccess()) {
                // Atualiza dados de sincronização
                $this->update($module->id, [
                    \'sync_status\' => \'synced\',
                    \'synced_at\' => now(),
                    \'external_id\' => $syncResult->getData()[\'external_id\'] ?? null,
                ]);

                return $this->success($syncResult->getData(), \'Sincronizado com sucesso\');
            }

            return $syncResult;

        } catch (Exception $e) {
            return $this->error(OperationStatus::EXTERNAL_ERROR, \'Erro na sincronização externa\', null, $e);
        }
    }

    /**
     * Processa {module} com API externa
     */
    public function processWithExternalApi(int $id, array $processData): ServiceResult
    {
        $moduleResult = $this->findById($id);
        if (!$moduleResult->isSuccess()) {
            return $moduleResult;
        }

        $module = $moduleResult->getData();

        // Tenta cache primeiro
        $cacheKey = \'processed_data_\' . $id . \'_\' . md5(json_encode($processData));
        $cachedResult = $this->cache->get($cacheKey);

        if ($cachedResult) {
            return $this->success($cachedResult, \'Dados obtidos do cache\');
        }

        // Processa com API externa
        $processResult = $this->externalApi->process([
            \'module_id\' => $id,
            \'process_data\' => $processData,
        ]);

        if ($processResult->isSuccess()) {
            $processedData = $processResult->getData();

            // Salva no cache por 1 hora
            $this->cache->put($cacheKey, $processedData, 3600);

            // Atualiza dados de processamento
            $this->update($id, [
                \'last_processed_at\' => now(),
                \'process_status\' => \'completed\',
            ]);

            return $this->success($processedData, \'Processado com sucesso\');
        }

        return $processResult;
    }

    /**
     * Gera relatório avançado
     */
    public function generateReport(array $filters = []): ServiceResult
    {
        $cacheKey = \'report_\' . md5(json_encode($filters)) . \'_\' . auth()->id();

        // Tenta cache primeiro
        $cachedReport = Cache::get($cacheKey);
        if ($cachedReport) {
            return $this->success($cachedReport, \'Relatório obtido do cache\');
        }

        // Busca dados principais
        $mainData = $this->list($filters);

        if (!$mainData->isSuccess()) {
            return $mainData;
        }

        // Busca dados relacionados
        $relatedData = $this->getRelatedData($filters);

        // Processa com API externa se necessário
        $externalData = $this->getExternalData($filters);

        // Combina dados do relatório
        $report = $this->buildReport($mainData->getData(), $relatedData, $externalData);

        // Salva no cache por 24 horas
        Cache::put($cacheKey, $report, 86400);

        return $this->success($report, \'Relatório gerado com sucesso\');
    }

    /**
     * Busca dados relacionados para relatório
     */
    private function getRelatedData(array $filters): array
    {
        // Implementação específica para buscar dados relacionados
        return [];
    }

    /**
     * Busca dados externos para relatório
     */
    private function getExternalData(array $filters): array
    {
        try {
            return $this->externalApi->getReportData($filters)->getData();
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Constrói estrutura do relatório
     */
    private function buildReport(array $mainData, array $relatedData, array $externalData): array
    {
        return [
            \'generated_at\' => now()->toISOString(),
            \'filters\' => $filters,
            \'summary\' => [
                \'total_records\' => count($mainData),
                \'processed_records\' => collect($mainData)->where(\'status\', \'processed\')->count(),
            ],
            \'main_data\' => $mainData,
            \'related_data\' => $relatedData,
            \'external_data\' => $externalData,
        ];
    }

    /**
     * Notifica sobre evento importante
     */
    private function notifyAboutEvent($module, string $event): void
    {
        try {
            $this->notification->send([
                \'type\' => \'module_event\',
                \'module_id\' => $module->id,
                \'event\' => $event,
                \'data\' => $module->toArray(),
            ]);
        } catch (Exception $e) {
            // Log erro mas não falha operação principal
            logger()->error(\'Erro ao enviar notificação\', [
                \'module_id\' => $module->id,
                \'event\' => $event,
                \'error\' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Loga erro de sincronização
     */
    private function logSyncError($module, ServiceResult $syncResult): void
    {
        logger()->warning(\'Falha na sincronização externa\', [
            \'module_id\' => $module->id,
            \'error\' => $syncResult->getMessage(),
        ]);
    }
}';
    }

    /**
     * GUIA DE UTILIZAÇÃO DOS TEMPLATES
     */
    public function getUsageGuide(): string
    {
        return '
## Como Usar os Templates de Services

### 1. Escolha o Nível Correto

**Nível 1 (Básico):**
- Para entidades simples sem regras complexas
- CRUD básico com validação simples
- Exemplos: Categories, Tags, Units

**Nível 2 (Intermediário):**
- Para entidades com regras de negócio específicas
- Validações avançadas e métodos específicos
- Exemplos: Products, Customers, Budgets

**Nível 3 (Avançado):**
- Para entidades com integrações externas
- Processamento complexo e cache
- Exemplos: Invoices, Payments, Reports

### 2. Substitua os Placeholders

No template, substitua:
- `{Module}` → Nome do módulo (ex: Customer, Product, Budget)
- `{module}` → Nome em minúsculo (ex: customer, product, budget)
- `{Module}Repository` → Nome do repository correspondente
- `ExternalApiService` → Nome do serviço externo específico

### 3. Personalize conforme Necessário

**Para Nível 1:**
```php
protected function getValidationRules(bool $isUpdate = false): array
{
    return [
        \'name\' => \'required|string|max:255\',
        \'code\' => \'required|string|unique:categories,code\' . ($isUpdate ? \',\' . $data[\'id\'] : \'\'),
        \'active\' => \'boolean\',
    ];
}
```

**Para Nível 2:**
```php
protected function validateBusinessRules(array $data, bool $isUpdate = false): ServiceResult
{
    if ($data[\'price\'] > 10000) {
        return $this->error(OperationStatus::INVALID_DATA, \'Preço muito alto\');
    }

    return $this->success($data);
}
```

**Para Nível 3:**
```php
public function processPayment(int $invoiceId, array $paymentData): ServiceResult
{
    // Implementação específica com gateway de pagamento
}
```

### 4. Implemente Métodos Específicos

Cada service pode ter métodos únicos:

```php
// Para Products
public function findByPriceRange(float $min, float $max): ServiceResult
{
    return $this->list([
        \'price\' => [\'operator\' => \'between\', \'value\' => [$min, $max]]
    ]);
}

// Para Customers
public function findNearby(float $lat, float $lng, int $radius = 10): ServiceResult
{
    // Implementação específica de busca por localização
}

// Para Reports
public function generateMonthlyReport(int $year, int $month): ServiceResult
{
    // Implementação específica de relatório mensal
}
```

### 5. Configure Injeção de Dependência

**Para services com dependências externas:**

```php
// app/Providers/AppServiceProvider.php
public function register()
{
    $this->app->singleton(AdvancedService::class, function ($app) {
        return new AdvancedService(
            $app->make(AdvancedRepository::class),
            $app->make(ExternalApiService::class),
            $app->make(CacheService::class),
            $app->make(NotificationService::class)
        );
    });
}
```

### 6. Implemente Tratamento de Erro

**Padrão recomendado:**
```php
public function criticalOperation(int $id): ServiceResult
{
    try {
        $result = $this->externalApi->process($id);

        if (!$result->isSuccess()) {
            return $result; // Retorna erro da API externa
        }

        return $this->success($result->getData(), \'Operação crítica realizada\');

    } catch (ExternalApiException $e) {
        return $this->error(OperationStatus::EXTERNAL_ERROR, \'API externa indisponível\', null, $e);
    } catch (ValidationException $e) {
        return $this->error(OperationStatus::INVALID_DATA, \'Dados inválidos\', null, $e);
    } catch (Exception $e) {
        return $this->error(OperationStatus::ERROR, \'Erro interno do servidor\', null, $e);
    }
}
```

## Benefícios dos Templates

✅ **Rapidez**: Criação rápida de services padronizados
✅ **Consistência**: Todos seguem convenções unificadas
✅ **Qualidade**: Tratamento de erro e validação inclusos
✅ **Flexibilidade**: Fáceis de personalizar conforme necessidade
✅ **Manutenibilidade**: Estrutura familiar para todos os desenvolvedores

## Estrutura de Arquivos Recomendada

```
app/Services/
├── Domain/                          # Services de domínio
│   ├── BasicService.php            # Nível 1 - Básico
│   ├── IntermediateService.php     # Nível 2 - Intermediário
│   └── AdvancedService.php         # Nível 3 - Avançado
├── Application/                     # Services de aplicação
├── Infrastructure/                  # Services externos
│   ├── PaymentGatewayService.php
│   ├── NotificationService.php
│   └── CacheService.php
└── Core/Abstracts/                  # Abstrações
    └── AbstractBaseService.php
```

## Convenções de Desenvolvimento

### **Métodos Obrigatórios:**
```php
protected function getSupportedFilters(): array
public function validate(array $data, bool $isUpdate = false): ServiceResult
```

### **ServiceResult Sempre:**
```php
public function create(array $data): ServiceResult
public function update(int $id, array $data): ServiceResult
public function delete(int $id): ServiceResult
public function list(array $filters = []): ServiceResult
```

### **Validação em Ordem:**
1. **Regras de negócio** primeiro (validateBusinessRules)
2. **Validações técnicas** depois (validate)
3. **Dependências externas** por último (validateWithExternalApi)

### **Tratamento de Cache:**
```php
public function getCachedData(int $id): ServiceResult
{
    $cacheKey = \'data_\' . $id;

    return Cache::remember($cacheKey, 3600, function() use ($id) {
        return $this->getFreshData($id);
    });
}
```

### **Logging Estruturado:**
```php
private function logOperation(string $operation, array $context = []): void
{
    logger()->info("Service operation: {$operation}", [
        \'service\' => static::class,
        \'context\' => $context,
        \'user_id\' => auth()->id(),
        \'tenant_id\' => tenant(\'id\'),
    ]);
}
```

## Boas Práticas

### **1. Validação Antecipada**
- Sempre valide antes de executar operações
- Use códigos de erro específicos (INVALID_DATA, CONFLICT, etc.)
- Retorne ServiceResult apropriado para cada cenário

### **2. Tratamento de Exceções**
- Capture exceções específicas primeiro
- Use exceções genéricas apenas para casos inesperados
- Mapeie exceções para OperationStatus correto

### **3. Performance**
- Use cache para operações custosas
- Implemente paginação para grandes datasets
- Considere processamento assíncrono quando apropriado

### **4. Testabilidade**
- Services devem ser testáveis unitariamente
- Use injeção de dependência para componentes externos
- Mock de APIs externas em testes

### **5. Documentação**
- Documente regras de negócio implementadas
- Especifique filtros suportados
- Documente métodos públicos com exemplos de uso';
    }
}
