# 👥 Skill: Customer Management (Gestão de Clientes)

**Descrição:** Garante o controle correto de clientes PF/PJ, seus relacionamentos e dados associados no Easy Budget.

**Categoria:** Gestão de Clientes
**Complexidade:** Média
**Status:** ✅ Implementado e Documentado

## 🎯 Objetivo

Padronizar a gestão completa de clientes no Easy Budget, desde o cadastro até a manutenção de relacionamentos, garantindo validações de negócio específicas para Pessoa Física e Pessoa Jurídica, integração com orçamentos, serviços e faturas, e controle de histórico de interações.

## 📋 Requisitos Técnicos

### **✅ Tipos de Clientes: PF vs PJ**

Implementar validações específicas para cada tipo de cliente:

```php
enum CustomerType: string
{
    case INDIVIDUAL = 'individual';   // Pessoa Física
    case COMPANY = 'company';         // Pessoa Jurídica

    public function requiresCpf(): bool
    {
        return $this === self::INDIVIDUAL;
    }

    public function requiresCnpj(): bool
    {
        return $this === self::COMPANY;
    }

    public function requiresCompanyName(): bool
    {
        return $this === self::COMPANY;
    }
}
```

### **✅ Validações de Documentos**

```php
class CustomerValidationService extends AbstractBaseService
{
    public function validateDocument(string $document, CustomerType $type): ServiceResult
    {
        if ($type->requiresCpf() && !empty($document)) {
            if (!$this->isValidCpf($document)) {
                return $this->error('CPF inválido', OperationStatus::INVALID_DATA);
            }
        }

        if ($type->requiresCnpj() && !empty($document)) {
            if (!$this->isValidCnpj($document)) {
                return $this->error('CNPJ inválido', OperationStatus::INVALID_DATA);
            }
        }

        return $this->success(null, 'Documento válido');
    }

    private function isValidCpf(string $cpf): bool
    {
        // Algoritmo de validação de CPF
        $cpf = preg_replace('/[^0-9]/', '', $cpf);

        if (strlen($cpf) != 11) {
            return false;
        }

        if (preg_match('/^(\d)\1{10}$/', $cpf)) {
            return false;
        }

        // Cálculo dos dígitos verificadores
        $sum = 0;
        for ($i = 0; $i < 9; $i++) {
            $sum += $cpf[$i] * (10 - $i);
        }
        $remainder = $sum % 11;
        $digit1 = ($remainder < 2) ? 0 : 11 - $remainder;

        $sum = 0;
        for ($i = 0; $i < 10; $i++) {
            $sum += $cpf[$i] * (11 - $i);
        }
        $remainder = $sum % 11;
        $digit2 = ($remainder < 2) ? 0 : 11 - $remainder;

        return $cpf[9] == $digit1 && $cpf[10] == $digit2;
    }

    private function isValidCnpj(string $cnpj): bool
    {
        // Algoritmo de validação de CNPJ
        $cnpj = preg_replace('/[^0-9]/', '', $cnpj);

        if (strlen($cnpj) != 14) {
            return false;
        }

        if (preg_match('/^(\d)\1{13}$/', $cnpj)) {
            return false;
        }

        // Cálculo dos dígitos verificadores
        $weights1 = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += $cnpj[$i] * $weights1[$i];
        }
        $remainder = $sum % 11;
        $digit1 = ($remainder < 2) ? 0 : 11 - $remainder;

        $weights2 = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
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

### **✅ Status de Clientes**

```php
enum CustomerStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case DELETED = 'deleted';

    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }

    public function canReceiveBudgets(): bool
    {
        return $this->isActive();
    }

    public function canReceiveInvoices(): bool
    {
        return $this->isActive();
    }
}
```

## 🏗️ Estrutura do Ciclo de Vida

### **📊 Fluxo Completo de Cliente**

```
┌─────────────┐    ┌─────────────┐    ┌─────────────────┐
│  Cadastro   │───▶│   Ativo     │───▶│   Inativo       │
└─────────────┘    └─────────────┘    └─────────────────┘
       │                   │                   │
       │                   │                   │
       ▼                   ▼                   ▼
