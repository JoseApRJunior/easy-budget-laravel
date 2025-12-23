# 🎯 Prompts Detalhados - Migração Budget Controller (Tarefas Menores)

## 📋 CONTEXTO

**Base:** Análise completa em `ANALISE_MIGRACAO_BUDGET_CONTROLLER.md`
**Status:** 25% implementado (3/12 métodos)
**Objetivo:** Quebrar em tarefas menores e específicas

---

# ✅ GRUPO 1: CONTROLLERS CRÍTICOS (6 Prompts) - **CONCLUÍDO**

## ✅ PROMPT 1.1: ~~Implementar store() - Criar Orçamento~~ **CONCLUÍDO**

**STATUS**: ✅ **IMPLEMENTADO** - Método store() funcionando

**FUNCIONALIDADES IMPLEMENTADAS**:

-  ✅ Método `store(BudgetStoreRequest $request): RedirectResponse`
-  ✅ Validação via `BudgetStoreRequest`
-  ✅ Código único padrão 'ORC-YYYYMMDD0001'
-  ✅ Transaction DB para atomicidade
-  ✅ Auditoria automática via Observer
-  ✅ Redirect para `provider.budgets.show`

**ARQUIVOS ATUALIZADOS**:

-  ✅ `app/Http/Controllers/BudgetController.php`
-  ✅ `app/Services/Domain/BudgetService.php`
-  ✅ `app/Http/Requests/BudgetStoreRequest.php`

**DATA IMPLEMENTAÇÃO**: 2025-11-06
**DESENVOLVEDOR**: Sistema implementado e testado

---

## ✅ PROMPT 1.2: ~~Implementar show() - Visualizar Orçamento~~ **CONCLUÍDO**

**STATUS**: ✅ **IMPLEMENTADO** - Método show() funcionando

**FUNCIONALIDADES IMPLEMENTADAS**:

-  ✅ Método `show(string $code): View`
-  ✅ Busca por código (não ID)
-  ✅ Eager loading: `customer.commonData`, `customer.contact`
-  ✅ Tenant scoping automático
-  ✅ View `pages.budget.show` criada
-  ✅ Layout responsivo com Bootstrap 5.3

**ARQUIVOS ATUALIZADOS**:

-  ✅ `app/Http/Controllers/BudgetController.php`
-  ✅ `app/Services/Domain/BudgetService.php`
-  ✅ `resources/views/pages/budget/show.blade.php`

**DATA IMPLEMENTAÇÃO**: 2025-11-06
**DESENVOLVEDOR**: Sistema implementado com estrutura correta de relacionamentos

---

## ✅ PROMPT 1.3: ~~Implementar edit() - Formulário de Edição~~ **CONCLUÍDO**

**STATUS**: ✅ **IMPLEMENTADO** - Método edit() funcionando

**FUNCIONALIDADES IMPLEMENTADAS**:

-  ✅ Método `edit(string $code): View`
-  ✅ Busca por código com relacionamentos
-  ✅ Validação de status editável
-  ✅ View `budgets.edit` preparada
-  ✅ Lista de clientes ativos

**ARQUIVOS ATUALIZADOS**:

-  ✅ `app/Http/Controllers/BudgetController.php`
-  ✅ `app/Enums/BudgetStatus.php` (métodos canEdit)

**DATA IMPLEMENTAÇÃO**: 2025-11-06
**DESENVOLVEDOR**: Formulário de edição com validação de status

---

## 🎯 PROMPT 1.3: Implementar update() - Formulário de Edição

Implemente APENAS o método update() no BudgetController:

TAREFA ESPECÍFICA:

-  Método: public function update(string $code): View
-  Busca: Orçamento por código com relacionamentos
-  Validação: Apenas orçamentos editáveis (status draft/pending)
-  View: budgets.edit
-  Dados: Orçamento + clientes ativos

IMPLEMENTAÇÃO:

```php
public function update(string $code): View
{
    try {
        $result = $this->budgetService->findByCode($code, [
            'customer:id,name',
            'items:id,budget_id,description,quantity,unit_price,total_price'
        ]);

        if (!$result->isSuccess()) {
            abort(404, 'Orçamento não encontrado');
        }

        $budget = $result->getData();

        // Verificar se pode editar
        if (!$budget->status->canEdit()) {
            abort(403, 'Orçamento não pode ser editado no status atual');
        }

        $customers = $this->customerService->getActiveCustomers();

        return view('budgets.edit', [
            'budget' => $budget,
            'customers' => $customers->getData()
        ]);

    } catch (Exception $e) {
        abort(500, 'Erro ao carregar formulário de edição');
    }
}
```

ARQUIVOS:

-  app/Http/Controllers/BudgetController.php (método update)
-  app/Enums/BudgetStatus.php (método canEdit se não existir)

CRITÉRIO DE SUCESSO: Formulário de edição carregado apenas para status editáveis

---

## 🎯 PROMPT 1.4: Implementar update_store() - Salvar Edições

Implemente APENAS o método update_store() no BudgetController:

TAREFA ESPECÍFICA:

-  Método: public function update_store(string $code, BudgetUpdateRequest $request): RedirectResponse
-  Validação: Status editável + dados válidos
-  Transaction: DB::transaction para atomicidade
-  Auditoria: Automática via BudgetObserver (old_values/new_values)

IMPLEMENTAÇÃO:

