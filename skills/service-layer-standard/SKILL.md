# 🛠️ Skill: Service Layer Standard

**Descrição:** Garante que Services do Easy Budget sigam o padrão com ServiceResult e separação de camadas.

**Categoria:** Arquitetura de Serviços
**Complexidade:** Média
**Status:** ✅ Implementado e Documentado

## 🎯 Objetivo

Padronizar a arquitetura de Services no Easy Budget Laravel, garantindo consistência, testabilidade e manutenibilidade através do uso do ServiceResult e separação clara de responsabilidades.

## 📋 Requisitos Técnicos

### **✅ ServiceResult Pattern**

Todos os Services devem retornar instâncias de `ServiceResult`:

```php
// ❌ Errado
public function create(array $data)
{
    return $this->repository->create($data);
}

// ✅ Correto
public function create(array $data): ServiceResult
{
    return $this->repository->create($data);
}
```

### **✅ Separação de Camadas**

- **Domain Services:** Regras de negócio específicas da entidade
- **Application Services:** Orquestração de workflows complexos
- **Infrastructure Services:** Integrações externas (APIs, e-mail, cache)

### **✅ Tratamento de Erros**

```php
public function create(array $data): ServiceResult
{
    try {
        // Validação
        $validation = $this->validate($data);
        if (!$validation->isSuccess()) {
            return $validation;
        }

        // Regras de negócio
        $businessRules = $this->validateBusinessRules($data);
        if (!$businessRules->isSuccess()) {
            return $businessRules;
        }

        // Operação
        return $this->repository->create($data);
    } catch (Exception $e) {
        return $this->error('Erro ao criar registro', OperationStatus::INTERNAL_ERROR, $e);
    }
}
```

## 🏗️ Estrutura Padrão

### **📁 Organização de Diretórios**

```
app/Services/
├── Domain/                    # Regras de negócio específicas
│   ├── BudgetService.php
│   ├── CustomerService.php
│   └── ProductService.php
├── Application/               # Orquestração de workflows
│   ├── BudgetWorkflowService.php
│   └── CustomerManagementService.php
├── Infrastructure/            # Integrações externas
│   ├── EmailService.php
│   ├── PaymentService.php
│   └── CacheService.php
└── Core/                      # Abstrações e contratos
    ├── Abstracts/
    └── Contracts/
```

### **🔧 AbstractBaseService**

Todos os Services devem estender `AbstractBaseService`:

```php
abstract class AbstractBaseService
{
    protected function success($data, string $message = ''): ServiceResult
    protected function error(string $message, OperationStatus $status, ?Exception $exception = null): ServiceResult
    protected function validate(array $data, bool $isUpdate = false): ServiceResult
}
```

## 📝 Padrões de Implementação

### **1. Domain Services**

```php
class BudgetService extends AbstractBaseService
{
    public function create(array $data): ServiceResult
    {
        // Validação de dados
        $validation = $this->validate($data);
        if (!$validation->isSuccess()) {
            return $validation;
        }

        // Regras de negócio específicas
        if ($data['total_value'] <= 0) {
            return $this->error('Valor total deve ser maior que zero', OperationStatus::INVALID_DATA);
        }

        // Criação no repository
        return $this->repository->create($data);
    }
}
```

### **2. Application Services**

```php
class BudgetWorkflowService extends AbstractBaseService
{
    public function createCompleteBudget(array $budgetData, array $itemsData): ServiceResult
    {
        return $this->safeExecute(function() use ($budgetData, $itemsData) {
            // 1. Criar orçamento
            $budgetResult = $this->budgetService->create($budgetData);
            if (!$budgetResult->isSuccess()) {
                return $budgetResult;
            }

            $budget = $budgetResult->getData();

            // 2. Criar itens
            foreach ($itemsData as $item) {
                $itemResult = $this->budgetItemService->create([
                    'budget_id' => $budget->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price']
                ]);

                if (!$itemResult->isSuccess()) {
                    return $itemResult;
                }
            }

            return $this->success($budget, 'Orçamento criado com sucesso');
        });
    }
}
```

### **3. Infrastructure Services**

```php
class EmailService extends AbstractBaseService
{
    public function sendBudgetNotification(Budget $budget, string $email): ServiceResult
    {
        try {
            // 1. Renderizar template
            $html = $this->renderTemplate('budget_notification', [
                'budget' => $budget,
                'customer' => $budget->customer
            ]);

            // 2. Enviar e-mail
            $this->mailer->send([
                'to' => $email,
                'subject' => 'Novo orçamento disponível',
                'html' => $html
            ]);

            return $this->success(null, 'E-mail enviado com sucesso');
        } catch (Exception $e) {
            return $this->error('Falha ao enviar e-mail', OperationStatus::INTERNAL_ERROR, $e);
        }
    }
}
```