┌─────────────┐    ┌─────────────┐    ┌─────────────────┐
│  Duplicação │    │  Histórico  │    │   Exclusão      │
└─────────────┘    └─────────────┘    └─────────────────┘
```

### **📝 Etapas do Ciclo de Vida**

#### **1. Cadastro de Cliente**

```php
public function createCustomer(CustomerDTO $dto): ServiceResult
{
    return $this->safeExecute(function() use ($dto) {
        // 1. Validar tipo de cliente
        $customerType = CustomerType::from($dto->type);

        // 2. Validar documentos
        if ($customerType->requiresCpf() && !empty($dto->cpf)) {
            $cpfValidation = $this->validateDocument($dto->cpf, $customerType);
            if (!$cpfValidation->isSuccess()) {
                return $cpfValidation;
            }
        }

        if ($customerType->requiresCnpj() && !empty($dto->cnpj)) {
            $cnpjValidation = $this->validateDocument($dto->cnpj, $customerType);
            if (!$cnpjValidation->isSuccess()) {
                return $cnpjValidation;
            }
        }

        // 3. Verificar duplicação
        if ($this->checkDuplicateCustomer($dto)) {
            return $this->error('Cliente já cadastrado', OperationStatus::DUPLICATE_DATA);
        }

        // 4. Criar dados comuns
        $commonData = $this->createCommonData($dto, $customerType);

        // 5. Criar cliente
        $customerData = [
            'tenant_id' => $dto->tenant_id,
            'status' => CustomerStatus::ACTIVE->value,
            'common_data_id' => $commonData->id,
            'contact_id' => null, // Será criado depois
            'address_id' => null, // Será criado depois
        ];

        $result = $this->repository->create($customerData);

        if ($result->isSuccess()) {
            $customer = $result->getData();

            // 6. Criar contatos e endereço
            $this->createCustomerContacts($customer, $dto);
            $this->createCustomerAddress($customer, $dto);

            // 7. Disparar eventos
            event(new CustomerCreated($customer));
        }

        return $result;
    });
}
```

#### **2. Atualização de Cliente**

```php
public function updateCustomer(Customer $customer, CustomerDTO $dto): ServiceResult
{
    return $this->safeExecute(function() use ($customer, $dto) {
        // 1. Validar documentos se forem alterados
        if ($dto->cpf !== $customer->commonData->cpf) {
            $cpfValidation = $this->validateDocument($dto->cpf, CustomerType::from($dto->type));
            if (!$cpfValidation->isSuccess()) {
                return $cpfValidation;
            }
        }

        if ($dto->cnpj !== $customer->commonData->cnpj) {
            $cnpjValidation = $this->validateDocument($dto->cnpj, CustomerType::from($dto->type));
            if (!$cnpjValidation->isSuccess()) {
                return $cnpjValidation;
            }
        }

        // 2. Atualizar dados comuns
        $this->updateCommonData($customer->commonData, $dto);

        // 3. Atualizar cliente
        $result = $this->repository->update($customer, [
            'status' => $dto->status,
        ]);

        if ($result->isSuccess()) {
            // 4. Atualizar contatos e endereço
            $this->updateCustomerContacts($customer, $dto);
            $this->updateCustomerAddress($customer, $dto);

            // 5. Disparar eventos
            event(new CustomerUpdated($customer));
        }

        return $result;
    });
}
```

#### **3. Inativação de Cliente**

```php
public function deactivateCustomer(Customer $customer): ServiceResult
{
    return $this->safeExecute(function() use ($customer) {
        // 1. Verificar se há orçamentos ativos
        if ($this->hasActiveBudgets($customer)) {
            return $this->error('Não é possível inativar cliente com orçamentos ativos', OperationStatus::INVALID_DATA);
        }

        // 2. Verificar se há faturas pendentes
        if ($this->hasPendingInvoices($customer)) {
            return $this->error('Não é possível inativar cliente com faturas pendentes', OperationStatus::INVALID_DATA);
        }

        // 3. Atualizar status
        return $this->repository->update($customer, [
            'status' => CustomerStatus::INACTIVE->value
        ]);
    });
}
```

## 🔗 Integrações com Orçamentos, Serviços e Faturas

### **✅ Integração com Orçamentos**

```php
class CustomerBudgetService extends AbstractBaseService
{
    public function getCustomerBudgets(Customer $customer, array $filters = []): ServiceResult
    {
        $query = $customer->budgets();

        // Aplicar filtros
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        $budgets = $query->with(['items', 'customer'])->get();

        return $this->success($budgets, 'Orçamentos do cliente');
    }