```php
public function update_store(string $code, BudgetUpdateRequest $request): RedirectResponse
{
    try {
        $result = $this->budgetService->updateByCode($code, $request->validated());

        if (!$result->isSuccess()) {
            return redirect()->back()
                ->withInput()
                ->with('error', $result->getMessage());
        }

        return redirect()->route('budgets.show', $code)
            ->with('success', 'Orçamento atualizado com sucesso!');

    } catch (Exception $e) {
        return redirect()->back()
            ->withInput()
            ->with('error', 'Erro ao atualizar orçamento: ' . $e->getMessage());
    }
}
```

ARQUIVOS:

-  app/Http/Controllers/BudgetController.php (método update_store)
-  app/Http/Requests/BudgetUpdateRequest.php (criar se não existir)
-  app/Services/Domain/BudgetService.php (método updateByCode)

CRITÉRIO DE SUCESSO: Orçamento atualizado com auditoria automática via Observer

---

## 🎯 PROMPT 1.5: Implementar change_status() - Mudança de Status

Implemente APENAS o método change_status() no BudgetController:

TAREFA ESPECÍFICA:

-  Método: public function change_status(string $code, Request $request): RedirectResponse
-  Validação: Transição de status válida
-  Cascata: Atualizar serviços relacionados
-  Transaction: DB::transaction para atomicidade

IMPLEMENTAÇÃO:

```php
public function change_status(string $code, Request $request): RedirectResponse
{
    $request->validate([
        'status' => ['required', 'string', Rule::in(BudgetStatus::values())]
    ]);

    try {
        $result = $this->budgetService->changeStatus($code, $request->status);

        if (!$result->isSuccess()) {
            return redirect()->back()
                ->with('error', $result->getMessage());
        }

        return redirect()->route('budgets.show', $code)
            ->with('success', 'Status alterado com sucesso!');

    } catch (Exception $e) {
        return redirect()->back()
            ->with('error', 'Erro ao alterar status: ' . $e->getMessage());
    }
}
```

ARQUIVOS:

-  app/Http/Controllers/BudgetController.php (método change_status)
-  app/Services/Domain/BudgetService.php (método changeStatus)

CRITÉRIO DE SUCESSO: Status alterado com cascata para serviços relacionados

---

## 🎯 PROMPT 1.6: Implementar delete_store() - Soft Delete

Implemente APENAS o método delete_store() no BudgetController:

TAREFA ESPECÍFICA:

-  Método: public function delete_store(string $code): RedirectResponse
-  Validação: Apenas orçamentos deletáveis (draft/cancelled)
-  Soft Delete: Usar SoftDeletes trait
-  Verificação: Relacionamentos que impedem exclusão

IMPLEMENTAÇÃO:

```php
public function delete_store(string $code): RedirectResponse
{
    try {
        $result = $this->budgetService->deleteByCode($code);

        if (!$result->isSuccess()) {
            return redirect()->back()
                ->with('error', $result->getMessage());
        }

        return redirect()->route('budgets.index')
            ->with('success', 'Orçamento excluído com sucesso!');

    } catch (Exception $e) {
        return redirect()->back()
            ->with('error', 'Erro ao excluir orçamento: ' . $e->getMessage());
    }
}
```

ARQUIVOS:

-  app/Http/Controllers/BudgetController.php (método delete_store)
-  app/Services/Domain/BudgetService.php (método deleteByCode)
-  app/Models/Budget.php (verificar SoftDeletes trait)

CRITÉRIO DE SUCESSO: Orçamento excluído apenas se status permitir

---

# ✅ GRUPO 2: SERVICES DE NEGÓCIO (5 Prompts) - **CONCLUÍDO**

## 🎯 PROMPT 2.1: Implementar generateUniqueCode() - Geração de Código

Implemente APENAS o método generateUniqueCode() no BudgetService:

TAREFA ESPECÍFICA:

-  Padrão: 'ORC-' + YYYYMMDD + sequencial (4 dígitos)
-  Lock: DB::transaction com FOR UPDATE para evitar duplicatas
-  Busca: Último código do dia atual
-  Incremento: +1 no sequencial

IMPLEMENTAÇÃO:

```php
private function generateUniqueCode(): string
{
    return DB::transaction(function () {
        $today = date('Ymd');
        $prefix = "ORC-{$today}";

        // Buscar último código do dia com lock
        $lastBudget = Budget::where('code', 'LIKE', "{$prefix}%")
            ->lockForUpdate()
            ->orderBy('code', 'desc')
            ->first();

        if (!$lastBudget) {
            return "{$prefix}0001";
        }

        // Extrair sequencial e incrementar
        $lastSequential = (int) substr($lastBudget->code, -4);
        $newSequential = str_pad($lastSequential + 1, 4, '0', STR_PAD_LEFT);

        return "{$prefix}{$newSequential}";
    });
}
```

ARQUIVOS:

-  app/Services/Domain/BudgetService.php (método generateUniqueCode)

CRITÉRIO DE SUCESSO: Códigos únicos gerados sem duplicatas mesmo com concorrência

---

## 🎯 PROMPT 2.2: Implementar handleStatusChange() - Mudança de Status

Implemente APENAS o método handleStatusChange() no BudgetService:

TAREFA ESPECÍFICA:

-  Validação: Transições permitidas via BudgetStatus enum
-  Cascata: Atualizar serviços relacionados
-  Regras: approved → services "in_progress", rejected → services "cancelled"
-  Transaction: Atomicidade completa

IMPLEMENTAÇÃO:

