## Objetivos
- Implementar endpoints AJAX para filtros de budgets, services, customers, products e invoices, além de busca de CEP via BrasilAPI.

## Escopo
- Controller: `App/Http/Controllers/Api/AjaxController` com métodos `cep`, `budgetsFilter`, `servicesFilter`, `customersSearch`, `productsSearch`, `invoicesFilter`.
- Rotas: adicionar grupo `api/ajax` sob middleware `auth` em `routes/api.php`.
- Integração: usar serviços já existentes (BudgetService, ServiceService, CustomerService, ProductService, InvoiceService) e `Http` client para CEP.

## Implementação Detalhada
- `cep`: valida `cep`; chama BrasilAPI (`GET https://brasilapi.com.br/api/cep/v1/{cep}`); retorna dados relevantes.
- `budgetsFilter`: utiliza `BudgetService->getBudgetsForProvider(auth()->id(), $filters)` com mapeamento de filtros.
- `servicesFilter`: `ServiceService->getFilteredServices($filters)`.
- `customersSearch`: `CustomerService->getFilteredCustomers($filters, tenant_id)`.
- `productsSearch`: `ProductService->getFilteredProducts($filters)`.
- `invoicesFilter`: `InvoiceService->getFilteredInvoices($filters)`.

## Testes e Validação
- Verificar JSON padronizado (`success`, `data`, `message`).
- Exercitar filtros típicos: `search`, `status`, `date_from/date_to`, ranges numéricos.
- CEP: retornar falhas quando formato inválido ou API indisponível.

## Critérios de Aceitação
- Endpoints respondem com paginação quando aplicável.
- Integração segura com BrasilAPI e multi-tenant aplicado nos serviços.
- Consistência com os padrões de controllers/serviços já adotados.

## Observações
- Reutilizar serviços existentes para manter regras de negócio e isolamento de tenant.
- Evitar criação de serviços adicionais; usar `Http` client nativo para CEP.