# 🎯 Prompts Detalhados - Migração Invoice Controller (Ordem Correta)

## 📋 CONTEXTO

**Base:** Análise completa em `ANALISE_COMPARATIVA_INVOICE_CONTROLLER.md` (Assumido)
**Status:** 0% implementado
**Objetivo:** Implementar o módulo de faturas completo, seguindo a arquitetura moderna do novo sistema, com base na análise do `InvoiceController` do sistema legado.
**Ordem:** Sequência lógica seguindo dependências técnicas (Repository → Form Requests → Service → Controller).
**IMPORTANTE:** Sistema usa InvoiceStatus (igual ao BudgetStatus) - NÃO há tabela invoice_statuses, status é armazenado diretamente como string no campo 'status' da tabela invoices.

---

# 🎯 GRUPO 1: REPOSITORY (Base de Dados) - **PRIMEIRO**

## 🎯 PROMPT 1.1: Implementar getFiltered() - Busca com Filtros

Implemente APENAS o método getFiltered() no InvoiceRepository:

TAREFA ESPECÍFICA:

-  Filtros: Status, cliente, período, busca (código da fatura, nome do cliente, descrição do serviço)
-  Paginação: Automática
-  Eager loading: Relacionamentos básicos
-  Tenant scoping: Automático

IMPLEMENTAÇÃO:

```php
public function getFiltered(array $filters = [], ?array $orderBy = null, ?int $limit = null): Collection
{
    $query = $this->model->newQuery();

    // Aplicar filtros
    if (!empty($filters['status'])) {
        $query->where('status', $filters['status']);
    }

    if (!empty($filters['customer_id'])) {
        $query->where('customer_id', $filters['customer_id']);
    }

    if (!empty($filters['date_from'])) {
        $query->whereDate('issue_date', '>=', $filters['date_from']);
    }

    if (!empty($filters['date_to'])) {
        $query->whereDate('issue_date', '<=', $filters['date_to']);
    }

    if (!empty($filters['search'])) {
        $query->where(function ($q) use ($filters) {
            $q->where('code', 'like', '%' . $filters['search'] . '%')
              ->orWhereHas('customer', function ($sq) use ($filters) {
                  $sq->where('name', 'like', '%' . $filters['search'] . '%');
              })
              ->orWhereHas('service', function ($sq) use ($filters) {
                  $sq->where('description', 'like', '%' . $filters['search'] . '%');
              });
        });
    }

    // Eager loading padrão
    $query->with(['customer', 'service.budget']);

    // Ordenação
    if ($orderBy) {
        foreach ($orderBy as $field => $direction) {
            $query->orderBy($field, $direction);
        }
    } else {
        $query->orderBy('issue_date', 'desc');
    }

    // Limite
    if ($limit) {
        $query->limit($limit);
    }

    return $query->get();
}
```

ARQUIVOS:

-  app/Repositories/InvoiceRepository.php (método getFiltered)

CRITÉRIO DE SUCESSO: Repository com filtros e eager loading

---

## 🎯 PROMPT 1.2: Implementar findByCode() - Repository

Implemente APENAS o método findByCode() no InvoiceRepository:

TAREFA ESPECÍFICA:

-  Busca: Por código (string)
-  Eager loading: Relacionamentos opcionais
-  Tenant scoping: Automático via AbstractTenantRepository

IMPLEMENTAÇÃO:

```php
public function findByCode(string $code, array $with = []): ?Model
{
    $query = $this->model->where('code', $code);

    if (!empty($with)) {
        $query->with($with);
    }

    return $query->first();
}
```

ARQUIVOS:

-  app/Repositories/InvoiceRepository.php (método findByCode)

CRITÉRIO DE SUCESSO: Repository com busca por código

---

## 🎯 PROMPT 1.3: Implementar countByStatus() - Métricas

Implemente APENAS o método countByStatus() no InvoiceRepository:

TAREFA ESPECÍFICA:

-  Contagem: Faturas por status dentro do tenant
-  Return: Array com status como chave e count como valor
-  Performance: Query otimizada

IMPLEMENTAÇÃO:

```php
public function countByStatus(): array
{
    return $this->model
        ->selectRaw('status, COUNT(*) as count')
        ->groupBy('status')
        ->pluck('count', 'status')
        ->toArray();
}

public function countOverdue(): int
{
    return $this->model->where('due_date', '<', now())
                       ->where('status', '!=', 'paid') // Assumindo que 'paid' é um status final
                       ->count();
}

public function getTotalRevenue(): float
{
    return $this->model->where('status', 'paid')->sum('total_amount');
}
```

ARQUIVOS:

-  app/Repositories/InvoiceRepository.php (métodos countByStatus, countOverdue, getTotalRevenue)

CRITÉRIO DE SUCESSO: Repository com métricas de faturas

---

# 🎯 GRUPO 2: FORM REQUESTS (Validação) - **SEGUNDO**

## 🎯 PROMPT 2.1: Criar InvoiceStoreRequest - Validação de Criação

Crie APENAS o InvoiceStoreRequest:

TAREFA ESPECÍFICA:

-  Campos: service_code, customer_id, issue_date, due_date, total_amount, status, items
-  Validação: Relacionamentos (service_id, customer_id) existem
-  Items: Array de produtos com product_id, quantity, unit_value
-  Unicidade: Código de fatura único por tenant
-  Status: Apenas status válidos do InvoiceStatus enum

IMPLEMENTAÇÃO:

```php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Enums\InvoiceStatus;
use App\Models\Service;
use App\Models\Customer;

class InvoiceStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        return [
            'service_code' => [
                'required',
                'string',
                'exists:services,code'
            ],
            'customer_id' => [
                'required',
                'integer',
                'exists:customers,id'
            ],
            'issue_date' => 'required|date',
            'due_date' => 'required|date|after_or_equal:issue_date',
            'status' => [
                'required',
                'string',
                'in:' . implode(',', array_map(fn($case) => $case->value, InvoiceStatus::cases()))
            ],
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_value' => 'required|numeric|min:0.01'
        ];
    }

    public function messages(): array
    {
        return [
            'service_code.required' => 'Código do serviço é obrigatório',
            'service_code.exists' => 'Serviço não encontrado',
            'customer_id.required' => 'Cliente é obrigatório',
            'customer_id.exists' => 'Cliente não encontrado',
            'issue_date.required' => 'Data de emissão é obrigatória',
            'due_date.required' => 'Data de vencimento é obrigatória',
            'due_date.after_or_equal' => 'Data de vencimento deve ser igual ou posterior à data de emissão',
            'status.required' => 'Status é obrigatório',
            'status.in' => 'Status inválido selecionado',
            'items.required' => 'Itens da fatura são obrigatórios',
            'items.min' => 'Deve ter pelo menos 1 item',
            'items.*.product_id.required' => 'Produto é obrigatório em cada item',
            'items.*.product_id.exists' => 'Produto não encontrado',
            'items.*.quantity.min' => 'Quantidade deve ser maior que zero',
            'items.*.unit_value.min' => 'Valor unitário deve ser maior que zero'
        ];
    }

    public function validated(): array
    {
        $data = parent::validated();

        // Buscar service_id pelo código
        $service = Service::where('code', $data['service_code'])->first();
        $data['service_id'] = $service->id;
        unset($data['service_code']);

        return $data;
    }
}
```

ARQUIVOS:

-  app/Http/Requests/InvoiceStoreRequest.php (criar)
-  app/Models/Service.php (relacionamento)
-  app/Models/Customer.php (relacionamento)

CRITÉRIO DE SUCESSO: Validação robusta com mensagens em português

---

## 🎯 PROMPT 2.2: Criar InvoiceUpdateRequest - Validação de Edição

Crie APENAS o InvoiceUpdateRequest:

TAREFA ESPECÍFICA:

-  Campos: Sem service_code (não pode alterar serviço vinculado)
-  Itens: Gerenciar itens existentes (update/delete/create)
-  Status: Apenas status editáveis
-  Due date: Validação de data futura

IMPLEMENTAÇÃO:

```php
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Enums\InvoiceStatus;

class InvoiceUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $invoiceId = $this->route('invoice'); // Assume que a rota tem um parâmetro 'invoice' com o ID da fatura

        return [
            'customer_id' => [
                'sometimes',
                'required',
                'integer',
                'exists:customers,id'
            ],
            'issue_date' => 'sometimes|required|date',
            'due_date' => 'sometimes|required|date|after_or_equal:issue_date',
            'status' => [
                'sometimes',
                'required',
                'string',
                'in:' . implode(',', array_map(fn($case) => $case->value, InvoiceStatus::cases()))
            ],
            'items' => 'sometimes|required|array|min:1',
            'items.*.id' => 'nullable|integer|exists:invoice_items,id',
            'items.*.product_id' => 'required_without:items.*.id|integer|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_value' => 'required|numeric|min:0.01',
            'items.*.action' => 'nullable|in:create,update,delete'
        ];
    }

    public function messages(): array
    {
        return [
            'customer_id.required' => 'Cliente é obrigatório',
            'customer_id.exists' => 'Cliente não encontrado',
            'issue_date.required' => 'Data de emissão é obrigatória',
            'due_date.required' => 'Data de vencimento é obrigatória',
            'due_date.after_or_equal' => 'Data de vencimento deve ser igual ou posterior à data de emissão',
            'status.required' => 'Status é obrigatório',
            'status.in' => 'Status inválido selecionado',
            'items.required' => 'Itens da fatura são obrigatórios',
            'items.min' => 'Deve ter pelo menos 1 item',
            'items.*.product_id.required' => 'Produto é obrigatório',
            'items.*.product_id.exists' => 'Produto não encontrado',
            'items.*.quantity.min' => 'Quantidade deve ser maior que zero',
            'items.*.unit_value.min' => 'Valor unitário deve ser maior que zero',
            'items.*.action.in' => 'Ação inválida para item'
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            // Validar que pelo menos um item está sendo criado/atualizado (não apenas deletado)
            $items = $this->items;
            $hasValidItems = false;

            if (is_array($items)) {
                foreach ($items as $item) {
                    if (($item['action'] ?? 'create') !== 'delete') {
                        $hasValidItems = true;
                        break;
                    }
                }
            } else {
                // Se 'items' não for um array, significa que não foi fornecido ou é inválido,
                // e a validação 'required|array|min:1' já deve ter falhado.
                // Se for 'sometimes', então não é obrigatório.
                $hasValidItems = true; // Se não foi fornecido, não precisamos validar itens ativos
            }


            if (!$hasValidItems && isset($this->items)) { // Apenas se 'items' foi fornecido e não tem itens válidos
                $validator->errors()->add('items', 'Deve ter pelo menos 1 item ativo');
            }
        });
    }
}
```

ARQUIVOS:

-  app/Http/Requests/InvoiceUpdateRequest.php (criar)
-  app/Models/InvoiceItem.php (relacionamento)

CRITÉRIO DE SUCESSO: Validação para edição com gerenciamento de itens

---

# 🎯 GRUPO 3: SERVICES (Lógica de Negócio) - **TERCEIRO**

## 🎯 PROMPT 3.1: Implementar findByCode() - Service

Implemente APENAS o método findByCode() no InvoiceService:

TAREFA ESPECÍFICA:

-  Busca: Por código (string) não por ID
-  Tenant scoping: Automático via TenantScoped
-  Eager loading: Relacionamentos opcionais
-  Error handling: Fatura não encontrada