```php
public function handleStatusChange(Budget $budget, string $newStatus): ServiceResult
{
    try {
        return DB::transaction(function () use ($budget, $newStatus) {
            $oldStatus = $budget->status;

            // Validar transição
            if (!$oldStatus->canTransitionTo(BudgetStatus::from($newStatus))) {
                return $this->error(
                    OperationStatus::VALIDATION_ERROR,
                    "Transição de {$oldStatus->value} para {$newStatus} não permitida"
                );
            }

            // Atualizar orçamento
            $budget->update(['status' => $newStatus]);

            // Atualizar serviços em cascata
            $this->updateRelatedServices($budget, $newStatus);

            return $this->success($budget, 'Status alterado com sucesso');
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

private function updateRelatedServices(Budget $budget, string $newStatus): void
{
    $serviceStatus = match($newStatus) {
        'approved' => 'in_progress',
        'rejected', 'cancelled' => 'cancelled',
        default => null
    };

    if ($serviceStatus) {
        $budget->services()->update(['status' => $serviceStatus]);
    }
}
```

ARQUIVOS:

-  app/Services/Domain/BudgetService.php (métodos handleStatusChange, updateRelatedServices)
-  app/Enums/BudgetStatus.php (método canTransitionTo se não existir)

CRITÉRIO DE SUCESSO: Status alterado com cascata automática para serviços

---

## 🎯 PROMPT 2.3: Implementar findByCode() - Busca por Código

Implemente APENAS o método findByCode() no BudgetService:

TAREFA ESPECÍFICA:

-  Busca: Por código (string) não por ID
-  Tenant scoping: Automático via TenantScoped
-  Eager loading: Relacionamentos opcionais
-  Error handling: Budget não encontrado

IMPLEMENTAÇÃO:

```php
public function findByCode(string $code, array $with = []): ServiceResult
{
    try {
        $query = Budget::where('code', $code);

        if (!empty($with)) {
            $query->with($with);
        }

        $budget = $query->first();

        if (!$budget) {
            return $this->error(
                OperationStatus::NOT_FOUND,
                "Orçamento com código {$code} não encontrado"
            );
        }

        return $this->success($budget, 'Orçamento encontrado');

    } catch (Exception $e) {
        return $this->error(
            OperationStatus::ERROR,
            'Erro ao buscar orçamento',
            null,
            $e
        );
    }
}
```

ARQUIVOS:

-  app/Services/Domain/BudgetService.php (método findByCode)

CRITÉRIO DE SUCESSO: Busca por código funcionando com eager loading opcional

---

## 🎯 PROMPT 2.4: Implementar updateByCode() - Atualizar por Código

Implemente APENAS o método updateByCode() no BudgetService:

TAREFA ESPECÍFICA:

-  Busca: Por código + validação de existência
-  Validação: Status editável
-  Update: Dados + itens relacionados
-  Transaction: Atomicidade completa

IMPLEMENTAÇÃO:

```php
public function updateByCode(string $code, array $data): ServiceResult
{
    try {
        return DB::transaction(function () use ($code, $data) {
            $budget = Budget::where('code', $code)->first();

            if (!$budget) {
                return $this->error(
                    OperationStatus::NOT_FOUND,
                    "Orçamento {$code} não encontrado"
                );
            }

            // Verificar se pode editar
            if (!$budget->status->canEdit()) {
                return $this->error(
                    OperationStatus::VALIDATION_ERROR,
                    "Orçamento não pode ser editado no status {$budget->status->value}"
                );
            }

            // Atualizar orçamento
            $budget->update($data);

            // Atualizar itens se fornecidos
            if (isset($data['items'])) {
                $this->updateBudgetItems($budget, $data['items']);
            }

            return $this->success($budget->fresh(), 'Orçamento atualizado');
        });

    } catch (Exception $e) {
        return $this->error(
            OperationStatus::ERROR,
            'Erro ao atualizar orçamento',
            null,
            $e
        );
    }
}

private function updateBudgetItems(Budget $budget, array $items): void
{
    // Deletar itens existentes
    $budget->items()->delete();

    // Criar novos itens
    foreach ($items as $item) {
        $budget->items()->create($item);
    }
}
```

ARQUIVOS:

-  app/Services/Domain/BudgetService.php (métodos updateByCode, updateBudgetItems)

CRITÉRIO DE SUCESSO: Orçamento atualizado apenas se status permitir

---

## 🎯 PROMPT 2.5: Implementar deleteByCode() - Deletar por Código

Implemente APENAS o método deleteByCode() no BudgetService:

TAREFA ESPECÍFICA:

-  Busca: Por código + validação
-  Validação: Status deletável (draft/cancelled)
-  Verificação: Relacionamentos que impedem exclusão
-  Soft Delete: Usar SoftDeletes

IMPLEMENTAÇÃO:

```php
public function deleteByCode(string $code): ServiceResult
{
    try {
        return DB::transaction(function () use ($code) {
            $budget = Budget::where('code', $code)->first();

            if (!$budget) {
                return $this->error(
                    OperationStatus::NOT_FOUND,
                    "Orçamento {$code} não encontrado"
                );
            }

            // Verificar se pode deletar
            if (!$budget->status->canDelete()) {
                return $this->error(
                    OperationStatus::VALIDATION_ERROR,
                    "Orçamento não pode ser excluído no status {$budget->status->value}"
                );
            }

            // Verificar relacionamentos
            if ($budget->services()->exists()) {
                return $this->error(
                    OperationStatus::VALIDATION_ERROR,
                    "Orçamento possui serviços associados e não pode ser excluído"
                );
            }

            // Soft delete
            $budget->delete();

            return $this->success(null, 'Orçamento excluído');
        });

    } catch (Exception $e) {
        return $this->error(
            OperationStatus::ERROR,
            'Erro ao excluir orçamento',
            null,
            $e
        );
    }
}
```

