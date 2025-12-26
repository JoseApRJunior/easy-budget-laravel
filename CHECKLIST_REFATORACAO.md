# Checklist de Refatoração - Arquitetura Multi-tenant e DTOs

Este documento consolida os checklists de refatoração identificados durante as sessões de desenvolvimento para garantir a consistência da arquitetura, isolamento de dados e segurança tipográfica.

## 1. Isolamento Multi-tenant (Global Scope)
- [ ] **Remover `tenant_id` das Camadas de Serviço**: O `tenant_id` não deve ser passado como parâmetro para métodos de busca ou criação nos Services.
- [ ] **Modelos Scoped**: Garantir que todos os modelos específicos de tenant utilizem a trait `TenantScoped`.
- [ ] **Repositórios Abstratos**: Todos os repositórios de entidades tenant-aware devem estender `AbstractTenantRepository`.
- [ ] **Uso de `newQuery()`**: Sempre iniciar consultas nos repositórios com `$this->model->newQuery()` para garantir a aplicação automática do Global Scope.
- [ ] **Validação de Isolamento**: Revisar rotas críticas para garantir que não haja vazamento de dados entre diferentes tenants.

## 2. Padronização de DTOs (Data Transfer Objects)
- [ ] **Método `fromRequest()`**: Implementar o método estático `fromRequest(array $data)` em todos os DTOs para centralizar o parsing e casting.
- [ ] **Tratamento de Datas**: Utilizar `DateHelper::parseBirthDate()` (ou similar) dentro dos DTOs para converter datas do formato brasileiro (DD/MM/YYYY) para o formato de banco (Y-m-d).
- [ ] **Tipagem Forte**: Substituir o uso de arrays associativos por DTOs em transferências entre Service e Repository (ex: `InvoiceItemDTO`, `BudgetItemDTO`).
- [ ] **Métodos de Repositório**: Garantir que os repositórios possuam métodos `createFromDTO()` e `updateFromDTO()`.

## 3. Camada de Serviço (Domain & Application)
- [ ] **Wrapper `safeExecute()`**: Utilizar o wrapper `safeExecute` para padronizar o tratamento de exceções e logs.
- [ ] **Retorno `ServiceResult`**: Todas as operações de negócio devem retornar uma instância de `ServiceResult`.
- [ ] **Transações**: Utilizar `DB::transaction` dentro do `safeExecute` para operações que envolvem múltiplas tabelas.
- [ ] **Auditoria**: Garantir que as operações de escrita chamem o `AuditLogService`.

## 4. Repositórios e Filtros
- [ ] **`RepositoryFiltersTrait`**: Utilizar a trait para padronizar a aplicação de filtros (`applyFilters`) e ordenação (`applyOrderBy`).
- [ ] **Paginação Padronizada**: Utilizar o método `getPaginated()` do `AbstractTenantRepository` sempre que possível.
- [ ] **Soft Deletes**: Garantir que o filtro `applySoftDeleteFilter` seja considerado em listagens paginadas.

## Status por Módulo (Progresso Atual)
- [x] **Provider Management**: Refatorado para remover `tenant_id` e usar `BusinessDataDTO`.
- [x] **Invoices**: Refatorado para usar `InvoiceItemDTO`.
- [ ] **Customers**: Em progresso (necessário padronizar `CustomerService` e `CustomerRepository`).
- [ ] **Budgets**: Pendente revisão de DTOs e isolamento.
- [ ] **Plan Subscriptions**: Pendente refatoração para `safeExecute` e métodos DTO.
- [ ] **Inventory/Products**: Pendente validação de DTOs.
