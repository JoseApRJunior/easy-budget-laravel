# Relatório de Análise: InvoicesController

## 📋 Informações Gerais

**Controller:** `InvoicesController`  
**Namespace Old System:** `app\controllers`  
**Tipo:** Controller de Criação de Faturas  
**Propósito:** Criar faturas completas ou parciais a partir de orçamentos

---

## 🎯 Funcionalidades Identificadas

### 1. **create()**
- **Descrição:** Cria fatura completa ou parcial
- **Método HTTP:** POST
- **Status:** **INCOMPLETO** (TODO no código)
- **Parâmetros:** Dados via POST request
- **Retorno:** View de criação de orçamento (provavelmente erro no código)
- **Observações:**
  - Código contém `var_dump($data)` - debug não removido
  - Retorna view errada (`budget/create.twig` ao invés de invoice)
  - Lógica não implementada
- **Dependências:**
  - `Twig` template engine
  - `ActivityService`

---

## 🔗 Dependências do Sistema Antigo

### Services Utilizados
- `ActivityService` - Logging de atividades

### Funcionalidade Esperada (Baseada no TODO)
- Criar fatura completa (100% do orçamento)
- Criar fatura parcial (percentual ou valor específico)
- Vincular fatura ao orçamento
- Gerar número de fatura
- Calcular valores

---

## 🏗️ Implementação no Novo Sistema Laravel

### Estrutura Proposta

```
app/Http/Controllers/
└── InvoiceController.php (já existe - verificar se tem esta funcionalidade)

app/Services/Domain/
└── InvoiceService.php (já existe)

app/Http/Requests/
├── CreateInvoiceRequest.php
└── CreatePartialInvoiceRequest.php

resources/views/
└── invoices/
    ├── create.blade.php
    └── create-from-budget.blade.php
```

### Rotas Sugeridas

```php
// routes/web.php
Route::middleware(['auth', 'tenant'])->prefix('invoices')->group(function () {
    // Criar fatura do zero
    Route::get('/create', [InvoiceController::class, 'create'])->name('invoices.create');
    Route::post('/', [InvoiceController::class, 'store'])->name('invoices.store');
    
    // Criar fatura a partir de orçamento
    Route::get('/create-from-budget/{budget}', [InvoiceController::class, 'createFromBudget'])
        ->name('invoices.create-from-budget');
    Route::post('/from-budget/{budget}', [InvoiceController::class, 'storeFromBudget'])
        ->name('invoices.store-from-budget');
    
    // Criar fatura parcial
    Route::post('/partial/{budget}', [InvoiceController::class, 'storePartial'])
        ->name('invoices.store-partial');
});
```

---

## 📝 Padrão de Implementação

### Controller Pattern: Form Controller

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\CreateInvoiceRequest;
use App\Http\Requests\CreatePartialInvoiceRequest;
use App\Models\Budget;
use App\Services\Domain\InvoiceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function __construct(
        private InvoiceService $invoiceService
    ) {}

    /**
     * Criar fatura a partir de orçamento (completa)
     */
    public function createFromBudget(Budget $budget): View
    {
        $this->authorize('create', Invoice::class);
        
        return view('invoices.create-from-budget', [
            'budget' => $budget->load(['customer', 'items']),
            'invoiceTypes' => ['complete' => 'Fatura Completa', 'partial' => 'Fatura Parcial'],
        ]);
    }

    /**
     * Salvar fatura completa a partir de orçamento
     */
    public function storeFromBudget(CreateInvoiceRequest $request, Budget $budget): RedirectResponse
    {
        $this->authorize('create', Invoice::class);

        $result = $this->invoiceService->createFromBudget(
            budget: $budget,
            data: $request->validated()
        );

        if ($result->isSuccess()) {
            return redirect()
                ->route('invoices.show', $result->data)
                ->with('success', 'Fatura criada com sucesso!');
        }

        return back()
            ->withInput()
            ->with('error', $result->message);
    }

    /**
     * Salvar fatura parcial a partir de orçamento
     */
    public function storePartial(CreatePartialInvoiceRequest $request, Budget $budget): RedirectResponse
    {
        $this->authorize('create', Invoice::class);

        $result = $this->invoiceService->createPartialInvoice(
            budget: $budget,
            amount: $request->input('amount'),
            percentage: $request->input('percentage'),
            description: $request->input('description'),
            dueDate: $request->input('due_date')
        );

        if ($result->isSuccess()) {
            return redirect()
                ->route('invoices.show', $result->data)
                ->with('success', 'Fatura parcial criada com sucesso!');
        }

        return back()
            ->withInput()
            ->with('error', $result->message);
    }
}
```

### Service Implementation

```php
<?php