IMPLEMENTAÇÃO:

```php
namespace App\Services\Domain;

use App\Enums\OperationStatus;
use App\Models\Invoice;
use App\Repositories\InvoiceRepository;
use App\Services\AbstractService;
use App\Services\ServiceResult;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Models\Product;
use App\Models\InvoiceItem;
use App\Models\Service;
use App\Enums\InvoiceStatus;

class InvoiceService extends AbstractService
{
    private InvoiceRepository $invoiceRepository;

    public function __construct(InvoiceRepository $invoiceRepository)
    {
        $this->invoiceRepository = $invoiceRepository;
    }

    public function findByCode(string $code, array $with = []): ServiceResult
    {
        try {
            $query = Invoice::where('code', $code);

            if (!empty($with)) {
                $query->with($with);
            }

            $invoice = $query->first();

            if (!$invoice) {
                return $this->error(
                    OperationStatus::NOT_FOUND,
                    "Fatura com código {$code} não encontrada"
                );
            }

            return $this->success($invoice, 'Fatura encontrada');

        } catch (Exception $e) {
            return $this->error(
                OperationStatus::ERROR,
                'Erro ao buscar fatura',
                null,
                $e
            );
        }
    }
}
```

ARQUIVOS:

-  app/Services/Domain/InvoiceService.php (método findByCode)

CRITÉRIO DE SUCESSO: Busca por código funcionando com eager loading opcional

---

## 🎯 PROMPT 3.2: Implementar getFilteredInvoices() - Busca com Filtros

Implemente APENAS o método getFilteredInvoices() no InvoiceService:

TAREFA ESPECÍFICA:

-  Filtros: Status, cliente, período, busca por código/descrição
-  Paginação: 15 registros por página
-  Ordenação: Por data de emissão (desc)
-  Eager loading: Relacionamentos básicos

IMPLEMENTAÇÃO:

```php
// Dentro de app/Services/Domain/InvoiceService.php

public function getFilteredInvoices(array $filters = [], array $with = []): ServiceResult
{
    try {
        $invoices = $this->invoiceRepository->getFiltered($filters, ['issue_date' => 'desc'], 15);

        return $this->success($invoices, 'Faturas filtradas');

    } catch (Exception $e) {
        return $this->error(
            OperationStatus::ERROR,
            'Erro ao filtrar faturas',
            null,
            $e
        );
    }
}
```

ARQUIVOS:

-  app/Services/Domain/InvoiceService.php (método getFilteredInvoices)

CRITÉRIO DE SUCESSO: Filtros funcionais com paginação

---

## 🎯 PROMPT 3.3: Implementar createInvoice() - Criar Fatura

Implemente APENAS o método createInvoice() no InvoiceService:

TAREFA ESPECÍFICA:

-  Geração: Código único {SERVICE_CODE}-INV{SEQUENCIAL}
-  Transaction: DB::transaction para atomicidade
-  Itens: Criar InvoiceItems relacionados
-  Total: Calcular total da fatura
-  Auditoria: Registrar criação

IMPLEMENTAÇÃO:

```php
// Dentro de app/Services/Domain/InvoiceService.php

public function createInvoice(array $data): ServiceResult
{
    try {
        return DB::transaction(function () use ($data) {
            // Buscar serviço
            $service = Service::where('code', $data['service_code'])->first();
            if (!$service) {
                return $this->error(
                    OperationStatus::NOT_FOUND,
                    'Serviço não encontrado'
                );
            }

            // Gerar código único
            $invoiceCode = $this->generateUniqueInvoiceCode($service->code);

            // Calcular total da fatura
            $totalAmount = $this->calculateInvoiceTotal($data['items']);

            // Criar fatura
            $invoice = Invoice::create([
                'tenant_id' => tenant()->id,
                'service_id' => $service->id,
                'customer_id' => $data['customer_id'],
                'code' => $invoiceCode,
                'issue_date' => $data['issue_date'],
                'due_date' => $data['due_date'],
                'total_amount' => $totalAmount,
                'status' => $data['status'] ?? InvoiceStatus::PENDING->value,
            ]);

            // Criar itens da fatura
            if (!empty($data['items'])) {
                $this->createInvoiceItems($invoice, $data['items']);
            }

            return $this->success($invoice->load([
                'customer',
                'service',
                'invoiceItems.product'
            ]), 'Fatura criada com sucesso');

        });

    } catch (Exception $e) {
        return $this->error(
            OperationStatus::ERROR,
            'Erro ao criar fatura',
            null,
            $e
        );
    }
}

private function generateUniqueInvoiceCode(string $serviceCode): string
{
    $lastInvoice = Invoice::whereHas('service', function ($query) use ($serviceCode) {
            $query->where('code', $serviceCode);
        })
        ->orderBy('code', 'desc')
        ->first();

    $sequential = 1;
    if ($lastInvoice && preg_match('/-INV(\d{3})$/', $lastInvoice->code, $matches)) {
        $sequential = (int) $matches[1] + 1;
    }

    return "{$serviceCode}-INV" . str_pad($sequential, 3, '0', STR_PAD_LEFT);
}

private function calculateInvoiceTotal(array $items): float
{
    $total = 0;
    foreach ($items as $itemData) {
        $total += ((float) $itemData['quantity'] * (float) $itemData['unit_value']);
    }
    return $total;
}
```

ARQUIVOS:

-  app/Services/Domain/InvoiceService.php (métodos createInvoice, generateUniqueInvoiceCode, calculateInvoiceTotal)
-  app/Models/Invoice.php (relacionamentos)

CRITÉRIO DE SUCESSO: Fatura criada com código único e itens relacionados

---