    public function getCustomerBudgetSummary(Customer $customer): array
    {
        $totalBudgets = $customer->budgets()->count();
        $totalValue = $customer->budgets()->sum('total_value');
        $approvedBudgets = $customer->budgets()->where('status', 'approved')->count();
        $pendingBudgets = $customer->budgets()->where('status', 'pending')->count();

        return [
            'total_budgets' => $totalBudgets,
            'total_value' => $totalValue,
            'approved_budgets' => $approvedBudgets,
            'pending_budgets' => $pendingBudgets,
            'average_value' => $totalBudgets > 0 ? $totalValue / $totalBudgets : 0,
        ];
    }
}
```

### **✅ Integração com Serviços**

```php
class CustomerServiceService extends AbstractBaseService
{
    public function getCustomerServices(Customer $customer, array $filters = []): ServiceResult
    {
        $services = $customer->services();

        // Aplicar filtros
        if (isset($filters['status'])) {
            $services->where('status', $filters['status']);
        }

        $services = $services->with(['budget', 'items'])->get();

        return $this->success($services, 'Serviços do cliente');
    }

    public function getCustomerServiceSummary(Customer $customer): array
    {
        $totalServices = $customer->services()->count();
        $completedServices = $customer->services()->where('status', 'completed')->count();
        $pendingServices = $customer->services()->where('status', 'pending')->count();

        return [
            'total_services' => $totalServices,
            'completed_services' => $completedServices,
            'pending_services' => $pendingServices,
            'completion_rate' => $totalServices > 0 ? ($completedServices / $totalServices) * 100 : 0,
        ];
    }
}
```

### **✅ Integração com Faturas**

```php
class CustomerInvoiceService extends AbstractBaseService
{
    public function getCustomerInvoices(Customer $customer, array $filters = []): ServiceResult
    {
        $invoices = $customer->invoices();

        // Aplicar filtros
        if (isset($filters['status'])) {
            $invoices->where('status', $filters['status']);
        }

        if (isset($filters['date_from'])) {
            $invoices->where('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $invoices->where('created_at', '<=', $filters['date_to']);
        }

        $invoices = $invoices->with(['budget', 'items'])->get();

        return $this->success($invoices, 'Faturas do cliente');
    }

    public function getCustomerInvoiceSummary(Customer $customer): array
    {
        $totalInvoices = $customer->invoices()->count();
        $totalValue = $customer->invoices()->sum('total');
        $paidInvoices = $customer->invoices()->where('status', 'paid')->count();
        $pendingInvoices = $customer->invoices()->where('status', 'sent')->count();
        $overdueInvoices = $customer->invoices()->where('status', 'overdue')->count();

        return [
            'total_invoices' => $totalInvoices,
            'total_value' => $totalValue,
            'paid_invoices' => $paidInvoices,
            'pending_invoices' => $pendingInvoices,
            'overdue_invoices' => $overdueInvoices,
            'collection_rate' => $totalInvoices > 0 ? ($paidInvoices / $totalInvoices) * 100 : 0,
        ];
    }
}
```

## 📋 Campos Opcionais e Validações Condicionais

### **✅ Campos Opcionais por Tipo de Cliente**

```php
class CustomerFieldValidationService extends AbstractBaseService
{
    public function validateConditionalFields(CustomerDTO $dto): ServiceResult
    {
        $customerType = CustomerType::from($dto->type);

        // Campos obrigatórios para Pessoa Física
        if ($customerType === CustomerType::INDIVIDUAL) {
            if (empty($dto->first_name) || empty($dto->last_name)) {
                return $this->error('Nome e sobrenome são obrigatórios para Pessoa Física', OperationStatus::INVALID_DATA);
            }
        }

        // Campos obrigatórios para Pessoa Jurídica
        if ($customerType === CustomerType::COMPANY) {
            if (empty($dto->company_name)) {
                return $this->error('Razão social é obrigatória para Pessoa Jurídica', OperationStatus::INVALID_DATA);
            }
        }

        // Campos opcionais baseados no tipo
        if ($customerType === CustomerType::INDIVIDUAL) {
            // Campos que podem ser nulos para PF
            $dto->company_name = null;
            $dto->cnpj = null;
        }

        if ($customerType === CustomerType::COMPANY) {
            // Campos que podem ser nulos para PJ
            $dto->cpf = null;
        }

        return $this->success(null, 'Campos validados');
    }
}
```

### **✅ Validações Condicionais de Contatos**

```php
class CustomerContactValidationService extends AbstractBaseService
{
    public function validateContactFields(array $contacts): ServiceResult
    {
        $emailCount = 0;
        $phoneCount = 0;

        foreach ($contacts as $contact) {
            if ($contact['type'] === 'email') {
                $emailCount++;
                if (!filter_var($contact['value'], FILTER_VALIDATE_EMAIL)) {
                    return $this->error('E-mail inválido', OperationStatus::INVALID_DATA);
                }
            }

            if ($contact['type'] === 'phone') {
                $phoneCount++;
                if (!$this->isValidPhone($contact['value'])) {
                    return $this->error('Telefone inválido', OperationStatus::INVALID_DATA);
                }
            }
        }

        // Pelo menos um e-mail é obrigatório
        if ($emailCount === 0) {
            return $this->error('É necessário pelo menos um e-mail', OperationStatus::INVALID_DATA);
        }

        return $this->success(null, 'Contatos validados');
    }

