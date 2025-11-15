## Objetivos
- Implementar criação de faturas a partir de orçamentos, incluindo fatura parcial, validação de saldo e geração de código único.

## Escopo
- Controller: ampliar `InvoiceController` com fluxo "store from budget" (mantendo `createFromBudget` existente).
- Service: adicionar métodos em `InvoiceService` para criar a fatura a partir do orçamento e validar saldo.
- Repository: utilitário para somar total já faturado por orçamento/serviço.
- Views: criar `resources/views/invoices/create-from-budget.blade.php`.
- Request: `InvoiceStoreFromBudgetRequest` para validar seleção de itens do orçamento.

## Implementação Detalhada
- InvoiceService
  - `createInvoiceFromBudget(string $budgetCode, array $payload)`: valida orçamento e cliente; mapeia itens do orçamento para itens da fatura; usa `generateUniqueInvoiceCode`; cria fatura e itens em transação.
  - `createPartialInvoiceFromBudget(string $budgetCode, array $selectedItems)`: valida itens e calcula total selecionado; compara com saldo restante; cria fatura com itens selecionados.
  - `getBudgetBilledTotals(int $budgetId)`: soma valores de faturas associadas ao orçamento (ignora canceladas/overdue conforme regra); retorna total faturado e saldo.
- InvoiceRepository
  - `sumTotalByBudgetId(int $budgetId, ?array $statusFilter = ['paid','pending','in_process'])`: agregação para total faturado.
- Controller
  - `storeFromBudget(Budget $budget, InvoiceStoreFromBudgetRequest $request)`: chama `createPartialInvoiceFromBudget`; redireciona para `invoices.show`.
- Request
  - `InvoiceStoreFromBudgetRequest`: campos `issue_date`, `due_date`, `status`, `items` onde cada item referencia `service_item_id`, `quantity`, `unit_value` (permite parcial); validações numéricas mínimas; garante pelo menos um item.
- Views
  - `invoices/create-from-budget.blade.php`: tabela com itens do orçamento (serviceItems) com check e quantidade; mostra total, saldo restante e validações client-side simples; mantém layout padrão.

## Regras de Negócio
- Saldo: `saldo = total_orcamento - total_faturado` (considerando apenas faturas válidas); `total_selecionado <= saldo`.
- Status permitido para faturar: orçamento `APPROVED` ou similar; impedir se `CANCELLED`.
- Código de fatura: usar `generateUniqueInvoiceCode(service_code)` já existente.

## Testes e Validação
- Unit (Service): criação completa e parcial; erro ao exceder saldo; cálculo de total faturado; geração de código sequencial.
- Integração (Controller): POST `storeFromBudget` com seleção de itens; redireciona e persiste itens; valida mensagens de erro.
- Views: renderização com orçamento e itens; exibição de saldo e totais; submissão bem-sucedida.

## Critérios de Aceitação
- Criar fatura completa/parcial a partir de orçamento com itens válidos.
- Bloquear criação quando exceder saldo.
- Gerar código único por serviço com sequência `SERVICECODE-INV###`.
- Exibir e usar a nova página `create-from-budget`.

## Observações
- Reutilizar `InvoiceStoreRequest` onde possível; o novo request foca nos itens vindos do orçamento.
- Seguir arquitetura e padrões já usados (ServiceResult, transações, multi-tenant).