## 🔍 Validações Obrigatórias

### **✅ Validação de Dados**

```php
protected function validate(array $data, bool $isUpdate = false): ServiceResult
{
    $rules = $this->getValidationRules($isUpdate);
    $validator = Validator::make($data, $rules);

    if ($validator->fails()) {
        return $this->error(
            'Dados inválidos',
            OperationStatus::INVALID_DATA,
            null,
            $validator->errors()->toArray()
        );
    }

    return $this->success(null, 'Validação bem-sucedida');
}
```

### **✅ Validação de Regras de Negócio**

```php
protected function validateBusinessRules(array $data): ServiceResult
{
    // Regras específicas do domínio
    if (isset($data['customer_id'])) {
        $customer = $this->customerRepository->findById($data['customer_id']);
        if (!$customer || $customer->status !== 'active') {
            return $this->error('Cliente inativo ou não encontrado', OperationStatus::INVALID_DATA);
        }
    }

    return $this->success(null, 'Regras de negócio validadas');
}
```

## 🧪 Testes e Qualidade

### **✅ Testes Unitários**

```php
public function testCreateBudgetWithValidData()
{
    $data = [
        'customer_id' => 1,
        'total_value' => 100.00,
        'description' => 'Test budget'
    ];

    $result = $this->budgetService->create($data);

    $this->assertTrue($result->isSuccess());
    $this->assertInstanceOf(Budget::class, $result->getData());
}

public function testCreateBudgetWithInvalidData()
{
    $data = [
        'customer_id' => 1,
        'total_value' => -100.00, // Valor negativo
        'description' => 'Test budget'
    ];

    $result = $this->budgetService->create($data);

    $this->assertFalse($result->isSuccess());
    $this->assertEquals(OperationStatus::INVALID_DATA, $result->getStatus());
}
```

### **✅ Cobertura de Testes**

- **Mínimo 80%** de cobertura de código
- **Testes unitários** para todos os métodos públicos
- **Testes de integração** para workflows complexos
- **Testes de validação** para cenários de erro

## 📊 Métricas de Qualidade

### **✅ Performance**

- **Response time** < 200ms para operações simples
- **Memory usage** monitorado e otimizado
- **Cache strategy** implementada para operações frequentes

### **✅ Manutenibilidade**

- **Complexidade ciclomática** < 10 por método
- **Número de linhas** < 50 por método
- **Número de parâmetros** < 5 por método

### **✅ Testabilidade**

- **Dependency injection** para todos os serviços externos
- **Interfaces** para serviços que precisam de mock
- **ServiceResult** padronizado para fácil verificação

## 🔧 Ferramentas de Desenvolvimento

### **✅ PHPStan**

```php
// Configuração recomendada
return [
    'level' => 8,
    'paths' => ['app/Services'],
    'ignoreErrors' => [
        '#ServiceResult#',
    ],
];
```

### **✅ Laravel Pint**

```json
{
    "preset": "psr12",
    "rules": {
        "array_syntax": {
            "syntax": "short"
        },
        "ordered_imports": true
    }
}
```

## 🚀 Implementação Gradual

### **Fase 1: Foundation**
- [ ] Criar AbstractBaseService
- [ ] Definir ServiceResult padrão
- [ ] Criar contratos básicos

### **Fase 2: Domain Services**
- [ ] Refatorar BudgetService
- [ ] Refatorar CustomerService
- [ ] Refatorar ProductService

### **Fase 3: Application Services**
- [ ] Criar BudgetWorkflowService
- [ ] Criar CustomerManagementService
- [ ] Criar InventoryWorkflowService

### **Fase 4: Infrastructure Services**
- [ ] Criar EmailService
- [ ] Criar PaymentService
- [ ] Criar CacheService

## 📚 Documentação Relacionada

- [Service Pattern](../../DesignPatterns/Services/ServicePattern.php)
- [Service Templates](../../DesignPatterns/Services/ServiceTemplates.php)
- [AbstractBaseService](../../app/Services/Core/Abstracts/AbstractBaseService.php)
- [ServiceResult](../../app/Support/ServiceResult.php)

## 🎯 Benefícios

### **✅ Consistência**
- Todos os Services seguem o mesmo padrão
- Resposta padronizada em toda aplicação
- Tratamento de erro uniforme

### **✅ Testabilidade**
- ServiceResult facilita testes unitários
- Dependency injection para mocks
- Isolamento de lógica de negócio

### **✅ Manutenibilidade**
- Código familiar para todos os desenvolvedores
- Separação clara de responsabilidades
- Fácil identificação de problemas

### **✅ Escalabilidade**
- Arquitetura preparada para crescimento
- Fácil adição de novos Services
- Reutilização de lógica entre Services

---

**Última atualização:** 10/01/2026
**Versão:** 1.0.0
**Status:** ✅ Implementado e em uso
