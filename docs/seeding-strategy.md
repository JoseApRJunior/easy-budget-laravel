# Estratégia de Seeding no Easy Budget Laravel

## Introdução

Esta documentação descreve a estratégia completa de seeding (popularização de dados) implementada no projeto Easy Budget migrado para Laravel. A estratégia diferencia dados globais (compartilhados entre todos os tenants) de dados específicos de desenvolvimento (tenant-scoped), garantindo consistência com o sistema legado, escalabilidade e segurança em diferentes ambientes. O foco é preservar a integridade dos dados de produção enquanto permite testes robustos em desenvolvimento.

A migração do legado (insert.sql) foi analisada para mapear estruturas existentes, como 7 status de orçamento, 83 áreas de atividade, entre outros, garantindo que os seeders reproduzam fielmente esses dados.

## Tabelas Globais vs. Dados de Desenvolvimento

### Tabelas Globais (Lookup Compartilhados)

Essas tabelas contêm dados de referência que são iguais para todos os tenants e não variam por tenant. Elas são seedadas uma vez no banco central (não tenant-scoped) e servem como base para lookups. Baseado na análise do legado, essas tabelas são globais porque representam configurações padronizadas do sistema, independentes de tenants específicos.

| Tabela/Entity           | Descrição                                    | Quantidade no Legado | Razão para Global                                                  |
| ----------------------- | -------------------------------------------- | -------------------- | ------------------------------------------------------------------ |
| roles                   | Roles de usuário (e.g., admin, provider)     | ~5 roles principais  | Configurações de permissão compartilhadas globalmente.             |
| permissions             | Permissões associadas a roles                | ~20-30 permissões    | Definições de acesso universais, não variam por tenant.            |
| statuses (BudgetStatus) | Status de orçamentos (e.g., draft, approved) | 7 status             | Padrões fixos para workflow de orçamentos, comuns a todos.         |
| areas (AreaOfActivity)  | Áreas de atividade profissional              | 83 áreas             | Catálogo global de profissões/áreas, baseado em padrões setoriais. |
| professions             | Profissões                                   | ~50-100 itens        | Referência compartilhada para classificação de serviços.           |
| categories              | Categorias de serviços/orçamentos            | ~10-20 categorias    | Classificações padronizadas, não tenant-específicas.               |
| plans                   | Planos de assinatura                         | ~4-6 planos          | Estrutura de pricing global, gerenciada centralmente.              |
| units                   | Unidades de medida (e.g., hora, dia)         | ~5-10 unidades       | Padrões de medição universais para serviços.                       |

Essas tabelas são seedadas via seeders dedicados (e.g., AreaOfActivitySeeder, PlanSeeder) e não dependem de tenant_id.

### Dados de Desenvolvimento (Tenant-Scoped)

Esses dados são específicos de tenants e incluem amostras para testes. No legado, eles eram inseridos via insert.sql para tenants existentes. Em Laravel, usamos SampleDataSeeder para gerar dados fictícios por tenant, evitando exposição de dados reais em dev/test.

| Tabela/Entity | Descrição                     | Seeders Responsáveis                     | Razão para Tenant-Scoped             |
| ------------- | ----------------------------- | ---------------------------------------- | ------------------------------------ |
| tenants       | Tenants (empresas/provedores) | TenantSeeder (básico) + SampleDataSeeder | Cada tenant tem isolamento de dados. |
| users         | Usuários por tenant           | SampleDataSeeder                         | Dados sensíveis, variam por tenant.  |
| budgets       | Orçamentos                    | SampleDataSeeder                         | Conteúdo específico de cada tenant.  |
| customers     | Clientes                      | SampleDataSeeder                         | Relacionados a budgets de um tenant. |
| services      | Serviços                      | SampleDataSeeder                         | Ofertas personalizadas por tenant.   |
| invoices      | Faturas                       | SampleDataSeeder                         | Transações por tenant.               |

Esses são seedados após a criação de tenants, com dependência em dados globais.

## Ordem de Dependência no DatabaseSeeder

O DatabaseSeeder orquestra a execução em ordem lógica para respeitar foreign keys e dependências. A sequência segue:

1. **Seeders Globais (Não-Tenant)**:

   -  PermissionSeeder: Cria permissões base.
   -  RoleSeeder: Cria roles, referenciando permissões.
   -  RolePermissionSeeder: Associa roles a permissões.
   -  BudgetStatusSeeder: Status fixos (7 itens do legado).
   -  AreaOfActivitySeeder: 83 áreas do legado.
   -  ProfessionSeeder: Profissões globais.
   -  CategorySeeder: Categorias compartilhadas.
   -  PlanSeeder: Planos de assinatura.
   -  UnitSeeder: Unidades de medida (se aplicável).

2. **Seeders Tenant-Specific (Desenvolvimento)**:
   -  TenantSeeder: Cria tenants de amostra.
   -  SampleDataSeeder: Para cada tenant, cria users, budgets, etc., usando dados globais como FKs (e.g., budget status_id referencia BudgetStatus global).

Essa ordem garante que lookups globais existam antes de dados dependentes. No legado, insert.sql seguia ordem similar implicitamente; aqui, explicitamos para evitar erros de integridade.

## Razões para Global vs. Tenant-Scoped

Baseado na análise do legado (insert.sql e schema):

-  **Globais**: São "lookup tables" imutáveis ou raramente alteradas, otimizando performance (um registro serve todos tenants) e consistência (evita duplicação). Ex: Status de orçamento são padronizados para relatórios uniformes.
-  **Tenant-Scoped**: Dados operacionais variam por tenant (e.g., budgets de um provedor não afetam outro), respeitando multi-tenancy. No legado, isolamento era via tenant_id; em Laravel, usamos BelongsToTenant trait.
-  Manutenção de Legado: Mapeamos ~7 status, 83 áreas diretamente de insert.sql para seeders, preservando IDs e relações. Ex: Status 1 = 'Draft', etc.

## Instruções para Rodar Seeders em Diferentes Ambientes

-  **Ambiente Local/Desenvolvimento**:

   -  Rode: `php artisan db:seed --class=DatabaseSeeder`
   -  Isso executa globais + SampleDataSeeder (cria ~3-5 tenants com dados fictícios: 10 users, 20 budgets cada).
   -  Para refresh: `php artisan migrate:fresh --seed`
   -  Use `--class=SampleDataSeeder` para recriar apenas dados de amostra.

-  **Ambiente de Testes**:

   -  Rode: `php artisan db:seed --class=DatabaseSeeder --env=testing`
   -  Evita SampleDataSeeder em massa; foque em fixtures mínimas para testes (PHPUnit).

-  **Ambiente de Produção**:
   -  **Evite SampleDataSeeder**: Nunca rode em prod para não poluir com dados fictícios.
   -  Rode apenas globais: `php artisan db:seed --class=GlobalLookupsSeeder` (crie este seeder custom para prod, chamando apenas seeders globais).
   -  Para migração inicial: Use `php artisan migrate --seed` apenas após backup, seedando globais e importando dados reais via scripts custom (e.g., de insert.sql).
   -  Consistência: Monitore insert.sql legado; crie scripts de migração para mapear dados reais (e.g., 83 áreas importadas via CSV).

## Manutenção de Consistência com Dados Legados

-  **Mapeamento de Dados**:
   -  Budget Status: 7 itens mapeados (1=Draft, 2=Pending, ..., 7=Closed).
   -  Áreas: 83 itens exatos do legado, com IDs preservados via seeder.
   -  Outros: Professions (~50), Categories (~15), Plans (4 tiers), Units (hora/dia/mês).
-  **Estratégia de Migração**: Crie um MigrationSeeder custom para importar de insert.sql (use Laravel's DB::statement para inserts batch). Teste integridade com SeederIntegrityTest.php.
-  **Atualizações**: Ao alterar seeders, valide contra legado com diffs. Mantenha versionamento nos seeders (e.g., v1.0 para legado).

## Conclusão

Essa estratégia garante um banco populado de forma segura e eficiente, alinhada ao plano de migração legacy-to-Laravel. Para mais detalhes, consulte seeders em `database/seeders/`.

_Última atualização: 2025-09-18_