    private function isValidPhone(string $phone): bool
    {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        return strlen($phone) >= 10 && strlen($phone) <= 11;
    }
}
```

## 📊 Relacionamentos e Histórico

### **✅ Histórico de Interações**

```php
class CustomerInteractionService extends AbstractBaseService
{
    public function addInteraction(Customer $customer, InteractionDTO $dto): ServiceResult
    {
        $interactionData = [
            'customer_id' => $customer->id,
            'tenant_id' => $customer->tenant_id,
            'interaction_type' => $dto->type,
            'description' => $dto->description,
            'interaction_date' => $dto->date,
            'created_by' => $dto->created_by,
            'next_action' => $dto->next_action,
            'next_action_date' => $dto->next_action_date,
            'outcome' => $dto->outcome,
        ];

        return $this->interactionRepository->create($interactionData);
    }

    public function getInteractionHistory(Customer $customer, int $limit = 10): ServiceResult
    {
        $interactions = $customer->interactions()
            ->orderBy('interaction_date', 'desc')
            ->limit($limit)
            ->get();

        return $this->success($interactions, 'Histórico de interações');
    }

    public function getPendingActions(Customer $customer): ServiceResult
    {
        $actions = $customer->interactions()
            ->whereNotNull('next_action')
            ->where('next_action_date', '>=', now())
            ->where(function ($query) {
                $query->whereNull('outcome')
                    ->orWhere('outcome', '!=', 'completed');
            })
            ->orderBy('next_action_date', 'asc')
            ->get();

        return $this->success($actions, 'Ações pendentes');
    }
}
```

### **✅ Sistema de Tags**

```php
class CustomerTagService extends AbstractBaseService
{
    public function assignTags(Customer $customer, array $tagIds): ServiceResult
    {
        try {
            $customer->tags()->sync($tagIds);
            return $this->success(null, 'Tags atribuídas com sucesso');
        } catch (Exception $e) {
            return $this->error('Erro ao atribuir tags', OperationStatus::INTERNAL_ERROR, $e);
        }
    }