ARQUIVOS:

-  app/Services/Domain/BudgetService.php (método deleteByCode)
-  app/Enums/BudgetStatus.php (método canDelete se não existir)

CRITÉRIO DE SUCESSO: Exclusão apenas se status permitir e sem relacionamentos

---

# ✅ GRUPO 3: PDF E TOKENS (4 Prompts) - **CONCLUÍDO**

## 🎯 PROMPT 3.1: Criar BudgetPdfService - Geração de PDF

Crie APENAS o BudgetPdfService para geração de PDF:

TAREFA ESPECÍFICA:

-  Service: app/Services/Infrastructure/BudgetPdfService.php
-  Library: mPDF ou DomPDF
-  Template: Blade view para PDF
-  Storage: storage/app/budgets/

IMPLEMENTAÇÃO:

```php
<?php

declare(strict_types=1);

namespace App\Services\Infrastructure;

use App\Models\Budget;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Mpdf\Mpdf;

class BudgetPdfService
{
    public function generatePdf(Budget $budget): string
    {
        // Renderizar HTML do orçamento
        $html = View::make('budgets.pdf', compact('budget'))->render();

        // Configurar mPDF
        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 15,
            'margin_right' => 15,
            'margin_top' => 16,
            'margin_bottom' => 16,
        ]);

        // Gerar PDF
        $mpdf->WriteHTML($html);
        $pdfContent = $mpdf->Output('', 'S');

        // Salvar arquivo
        $filename = "budget_{$budget->code}.pdf";
        $path = "budgets/{$filename}";

        Storage::put($path, $pdfContent);

        return $path;
    }

    public function generateHash(string $pdfPath): string
    {
        $content = Storage::get($pdfPath);
        return hash('sha256', $content);
    }
}
```

ARQUIVOS:

-  app/Services/Infrastructure/BudgetPdfService.php
-  resources/views/budgets/pdf.blade.php (template)

CRITÉRIO DE SUCESSO: PDF gerado e salvo com hash de verificação

---

## 🎯 PROMPT 3.2: Criar BudgetTokenService - Gestão de Tokens

Crie APENAS o BudgetTokenService para tokens públicos:

TAREFA ESPECÍFICA:

-  Service: app/Services/Infrastructure/BudgetTokenService.php
-  Token: Único, seguro, com expiração
-  Validação: Token + expiração
-  Regeneração: Automática quando expira

IMPLEMENTAÇÃO:

```php
<?php

declare(strict_types=1);

namespace App\Services\Infrastructure;

use App\Models\Budget;
use Illuminate\Support\Str;
use Carbon\Carbon;

class BudgetTokenService
{
    private const TOKEN_EXPIRY_DAYS = 7;

    public function generateToken(Budget $budget): string
    {
        $token = Str::random(43);
        $expiresAt = Carbon::now()->addDays(self::TOKEN_EXPIRY_DAYS);

        $budget->update([
            'public_token' => $token,
            'public_expires_at' => $expiresAt
        ]);

        return $token;
    }

    public function validateToken(string $token): array
    {
        $budget = Budget::where('public_token', $token)->first();

        if (!$budget) {
            return ['valid' => false, 'condition' => 'invalid'];
        }

        if (Carbon::now()->gt($budget->public_expires_at)) {
            return ['valid' => false, 'condition' => 'expired', 'budget' => $budget];
        }

        return ['valid' => true, 'condition' => 'valid', 'budget' => $budget];
    }

    public function regenerateToken(Budget $budget): string
    {
        return $this->generateToken($budget);
    }
}
```

ARQUIVOS:

-  app/Services/Infrastructure/BudgetTokenService.php

CRITÉRIO DE SUCESSO: Tokens gerados, validados e regenerados automaticamente

---

## 🎯 PROMPT 3.3: Atualizar print() - Geração Real de PDF

Atualize APENAS o método print() no BudgetController:

TAREFA ESPECÍFICA:

-  Integração: BudgetPdfService
-  Response: Content-Type application/pdf
-  Hash: Verificação de integridade
-  Cache: PDF por 24h

IMPLEMENTAÇÃO:

```php
public function print(string $code): Response
{
    try {
        $result = $this->budgetService->findByCode($code, [
            'customer:id,name,email,phone',
            'items:id,budget_id,description,quantity,unit_price,total_price'
        ]);

        if (!$result->isSuccess()) {
            abort(404, 'Orçamento não encontrado');
        }

        $budget = $result->getData();

        // Gerar PDF
        $pdfPath = $this->budgetPdfService->generatePdf($budget);
        $hash = $this->budgetPdfService->generateHash($pdfPath);

        // Atualizar hash no banco
        $budget->update(['pdf_verification_hash' => $hash]);

        // Retornar PDF
        $pdfContent = Storage::get($pdfPath);

        return response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "inline; filename=\"orcamento_{$budget->code}.pdf\"",
            'Cache-Control' => 'public, max-age=86400' // 24h
        ]);

    } catch (Exception $e) {
        abort(500, 'Erro ao gerar PDF');
    }
}
```

ARQUIVOS:

-  app/Http/Controllers/BudgetController.php (método print)
-  Injeção: BudgetPdfService no constructor

CRITÉRIO DE SUCESSO: PDF gerado e retornado com Content-Type correto

---

## 🎯 PROMPT 3.4: Atualizar chooseBudgetStatus() - Regeneração de Token