declare(strict_types=1);

namespace App\Services\Domain;

use App\Models\Budget;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Support\ServiceResult;
use App\Enums\OperationStatus;
use Illuminate\Support\Facades\DB;

class InvoiceService extends AbstractBaseService
{
    /**
     * Criar fatura completa a partir de orçamento
     */
    public function createFromBudget(Budget $budget, array $data): ServiceResult
    {
        try {
            return DB::transaction(function () use ($budget, $data) {
                // Validar se orçamento pode gerar fatura
                if (!$budget->budget_status->canGenerateInvoice()) {
                    return ServiceResult::error(
                        OperationStatus::VALIDATION_ERROR,
                        'Orçamento não está em status válido para gerar fatura'
                    );
                }

                // Verificar se já existe fatura para este orçamento
                if ($budget->invoices()->exists()) {
                    return ServiceResult::error(
                        OperationStatus::VALIDATION_ERROR,
                        'Já existe fatura para este orçamento'
                    );
                }

                // Criar fatura
                $invoice = Invoice::create([
                    'tenant_id' => $budget->tenant_id,
                    'budget_id' => $budget->id,
                    'customer_id' => $budget->customer_id,
                    'invoice_number' => $this->generateInvoiceNumber(),
                    'issue_date' => now(),
                    'due_date' => $data['due_date'] ?? now()->addDays(30),
                    'subtotal' => $budget->total - $budget->discount,
                    'discount' => $budget->discount,
                    'total' => $budget->total,
                    'status' => 'pending',
                    'notes' => $data['notes'] ?? null,
                ]);

                // Copiar itens do orçamento
                foreach ($budget->items as $budgetItem) {
                    InvoiceItem::create([
                        'invoice_id' => $invoice->id,
                        'product_id' => $budgetItem->product_id,
                        'description' => $budgetItem->description,
                        'quantity' => $budgetItem->quantity,
                        'unit_price' => $budgetItem->unit_price,
                        'total' => $budgetItem->total,
                    ]);
                }

                // Atualizar status do orçamento
                $budget->update(['budget_status' => 'invoiced']);

                // Log atividade
                activity()
                    ->performedOn($invoice)
                    ->causedBy(auth()->user())
                    ->log('Fatura criada a partir do orçamento #' . $budget->code);

                return ServiceResult::success($invoice, 'Fatura criada com sucesso');
            });

        } catch (\Exception $e) {
            return ServiceResult::error(
                OperationStatus::ERROR,
                'Erro ao criar fatura: ' . $e->getMessage()
            );
        }
    }

    /**
     * Criar fatura parcial
     */
    public function createPartialInvoice(
        Budget $budget,
        ?float $amount = null,
        ?float $percentage = null,
        ?string $description = null,
        ?string $dueDate = null
    ): ServiceResult {
        try {
            return DB::transaction(function () use ($budget, $amount, $percentage, $description, $dueDate) {
                // Calcular valor da fatura parcial
                if ($percentage) {
                    $invoiceAmount = $budget->total * ($percentage / 100);
                } elseif ($amount) {
                    $invoiceAmount = $amount;
                } else {
                    return ServiceResult::error(
                        OperationStatus::VALIDATION_ERROR,
                        'Informe o valor ou percentual da fatura parcial'
                    );
                }

                // Validar valor
                $totalInvoiced = $budget->invoices()->sum('total');
                $remainingAmount = $budget->total - $totalInvoiced;

                if ($invoiceAmount > $remainingAmount) {
                    return ServiceResult::error(
                        OperationStatus::VALIDATION_ERROR,
                        "Valor excede o saldo restante de R$ " . number_format($remainingAmount, 2, ',', '.')
                    );
                }

                // Criar fatura parcial
                $invoice = Invoice::create([
                    'tenant_id' => $budget->tenant_id,
                    'budget_id' => $budget->id,
                    'customer_id' => $budget->customer_id,
                    'invoice_number' => $this->generateInvoiceNumber(),
                    'issue_date' => now(),
                    'due_date' => $dueDate ?? now()->addDays(30),
                    'subtotal' => $invoiceAmount,
                    'discount' => 0,
                    'total' => $invoiceAmount,
                    'status' => 'pending',
                    'is_partial' => true,
                    'notes' => $description ?? "Fatura parcial ({$percentage}%)",
                ]);

                // Log atividade
                activity()
                    ->performedOn($invoice)
                    ->causedBy(auth()->user())
                    ->log("Fatura parcial criada (R$ {$invoiceAmount})");

                return ServiceResult::success($invoice, 'Fatura parcial criada com sucesso');
            });

        } catch (\Exception $e) {
            return ServiceResult::error(
                OperationStatus::ERROR,
                'Erro ao criar fatura parcial: ' . $e->getMessage()
            );
        }
    }