    public function getCustomerTags(Customer $customer): ServiceResult
    {
        $tags = $customer->tags;
        return $this->success($tags, 'Tags do cliente');
    }

    public function removeTag(Customer $customer, CustomerTag $tag): ServiceResult
    {
        try {
            $customer->tags()->detach($tag->id);
            return $this->success(null, 'Tag removida com sucesso');
        } catch (Exception $e) {
            return $this->error('Erro ao remover tag', OperationStatus::INTERNAL_ERROR, $e);
        }
    }
}
```

## 🧪 Testes e Validação

### **✅ Testes de Validação de Documentos**

```php
public function testValidCpf()
{
    $cpf = '123.456.789-09';
    $result = $this->customerValidationService->validateDocument($cpf, CustomerType::INDIVIDUAL);
    $this->assertTrue($result->isSuccess());
}

public function testInvalidCpf()
{
    $cpf = '111.111.111-11'; // CPF inválido (todos dígitos iguais)
    $result = $this->customerValidationService->validateDocument($cpf, CustomerType::INDIVIDUAL);
    $this->assertFalse($result->isSuccess());
    $this->assertEquals(OperationStatus::INVALID_DATA, $result->getStatus());
}

public function testValidCnpj()
{
    $cnpj = '12.345.678/0001-95';
    $result = $this->customerValidationService->validateDocument($cnpj, CustomerType::COMPANY);
    $this->assertTrue($result->isSuccess());
}

public function testInvalidCnpj()
{
    $cnpj = '11.111.111/1111-11'; // CNPJ inválido (todos dígitos iguais)
    $result = $this->customerValidationService->validateDocument($cnpj, CustomerType::COMPANY);
    $this->assertFalse($result->isSuccess());
    $this->assertEquals(OperationStatus::INVALID_DATA, $result->getStatus());
}
```

### **✅ Testes de Integração**

```php
public function testCustomerBudgetIntegration()
{
    $customer = Customer::factory()->create();
    $budget = Budget::factory()->create(['customer_id' => $customer->id]);

    $result = $this->customerBudgetService->getCustomerBudgets($customer);
    $this->assertTrue($result->isSuccess());

    $budgets = $result->getData();
    $this->assertCount(1, $budgets);
    $this->assertEquals($customer->id, $budgets[0]->customer_id);
}

public function testCustomerInvoiceIntegration()
{
    $customer = Customer::factory()->create();
    $invoice = Invoice::factory()->create(['customer_id' => $customer->id]);

    $result = $this->customerInvoiceService->getCustomerInvoices($customer);
    $this->assertTrue($result->isSuccess());

    $invoices = $result->getData();
    $this->assertCount(1, $invoices);
    $this->assertEquals($customer->id, $invoices[0]->customer_id);
}
```

## 📈 Métricas e Monitoramento

### **✅ Métricas de Cliente**

```php
class CustomerMetricsService extends AbstractBaseService
{
    public function getCustomerMetrics(array $filters = []): array
    {
        $query = Customer::query();

        // Aplicar filtros
        if (isset($filters['tenant_id'])) {
            $query->where('tenant_id', $filters['tenant_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        $customers = $query->get();

        return [
            'total_customers' => $customers->count(),
            'active_customers' => $customers->where('status', 'active')->count(),
            'inactive_customers' => $customers->where('status', 'inactive')->count(),
            'individual_customers' => $customers->where('type', 'individual')->count(),
            'company_customers' => $customers->where('type', 'company')->count(),
            'average_interactions_per_customer' => $this->calculateAverageInteractions($customers),
            'top_tags' => $this->getTopTags($customers),
        ];
    }

    private function calculateAverageInteractions(Collection $customers): float
    {
        $totalInteractions = 0;
        $totalCustomers = $customers->count();

        foreach ($customers as $customer) {
            $totalInteractions += $customer->interactions()->count();
        }

        return $totalCustomers > 0 ? $totalInteractions / $totalCustomers : 0;
    }

    private function getTopTags(Collection $customers): array
    {
        $tagCounts = [];

        foreach ($customers as $customer) {
            foreach ($customer->tags as $tag) {
                $tagCounts[$tag->name] = ($tagCounts[$tag->name] ?? 0) + 1;
            }
        }

        arsort($tagCounts);
        return array_slice($tagCounts, 0, 5, true);
    }
}
```

### **✅ Alertas de Cliente**

```php
class CustomerAlertService extends AbstractBaseService
{
    public function checkCustomerAlerts(): void
    {
        // Clientes inativos há mais de 6 meses
        $this->checkInactiveCustomers();

        // Clientes com interações antigas
        $this->checkStaleInteractions();

        // Clientes com faturas vencidas
        $this->checkOverdueInvoices();
    }

    private function checkInactiveCustomers(): void
    {
        $inactiveCustomers = Customer::where('status', 'inactive')
            ->where('updated_at', '<', now()->subMonths(6))
            ->get();

        foreach ($inactiveCustomers as $customer) {
            $this->sendInactiveCustomerAlert($customer);
        }
    }

    private function checkStaleInteractions(): void
    {
        $customers = Customer::has('interactions')
            ->with('lastInteraction')
            ->get();

        foreach ($customers as $customer) {
            if ($customer->lastInteraction &&
                $customer->lastInteraction->interaction_date < now()->subMonths(3)) {
                $this->sendStaleInteractionAlert($customer);
            }
        }
    }
}
```

## 🚀 Implementação Gradual

### **Fase 1: Foundation**
- [ ] Implementar CustomerType enum
- [ ] Criar CustomerValidationService
- [ ] Implementar validações de CPF/CNPJ
- [ ] Definir CustomerStatus enum

### **Fase 2: Core Features**
- [ ] Implementar CustomerService básico
- [ ] Criar CustomerBudgetService
- [ ] Criar CustomerInvoiceService
- [ ] Implementar CustomerInteractionService

### **Fase 3: Advanced Features**
- [ ] Implementar CustomerTagService
- [ ] Criar CustomerMetricsService
- [ ] Implementar CustomerAlertService
- [ ] Sistema de histórico de alterações

### **Fase 4: Integration**
- [ ] Dashboard de gerenciamento de clientes
- [ ] Relatórios de análise de clientes
- [ ] Integração com sistemas de CRM externos
- [ ] Exportação de dados de clientes

## 📚 Documentação Relacionada

- [Customer Model](../../app/Models/Customer.php)
- [CommonData Model](../../app/Models/CommonData.php)
- [CustomerService](../../app/Services/Domain/CustomerService.php)
- [CustomerDTO](../../app/DTOs/Customer/CustomerDTO.php)
- [CustomerValidationService](../../app/Services/Domain/CustomerValidationService.php)

## 🎯 Benefícios

### **✅ Controle de Clientes**
- Gestão completa de clientes PF e PJ
- Validações rigorosas de documentos
- Controle de status e histórico
- Sistema de tags e classificação

### **✅ Integração Perfeita**
- Integração completa com orçamentos
- Integração completa com serviços
- Integração completa com faturas
- Histórico de interações detalhado

### **✅ Gestão de Relacionamento**
- Sistema de interações e follow-ups
- Classificação por tags e prioridades
- Alertas proativos para ações
- Métricas de relacionamento

### **✅ Tomada de Decisão**
- Dashboards com métricas de clientes
- Histórico de alterações para auditoria
- Relatórios de análise de clientes
- Identificação de oportunidades

---

**Última atualização:** 10/01/2026
**Versão:** 1.0.0
**Status:** ✅ Implementado e em uso