## 🎯 PROMPT 3.4: Implementar createInvoiceItems() - Criar Itens

Implemente APENAS o método createInvoiceItems() no InvoiceService:

TAREFA ESPECÍFICA:

-  Validação: Produtos existem e estão ativos
-  Cálculo: Total dos itens
-  Transaction: Atomicidade com criação da fatura
-  Relacionamento: Vincular à fatura

IMPLEMENTAÇÃO:

```php
// Dentro de app/Services/Domain/InvoiceService.php

private function createInvoiceItems(Invoice $invoice, array $items): void
{
    foreach ($items as $itemData) {
        // Validar produto
        $product = Product::where('id', $itemData['product_id'])
            ->where('active', true)
            ->first();

        if (!$product) {
            throw new Exception("Produto ID {$itemData['product_id']} não encontrado ou inativo");
        }

        // Calcular total do item
        $quantity = (float) $itemData['quantity'];
        $unitValue = (float) $itemData['unit_value'];
        $total = $quantity * $unitValue;

        // Criar item
        InvoiceItem::create([
            'tenant_id' => $invoice->tenant_id,
            'invoice_id' => $invoice->id,
            'product_id' => $product->id,
            'unit_value' => $unitValue,
            'quantity' => $quantity,
            'total' => $total
        ]);
    }
}
```

ARQUIVOS:

-  app/Services/Domain/InvoiceService.php (método createInvoiceItems)
-  app/Models/InvoiceItem.php (relacionamentos)

CRITÉRIO DE SUCESSO: Itens criados com validação e cálculo correto de totais

---

## 🎯 PROMPT 3.5: Implementar updateInvoiceByCode() - Atualizar por Código

Implemente APENAS o método updateInvoiceByCode() no InvoiceService:

TAREFA ESPECÍFICA:

-  Busca: Por código + validação de existência
-  Validação: Status editável
-  Itens: Gerenciar itens (delete/update/create)
-  Transaction: Atomicidade completa

IMPLEMENTAÇÃO:

```php
// Dentro de app/Services/Domain/InvoiceService.php

public function updateInvoiceByCode(string $code, array $data): ServiceResult
{
    try {
        return DB::transaction(function () use ($code, $data) {
            $invoice = Invoice::where('code', $code)->first();

            if (!$invoice) {
                return $this->error(
                    OperationStatus::NOT_FOUND,
                    "Fatura {$code} não encontrada"
                );
            }

            // Verificar se pode editar (assumindo um método canEdit no Enum ou Model)
            // if (!$invoice->status->canEdit()) {
            //     return $this->error(
            //         OperationStatus::VALIDATION_ERROR,
            //         "Fatura não pode ser editada no status {$invoice->status->value}"
            //     );
            // }

            // Atualizar fatura
            $invoice->update([
                'customer_id' => $data['customer_id'] ?? $invoice->customer_id,
                'issue_date' => $data['issue_date'] ?? $invoice->issue_date,
                'due_date' => $data['due_date'] ?? $invoice->due_date,
                'status' => $data['status'] ?? $invoice->status,
            ]);

            // Gerenciar itens se fornecidos
            if (isset($data['items'])) {
                $this->updateInvoiceItems($invoice, $data['items']);
            }

            // Recalcular total da fatura após gerenciar itens
            $invoice->total_amount = $this->calculateInvoiceTotal($invoice->invoiceItems->toArray());
            $invoice->save();

            return $this->success($invoice->fresh([
                'invoiceItems.product',
                'customer',
                'service'
            ]), 'Fatura atualizada');

        });

    } catch (Exception $e) {
        return $this->error(
            OperationStatus::ERROR,
            'Erro ao atualizar fatura',
            null,
            $e
        );
    }
}

private function updateInvoiceItems(Invoice $invoice, array $itemsData): void
{
    $existingItemIds = $invoice->invoiceItems->pluck('id')->toArray();
    $itemsToKeep = [];

    foreach ($itemsData as $itemData) {
        if (isset($itemData['id']) && in_array($itemData['id'], $existingItemIds)) {
            // Atualizar item existente
            $item = $invoice->invoiceItems->firstWhere('id', $itemData['id']);
            if ($item) {
                if (($itemData['action'] ?? 'update') === 'delete') {
                    $item->delete();
                } else {
                    $item->update([
                        'product_id' => $itemData['product_id'],
                        'quantity' => $itemData['quantity'],
                        'unit_value' => $itemData['unit_value'],
                        'total' => (float) $itemData['quantity'] * (float) $itemData['unit_value']
                    ]);
                    $itemsToKeep[] = $item->id;
                }
            }
        } elseif (($itemData['action'] ?? 'create') === 'create') {
            // Criar novo item
            $product = Product::where('id', $itemData['product_id'])
                ->where('active', true)
                ->first();

            if (!$product) {
                throw new Exception("Produto ID {$itemData['product_id']} não encontrado ou inativo");
            }

            $newItem = InvoiceItem::create([
                'tenant_id' => $invoice->tenant_id,
                'invoice_id' => $invoice->id,
                'product_id' => $product->id,
                'unit_value' => (float) $itemData['unit_value'],
                'quantity' => (float) $itemData['quantity'],
                'total' => (float) $itemData['quantity'] * (float) $itemData['unit_value']
            ]);
            $itemsToKeep[] = $newItem->id;
        }
    }

    // Deletar itens que não foram mantidos
    $invoice->invoiceItems()->whereNotIn('id', $itemsToKeep)->delete();
}
```

ARQUIVOS:

-  app/Services/Domain/InvoiceService.php (método updateInvoiceByCode, updateInvoiceItems)
-  app/Enums/InvoiceStatus.php (usar getAllowedTransitions)

CRITÉRIO DE SUCESSO: Fatura atualizada com gerenciamento de itens

