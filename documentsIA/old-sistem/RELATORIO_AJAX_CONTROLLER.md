# Relatório de Análise: AjaxController

## 📋 Informações Gerais

**Controller:** `AjaxController`  
**Namespace Old System:** `app\controllers`  
**Tipo:** Controller de API/AJAX  
**Propósito:** Endpoints AJAX para busca e filtros dinâmicos

---

## 🎯 Funcionalidades Identificadas

### 1. **buscarCep()**
- **Descrição:** Busca informações de endereço via CEP usando BrasilAPI
- **Método HTTP:** GET/POST
- **Parâmetros:** `cep` (via request)
- **Retorno:** JSON com dados do endereço
- **Dependências:**
  - `BrasilApi\Client`
  - Request parameter: `cep`

### 2. **budgets_filter()**
- **Descrição:** Filtra orçamentos para relatórios
- **Método HTTP:** POST
- **Parâmetros:** Filtros diversos via request
- **Retorno:** JSON com lista de orçamentos filtrados
- **Dependências:**
  - `Budget` model
  - `Budget->getBudgetsByFilterReport()`
  - `tenant_id` do usuário autenticado

### 3. **services_filter()**
- **Descrição:** Filtra serviços para relatórios
- **Método HTTP:** POST
- **Parâmetros:** Filtros diversos via request
- **Retorno:** JSON com lista de serviços filtrados
- **Dependências:**
  - `Service` model
  - `Service->getServicesByFilterReport()`
  - `tenant_id` do usuário autenticado

### 4. **customerSearch()**
- **Descrição:** Busca clientes com filtros
- **Método HTTP:** POST
- **Parâmetros:** Filtros diversos via request
- **Retorno:** JSON com lista de clientes
- **Dependências:**
  - `Customer` model
  - `Customer->getCustomersByFilter()`
  - `tenant_id` do usuário autenticado

### 5. **productSearch()**
- **Descrição:** Busca produtos com filtros
- **Método HTTP:** POST
- **Parâmetros:** Filtros diversos via request
- **Retorno:** JSON com lista de produtos
- **Dependências:**
  - `Product` model
  - `Product->getProductsByFilterReport()`
  - `tenant_id` do usuário autenticado

### 6. **invoices_filter()**
- **Descrição:** Filtra faturas para relatórios
- **Método HTTP:** POST
- **Parâmetros:** Filtros diversos via request
- **Retorno:** JSON com lista de faturas filtradas
- **Dependências:**
  - `Invoice` model
  - `Invoice->getInvoicesByFilter()`
  - `tenant_id` do usuário autenticado

---

## 🔗 Dependências do Sistema Antigo

### Models Utilizados
- `Budget`
- `Customer`
- `Invoice`
- `Product`
- `Service`

### Bibliotecas Externas
- `BrasilApi\Client` - API de consulta de CEP

### Métodos de Models Chamados
- `Budget->getBudgetsByFilterReport($data, $tenant_id)`
- `Service->getServicesByFilterReport($data, $tenant_id)`
- `Customer->getCustomersByFilter($data, $tenant_id)`
- `Product->getProductsByFilterReport($data, $tenant_id)`
- `Invoice->getInvoicesByFilter($data, $tenant_id)`

---

## 🏗️ Implementação no Novo Sistema Laravel

### Estrutura Proposta

```
app/Http/Controllers/
└── AjaxController.php (API Controller)

app/Services/
├── CepService.php (busca CEP)
└── FilterService.php (filtros genéricos)

routes/
└── api.php (rotas AJAX)
```

### Rotas Sugeridas

```php
// routes/api.php
Route::middleware(['auth:sanctum', 'tenant'])->prefix('ajax')->group(function () {
    Route::post('/cep', [AjaxController::class, 'buscarCep']);
    Route::post('/budgets/filter', [AjaxController::class, 'budgetsFilter']);
    Route::post('/services/filter', [AjaxController::class, 'servicesFilter']);
    Route::post('/customers/search', [AjaxController::class, 'customerSearch']);
    Route::post('/products/search', [AjaxController::class, 'productSearch']);
    Route::post('/invoices/filter', [AjaxController::class, 'invoicesFilter']);
});
```

### Services Necessários

1. **CepService** - Integração com BrasilAPI
2. **BudgetFilterService** - Filtros de orçamentos
3. **ServiceFilterService** - Filtros de serviços
4. **CustomerFilterService** - Busca de clientes
5. **ProductFilterService** - Busca de produtos
6. **InvoiceFilterService** - Filtros de faturas

### Repositories Necessários

- `BudgetRepository` (já existe)
- `ServiceRepository` (já existe)
- `CustomerRepository` (já existe)
- `ProductRepository` (já existe)
- `InvoiceRepository` (já existe)

---

## 📝 Padrão de Implementação

### Controller Pattern: API Controller

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Infrastructure\CepService;
use App\Services\Domain\BudgetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AjaxController extends Controller
{
    public function __construct(
        private CepService $cepService,
        private BudgetService $budgetService,
        // outros services...
    ) {}

    public function buscarCep(Request $request): JsonResponse
    {
        $validated = $request->validate(['cep' => 'required|string|size:8']);
        
        $result = $this->cepService->buscar($validated['cep']);
        
        return $result->isSuccess()
            ? response()->json($result->data)
            : response()->json(['error' => $result->message], 400);
    }

    public function budgetsFilter(Request $request): JsonResponse
    {
        $filters = $request->all();
        $result = $this->budgetService->filterForReport($filters);
        
        return response()->json($result->data);
    }
}
```

---

## ✅ Checklist de Implementação

### Fase 1: Infraestrutura
- [ ] Criar `CepService` com integração BrasilAPI
- [ ] Criar métodos de filtro nos repositories existentes
- [ ] Adicionar validação de requests

### Fase 2: Controller
- [ ] Criar `AjaxController` com padrão API
- [ ] Implementar método `buscarCep()`
- [ ] Implementar método `budgetsFilter()`
- [ ] Implementar método `servicesFilter()`
- [ ] Implementar método `customerSearch()`
- [ ] Implementar método `productSearch()`
- [ ] Implementar método `invoicesFilter()`

### Fase 3: Rotas e Middleware
- [ ] Configurar rotas em `routes/api.php`
- [ ] Aplicar middleware `auth:sanctum`
- [ ] Aplicar middleware `tenant`
- [ ] Configurar rate limiting

### Fase 4: Testes
- [ ] Testes unitários para `CepService`
- [ ] Testes de feature para cada endpoint
- [ ] Testes de integração com tenant

---

## 🔒 Considerações de Segurança

1. **Autenticação:** Todos os endpoints requerem autenticação via Sanctum
2. **Tenant Scoping:** Filtros automáticos por `tenant_id`
3. **Validação:** Validar todos os inputs antes de processar
4. **Rate Limiting:** Limitar requisições por minuto
5. **CORS:** Configurar CORS adequadamente

---

## 📊 Prioridade de Implementação

**Prioridade:** MÉDIA  
**Complexidade:** BAIXA  
**Dependências:** CepService, Repositories com métodos de filtro

**Ordem Sugerida:**
1. CepService (independente)
2. Métodos de filtro nos repositories
3. AjaxController com endpoints
4. Testes

---

## 💡 Melhorias Sugeridas

1. **Cache:** Cachear resultados de CEP por 24h
2. **Paginação:** Adicionar paginação nos filtros
3. **Ordenação:** Permitir ordenação customizada
4. **Export:** Adicionar opção de exportar resultados
5. **Debounce:** Implementar debounce no frontend
6. **Logs:** Registrar buscas para analytics