Atualize APENAS o método chooseBudgetStatus() no BudgetController:

TAREFA ESPECÍFICA:

-  Integração: BudgetTokenService
-  Regeneração: Automática quando token expira
-  Email: Novo token por email
-  UX: Melhor experiência do usuário

IMPLEMENTAÇÃO:

```php
public function chooseBudgetStatus(string $token): View|RedirectResponse
{
    try {
        $validation = $this->budgetTokenService->validateToken($token);

        if (!$validation['valid']) {
            if ($validation['condition'] === 'expired') {
                // Regenerar token automaticamente
                $budget = $validation['budget'];
                $newToken = $this->budgetTokenService->regenerateToken($budget);

                // TODO: Enviar novo email com token
                // $this->emailService->sendBudgetToken($budget, $newToken);

                return redirect()->back()
                    ->with('info', 'Token expirado. Um novo token foi enviado por email.');
            }

            return redirect()->back()
                ->with('error', 'Token inválido ou expirado.');
        }

        $budget = $validation['budget'];

        return view('budgets.choose-status', compact('budget', 'token'));

    } catch (Exception $e) {
        return redirect()->back()
            ->with('error', 'Erro ao validar token.');
    }
}
```

ARQUIVOS:

-  app/Http/Controllers/BudgetController.php (método chooseBudgetStatus)
-  Injeção: BudgetTokenService no constructor

CRITÉRIO DE SUCESSO: Token regenerado automaticamente quando expira

---

# 📊 GRUPO 4: MIGRATION E ENUM (2 Prompts)

## ✅ PROMPT 4.1: ~~Criar Migration - Campos Ausentes~~ **CONCLUÍDO**

**STATUS**: ✅ **IMPLEMENTADO** - Campos já incluídos na migration inicial

**CAMPOS IMPLEMENTADOS**:

-  ✅ `history` - Histórico de mudanças em JSON (LONGTEXT)
-  ✅ `pdf_verification_hash` - Hash SHA256 do PDF (VARCHAR 64, UNIQUE)
-  ✅ `public_token` - Token para acesso público (VARCHAR 43, UNIQUE)
-  ✅ `public_expires_at` - Expiração do token público (TIMESTAMP)

**ÍNDICES CRIADOS**:

-  ✅ `budgets_public_token_index` - Busca rápida por token
-  ✅ `budgets_public_token_public_expires_at_index` - Busca por token + expiração

**ARQUIVOS ATUALIZADOS**:

-  ✅ `database/migrations/2025_09_27_132300_create_initial_schema.php`
-  ✅ `app/Models/Budget.php` (fillable e casts)

**DATA IMPLEMENTAÇÃO**: 2025-11-06
**DESENVOLVEDOR**: Sistema já implementado no schema inicial

---

## 🎯 PROMPT 4.2: Atualizar BudgetStatus Enum - Métodos de Transição

Atualize APENAS o BudgetStatus enum com métodos de transição:

TAREFA ESPECÍFICA:

-  Métodos: canEdit(), canDelete(), canTransitionTo()
-  Regras: Baseadas no sistema legado
-  Validações: Transições permitidas

IMPLEMENTAÇÃO:

```php
<?php

declare(strict_types=1);

namespace App\Enums;

enum BudgetStatus: string
{
    case DRAFT = 'draft';
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case CANCELLED = 'cancelled';
    case COMPLETED = 'completed';

    public function canEdit(): bool
    {
        return match($this) {
            self::DRAFT, self::PENDING => true,
            default => false
        };
    }

    public function canDelete(): bool
    {
        return match($this) {
            self::DRAFT, self::CANCELLED => true,
            default => false
        };
    }

    public function canTransitionTo(self $newStatus): bool
    {
        return match($this) {
            self::DRAFT => in_array($newStatus, [self::PENDING, self::CANCELLED]),
            self::PENDING => in_array($newStatus, [self::APPROVED, self::REJECTED, self::CANCELLED]),
            self::APPROVED => in_array($newStatus, [self::COMPLETED, self::CANCELLED]),
            self::REJECTED => in_array($newStatus, [self::CANCELLED]),
            default => false
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match($this) {
            self::DRAFT => 'Rascunho',
            self::PENDING => 'Pendente',
            self::APPROVED => 'Aprovado',
            self::REJECTED => 'Rejeitado',
            self::CANCELLED => 'Cancelado',
            self::COMPLETED => 'Concluído'
        };
    }
}
```

ARQUIVOS:

-  app/Enums/BudgetStatus.php

CRITÉRIO DE SUCESSO: Transições de status validadas corretamente

---

# 🧪 GRUPO 5: TESTES (3 Prompts)

## 🎯 PROMPT 5.1: Testes de Controller - Métodos CRUD

Crie APENAS testes para métodos CRUD do BudgetController:

TAREFA ESPECÍFICA:

-  Arquivo: tests/Feature/BudgetControllerTest.php
-  Métodos: store, show, update, update_store
-  Setup: Factories + tenant scoping
-  **IMPORTANTE**: Use o banco configurado no .env existente, NÃO recriar o banco

IMPLEMENTAÇÃO:

```php
<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Budget;
use App\Models\Customer;
use App\Models\User;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Customer $customer;
    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->customer = Customer::factory()->create(['tenant_id' => $this->tenant->id]);

        $this->actingAs($this->user);
        tenancy()->initialize($this->tenant);
    }

    public function test_store_creates_budget_with_unique_code(): void
    {
        $data = [
            'customer_id' => $this->customer->id,
            'description' => 'Orçamento teste',
            'due_date' => now()->addDays(30)->format('Y-m-d'),
            'items' => [
                ['description' => 'Item 1', 'quantity' => 1, 'unit_price' => 100.00]
            ]
        ];

        $response = $this->post(route('budgets.store'), $data);

        $response->assertRedirect();
        $this->assertDatabaseHas('budgets', [
            'customer_id' => $this->customer->id,
            'description' => 'Orçamento teste'
        ]);

        $budget = Budget::latest()->first();
        $this->assertStringStartsWith('ORC-' . date('Ymd'), $budget->code);
    }

    public function test_show_displays_budget_details(): void
    {
        $budget = Budget::factory()->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->customer->id
        ]);

        $response = $this->get(route('budgets.show', $budget->code));

        $response->assertOk();
        $response->assertViewIs('budgets.show');
        $response->assertViewHas('budget');
    }
}
```

ARQUIVOS:

-  tests/Feature/BudgetControllerTest.php

CRITÉRIO DE SUCESSO: Testes passando para métodos CRUD básicos

---

## 🎯 PROMPT 5.2: Testes de Service - Lógica de Negócio

Crie APENAS testes para BudgetService:

TAREFA ESPECÍFICA:

-  Arquivo: tests/Unit/BudgetServiceTest.php
-  Métodos: generateUniqueCode, handleStatusChange, findByCode
-  Mock: Repositories e dependências
-  **IMPORTANTE**: Use o banco configurado no .env existente, NÃO recriar o banco

IMPLEMENTAÇÃO:

```php
<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Domain\BudgetService;
use App\Models\Budget;
use App\Enums\BudgetStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetServiceTest extends TestCase
{
    use RefreshDatabase;

    private BudgetService $budgetService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->budgetService = app(BudgetService::class);
    }

    public function test_generate_unique_code_creates_sequential_codes(): void
    {
        // Criar orçamento existente
        Budget::factory()->create(['code' => 'ORC-' . date('Ymd') . '0001']);

        // Gerar novo código
        $reflection = new \ReflectionClass($this->budgetService);
        $method = $reflection->getMethod('generateUniqueCode');
        $method->setAccessible(true);

        $newCode = $method->invoke($this->budgetService);

        $this->assertEquals('ORC-' . date('Ymd') . '0002', $newCode);
    }

    public function test_handle_status_change_updates_related_services(): void
    {
        $budget = Budget::factory()->create(['status' => BudgetStatus::PENDING]);
        $service = $budget->services()->create([
            'name' => 'Serviço teste',
            'status' => 'pending'
        ]);

        $result = $this->budgetService->handleStatusChange($budget, 'approved');

        $this->assertTrue($result->isSuccess());
        $this->assertEquals('approved', $budget->fresh()->status->value);
        $this->assertEquals('in_progress', $service->fresh()->status);
    }

    public function test_find_by_code_returns_budget(): void
    {
        $budget = Budget::factory()->create(['code' => 'ORC-202501010001']);

        $result = $this->budgetService->findByCode('ORC-202501010001');

        $this->assertTrue($result->isSuccess());
        $this->assertEquals($budget->id, $result->getData()->id);
    }
}
```

ARQUIVOS:

-  tests/Unit/BudgetServiceTest.php

CRITÉRIO DE SUCESSO: Testes unitários passando para lógica de negócio

---

## 🎯 PROMPT 5.3: Testes de Observer - Auditoria

Crie APENAS testes para BudgetObserver:

TAREFA ESPECÍFICA:

-  Arquivo: tests/Unit/BudgetObserverTest.php
-  Eventos: created, updated, deleted
-  Auditoria: AuditLog com old_values/new_values
-  **IMPORTANTE**: Use o banco configurado no .env existente, NÃO recriar o banco

IMPLEMENTAÇÃO:

```php
<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Budget;
use App\Models\AuditLog;
use App\Models\User;
use App\Enums\BudgetStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetObserverTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_log_created_on_budget_creation(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $budget = Budget::factory()->create();

        $this->assertDatabaseHas('audit_logs', [
            'model_type' => Budget::class,
            'model_id' => $budget->id,
            'action' => 'budget_created',
            'user_id' => $user->id
        ]);
    }

    public function test_audit_log_includes_old_new_values_on_update(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $budget = Budget::factory()->create(['status' => BudgetStatus::DRAFT]);

        // Limpar logs anteriores
        AuditLog::truncate();

        $budget->update(['status' => BudgetStatus::PENDING]);

        $auditLog = AuditLog::latest()->first();

        $this->assertNotNull($auditLog);
        $this->assertEquals('budget_updated', $auditLog->action);
        $this->assertArrayHasKey('status', $auditLog->old_values);
        $this->assertArrayHasKey('status', $auditLog->new_values);
        $this->assertEquals('draft', $auditLog->old_values['status']);
        $this->assertEquals('pending', $auditLog->new_values['status']);
    }

    public function test_audit_log_records_ip_and_user_agent(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $budget = Budget::factory()->create();

        $auditLog = AuditLog::where('model_id', $budget->id)->first();

        $this->assertNotNull($auditLog->ip_address);
        $this->assertNotNull($auditLog->user_agent);
    }
}
```

ARQUIVOS:

-  tests/Unit/BudgetObserverTest.php

CRITÉRIO DE SUCESSO: Auditoria automática funcionando via Observer

---

# 🎨 GRUPO 6: VIEWS (2 Prompts)

## 🎯 PROMPT 6.1: Criar budgets/show.blade.php - Visualização Completa