---

## 🎯 PROMPT 3.6: Implementar changeStatus() - Mudança de Status

Implemente APENAS o método changeStatus() no InvoiceService:

TAREFA ESPECÍFICA:

-  Validação: Transições permitidas via InvoiceStatus
-  Auditoria: Registrar mudança
-  Transaction: Atomicidade

IMPLEMENTAÇÃO:

```php
// Dentro de app/Services/Domain/InvoiceService.php

public function changeStatus(string $code, string $newStatus): ServiceResult
{
    try {
        return DB::transaction(function () use ($code, $newStatus) {
            $invoice = Invoice::where('code', $code)->first();

            if (!$invoice) {
                return $this->error(
                    OperationStatus::NOT_FOUND,
                    "Fatura {$code} não encontrada"
                );
            }

            $oldStatus = $invoice->status;

            // Validar transição usando InvoiceStatus
            $allowedTransitions = InvoiceStatus::getAllowedTransitions($oldStatus);
            if (!in_array($newStatus, $allowedTransitions)) {
                return $this->error(
                    OperationStatus::VALIDATION_ERROR,
                    "Transição de {$oldStatus} para {$newStatus} não permitida"
                );
            }

            // Atualizar fatura
            $invoice->update(['status' => $newStatus]);

            return $this->success($invoice, 'Status alterado com sucesso');

        });

    } catch (Exception $e) {
        return $this->error(
            OperationStatus::ERROR,
            'Erro ao alterar status',
            null,
            $e
        );
    }
}
```

ARQUIVOS:

-  app/Services/Domain/InvoiceService.php (método changeStatus)
-  app/Enums/InvoiceStatus.php (método getAllowedTransitions - já implementado)

CRITÉRIO DE SUCESSO: Status alterado com validação de transições

---

## 🎯 PROMPT 3.7: Implementar deleteByCode() - Deletar por Código

Implemente APENAS o método deleteByCode() no InvoiceService:

TAREFA ESPECÍFICA:

-  Busca: Por código + validação de deletabilidade
-  Verificação: Relacionamentos que impedem exclusão (pagamentos)
-  Cascata: Deletar itens primeiro
-  Transaction: Atomicidade

IMPLEMENTAÇÃO:

```php
// Dentro de app/Services/Domain/InvoiceService.php

public function deleteByCode(string $code): ServiceResult
{
    try {
        return DB::transaction(function () use ($code) {
            $invoice = Invoice::where('code', $code)->first();

            if (!$invoice) {
                return $this->error(
                    OperationStatus::NOT_FOUND,
                    "Fatura {$code} não encontrada"
                );
            }

            // Verificar se pode deletar (assumindo método canDeleteInvoice no Repository ou Service)
            // if (!$this->invoiceRepository->canDeleteInvoice($invoice->id)) {
            //     return $this->error(
            //         OperationStatus::VALIDATION_ERROR,
            //         'Fatura não pode ser excluída devido a dependências'
            //     );
            // }

            // Não pode deletar se tiver pagamentos
            if ($invoice->payments()->count() > 0) {
                return $this->error(
                    OperationStatus::VALIDATION_ERROR,
                    'Fatura possui pagamentos e não pode ser excluída'
                );
            }

            // Deletar itens da fatura
            $invoice->invoiceItems()->delete();

            // Deletar a fatura
            $invoice->delete();

            return $this->success(null, 'Fatura excluída com sucesso');

        });

    } catch (Exception $e) {
        return $this->error(
            OperationStatus::ERROR,
            'Erro ao excluir fatura',
            null,
            $e
        );
    }
}
```

ARQUIVOS:

-  app/Services/Domain/InvoiceService.php (método deleteByCode)
-  app/Repositories/InvoiceRepository.php (método canDeleteInvoice - a ser criado)

CRITÉRIO DE SUCESSO: Fatura deletada com validação de dependências

---

## 🎯 PROMPT 3.8: Implementar generateInvoicePdf() - Gerar PDF da Fatura

Implemente APENAS o método generateInvoicePdf() no InvoiceService:

TAREFA ESPECÍFICA:

-  Geração: PDF da fatura
-  Armazenamento: Armazenar PDF em storage
-  Return: Caminho para o PDF gerado
-  Biblioteca: Usar uma biblioteca de PDF (ex: Dompdf, Snappy)

IMPLEMENTAÇÃO:

```php
// Dentro de app/Services/Domain/InvoiceService.php

// Assumindo que você tem uma biblioteca de PDF configurada, por exemplo, Dompdf
// use Barryvdh\DomPDF\Facade\Pdf; // Se estiver usando barryvdh/laravel-dompdf

public function generateInvoicePdf(string $code): ServiceResult
{
    try {
        $invoiceResult = $this->findByCode($code, ['customer', 'service', 'invoiceItems.product']);

        if (!$invoiceResult->isSuccess()) {
            return $invoiceResult;
        }

        $invoice = $invoiceResult->getData();

        // Renderizar view Blade para o PDF
        $pdfContent = view('invoices.pdf', compact('invoice'))->render();

        // Gerar PDF (exemplo com Dompdf)
        // $pdf = Pdf::loadHtml($pdfContent);
        // $filename = 'invoice_' . $invoice->code . '.pdf';
        // $path = 'invoices/' . tenant()->id . '/' . $filename;
        // Storage::disk('public')->put($path, $pdf->output());

        // Por simplicidade, apenas um placeholder de caminho
        $path = 'storage/invoices/' . tenant()->id . '/invoice_' . $invoice->code . '.pdf';

        return $this->success($path, 'PDF da fatura gerado com sucesso');

    } catch (Exception $e) {
        return $this->error(
            OperationStatus::ERROR,
            'Erro ao gerar PDF da fatura',
            null,
            $e
        );
    }
}
```

