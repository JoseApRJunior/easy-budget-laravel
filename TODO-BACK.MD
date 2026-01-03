# TODO - Backend

### 🔧 10. ENUM PATTERNS - Padrões para Uso de Enums

> **📚 Documentação Completa de Padrões para Enums**
>
> Baseado na implementação de **InvoiceStatus** e outros Enums do sistema
>
> ✅ **OBRIGATÓRIO:** Todos os novos Enums devem seguir estes padrões
>
> 🎯 **Objetivo:** Consistência, segurança de tipos e manutenibilidade

### 📋 Índice Rápido

1. [Estrutura Básica de Enum](#-estrutura-básica-de-enum)
2. [Métodos Úteis em Enums](#-métodos-úteis-em-enums)
3. [Uso em Controllers](#-uso-em-controllers)
4. [Uso em Views](#-uso-em-views)
5. [Validação com Enums](#-validação-com-enums)
6. [Case Sensitivity](#-case-sensitivity)
7. [Exemplos Práticos](#-exemplos-práticos)
8. [Padrão de Filtragem de Data](#-11-date-filtering-patterns---padrão-de-filtragem-de-data)
9. [Geração de Códigos Únicos](#-12-unique-code-generation-patterns)

---

### 🆔 12. UNIQUE CODE GENERATION PATTERNS

Para módulos que exigem códigos sequenciais únicos (Orçamentos, Serviços), utilizamos o padrão de reinício mensal com 6 dígitos sequenciais para garantir escalabilidade e organização.

#### Padrão de Formato:
`PREFIX-YYYY-MM-000000`

- **PREFIX:** 3 ou 4 letras identificadoras (ex: `ORC`, `SERV`)
- **YYYY:** Ano com 4 dígitos
- **MM:** Mês com 2 dígitos
- **000000:** Sequencial de 6 dígitos (reinicia a cada mês)

#### Exemplo de Implementação no Repository:
```php
public function generateUniqueCode(): string
{
    $year = date('Y');
    $month = date('m');
    $lastRecord = $this->getLastByMonth($year, $month);
    
    $sequential = 1;
    if ($lastRecord) {
        $sequential = $this->extractSequentialNumber($lastRecord->code) + 1;
    }
    
    return sprintf('PREFIX-%s-%s-%06d', $year, $month, $sequential);
}
```

#### Regras Importantes:
- **Tenant Isolation:** A busca pelo último registro deve sempre respeitar o `tenant_id`.
- **Monthly Reset:** O contador deve reiniciar em 1 no primeiro dia de cada mês.
- **Scalability:** O uso de 6 dígitos permite até 999.999 registros por mês por empresa.
- **Concurrency:** Em cenários de alta concorrência, utilizar `GET_LOCK()` do banco de dados para evitar duplicidade.

### 🏗️ Estrutura Básica de Enum

```php
<?php

namespace App\Enums;

enum InvoiceStatus: string
{
    case PENDING = 'pending';
    case PAID = 'paid';
    case CANCELLED = 'cancelled';
    case OVERDUE = 'overdue';
    case PARTIAL = 'partial';

    // Métodos úteis serão adicionados aqui
}
```

**Boas Práticas:**

-  ✅ Usar **UPPER_CASE** para nomes de casos
-  ✅ Usar **lowercase** para valores (backing values)
-  ✅ Sempre definir tipo de backing (string, int)
-  ✅ Manter consistência com valores no banco de dados
-  ❌ Evitar espaços ou caracteres especiais nos valores

---

### 🔧 Métodos Úteis em Enums

```php
// Método para obter todos os valores
public static function values(): array
{
    return array_column(self::cases(), 'value');
}

// Método para obter todas as opções para selects
public static function options(): array
{
    return array_combine(self::values(), self::labels());
}

// Método para obter labels legíveis
public static function labels(): array
{
    return [
        self::PENDING->value => 'Pendente',
        self::PAID->value => 'Pago',
        self::CANCELLED->value => 'Cancelado',
        self::OVERDUE->value => 'Vencido',
        self::PARTIAL->value => 'Parcial',
    ];
}

// Método para obter label de um valor específico
public static function label(string $value): string
{
    return self::labels()[$value] ?? $value;
}

// Método para verificar se um valor é válido
public static function isValid(string $value): bool
{
    return in_array($value, self::values());
}

// Método para obter cor associada ao status
public static function color(string $value): string
{
    $colors = [
        self::PENDING->value => 'warning',
        self::PAID->value => 'success',
        self::CANCELLED->value => 'danger',
        self::OVERDUE->value => 'danger',
        self::PARTIAL->value => 'info',
    ];

    return $colors[$value] ?? 'secondary';
}

// Método para obter ícone associado ao status
public static function icon(string $value): string
{
    $icons = [
        self::PENDING->value => 'bi-hourglass-split',
        self::PAID->value => 'bi-check-circle',
        self::CANCELLED->value => 'bi-x-circle',
        self::OVERDUE->value => 'bi-exclamation-triangle',
        self::PARTIAL->value => 'bi-cash-coin',
    ];

    return $icons[$value] ?? 'bi-question-circle';
}
```

---

### 🎯 Uso em Controllers

```php
// No controller - Exemplo de uso seguro com Enums

public function updateStatus(Invoice $invoice, Request $request)
{
    $validated = $request->validate([
        'status' => ['required', 'string', Rule::in(InvoiceStatus::values())],
    ]);

    $status = InvoiceStatus::from($validated['status']);

    $invoice->update(['status' => $status]);

    return redirect()->back()->with('success', 'Status atualizado com sucesso!');
}

// Exemplo com ServiceResult
public function getInvoicesByStatus(string $status): ServiceResult
{
    if (!InvoiceStatus::isValid($status)) {
        return $this->error('Status inválido', 400);
    }

    $invoices = Invoice::where('status', $status)
        ->where('tenant_id', tenant('id'))
        ->get();

    return $this->success($invoices);
}
```

---

### 🛡️ Validação com Enums

```php
// Em Form Requests
public function rules()
{
    return [
        'status' => ['required', 'string', Rule::in(InvoiceStatus::values())],
    ];
}

// Em controllers
$request->validate([
    'status' => ['required', 'string', Rule::in(InvoiceStatus::values())],
]);

// Validação manual
if (!InvoiceStatus::isValid($request->status)) {
    return back()->withErrors(['status' => 'Status inválido']);
}
```

---

### 🔤 Case Sensitivity

> **⚠️ IMPORTANTE:** PHP Enums são **case-sensitive** para os valores (backing values)

```php
// ❌ Isso causará erro:
InvoiceStatus::from('PENDING'); // Erro! Valor deve ser 'pending'

// ✅ Correto:
InvoiceStatus::from('pending'); // OK

// ✅ Melhor prática: Sempre usar o Enum diretamente
$status = InvoiceStatus::PENDING; // Melhor abordagem
$value = $status->value; // 'pending'

// ✅ Comparação segura:
if ($invoice->status === InvoiceStatus::PENDING->value) {
    // Faz algo
}

// ✅ Verificação de igualdade:
if (InvoiceStatus::isValid($someValue)) {
    $status = InvoiceStatus::from($someValue);
}
```

**Boas Práticas para Case Sensitivity:**

1. ✅ **Sempre usar o Enum diretamente** quando possível
2. ✅ **Validar valores de entrada** antes de converter para Enum
3. ✅ **Usar métodos helper** como `isValid()` para verificar valores
4. ✅ **Manter consistência** entre valores no banco e no Enum
5. ❌ **Nunca assumir** que valores de entrada são válidos
6. ❌ **Evitar comparações diretas** de strings sem validação

---

### 🛡️ 11. STATUS ENUM INTERFACE - Padronização de Status

> **✅ OBRIGATÓRIO:** Todo Enum de status (Budget, Service, Invoice, etc.) deve implementar a `StatusEnumInterface`.

#### Estrutura Recomendada
Utilize a trait `HasStatusEnumMethods` para implementar automaticamente os métodos da interface.

```php
namespace App\Enums;

use App\Contracts\Interfaces\StatusEnumInterface;
use App\Traits\Enums\HasStatusEnumMethods;

enum ServiceStatus: string implements StatusEnumInterface
{
    use HasStatusEnumMethods;

    case DRAFT = 'draft';
    // ... cases
}
```

#### Benefícios
- **getOptions():** Retorna opções prontas para o Select do Blade.
- **getMetadata():** Retorna cor, ícone e label para badges.
- **isFinished() / isActive():** Facilita lógica de negócio (ex: não editar se finalizado).

---

### 📅 12. DATE FILTERING & DTO PATTERNS - Normalização de Datas

#### Normalização no Controller (Filtros)
Datas vindas do Frontend (`DD/MM/AAAA`) devem ser convertidas para `Y-m-d` antes de consultas.

```php
use App\Helpers\DateHelper;

public function index(Request $request)
{
    $filters = $request->validate([
        'start_date' => 'nullable|string',
        'end_date' => 'nullable|string',
    ]);

    if (!empty($filters['start_date'])) {
        $filters['start_date'] = DateHelper::parseDate($filters['start_date']);
    }
    // ...
}
```

#### Normalização no DTO
Use `DateHelper::toCarbon()` ou `DateHelper::parseDate()` no `fromRequest`.

```php
public static function fromRequest(array $data): self
{
    return new self(
        // Para campos Carbon no DTO
        due_date: DateHelper::toCarbon($data['due_date'] ?? null),
        
        // Para campos string (Y-m-d) no DTO
        birth_date: DateHelper::parseDate($data['birth_date'] ?? null),
    );
}
```
protected function applyDateRangeFilter($query, $startDate, $endDate, $column = 'created_at')
{
    if ($startDate) {
        $query->where($column, '>=', Carbon::parse($startDate)->startOfDay());
    }
    if ($endDate) {
        $query->where($column, '<=', Carbon::parse($endDate)->endOfDay());
    }
}
```

#### Services e DTOs
- **Parsing:** Utilizar `DateHelper::parseBirthDate()` (ou similar) para converter strings vindas do frontend (`DD/MM/AAAA`) para o formato do banco (`Y-m-d`).
- **Validação:** Garantir que datas inválidas não quebrem a aplicação, tratando exceções de parsing no DTO ou Service.

#### Multi-tenancy
- **Isolamento:** Sempre garantir que filtros de data sejam aplicados *após* ou *junto* com o isolamento de tenant (`tenant_id`), preferencialmente usando o trait `TenantScoped` no Model.

---

### 🚀 12. CRUD PATTERNS - Padrões de CRUD (Ex: ServiceController)

#### Controllers
- **ServiceResult:** Sempre utilizar o padrão `ServiceResult` para retorno dos services.
- **Autorização:** Utilizar `$this->authorize()` para validar permissões via Policies.
- **Redirects:** Tratar sucessos e erros com mensagens de feedback amigáveis (`with('success', ...)` ou `with('error', ...)`).
- **Index/Filtros:** Seguir o padrão de passagem de filtros para o Service/Repository.

Exemplo de Index:
```php
public function index(Request $request): View
{
    $this->authorize('viewAny', Service::class);
    
    $filters = $request->only(['search', 'category', 'active', 'start_date', 'end_date', 'deleted', 'per_page']);
    $result = $this->serviceService->getPaginated($filters);
    
    return view('pages.service.index', [
        'services' => $result->getData(),
        'filters' => $filters,
        'categories' => $this->categoryService->listParents()->getData(),
    ]);
}
```

Exemplo de Store:
```php
public function store(ServiceStoreRequest $request): RedirectResponse
{
    $this->authorize('create', Service::class);
    $dto = ServiceDTO::fromRequest($request->validated());
    $result = $this->serviceService->create($dto);

    if ($result->isError()) {
        return redirect()->back()->withInput()->with('error', $result->getMessage());
    }

    return redirect()->route('provider.services.show', $result->getData()->code)
        ->with('success', 'Criado com sucesso!');
}
```

#### Services
- **DTOs:** Utilizar DTOs para transferência de dados entre Controller e Service.
- **Transações:** Utilizar `DB::transaction()` para garantir integridade em operações complexas.
- **Execução Segura:** Utilizar o método `safeExecute` (da `AbstractBaseService`) para capturar exceções e retornar `ServiceResult`.

#### Repositories
- **Filtros de Data:** Utilizar `applyDateRangeFilter` da `RepositoryFiltersTrait`.
- **Tenant:** Garantir que todas as consultas respeitem o `tenant_id` (via `AbstractTenantRepository` ou Trait `TenantScoped`).

---

### ⚠️ Erros Comuns e Soluções

#### Erro 1: Valor vazio para Enum

```php
// ❌ Causa erro:
$status = InvoiceStatus::from(''); // ValueError: "" is not a valid backing value

// ✅ Solução:
if (!empty($value) && InvoiceStatus::isValid($value)) {
    $status = InvoiceStatus::from($value);
}
```

#### Erro 2: Case sensitivity

```php
// ❌ Causa erro:
$status = InvoiceStatus::from('PENDING'); // Erro! Deve ser 'pending'

// ✅ Solução:
$status = InvoiceStatus::from(strtolower($input)); // Se necessário converter
// Ou melhor:
$status = InvoiceStatus::PENDING; // Usar o Enum diretamente
```

#### Erro 3: Valor não válido

```php
// ❌ Causa erro:
$status = InvoiceStatus::from('invalid_status');

// ✅ Solução:
if (InvoiceStatus::isValid($value)) {
    $status = InvoiceStatus::from($value);
} else {
    // Tratar erro ou usar valor padrão
    $status = InvoiceStatus::PENDING;
}
```

---

### 🎯 Checklist para Uso de Enums

-  [ ] Definir Enum com backing type adequado (string/int)
-  [ ] Implementar métodos helper (values, options, labels, etc.)
-  [ ] Validar entradas de usuário antes de converter para Enum
-  [ ] Usar Enum diretamente sempre que possível
-  [ ] Implementar verificações de null/empty antes de usar
-  [ ] Documentar todos os casos de uso do Enum
-  [ ] Testar todos os valores do Enum
-  [ ] Manter consistência entre banco de dados e Enum

---

### 📚 Referência Rápida

```php
// Obter todos os valores
InvoiceStatus::values();

// Obter opções para select
InvoiceStatus::options();

// Obter label legível
InvoiceStatus::label('pending'); // "Pendente"

// Verificar se valor é válido
InvoiceStatus::isValid('pending'); // true

// Obter cor para badge
InvoiceStatus::color('pending'); // "warning"

// Obter ícone
InvoiceStatus::icon('pending'); // "bi-hourglass-split"

// Usar Enum diretamente (melhor prática)
$status = InvoiceStatus::PENDING;
$value = $status->value; // "pending"
```

### 📅 11. DATE FILTERING PATTERN - Padrão de Filtro de Datas

Para garantir precisão em consultas de datas, especialmente em colunas `datetime` e `timestamp`, utilize sempre o intervalo completo do dia.

**No Service/Repository:**
```php
// ✅ CORRETO: Utilizar intervalo completo (00:00:00 até 23:59:59)
if ($startDate) {
    $query->where('created_at', '>=', $startDate . ' 00:00:00');
}
if ($endDate) {
    $query->where('created_at', '<=', $endDate . ' 23:59:59');
}

// ❌ EVITAR: whereDate pode ser impreciso em alguns bancos ou ignorar componentes de tempo
$query->whereDate('created_at', '>=', $startDate);
```

**Normalização de Datas (DateHelper):**
Sempre utilize o `DateHelper` para normalizar datas vindas do frontend (DD/MM/YYYY) para o formato do banco (YYYY-MM-DD).

```php
$startDate = DateHelper::parseBirthDate($filters['start_date'] ?? null);
```

---

### 📋 Checklist de Implementação (Backend)
- [ ] Usar Service Layer e Actions.
- [ ] Tipagem estrita (`declare(strict_types=1)`).
- [ ] Tratamento de erros com `ServiceResult`.
- [ ] Filtros de data usando intervalo completo (00:00:00 - 23:59:59).
- [ ] Uso de DTOs para transferência de dados complexos.

---

## 📊 ESTATÍSTICAS FINAIS

-  **Arquivos analisados:** 25+ arquivos de views
-  **Problemas identificados:** 15 inconsistências críticas
-  **Arquivos corrigidos:** 15 arquivos principais
-  **Conformidade final:**
   -  Schedule: 98% ✅
   -  Reports: 98% ✅
   -  Modules: 98% ✅
   -  Admin Views: 95% ✅
-  **Relatórios gerados:** 4 relatórios detalhados
-  **Tempo investido:** 8+ horas de análise e implementação

## 🎯 RESULTADOS ALCANÇADOS

### Conformidade de Padrões

-  **98% de conformidade** nos módulos principais (Schedule, Reports, Modules)
-  **95% de conformidade** nas views administrativas
-  **100% das URLs** convertidas para helpers Laravel
-  **100% da navegação** funcionando corretamente

### Melhorias Implementadas

-  **Interface padronizada** em todos os módulos
-  **Responsividade completa** para dispositivos móveis
-  **Performance otimizada** com paginação adequada
-  **UX consistente** em todo o sistema

### Documentação Produzida

-  **4 relatórios detalhados** de análise e correções
-  **TODO.md atualizado** com status do projeto
-  **Padrões documentados** para futuras implementações

---

---

## 👥 Refatoração do Módulo de Clientes (CONCLUÍDO)

### Objetivo

Modernizar o módulo de clientes para utilizar os novos padrões arquiteturais do sistema: DTOs, ServiceResult, Service Layer e tipagem estrita.

### Tarefas Concluídas

#### 1. Estrutura de DTOs
- [x] Criar `CustomerDTO` para transferência de dados de criação e atualização.
- [x] Criar `CustomerFilterDTO` para padronizar filtros de busca e listagem.
- [x] Criar `CustomerInteractionDTO` para registro de interações.
- [x] Criar `AddressDTO` e `ContactDTO` para sub-entidades.

#### 2. Service Layer
- [x] Refatorar `CustomerService` para usar DTOs e retornar `ServiceResult`.
- [x] Implementar `AddressService` e `ContactService` seguindo o padrão.
- [x] Integrar `CustomerInteractionService` com os novos padrões.
- [x] Implementar métodos de busca avançada e estatísticas no Service.

#### 3. Controllers
- [x] Refatorar `CustomerController` (Web) para usar DTOs e `ServiceResult`.
- [x] Refatorar `CustomerApiController` (API) para usar DTOs e `ServiceResult`.
- [x] Padronizar retornos JSON usando os helpers da classe base `Controller`.
- [x] Garantir que todas as permissões sejam validadas via Policies.

#### 4. Repositories
- [x] Garantir que `CustomerRepository` suporte operações baseadas em DTO.
- [x] Validar isolamento de tenant em todas as consultas.
- [x] Implementar filtros de data padronizados.

---

## 🔄 Sistema de Reservas de Estoque (PENDENTE)

### Objetivo

Implementar sistema completo de reservas de estoque para controlar produtos reservados vs disponíveis.

### Tarefas

#### 1. Estrutura de Banco de Dados

-  [ ] Criar migration para tabela `inventory_reservations`
   -  Campos: `id`, `tenant_id`, `product_id`, `quantity`, `reserved_by_type`, `reserved_by_id`, `status`, `expires_at`, `created_at`, `updated_at`
-  [ ] Adicionar campo `reserved_quantity` na tabela `inventories`
-  [ ] Criar índices para performance (product_id, tenant_id, status, expires_at)

#### 2. Models e Relacionamentos

-  [ ] Criar model `InventoryReservation`
-  [ ] Adicionar relacionamentos em `Product` e `Inventory`
-  [ ] Implementar scopes (active, expired, byProduct)

#### 3. Lógica de Negócio

-  [ ] Atualizar `InventoryService::reserveProduct()` com lógica real
   -  Validar estoque disponível (quantity - reserved_quantity)
   -  Criar registro em inventory_reservations
   -  Incrementar reserved_quantity
-  [ ] Atualizar `InventoryService::releaseReservation()` com lógica real
   -  Marcar reserva como liberada
   -  Decrementar reserved_quantity
-  [ ] Criar método `InventoryService::getAvailableStock()` (quantity - reserved_quantity)

#### 4. Expiração de Reservas

-  [ ] Criar job `ExpireInventoryReservations`
-  [ ] Agendar job no Kernel (rodar a cada hora)
-  [ ] Implementar lógica de expiração automática
-  [ ] Notificar quando reserva expirar

#### 5. Testes

-  [ ] Testes unitários para InventoryService
-  [ ] Testes de integração para fluxo completo
-  [ ] Testes de expiração de reservas

#### 6. Documentação

-  [ ] Documentar fluxo de reservas
-  [ ] Atualizar diagramas de banco de dados
-  [ ] Criar guia de uso para desenvolvedores

### Prioridade

**Média** - Sistema funciona sem reservas reais, mas implementação futura melhora controle de estoque.

### Estimativa

**8-12 horas** de desenvolvimento + testes

---

## 📝 NOTAS FINAIS

Retire os botões "Exportar Todos" e "Atualizar" da tela de relatórios. Esta interface funciona como ponto central de acesso aos relatórios dos módulos, diferindo de outros índices. Priorize melhorias visuais mantendo os padrões do sistema.