Crie APENAS a view budgets/show.blade.php:

TAREFA ESPECÍFICA:

-  Layout: Dados do orçamento + itens + ações
-  Responsivo: Bootstrap 5.3
-  Ações: Baseadas no status atual
-  PDF: Link para download

IMPLEMENTAÇÃO:

```blade
@extends('layouts.app')

@section('title', 'Orçamento ' . $budget->code)

@section('content')
<div class="container py-1">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Orçamento {{ $budget->code }}</h1>
            <p class="text-muted mb-0">
                Status: <span class="badge bg-{{ $budget->status->value === 'approved' ? 'success' : 'warning' }}">
                    {{ $budget->status->label() }}
                </span>
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('budgets.print', $budget->code) }}"
               class="btn btn-outline-primary" target="_blank">
                <i class="bi bi-file-pdf me-2"></i>PDF
            </a>
            @if($budget->status->canEdit())
                <a href="{{ route('budgets.edit', $budget->code) }}"
                   class="btn btn-primary">
                    <i class="bi bi-pencil me-2"></i>Editar
                </a>
            @endif
        </div>
    </div>

    <div class="row">
        <!-- Dados do Cliente -->
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Cliente</h5>
                </div>
                <div class="card-body">
                    <h6>{{ $budget->customer->name }}</h6>
                    <p class="text-muted mb-1">{{ $budget->customer->email }}</p>
                    <p class="text-muted mb-0">{{ $budget->customer->phone }}</p>
                </div>
            </div>
        </div>

        <!-- Dados do Orçamento -->
        <div class="col-lg-6 mb-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Detalhes</h5>
                </div>
                <div class="card-body">
                    <p><strong>Descrição:</strong> {{ $budget->description }}</p>
                    <p><strong>Data de Vencimento:</strong>
                        {{ $budget->due_date ? $budget->due_date->format('d/m/Y') : 'Não definida' }}
                    </p>
                    <p><strong>Criado em:</strong> {{ $budget->created_at->format('d/m/Y H:i') }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Itens do Orçamento -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Itens do Orçamento</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Descrição</th>
                            <th class="text-center">Qtd</th>
                            <th class="text-end">Valor Unit.</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($budget->items as $item)
                            <tr>
                                <td>{{ $item->description }}</td>
                                <td class="text-center">{{ $item->quantity }}</td>
                                <td class="text-end">R$ {{ number_format($item->unit_price, 2, ',', '.') }}</td>
                                <td class="text-end">R$ {{ number_format($item->total_price, 2, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-1">
                                    Nenhum item adicionado
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if($budget->items->count() > 0)
                        <tfoot class="table-light">
                            <tr>
                                <th colspan="3" class="text-end">Total Geral:</th>
                                <th class="text-end">R$ {{ number_format($budget->total_amount, 2, ',', '.') }}</th>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    </div>

    <!-- Ações de Status -->
    @if($budget->status === App\Enums\BudgetStatus::PENDING)
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Ações</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('budgets.change-status', $budget->code) }}"
                      class="d-inline">
                    @csrf
                    <input type="hidden" name="status" value="approved">
                    <button type="submit" class="btn btn-success me-2">
                        <i class="bi bi-check-lg me-2"></i>Aprovar
                    </button>
                </form>

                <form method="POST" action="{{ route('budgets.change-status', $budget->code) }}"
                      class="d-inline">
                    @csrf
                    <input type="hidden" name="status" value="rejected">
                    <button type="submit" class="btn btn-danger">
                        <i class="bi bi-x-lg me-2"></i>Rejeitar
                    </button>
                </form>
            </div>
        </div>
    @endif
</div>
@endsection
```

ARQUIVOS:

-  resources/views/budgets/show.blade.php

CRITÉRIO DE SUCESSO: Visualização completa com ações baseadas no status

---

## 🎯 PROMPT 6.2: Criar budgets/pdf.blade.php - Template PDF

````

Crie APENAS a view budgets/pdf.blade.php para geração de PDF:

TAREFA ESPECÍFICA:

-  Layout: Otimizado para PDF (A4)
-  Dados: Completos do orçamento
-  Estilo: CSS inline para compatibilidade
-  Formato: Profissional

IMPLEMENTAÇÃO:

```blade
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Orçamento {{ $budget->code }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #007bff;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #007bff;
            margin: 0;
            font-size: 24px;
        }
        .info-section {
            margin-bottom: 25px;
        }
        .info-section h3 {
            background-color: #f8f9fa;
            padding: 8px 12px;
            margin: 0 0 10px 0;
            border-left: 4px solid #007bff;
            font-size: 14px;
        }
        .info-grid {
            display: table;
            width: 100%;
        }
        .info-row {
            display: table-row;
        }
        .info-label {
            display: table-cell;
            font-weight: bold;
            padding: 5px 10px 5px 0;
            width: 120px;
        }
        .info-value {
            display: table-cell;
            padding: 5px 0;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .items-table th,
        .items-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .items-table th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .items-table .text-center {
            text-align: center;
        }
        .items-table .text-right {
            text-align: right;
        }
        .total-row {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-approved { background-color: #d4edda; color: #155724; }
        .status-pending { background-color: #fff3cd; color: #856404; }
        .status-rejected { background-color: #f8d7da; color: #721c24; }
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 20px;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h1>ORÇAMENTO</h1>
        <p><strong>Código:</strong> {{ $budget->code }}</p>
        <p>
            <span class="status-badge status-{{ $budget->status->value }}">
                {{ $budget->status->label() }}
            </span>
        </p>
    </div>

    <!-- Dados do Cliente -->
    <div class="info-section">
        <h3>Dados do Cliente</h3>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Nome:</div>
                <div class="info-value">{{ $budget->customer->name }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">E-mail:</div>
                <div class="info-value">{{ $budget->customer->email }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Telefone:</div>
                <div class="info-value">{{ $budget->customer->phone ?? 'Não informado' }}</div>
            </div>
        </div>
    </div>

    <!-- Dados do Orçamento -->
    <div class="info-section">
        <h3>Detalhes do Orçamento</h3>
        <div class="info-grid">
            <div class="info-row">
                <div class="info-label">Descrição:</div>
                <div class="info-value">{{ $budget->description }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Data de Criação:</div>
                <div class="info-value">{{ $budget->created_at->format('d/m/Y H:i') }}</div>
            </div>
            <div class="info-row">
                <div class="info-label">Vencimento:</div>
                <div class="info-value">
                    {{ $budget->due_date ? $budget->due_date->format('d/m/Y') : 'Não definido' }}
                </div>
            </div>
        </div>
    </div>

    <!-- Itens do Orçamento -->
    <div class="info-section">
        <h3>Itens do Orçamento</h3>
        <table class="items-table">
            <thead>
                <tr>
                    <th>Descrição</th>
                    <th class="text-center" width="80">Qtd</th>
                    <th class="text-right" width="100">Valor Unit.</th>
                    <th class="text-right" width="100">Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse($budget->items as $item)
                    <tr>
                        <td>{{ $item->description }}</td>
                        <td class="text-center">{{ $item->quantity }}</td>
                        <td class="text-right">R$ {{ number_format($item->unit_price, 2, ',', '.') }}</td>
                        <td class="text-right">R$ {{ number_format($item->total_price, 2, ',', '.') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="text-center">Nenhum item adicionado</td>
                    </tr>
                @endforelse
            </tbody>
            @if($budget->items->count() > 0)
                <tfoot>
                    <tr class="total-row">
                        <td colspan="3" class="text-right"><strong>TOTAL GERAL:</strong></td>
                        <td class="text-right">
                            <strong>R$ {{ number_format($budget->total_amount, 2, ',', '.') }}</strong>
                        </td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>

    <!-- Footer -->
    <div class="footer">
        <p>Este orçamento foi gerado automaticamente em {{ now()->format('d/m/Y H:i') }}</p>
        <p>Código de verificação: {{ $budget->pdf_verification_hash ?? 'Não disponível' }}</p>
    </div>
</body>
</html>
````

ARQUIVOS:

-  resources/views/budgets/pdf.blade.php

CRITÉRIO DE SUCESSO: PDF gerado com layout profissional e dados completos

```

---

# 📊 RESUMO DOS PROMPTS DETALHADOS

## 📈 **ESTATÍSTICAS**

| Grupo | Prompts | Tempo Estimado | Prioridade |
|-------|---------|----------------|------------|
| **Controllers** | 6 | 6-8 dias | 🔴 Crítica |
| **Services** | 5 | 4-5 dias | 🔴 Crítica |
| **PDF/Tokens** | 4 | 3-4 dias | 🟨 Alta |
| **Migration/Enum** | 2 | 1 dia | 🟨 Alta |
| **Testes** | 3 | 2-3 dias | 🟩 Média |
| **Views** | 2 | 1-2 dias | 🟩 Média |
| **TOTAL** | **22** | **17-23 dias** | - |

## 🎯 **ORDEM DE EXECUÇÃO RECOMENDADA**

### **Fase 1: Base Crítica (8 dias)**
1. PROMPT 4.1: Migration campos ausentes
2. PROMPT 4.2: BudgetStatus enum métodos
3. PROMPT 2.1: generateUniqueCode()
4. PROMPT 2.3: findByCode()
5. PROMPT 1.1: store() controller
6. PROMPT 1.2: show() controller

### **Fase 2: CRUD Completo (6 dias)**
7. PROMPT 2.4: updateByCode() service
8. PROMPT 1.3: update() controller
9. PROMPT 1.4: update_store() controller
10. PROMPT 2.5: deleteByCode() service
11. PROMPT 1.6: delete_store() controller

### **Fase 3: Lógica Avançada (4 dias)**
12. PROMPT 2.2: handleStatusChange() service
13. PROMPT 1.5: change_status() controller

### **Fase 4: PDF e Tokens (4 dias)**
14. PROMPT 3.1: BudgetPdfService
15. PROMPT 3.2: BudgetTokenService
16. PROMPT 3.3: print() atualizado
17. PROMPT 3.4: chooseBudgetStatus() atualizado

### **Fase 5: Views e Testes (3 dias)**
18. PROMPT 6.1: show.blade.php
19. PROMPT 6.2: pdf.blade.php
20. PROMPT 5.1: Controller tests
21. PROMPT 5.2: Service tests
22. PROMPT 5.3: Observer tests

## ✅ **CRITÉRIOS DE SUCESSO POR PROMPT**

Cada prompt tem critério específico de sucesso para validação imediata da implementação.

## 🚀 **BENEFÍCIOS DA ABORDAGEM DETALHADA**

- ✅ **Tarefas menores** - Mais fáceis de delegar e executar
- ✅ **Validação incremental** - Cada prompt tem critério de sucesso
- ✅ **Paralelização** - Alguns prompts podem ser executados em paralelo
- ✅ **Rollback granular** - Problemas isolados por prompt
- ✅ **Progress tracking** - 22 checkpoints claros de progresso
```
