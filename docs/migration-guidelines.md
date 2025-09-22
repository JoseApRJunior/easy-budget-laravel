# Guia de Melhores Práticas para Migrações em Multi-Tenancy com UUID no Easy-Budget Laravel

Este documento estabelece padrões para criação e revisão de migrações no projeto Easy-Budget Laravel, que utiliza multi-tenancy com UUIDs para `tenants.id` (primary key string(36)). Baseado nas correções recentes de incompatibilidades de foreign keys, remoção de duplicatas e testes de integridade (ver `MigrationIntegrityTest.php`), este guia visa padronizar implementações futuras, prevenir regressões e garantir isolamento de dados por tenant.

## 1. Padrão para Foreign Keys de tenant_id

Sempre defina `tenant_id` como `string('tenant_id', 36)` para compatibilidade com UUIDs de `tenants.id`. Inclua constraint explícita com `foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete()` para ações em cascata no delete do tenant.

**Exemplo Correto:**

```php
Schema::create('budgets', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('tenant_id', 36);
    $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
    // outros campos...
});
```

**Referência:** Migração corrigida em `2024_01_01_000008_create_area_of_activities_table.php`, que ajustou `tenant_id` para string(36) com constraint.

## 2. Tabelas Tenant-Scoped vs Globais

-  **Tabelas Globais (sem tenant_id):** Usadas para dados compartilhados, como planos de assinatura (ex: `plans`). Não incluem `tenant_id` nem constraints relacionadas.
-  **Tabelas Tenant-Scoped:** Incluem `tenant_id` obrigatório para isolamento. Todas as queries devem filtrar por tenant para evitar vazamento de dados.

**Exemplo:**

-  Global: `create_plans_table.php` (sem `tenant_id`).
-  Scoped: `create_budgets_table.php` (com `tenant_id` e filtro obrigatório em models via trait `BelongsToTenant`).

Isso garante que dados como orçamentos sejam isolados por tenant, enquanto planos são acessíveis globalmente.

## 3. Convenções de Nomenclatura para Colunas e Constraints de FK

-  **Colunas FK:** Use `{tabela_referenciada}_id` (ex: `tenant_id`, `user_id`, `budget_id`). Para tenant, sempre `tenant_id`.
-  **Constraints FK:** Nomeie como `fk_{tabela}_{coluna}_{tabela_referenciada}` (ex: `fk_budgets_tenant_id_tenants`).
-  **Indexes:** `idx_{tabela}_{coluna}` (ex: `idx_budgets_tenant_id`).

**Exemplo:**

```php
$table->string('tenant_id', 36);
$table->foreign('tenant_id', 'fk_budgets_tenant_id_tenants')->references('id')->on('tenants');
$table->index('tenant_id', 'idx_budgets_tenant_id');
```

Referência: Aplicado em migrações como `2025_09_17_100001_update_customers_fks.php` para padronização.

## 4. Indexes Obrigatórios

Crie indexes em:

-  `tenant_id` em todas as tabelas tenant-scoped para otimizar queries filtradas.
-  Todas as FKs para performance em joins.
-  Campos frequentemente consultados (ex: `email` em users).

**Exemplo:**

```php
$table->index(['tenant_id', 'user_id'], 'idx_budgets_tenant_user'); // Composite para queries comuns.
```

Esses indexes previnem lentidão em ambientes multi-tenant com alto volume de dados. Ver `MigrationIntegrityTest.php` para validação de indexes pós-migração.

## 5. Composite Unique Constraints Incluindo tenant_id

Para isolamento, inclua `tenant_id` em todas as unique constraints em tabelas scoped, evitando duplicatas cross-tenant.

**Exemplo Correto:**

```php
$table->unique(['tenant_id', 'slug'], 'unique_tenant_slug');
```

**Exemplo Incorreto (evitar):**

```php
$table->unique('slug'); // Permite duplicatas entre tenants.
```

Referência: Corrigido em `2025_09_17_000000_alter_role_permissions_table_for_composite_pk.php`, adicionando `tenant_id` a uniques.

## 6. Exemplos de Padrões Corretos e Incorretos

### Padrão Correto (Tabela Budgets)

```php
Schema::create('budgets', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->string('tenant_id', 36);
    $table->foreign('tenant_id', 'fk_budgets_tenant_id_tenants')->references('id')->on('tenants')->cascadeOnDelete();
    $table->string('title');
    $table->unique(['tenant_id', 'title'], 'unique_tenant_title');
    $table->index('tenant_id', 'idx_budgets_tenant_id');
    $table->timestamps();
});
```

### Padrão Incorreto (com problemas comuns)

```php
Schema::create('budgets', function (Blueprint $table) {
    $table->id(); // int auto-increment, incompatível com UUIDs.
    $table->unsignedBigInteger('tenant_id'); // Tipo errado para UUID.
    // Sem foreign key ou index.
    $table->unique('title'); // Sem tenant_id, permite cross-tenant duplicates.
});
```

Correções semelhantes foram aplicadas em `create_area_of_activities_table.php` para alinhar com UUIDs e isolamento.

## 7. Checklist para Revisar Migrações Antes de Deploy

Use esta checklist para validar migrações antes de rodar `php artisan migrate`:

-  [ ] `tenant_id` definido como `string('tenant_id', 36)` em tabelas scoped?
-  [ ] Foreign key para `tenant_id` com constraint explícita e `cascadeOnDelete`?
-  [ ] Tabelas globais (ex: plans) sem `tenant_id` desnecessário?
-  [ ] Nomenclatura de colunas/constraints/indexes segue convenções (fk*, idx*, unique*tenant*)?
-  [ ] Indexes em `tenant_id` e todas FKs criados?
-  [ ] Todas unique constraints incluem `tenant_id` em tabelas scoped?
-  [ ] Migração compatível com MySQL 8.0+ e UUIDs (sem colisões)?
-  [ ] Teste de integridade passa (`php artisan test --filter=MigrationIntegrityTest`)?
-  [ ] Sem duplicatas ou constraints quebradas (ver logs de migração)?
-  [ ] Documentação atualizada em models e READMEs afetados?

Execute `php artisan migrate:status` e revise diffs em ambiente de staging. Este checklist previne regressões observadas em subtasks anteriores.

## 8. Uso de change() em Migrações e Dependência Doctrine DBAL

Para alterar colunas existentes em migrações usando `$table->change()`, o Laravel requer a dependência Doctrine DBAL. Esta é necessária para introspecção e modificação segura do schema.

**Instalação:**
```bash
composer require doctrine/dbal --dev
```

**Quando Usar:**
- Sempre que precisar de `change()` para modificar tipos, defaults ou constraints em colunas (ex: alterar `contact_id` de unsignedBigInteger para foreignId em `update_customers_fks.php`).
- Mantenha em require-dev para não incluir em produção.

**Boas Práticas:**
- Teste migrações em ambiente de staging antes de produção.
- Use `php artisan migrate:rollback --step=1` para validar reversibilidade.
- Documente dependências em `composer.json` e atualize lock após instalação.

**Referência:** Adicionado após instalação via `composer require doctrine/dbal --dev` em 2025-09-18 para suportar migrações como `2025_09_17_100001_update_customers_fks.php`.

## Referências Adicionais

-  Migrações corrigidas: `create_area_of_activities_table.php`, `update_customers_fks.php`.
-  Teste: `tests/Feature/MigrationIntegrityTest.php` (valida schema pós-migração).
-  Para mais detalhes, consulte `docs/INDICE-DOCUMENTACAO.md`.

Este guia facilita a manutenção do schema multi-tenant, reduzindo erros em novas features e garantindo escalabilidade.