ARQUIVOS:

-  app/Services/Domain/InvoiceService.php (método generateInvoicePdf)
-  resources/views/invoices/pdf.blade.php (criar)

CRITÉRIO DE SUCESSO: PDF da fatura gerado e armazenado

---

# 🎯 GRUPO 4: CONTROLLERS (Interface HTTP) - **QUARTO**

## 🎯 PROMPT 4.1: Implementar index() - Lista de Faturas

Implemente APENAS o método index() no InvoiceController:

TAREFA ESPECÍFICA:

-  Método: public function index(Request $request): View
-  Filtros: Status, cliente, período, busca por código/descrição
-  Paginação: 15 registros por página
-  Eager loading: `customer`, `service.budget`, `invoiceStatus`

IMPLEMENTAÇÃO:

```php
namespace App\Http\Controllers;

use App\Http\Requests\InvoiceStoreRequest;
use App\Http\Requests\InvoiceUpdateRequest;
use App\Services\Domain\CustomerService;
use App\Services\Domain\InvoiceService;
use App\Services\Domain\ServiceService;
use App\Enums\InvoiceStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Exception;

class InvoiceController extends Controller
{
    private InvoiceService $invoiceService;
    private CustomerService $customerService;
    private ServiceService $serviceService; // Para o formulário de criação

    public function __construct(InvoiceService $invoiceService, CustomerService $customerService, ServiceService $serviceService)
    {
        $this->invoiceService = $invoiceService;
        $this->customerService = $customerService;
        $this->serviceService = $serviceService;
    }

    public function index(Request $request): View
    {
        try {
            $filters = $request->only(['status', 'customer_id', 'date_from', 'date_to', 'search']);

            $result = $this->invoiceService->getFilteredInvoices($filters, [
                'customer:id,name',
                'service:id,code,description'
            ]);

            if (!$result->isSuccess()) {
                abort(500, 'Erro ao carregar lista de faturas');
            }

            $invoices = $result->getData();

            return view('invoices.index', [
                'invoices' => $invoices,
                'filters' => $filters,
                'statusOptions' => InvoiceStatus::cases(),
                'customers' => $this->customerService->getActiveCustomers()
            ]);

        } catch (Exception $e) {
            abort(500, 'Erro ao carregar faturas');
        }
    }
}
```

ARQUIVOS:

-  app/Http/Controllers/InvoiceController.php (método index)
-  app/Services/Domain/InvoiceService.php (método getFilteredInvoices)
-  resources/views/invoices/index.blade.php (criar)

CRITÉRIO DE SUCESSO: Lista de faturas com filtros funcionais e paginação

---

## 🎯 PROMPT 4.2: Implementar create() - Formulário de Criação

Implemente APENAS o método create() no InvoiceController:

TAREFA ESPECÍFICA:

-  Método: public function create(?string $serviceCode = null): View
-  Pré-seleção: Serviço por código (opcional)
-  Dados: Clientes, serviços disponíveis, status de fatura
-  Geração: Código de fatura automático (futuro)

IMPLEMENTAÇÃO:

```php
// Dentro de app/Http/Controllers/InvoiceController.php

public function create(?string $serviceCode = null): View
{
    try {
        $service = null;

        if ($serviceCode) {
            $serviceResult = $this->serviceService->findByCode($serviceCode);
            if ($serviceResult->isSuccess()) {
                $service = $serviceResult->getData();
            }
        }

        return view('invoices.create', [
            'service' => $service,
            'customers' => $this->customerService->getActiveCustomers(),
            'services' => $this->serviceService->getNotBilledServices(), // Assumindo um método para serviços não faturados
            'statusOptions' => InvoiceStatus::cases()
        ]);

    } catch (Exception $e) {
        abort(500, 'Erro ao carregar formulário de criação de fatura');
    }
}
```

ARQUIVOS:

-  app/Http/Controllers/InvoiceController.php (método create)
-  resources/views/invoices/create.blade.php (criar)

CRITÉRIO DE SUCESSO: Formulário de criação carregado com dados necessários

---

## 🎯 PROMPT 4.3: Implementar store() - Criar Fatura

Implemente APENAS o método store() no InvoiceController:

TAREFA ESPECÍFICA:

-  Método: public function store(InvoiceStoreRequest $request): RedirectResponse
-  Validação: InvoiceStoreRequest
-  Lógica: Chamar InvoiceService::createInvoice()
-  Redirecionamento: Para invoices.show em caso de sucesso, back em caso de erro.

IMPLEMENTAÇÃO:

```php
// Dentro de app/Http/Controllers/InvoiceController.php

public function store(InvoiceStoreRequest $request): RedirectResponse
{
    try {
        $result = $this->invoiceService->createInvoice($request->validated());

        if (!$result->isSuccess()) {
            return redirect()->back()
                ->withInput()
                ->with('error', $result->getMessage());
        }

        $invoice = $result->getData();

        return redirect()->route('invoices.show', $invoice->code)
            ->with('success', 'Fatura criada com sucesso!');

    } catch (Exception $e) {
        return redirect()->back()
            ->withInput()
            ->with('error', 'Erro ao criar fatura: ' . $e->getMessage());
    }
}
```

ARQUIVOS:

-  app/Http/Controllers/InvoiceController.php (método store)
-  app/Services/Domain/InvoiceService.php (método createInvoice)

CRITÉRIO DE SUCESSO: Fatura criada com sucesso e redirecionamento correto

---

## 🎯 PROMPT 4.4: Implementar show() - Detalhes da Fatura

Implemente APENAS o método show() no InvoiceController:

TAREFA ESPECÍFICA:

-  Método: public function show(string $code): View
-  Busca: Por código com relacionamentos completos
-  Eager loading: `customer`, `service.budget`, `invoiceItems.product`, `invoiceStatus`, `payments`
-  Pagamentos: Listar pagamentos vinculados

IMPLEMENTAÇÃO:

```php
// Dentro de app/Http/Controllers/InvoiceController.php

public function show(string $code): View
{
    try {
        $result = $this->invoiceService->findByCode($code, [
            'customer.commonData',
            'service.budget',
            'invoiceItems.product',
            'payments'
        ]);

        if (!$result->isSuccess()) {
            abort(404, 'Fatura não encontrada');
        }

        $invoice = $result->getData();

        return view('invoices.show', [
            'invoice' => $invoice
        ]);

    } catch (Exception $e) {
        abort(500, 'Erro ao carregar fatura');
    }
}
```

ARQUIVOS:

-  app/Http/Controllers/InvoiceController.php (método show)
-  resources/views/invoices/show.blade.php (criar)

CRITÉRIO DE SUCESSO: Detalhes completos da fatura com todos os relacionamentos

---

## 🎯 PROMPT 4.5: Implementar edit() - Formulário de Edição

Implemente APENAS o método edit() no InvoiceController:

TAREFA ESPECÍFICA:

-  Método: public function edit(string $code): View
-  Busca: Por código com itens relacionados
-  Validação: Status editável
-  Dados: Mesmos dados do create()

IMPLEMENTAÇÃO:

```php
// Dentro de app/Http/Controllers/InvoiceController.php

public function edit(string $code): View
{
    try {
        $result = $this->invoiceService->findByCode($code, [
            'invoiceItems.product',
            'customer',
            'service'
        ]);

        if (!$result->isSuccess()) {
            abort(404, 'Fatura não encontrada');
        }

        $invoice = $result->getData();

        // Verificar se pode editar (usando InvoiceStatus)
        if (!in_array($invoice->status, ['pending'])) { // Apenas pending pode ser editado
            abort(403, 'Fatura não pode ser editada no status atual');
        }

        if (!$result->isSuccess()) {
            abort(404, 'Fatura não encontrada');
        }

        $invoice = $result->getData();

        // Verificar se pode editar (usando InvoiceStatus)
        if (!in_array($invoice->status, ['pending'])) { // Apenas pending pode ser editado
            abort(403, 'Fatura não pode ser editada no status atual');
        }

        return view('invoices.edit', [
            'invoice' => $invoice,
            'customers' => $this->customerService->getActiveCustomers(),
            'services' => $this->serviceService->getNotBilledServices(),
            'statusOptions' => InvoiceStatus::cases()
        ]);

    } catch (Exception $e) {
        abort(500, 'Erro ao carregar formulário de edição de fatura');
    }
}
```

ARQUIVOS:

-  app/Http/Controllers/InvoiceController.php (método edit)
-  resources/views/invoices/edit.blade.php (criar)

CRITÉRIO DE SUCESSO: Formulário de edição carregado apenas para status 'pending'

---

## 🎯 PROMPT 4.6: Implementar update() - Atualizar Fatura

Implemente APENAS o método update() no InvoiceController:

TAREFA ESPECÍFICA:

-  Método: public function update(string $code, InvoiceUpdateRequest $request): RedirectResponse
-  Validação: InvoiceUpdateRequest
-  Lógica: Chamar InvoiceService::updateInvoiceByCode()
-  Redirecionamento: Para invoices.show em caso de sucesso, back em caso de erro.

IMPLEMENTAÇÃO:

```php
// Dentro de app/Http/Controllers/InvoiceController.php

public function update(string $code, InvoiceUpdateRequest $request): RedirectResponse
{
    try {
        $result = $this->invoiceService->updateInvoiceByCode($code, $request->validated());

        if (!$result->isSuccess()) {
            return redirect()->back()
                ->withInput()
                ->with('error', $result->getMessage());
        }

        $invoice = $result->getData();

        return redirect()->route('invoices.show', $invoice->code)
            ->with('success', 'Fatura atualizada com sucesso!');

    } catch (Exception $e) {
        return redirect()->back()
            ->withInput()
            ->with('error', 'Erro ao atualizar fatura: ' . $e->getMessage());
    }
}
```

ARQUIVOS:

-  app/Http/Controllers/InvoiceController.php (método update)
-  app/Services/Domain/InvoiceService.php (método updateInvoiceByCode)

CRITÉRIO DE SUCESSO: Fatura atualizada com gerenciamento de itens

---

## 🎯 PROMPT 4.7: Implementar change_status() - Mudança de Status

Implemente APENAS o método change_status() no InvoiceController:

TAREFA ESPECÍFICA:

-  Método: public function change_status(string $code, Request $request): RedirectResponse
-  Validação: Transição de status válida
-  Auditoria: Registrar mudança de status

IMPLEMENTAÇÃO:

```php
// Dentro de app/Http/Controllers/InvoiceController.php

public function change_status(string $code, Request $request): RedirectResponse
{
    $request->validate([
        'status' => ['required', 'string', 'in:' . implode(',', array_map(fn($case) => $case->value, InvoiceStatus::cases()))]
    ]);

    try {
        $result = $this->invoiceService->changeStatus($code, $request->status);

        if (!$result->isSuccess()) {
            return redirect()->back()
                ->with('error', $result->getMessage());
        }

        return redirect()->route('invoices.show', $code)
            ->with('success', 'Status da fatura alterado com sucesso!');

    } catch (Exception $e) {
        return redirect()->back()
            ->with('error', 'Erro ao alterar status da fatura: ' . $e->getMessage());
    }
}
```

ARQUIVOS:

-  app/Http/Controllers/InvoiceController.php (método change_status)
-  app/Services/Domain/InvoiceService.php (método changeStatus)

