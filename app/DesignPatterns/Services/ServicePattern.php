<?php

declare(strict_types=1);

namespace App\DesignPatterns\Services;

/**
 * Padrão Unificado para Services no Easy Budget Laravel
 *
 * Define convenções consistentes para desenvolvimento de services,
 * garantindo uniformidade, manutenibilidade e reutilização de código.
 *
 * @package App\DesignPatterns
 */
class ServicePattern
{
    /**
     * PADRÃO UNIFICADO PARA SERVICES
     *
     * Baseado na análise dos services existentes, definimos 3 níveis:
     */

    /**
     * NÍVEL 1 - Service Básico (CRUD Simples)
     * Para serviços com operações básicas sem lógica complexa
     *
     * @example CustomerService básico, ProductService básico
     */
    public function basicService(): string
    {
        return '
abstract class BasicService extends AbstractBaseService
{
    protected function getSupportedFilters(): array
    {
        return [
            \'id\', \'name\', \'status\', \'active\',
            \'created_at\', \'updated_at\'
        ];
    }

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

    protected function getValidationRules(bool $isUpdate = false): array
    {
        $rules = [
            \'name\' => \'required|string|max:255\',
            \'status\' => \'required|in:active,inactive\',
        ];

        return $rules;
    }
}';
    }

    /**
     * NÍVEL 2 - Service Intermediário (Com Lógica de Negócio)
     * Para serviços com regras de negócio específicas
     *
     * @example PlanService, BudgetService
     */
    public function intermediateService(): string
    {
        return '
class IntermediateService extends AbstractBaseService
{
    protected function getSupportedFilters(): array
    {
        return [
            \'id\', \'name\', \'slug\', \'status\', \'price\',
            \'created_at\', \'updated_at\', \'category_id\'
        ];
    }

    public function validate(array $data, bool $isUpdate = false): ServiceResult
    {
        $rules = $this->getValidationRules($isUpdate);

        // Validações específicas do negócio
        $businessValidation = $this->validateBusinessRules($data, $isUpdate);
        if (!$businessValidation->isSuccess()) {
            return $businessValidation;
        }

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            $messages = implode(\', \', $validator->errors()->all());
            return $this->error(OperationStatus::INVALID_DATA, $messages);
        }

        return $this->success($data);
    }

    protected function validateBusinessRules(array $data, bool $isUpdate): ServiceResult
    {
        // Validações específicas do negócio
        if (isset($data[\'price\']) && $data[\'price\'] < 0) {
            return $this->error(OperationStatus::INVALID_DATA, \'Preço deve ser positivo\');
        }

        return $this->success($data);
    }

    protected function getValidationRules(bool $isUpdate = false): array
    {
        return [
            \'name\' => \'required|string|max:255\',
            \'slug\' => \'required|string|max:50|unique:plans,slug\' . ($isUpdate ? \',\' . ($data[\'id\'] ?? \'\') : \'\'),
            \'price\' => \'required|numeric|min:0\',
            \'status\' => \'required|in:active,inactive,suspended\',
        ];
    }

    // Métodos específicos de negócio
    public function findActive(): ServiceResult
    {
        return $this->list([\'status\' => \'active\']);
    }

    public function findBySlug(string $slug): ServiceResult
    {
        return $this->findOneBy([\'slug\' => $slug]);
    }
}';
    }

    /**
     * NÍVEL 3 - Service Avançado (Com Múltiplas Responsabilidades)
     * Para serviços complexos com integração externa e lógica avançada
     *
     * @example PaymentService, ReportService
     */
    public function advancedService(): string
    {
        return '
class AdvancedService extends AbstractBaseService
{
    private ExternalApiService $externalApi;
    private CacheService $cache;

    public function __construct(
        RepositoryInterface $repository,
        ExternalApiService $externalApi,
        CacheService $cache
    ) {
        parent::__construct($repository);
        $this->externalApi = $externalApi;
        $this->cache = $cache;
    }

    protected function getSupportedFilters(): array
    {
        return [
            \'id\', \'name\', \'status\', \'type\', \'external_id\',
            \'created_at\', \'updated_at\', \'processed_at\'
        ];
    }

    public function validate(array $data, bool $isUpdate = false): ServiceResult
    {
        // Validação básica
        $basicValidation = parent::validate($data, $isUpdate);
        if (!$basicValidation->isSuccess()) {
            return $basicValidation;
        }

        // Validação específica
        $specificValidation = $this->validateSpecificRules($data);
        if (!$specificValidation->isSuccess()) {
            return $specificValidation;
        }

        // Validação externa
        $externalValidation = $this->validateWithExternalApi($data);
        if (!$externalValidation->isSuccess()) {
            return $externalValidation;
        }

        return $this->success($data);
    }

    public function processWithExternalApi(array $data): ServiceResult
    {
        // Tenta buscar do cache primeiro
        $cacheKey = \'external_data_\' . md5(json_encode($data));
        $cachedResult = $this->cache->get($cacheKey);

        if ($cachedResult) {
            return $this->success($cachedResult);
        }

        // Processa com API externa
        $apiResult = $this->externalApi->process($data);

        if ($apiResult->isSuccess()) {
            // Salva no cache por 1 hora
            $this->cache->put($cacheKey, $apiResult->getData(), 3600);
            return $apiResult;
        }

        return $apiResult;
    }

    public function generateReport(array $filters): ServiceResult
    {
        // Busca dados principais
        $mainData = $this->list($filters);

        // Busca dados relacionados
        $relatedData = $this->getRelatedData($filters);

        // Processa com API externa se necessário
        $externalData = $this->processWithExternalApi($filters);

        // Combina todos os dados
        $reportData = $this->combineReportData(
            $mainData->getData(),
            $relatedData->getData(),
            $externalData->getData()
        );

        return $this->success($reportData);
    }

    protected function validateSpecificRules(array $data): ServiceResult
    {
        // Validações específicas complexas
        return $this->success($data);
    }

    protected function validateWithExternalApi(array $data): ServiceResult
    {
        // Validação com serviço externo
        return $this->success($data);
    }
}';
    }

    /**
     * CONVENÇÕES PARA SERVICERESULT
     */

    /**
     * Uso Correto do ServiceResult
     */
    public function serviceResultConventions(): string
    {
        return '
// ✅ CORRETO - Uso adequado do ServiceResult

// 1. Sempre retornar ServiceResult
public function create(array $data): ServiceResult
{
    $validation = $this->validate($data);
    if (!$validation->isSuccess()) {
        return $validation; // Retorna erro de validação
    }

    try {
        $entity = $this->repository->create($data);
        return $this->success($entity, \'Criado com sucesso\');
    } catch (Exception $e) {
        return $this->error(OperationStatus::ERROR, \'Erro interno\', null, $e);
    }
}

// 2. Usar métodos auxiliares consistentes
protected function success(mixed $data = null, string $message = \'\'): ServiceResult
{
    return ServiceResult::success($data, $message);
}

protected function error(OperationStatus|string $status, string $message = \'\', mixed $data = null, ?Exception $exception = null): ServiceResult
{
    return ServiceResult::error($status, $message, $data, $exception);
}

// 3. Tratamento específico por tipo de erro
public function update(int $id, array $data): ServiceResult
{
    // Validação primeiro
    $validation = $this->validate($data, true);
    if (!$validation->isSuccess()) {
        return $validation;
    }

    // Busca entidade
    $entityResult = $this->findById($id);
    if (!$entityResult->isSuccess()) {
        return $entityResult; // Retorna NOT_FOUND
    }

    try {
        $updated = $this->repository->update($id, $data);
        return $this->success($updated, \'Atualizado com sucesso\');
    } catch (QueryException $e) {
        return $this->error(OperationStatus::CONFLICT, \'Dados conflitantes\', null, $e);
    } catch (Exception $e) {
        return $this->error(OperationStatus::ERROR, \'Erro interno\', null, $e);
    }
}

// ❌ INCORRETO - Não fazer isso

// 1. Não retornar dados diretamente
public function create(array $data): array // ❌ Errado
{
    return $this->repository->create($data); // ❌ Não retorna ServiceResult
}

// 2. Não usar exceções para controle de fluxo
public function findById(int $id): ?Model // ❌ Errado
{
    try {
        return $this->repository->find($id);
    } catch (Exception $e) {
        return null; // ❌ Silencia erro
    }
}

// 3. Não misturar tipos de retorno
public function list(): array|ServiceResult // ❌ Errado
{
    return $this->repository->getAll(); // ❌ Inconsistente
}';
    }

    /**
     * EXEMPLOS PRÁTICOS DE IMPLEMENTAÇÃO
     */

    /**
     * Exemplo de Service Nível 1 - Básico
     */
    public function basicServiceExample(): string
    {
        return '<?php

namespace App\Services\Domain;

use App\Models\Product;
use App\Repositories\ProductRepository;
use App\Services\Core\Abstracts\AbstractBaseService;
use App\Support\ServiceResult;
use Illuminate\Support\Facades\Validator;

/**
 * Serviço básico para produtos - Apenas operações CRUD
 */
class ProductService extends AbstractBaseService
{
    public function __construct(ProductRepository $repository)
    {
        parent::__construct($repository);
    }

    protected function getSupportedFilters(): array
    {
        return [
            \'id\', \'name\', \'sku\', \'price\', \'active\',
            \'category_id\', \'created_at\', \'updated_at\'
        ];
    }

    public function validate(array $data, bool $isUpdate = false): ServiceResult
    {
        $rules = [
            \'name\' => \'required|string|max:255\',
            \'sku\' => \'required|string|max:50|unique:products,sku\' . ($isUpdate && isset($data[\'id\']) ? \',\' . $data[\'id\'] : \'\'),
            \'price\' => \'required|numeric|min:0\',
            \'active\' => \'boolean\',
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            $messages = implode(\', \', $validator->errors()->all());
            return $this->error(OperationStatus::INVALID_DATA, $messages);
        }

        return $this->success($data);
    }

    public function create(array $data): ServiceResult
    {
        $validation = $this->validate($data);
        if (!$validation->isSuccess()) {
            return $validation;
        }

        return parent::create($data);
    }

    public function update(int $id, array $data): ServiceResult
    {
        $validation = $this->validate($data, true);
        if (!$validation->isSuccess()) {
            return $validation;
        }

        return parent::update($id, $data);
    }
}';
    }

    /**
     * Exemplo de Service Nível 2 - Intermediário
     */
    public function intermediateServiceExample(): string
    {
        return '<?php

namespace App\Services\Domain;

use App\Models\Budget;
use App\Repositories\BudgetRepository;
use App\Services\Core\Abstracts\AbstractBaseService;
use App\Support\ServiceResult;
use Illuminate\Support\Facades\Validator;

/**
 * Serviço intermediário para orçamentos - Com lógica de negócio
 */
class BudgetService extends AbstractBaseService
{
    public function __construct(BudgetRepository $repository)
    {
        parent::__construct($repository);
    }

    protected function getSupportedFilters(): array
    {
        return [
            \'id\', \'code\', \'customer_id\', \'status\', \'total_value\',
            \'due_date\', \'created_at\', \'updated_at\'
        ];
    }

    public function validate(array $data, bool $isUpdate = false): ServiceResult
    {
        // Validação de regras de negócio primeiro
        $businessValidation = $this->validateBusinessRules($data);
        if (!$businessValidation->isSuccess()) {
            return $businessValidation;
        }

        $rules = [
            \'code\' => \'required|string|max:50|unique:budgets,code\' . ($isUpdate && isset($data[\'id\']) ? \',\' . $data[\'id\'] : \'\'),
            \'customer_id\' => \'required|exists:customers,id\',
            \'total_value\' => \'required|numeric|min:0\',
            \'due_date\' => \'nullable|date|after:today\',
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            $messages = implode(\', \', $validator->errors()->all());
            return $this->error(OperationStatus::INVALID_DATA, $messages);
        }

        return $this->success($data);
    }

    protected function validateBusinessRules(array $data): ServiceResult
    {
        // Regra: orçamento deve ter pelo menos um item
        if (isset($data[\'items\']) && count($data[\'items\']) === 0) {
            return $this->error(OperationStatus::INVALID_DATA, \'Orçamento deve ter pelo menos um item\');
        }

        // Regra: valor total deve bater com soma dos itens
        if (isset($data[\'items\']) && isset($data[\'total_value\'])) {
            $itemsTotal = collect($data[\'items\'])->sum(\'total\');
            if (abs($itemsTotal - $data[\'total_value\']) > 0.01) {
                return $this->error(OperationStatus::INVALID_DATA, \'Valor total não confere com itens\');
            }
        }

        return $this->success($data);
    }

    // Métodos específicos de negócio
    public function approve(int $budgetId): ServiceResult
    {
        $budgetResult = $this->findById($budgetId);
        if (!$budgetResult->isSuccess()) {
            return $budgetResult;
        }

        $budget = $budgetResult->getData();

        // Verifica se pode ser aprovado
        if ($budget->status !== \'pending\') {
            return $this->error(OperationStatus::CONFLICT, \'Apenas orçamentos pendentes podem ser aprovados\');
        }

        return $this->update($budgetId, [\'status\' => \'approved\']);
    }

    public function calculateTotal(array $items): ServiceResult
    {
        try {
            $total = collect($items)->sum(function ($item) {
                return ($item[\'quantity\'] ?? 0) * ($item[\'unit_price\'] ?? 0);
            });

            return $this->success([
                \'total\' => $total,
                \'item_count\' => count($items),
                \'average_value\' => count($items) > 0 ? $total / count($items) : 0
            ]);
        } catch (Exception $e) {
            return $this->error(OperationStatus::ERROR, \'Erro ao calcular total\', null, $e);
        }
    }

    public function getBudgetsByCustomer(int $customerId): ServiceResult
    {
        return $this->list([\'customer_id\' => $customerId]);
    }

    public function getOverdueBudgets(): ServiceResult
    {
        return $this->list([
            \'status\' => \'approved\',
            \'due_date\' => [\'operator\' => \'<\', \'value\' => now()->toDateString()]
        ]);
    }
}';
    }

    /**
     * Exemplo de Service Nível 3 - Avançado
     */
    public function advancedServiceExample(): string
    {
        return '<?php

namespace App\Services\Domain;

use App\Models\Invoice;
use App\Repositories\InvoiceRepository;
use App\Services\Core\Abstracts\AbstractBaseService;
use App\Services\Infrastructure\PaymentGatewayService;
use App\Services\Infrastructure\NotificationService;
use App\Support\ServiceResult;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

/**
 * Serviço avançado para faturas - Com integrações externas
 */
class InvoiceService extends AbstractBaseService
{
    private PaymentGatewayService $paymentGateway;
    private NotificationService $notification;

    public function __construct(
        InvoiceRepository $repository,
        PaymentGatewayService $paymentGateway,
        NotificationService $notification
    ) {
        parent::__construct($repository);
        $this->paymentGateway = $paymentGateway;
        $this->notification = $notification;
    }

    protected function getSupportedFilters(): array
    {
        return [
            \'id\', \'code\', \'customer_id\', \'status\', \'total_value\',
            \'due_date\', \'payment_date\', \'payment_method\',
            \'external_payment_id\', \'created_at\', \'updated_at\'
        ];
    }

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
        $externalValidation = $this->validateWithPaymentGateway($data);
        if (!$externalValidation->isSuccess()) {
            return $externalValidation;
        }

        return $this->success($data);
    }

    public function processPayment(int $invoiceId, array $paymentData): ServiceResult
    {
        // Busca fatura
        $invoiceResult = $this->findById($invoiceId);
        if (!$invoiceResult->isSuccess()) {
            return $invoiceResult;
        }

        $invoice = $invoiceResult->getData();

        // Verifica se já foi paga
        if ($invoice->status === \'paid\') {
            return $this->error(OperationStatus::CONFLICT, \'Fatura já foi paga\');
        }

        // Processa pagamento com gateway externo
        $paymentResult = $this->paymentGateway->processPayment([
            \'amount\' => $invoice->total_value,
            \'currency\' => \'BRL\',
            \'description\' => \'Pagamento da fatura \' . $invoice->code,
            \'payment_data\' => $paymentData
        ]);

        if (!$paymentResult->isSuccess()) {
            return $paymentResult;
        }

        $paymentResponse = $paymentResult->getData();

        // Atualiza fatura com dados do pagamento
        $updateResult = $this->update($invoiceId, [
            \'status\' => \'paid\',
            \'payment_date\' => now(),
            \'payment_method\' => $paymentData[\'method\'] ?? \'unknown\',
            \'external_payment_id\' => $paymentResponse[\'payment_id\'] ?? null,
            \'transaction_data\' => $paymentResponse
        ]);

        if (!$updateResult->isSuccess()) {
            return $updateResult;
        }

        // Envia notificação de pagamento confirmado
        $this->notification->sendPaymentConfirmation($invoice, $paymentResponse);

        return $this->success($updateResult->getData(), \'Pagamento processado com sucesso\');
    }

    public function generateMonthlyReport(int $year, int $month): ServiceResult
    {
        $cacheKey = "invoice_report_{$year}_{$month}_" . auth()->id();

        // Tenta buscar do cache primeiro
        $cachedReport = Cache::get($cacheKey);
        if ($cachedReport) {
            return $this->success($cachedReport, \'Relatório obtido do cache\');
        }

        // Busca dados principais
        $startDate = "{$year}-{$month}-01";
        $endDate = date(\'Y-m-t\', strtotime($startDate));

        $invoicesResult = $this->list([
            \'created_at\' => [
                \'operator\' => \'between\',
                \'value\' => [$startDate, $endDate]
            ]
        ]);

        if (!$invoicesResult->isSuccess()) {
            return $invoicesResult;
        }

        $invoices = $invoicesResult->getData();

        // Busca dados relacionados
        $relatedData = $this->getRelatedReportData($invoices);

        // Processa dados externos se necessário
        $externalData = $this->getExternalReportData($year, $month);

        // Combina dados do relatório
        $report = $this->buildReportData($invoices, $relatedData, $externalData);

        // Salva no cache por 24 horas
        Cache::put($cacheKey, $report, 86400);

        return $this->success($report, \'Relatório gerado com sucesso\');
    }

    protected function validateBasicRules(array $data, bool $isUpdate): ServiceResult
    {
        $rules = [
            \'code\' => \'required|string|max:50|unique:invoices,code\' . ($isUpdate && isset($data[\'id\']) ? \',\' . $data[\'id\'] : \'\'),
            \'customer_id\' => \'required|exists:customers,id\',
            \'total_value\' => \'required|numeric|min:0.01\',
            \'due_date\' => \'required|date|after:today\',
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            $messages = implode(\', \', $validator->errors()->all());
            return $this->error(OperationStatus::INVALID_DATA, $messages);
        }

        return $this->success($data);
    }

    protected function validateBusinessRules(array $data): ServiceResult
    {
        // Validações específicas de negócio
        if (isset($data[\'total_value\']) && $data[\'total_value\'] > 100000) {
            return $this->error(OperationStatus::INVALID_DATA, \'Valor máximo por fatura é R$ 100.000,00\');
        }

        return $this->success($data);
    }

    protected function validateWithPaymentGateway(array $data): ServiceResult
    {
        // Validação com gateway de pagamento
        if (isset($data[\'payment_method\'])) {
            $gatewayValidation = $this->paymentGateway->validatePaymentMethod($data[\'payment_method\']);
            if (!$gatewayValidation->isSuccess()) {
                return $gatewayValidation;
            }
        }

        return $this->success($data);
    }

    private function getRelatedReportData(array $invoices): array
    {
        // Busca dados relacionados para o relatório
        return [
            \'customer_count\' => collect($invoices)->pluck(\'customer_id\')->unique()->count(),
            \'payment_methods\' => collect($invoices)->pluck(\'payment_method\')->countBy()->toArray(),
        ];
    }

    private function getExternalReportData(int $year, int $month): array
    {
        // Busca dados de APIs externas se necessário
        return [];
    }

    private function buildReportData(array $invoices, array $relatedData, array $externalData): array
    {
        return [
            \'period\' => "{$year}-{$month}",
            \'total_invoices\' => count($invoices),
            \'total_value\' => collect($invoices)->sum(\'total_value\'),
            \'average_value\' => count($invoices) > 0 ? collect($invoices)->sum(\'total_value\') / count($invoices) : 0,
            \'paid_invoices\' => collect($invoices)->where(\'status\', \'paid\')->count(),
            \'pending_invoices\' => collect($invoices)->where(\'status\', \'pending\')->count(),
            \'related_data\' => $relatedData,
            \'external_data\' => $externalData,
            \'generated_at\' => now()->toISOString()
        ];
    }
}';
    }

    /**
     * GUIA DE IMPLEMENTAÇÃO
     */
    public function getImplementationGuide(): string
    {
        return '
## Guia de Implementação - Escolhendo o Nível Correto

### NÍVEL 1 - Service Básico
✅ Quando usar:
- Operações CRUD simples sem lógica complexa
- Entidades sem regras de negócio específicas
- Projetos iniciais ou módulos simples
- Protótipos rápidos

❌ Não usar quando:
- Há regras de negócio específicas
- Integração com APIs externas necessária
- Relatórios complexos requeridos
- Validações avançadas necessárias

### NÍVEL 2 - Service Intermediário
✅ Quando usar:
- Regras de negócio específicas do domínio
- Validações complexas necessárias
- Métodos específicos além do CRUD básico
- Relacionamentos que precisam tratamento especial

❌ Não usar quando:
- Operações muito simples (use nível 1)
- Integrações externas complexas (use nível 3)
- Múltiplas responsabilidades distintas

### NÍVEL 3 - Service Avançado
✅ Quando usar:
- Integração com APIs externas
- Processamento assíncrono necessário
- Cache e performance críticos
- Múltiplas fontes de dados
- Relatórios complexos com dados externos

❌ Não usar quando:
- Operações simples (use nível 1 ou 2)
- Projeto inicial sem necessidade de complexidade
- Time de desenvolvimento pequeno

## Benefícios do Padrão

✅ **Consistência**: Todos os services seguem convenções unificadas
✅ **Manutenibilidade**: Código familiar e fácil de manter
✅ **Testabilidade**: ServiceResult facilita testes unitários
✅ **Tratamento de Erro**: Padronização no tratamento de exceções
✅ **Performance**: Cache e otimização inclusos quando necessário
✅ **Escalabilidade**: Arquitetura preparada para crescimento

## Estrutura Recomendada

```
app/Services/
├── Core/Abstracts/AbstractBaseService.php    # Base para todos
├── Domain/                                   # Services de domínio
│   ├── BasicService.php                     # Nível 1
│   ├── IntermediateService.php              # Nível 2
│   └── AdvancedService.php                  # Nível 3
└── Infrastructure/                          # Services externos
    ├── PaymentGatewayService.php
    ├── NotificationService.php
    └── CacheService.php
```

## Convenções de Código

### **Métodos Obrigatórios:**
```php
protected function getSupportedFilters(): array
public function validate(array $data, bool $isUpdate = false): ServiceResult
```

### **ServiceResult Sempre:**
```php
// ✅ Sempre retorne ServiceResult
public function create(array $data): ServiceResult
public function update(int $id, array $data): ServiceResult
public function delete(int $id): ServiceResult
public function list(array $filters = []): ServiceResult
```

### **Validação em Duas Etapas:**
```php
public function create(array $data): ServiceResult
{
    // 1. Validação de negócio primeiro
    $businessValidation = $this->validateBusinessRules($data);
    if (!$businessValidation->isSuccess()) {
        return $businessValidation;
    }

    // 2. Validação técnica depois
    $technicalValidation = $this->validate($data);
    if (!$technicalValidation->isSuccess()) {
        return $technicalValidation;
    }

    // 3. Execução
    return parent::create($data);
}
```

### **Tratamento de Erro Padronizado:**
```php
try {
    $result = $this->externalApi->process($data);
    return $result;
} catch (ExternalApiException $e) {
    return $this->error(OperationStatus::EXTERNAL_ERROR, \'Erro na API externa\', null, $e);
} catch (Exception $e) {
    return $this->error(OperationStatus::ERROR, \'Erro interno\', null, $e);
}
```

## Boas Práticas

### **1. Validação Antecipada**
- Valide regras de negócio antes de validações técnicas
- Retorne ServiceResult específico para cada tipo de erro
- Use códigos de erro apropriados (INVALID_DATA, CONFLICT, etc.)

### **2. Tratamento de Exceções**
- Use try/catch apenas para erros inesperados
- Capture exceções específicas (QueryException, ApiException)
- Mapeie exceções para OperationStatus apropriado

### **3. Performance**
- Use cache para operações custosas
- Implemente paginação para grandes datasets
- Considere processamento assíncrono para tarefas pesadas

### **4. Testabilidade**
- Services devem ser testáveis unitariamente
- Use injeção de dependência para serviços externos
- Mock de APIs externas em testes

### **5. Documentação**
- Documente métodos públicos com exemplos
- Especifique filtros suportados
- Documente regras de negócio implementadas';
    }

}