    /**
     * Gerar número único de fatura
     */
    private function generateInvoiceNumber(): string
    {
        $tenantId = auth()->user()->tenant_id;
        $year = date('Y');
        $month = date('m');
        
        $lastInvoice = Invoice::where('tenant_id', $tenantId)
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->orderBy('id', 'desc')
            ->first();

        $sequence = $lastInvoice ? ((int) substr($lastInvoice->invoice_number, -4)) + 1 : 1;

        return sprintf('INV-%s%s-%04d', $year, $month, $sequence);
    }
}
```

### Request Validation

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateInvoiceRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'due_date' => 'required|date|after:today',
            'notes' => 'nullable|string|max:1000',
            'payment_method' => 'nullable|string|in:credit_card,debit_card,pix,boleto,cash',
        ];
    }
}

class CreatePartialInvoiceRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'amount' => 'required_without:percentage|numeric|min:0.01',
            'percentage' => 'required_without:amount|numeric|min:1|max:100',
            'description' => 'nullable|string|max:500',
            'due_date' => 'required|date|after:today',
        ];
    }

    public function messages(): array
    {
        return [
            'amount.required_without' => 'Informe o valor ou percentual',
            'percentage.required_without' => 'Informe o percentual ou valor',
        ];
    }
}
```

---

## ✅ Checklist de Implementação

### Fase 1: Service
- [ ] Adicionar método `createFromBudget()` em `InvoiceService`
- [ ] Adicionar método `createPartialInvoice()` em `InvoiceService`
- [ ] Implementar `generateInvoiceNumber()`
- [ ] Validar regras de negócio

### Fase 2: Controller
- [ ] Adicionar métodos em `InvoiceController`
- [ ] Criar `CreateInvoiceRequest`
- [ ] Criar `CreatePartialInvoiceRequest`

### Fase 3: Views
- [ ] Criar `create-from-budget.blade.php`
- [ ] Adicionar formulário de fatura completa
- [ ] Adicionar formulário de fatura parcial
- [ ] JavaScript para cálculos dinâmicos

### Fase 4: Database
- [ ] Adicionar campo `is_partial` em `invoices`
- [ ] Adicionar campo `budget_id` em `invoices`
- [ ] Criar índices necessários

### Fase 5: Testes
- [ ] Testes unitários para `InvoiceService`
- [ ] Testes de feature para criação de faturas
- [ ] Testes de validação

---

## 🔒 Considerações de Segurança

1. **Autorização:** Verificar permissões antes de criar fatura
2. **Validação:** Validar valores e percentuais
3. **Tenant Isolation:** Garantir isolamento por tenant
4. **Duplicação:** Prevenir criação de faturas duplicadas
5. **Integridade:** Validar saldo restante em faturas parciais

---

## 📊 Prioridade de Implementação

**Prioridade:** ALTA  
**Complexidade:** MÉDIA  
**Dependências:** InvoiceService, Budget model

**Ordem Sugerida:**
1. Implementar métodos em InvoiceService
2. Adicionar rotas e controller
3. Criar views
4. Testes

---

## 💡 Melhorias Sugeridas

1. **Múltiplas Parcelas:** Gerar múltiplas faturas parciais de uma vez
2. **Agendamento:** Agendar geração de faturas
3. **Recorrência:** Faturas recorrentes automáticas
4. **Notificações:** Notificar cliente quando fatura é gerada
5. **PDF Automático:** Gerar PDF automaticamente
6. **Email:** Enviar fatura por email
7. **Histórico:** Mostrar histórico de faturas do orçamento
8. **Cancelamento:** Permitir cancelar fatura e restaurar orçamento