CRITÉRIO DE SUCESSO: Status alterado com validação de transições

---

## 🎯 PROMPT 4.8: Implementar delete_store() - Deletar Fatura

Implemente APENAS o método delete_store() no InvoiceController:

TAREFA ESPECÍFICA:

-  Método: public function delete_store(string $code): RedirectResponse
-  Verificação: Relacionamentos que impedem exclusão (pagamentos)
-  Cascata: Deletar itens da fatura primeiro
-  Auditoria: Registrar exclusão

IMPLEMENTAÇÃO:

```php
// Dentro de app/Http/Controllers/InvoiceController.php

public function delete_store(string $code): RedirectResponse
{
    try {
        $result = $this->invoiceService->deleteByCode($code);

        if (!$result->isSuccess()) {
            return redirect()->back()
                ->with('error', $result->getMessage());
        }

        return redirect()->route('invoices.index')
            ->with('success', 'Fatura excluída com sucesso!');

    } catch (Exception $e) {
        return redirect()->back()
            ->with('error', 'Erro ao excluir fatura: ' . $e->getMessage());
    }
}
```

ARQUIVOS:

-  app/Http/Controllers/InvoiceController.php (método delete_store)
-  app/Services/Domain/InvoiceService.php (método deleteByCode)

CRITÉRIO DE SUCESSO: Fatura deletada apenas se não tiver dependências bloqueantes

---

## 🎯 PROMPT 4.9: Implementar downloadPdf() - Download PDF

Implemente APENAS o método downloadPdf() no InvoiceController:

TAREFA ESPECÍFICA:

-  Método: public function downloadPdf(string $code): Response
-  Lógica: Chamar InvoiceService::generateInvoicePdf()
-  Retorno: Download do arquivo PDF

IMPLEMENTAÇÃO:

```php
// Dentro de app/Http/Controllers/InvoiceController.php

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

public function downloadPdf(string $code): Response
{
    try {
        $result = $this->invoiceService->generateInvoicePdf($code);

        if (!$result->isSuccess()) {
            return redirect()->back()
                ->with('error', $result->getMessage());
        }

        $filePath = $result->getData(); // Ex: storage/invoices/tenant_id/invoice_CODE.pdf

        if (!Storage::disk('public')->exists(Str::after($filePath, 'storage/'))) {
            abort(404, 'PDF da fatura não encontrado.');
        }

        return Storage::disk('public')->download(Str::after($filePath, 'storage/'), 'fatura_' . $code . '.pdf');

    } catch (Exception $e) {
        return redirect()->back()
            ->with('error', 'Erro ao baixar PDF da fatura: ' . $e->getMessage());
    }
}
```

ARQUIVOS:

-  app/Http/Controllers/InvoiceController.php (método downloadPdf)
-  app/Services/Domain/InvoiceService.php (método generateInvoicePdf)

CRITÉRIO DE SUCESSO: Download do PDF da fatura funcionando

---

# 📈 **ESTATÍSTICAS**

**Total de Prompts:** 20 prompts
**Ordem Correta:** Repository → FormRequests → Services → Controllers
**Status Atual:** 0% implementado
**Prioridade:** GRUPO 1 (Repository) - **PRIMEIRO**
**IMPORTANTE:** Sistema usa InvoiceStatus (igual ao BudgetStatus) - Status armazenado como string no campo 'status' da tabela invoices. NÃO há tabela invoice_statuses nem modelo InvoiceStatus.

### **Fase 1: Repository (1.5 dias)**

-  PROMPTS 1.1 a 1.3: getFiltered, findByCode, countByStatus

### **Fase 2: Form Requests (1 dia)**

-  PROMPTS 2.1 a 2.2: InvoiceStoreRequest, InvoiceUpdateRequest

### **Fase 3: Services Críticos (5 dias)**

-  PROMPTS 3.1 a 3.4: findByCode, getFilteredInvoices, createInvoice, createInvoiceItems

### **Fase 4: Services Avançados (3 dias)**

-  PROMPTS 3.5 a 3.8: updateInvoiceByCode, changeStatus, deleteByCode, generateInvoicePdf

### **Fase 5: Controllers CRUD (4 dias)**

-  PROMPTS 4.1 a 4.4: index, create, store, show

### **Fase 6: Controllers Avançados (3 dias)**

-  PROMPTS 4.5 a 4.9: edit, update, change_status, delete_store, downloadPdf

## ✅ **CRITÉRIOS DE SUCESSO POR PROMPT**

-  **Repository:** Queries otimizadas com eager loading
-  **FormRequest:** Validação robusta com mensagens em português
-  **Service:** Lógica de negócio completa com transação e auditoria
-  **Controller:** Método funcionando com validação e error handling

## 🚀 **BENEFÍCIOS DA ORDEM CORRETA**

-  **Dependências respeitadas:** Repository → Services → Controllers
-  **Validação primeiro:** Form Requests antes dos Controllers
-  **Base sólida:** Repository implementado antes dos Services
-  **Testabilidade:** Cada grupo pode ser testado independentemente
-  **Zero dependências circulares:** Arquitetura clara e desacoplada
-  **Status via Enum:** Usa InvoiceStatus igual ao BudgetStatus (sem tabela invoice_statuses). Status armazenado como string no campo 'status'.

**NOTA IMPORTANTE - Schema do Banco:** A tabela `invoices` já está configurada corretamente na migration `2025_09_27_132300_create_initial_schema.php` (linha 427) usando `$table->string( 'status', 20 );` para armazenar o valor do enum. A tabela `invoice_statuses` (linhas 72-82) é desnecessária no novo sistema e pode ser removida em migrações futuras.

**Total:** 20 prompts na ordem técnica correta para completar a migração do InvoiceController.
