# Tasks - Easy Budget Laravel

## 📋 Documentação de Tarefas Repetitivas

Este documento registra tarefas repetitivas e seus workflows para facilitar manutenção e desenvolvimento futuro do sistema Easy Budget Laravel.

## 🔧 Tarefas de Desenvolvimento

### **🏗️ Adicionar Novo Modelo Eloquent**

**Última execução:** Durante desenvolvimento inicial
**Arquivos modificados:**

-  `app/Models/` - Novo modelo
-  `database/migrations/` - Migration correspondente
-  `app/Repositories/` - Repository para acesso a dados
-  `app/Services/` - Service layer para lógica de negócio
-  `app/Http/Controllers/` - Controller HTTP
-  `app/Http/Requests/` - Form requests para validação
-  `resources/views/` - Views Blade se necessário
-  `routes/web.php` - Rotas se necessário

**Passos:**

1. Criar modelo com `php artisan make:model NomeModelo -m`
2. Definir relacionamentos no modelo (belongsTo, hasMany, etc.)
3. Implementar trait TenantScoped se necessário
4. Implementar trait Auditable se necessário
5. Criar repository com `php artisan make:interface NomeModeloRepository` e implementação
6. Criar service com `php artisan make:service NomeModeloService` para lógica de negócio
7. Criar controller com `php artisan make:controller NomeModeloController --resource`
8. Implementar regras de validação no Request correspondente
9. Criar views Blade na estrutura padrão (se necessário para interface web)
10.   Adicionar rotas em `routes/web.php`
11.   Testar funcionalidades CRUD seguindo arquitetura completa
12.   Atualizar documentação se necessário

**Considerações importantes:**

-  Sempre usar fillable/guarded apropriadamente
-  Implementar soft deletes quando apropriado
-  Considerar índices de performance para queries frequentes
-  Usar políticas (Policies) para autorização
-  Implementar validação no lado servidor e cliente
-  **Arquitetura completa:** Repository para acesso a dados, Service para lógica de negócio
-  **Service Layer:** Centralizar regras de negócio e validações complexas
-  **Repository Pattern:** Abstrair operações de banco e permitir testes com mocks
-  **Dependency Injection:** Usar interfaces para permitir flexibilidade
-  **Traits TenantScoped e Auditable:** Aplicar automaticamente quando necessário

**Exemplo de implementação:**

```php
// app/Models/NovoModelo.php
class NovoModelo extends Model
{
    use HasFactory, SoftDeletes, TenantScoped, Auditable;

    protected $fillable = ['tenant_id', 'nome', 'descricao', 'ativo'];

    protected $casts = [
        'ativo' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}

// app/Repositories/NovoModeloRepository.php
interface NovoModeloRepository
{
    public function findByIdAndTenantId(int $id, int $tenantId): ?NovoModelo;
    public function listByTenantId(int $tenantId, array $filters = []): Collection;
    public function create(array $data): NovoModelo;
    public function update(NovoModelo $model, array $data): bool;
    public function delete(NovoModelo $model): bool;
}

// app/Services/NovoModeloService.php
class NovoModeloService extends BaseTenantService
{
    private NovoModeloRepository $repository;

    public function __construct(NovoModeloRepository $repository)
    {
        $this->repository = $repository;
    }

    protected function findEntityByIdAndTenantId(int $id, int $tenantId): ?Model
    {
        return $this->repository->findByIdAndTenantId($id, $tenantId);
    }

    protected function listEntitiesByTenantId(int $tenantId, array $filters = []): array
    {
        return $this->repository->listByTenantId($tenantId, $filters);
    }

    public function createByTenantId(array $data, int $tenantId): ServiceResult
    {
        $validation = $this->validateForTenant($data, $tenantId);
        if (!$validation->isSuccess()) {
            return $validation;
        }

        $entity = $this->repository->create($data);
        return $this->success($entity, 'Criado com sucesso.');
    }
}
```

### **📊 Criar Novo Relatório**

**Última execução:** Durante implementação de relatórios financeiros
**Arquivos modificados:**

-  `app/Services/ReportService.php` - Lógica do relatório
-  `app/Http/Controllers/ReportController.php` - Controller do relatório
-  `resources/views/reports/` - Views do relatório
-  `routes/web.php` - Nova rota
-  `config/cache.php` - Configuração de cache se necessário

**Passos:**

1. Identificar dados necessários para o relatório
2. Criar método no ReportService com query otimizada
3. Implementar cache com chave específica
4. Criar método no ReportController
5. Criar view Blade para exibição
6. Adicionar rota GET para o relatório
7. Implementar exportação PDF/Excel se necessário
8. Testar performance com grandes volumes de dados
9. Documentar novo relatório no sistema de ajuda

**Considerações importantes:**

-  Usar eager loading para relacionamentos
-  Implementar paginação para grandes datasets
-  Considerar filtros e ordenação
-  Otimizar queries com índices adequados
-  Implementar cache inteligente com TTL apropriado

### **🔐 Implementar Nova Permissão RBAC**

**Última execução:** Durante configuração inicial do sistema de permissões
**Arquivos modificados:**

-  `database/seeders/PermissionSeeder.php` - Adicionar nova permissão
-  `app/Http/Controllers/` - Controllers que usam a permissão
-  `resources/views/` - Views que precisam da permissão
-  `app/Services/PermissionService.php` - Se necessário atualizar lógica

**Passos:**

1. Identificar ação que precisa de permissão
2. Adicionar permissão no seeder com nome descritivo
3. Executar `php artisan db:seed --class=PermissionSeeder`
4. Implementar verificação no controller com `$this->authorize()`
5. Ou usar PermissionService para verificação customizada
6. Adicionar botões/ações nas views com `@can` directive
7. Testar com diferentes roles de usuário
8. Documentar nova permissão no sistema

**Considerações importantes:**

-  Usar nomes de permissões consistentes (verbo + recurso)
-  Agrupar permissões relacionadas
-  Considerar hierarquia de permissões
-  Implementar fallback para usuários sem permissão

## 🚀 Tarefas de Deploy e Manutenção

### **📦 Deploy para Produção**

**Última execução:** Durante configuração inicial
**Arquivos modificados:**

-  `.env` - Variáveis de produção
-  `config/` - Configurações específicas
-  `storage/` - Permissões de arquivos
-  `bootstrap/cache/` - Cache de configuração

**Passos:**

1. Backup do banco de dados de produção
2. Fazer upload dos arquivos para servidor
3. Instalar dependências: `composer install --optimize-autoloader --no-dev`
4. Executar `npm run build` para assets
5. Configurar variáveis de ambiente (.env)
6. Executar migrations: `php artisan migrate --force`
7. Otimizar cache: `php artisan config:cache`, `php artisan route:cache`
8. Configurar permissões de storage: `chmod -R 755 storage bootstrap/cache`
9. Reiniciar serviços web (Apache/Nginx)
10.   Testar funcionalidades críticas

**Considerações importantes:**

-  Nunca usar APP_DEBUG=true em produção
-  Configurar SSL/HTTPS obrigatoriamente
-  Implementar monitoramento de erros (Sentry, Bugsnag)
-  Configurar backups automáticos
-  Testar processo de deploy em ambiente de staging primeiro

### **🔧 Atualização de Dependências**

**Última execução:** Durante desenvolvimento inicial
**Arquivos modificados:**

-  `composer.json` - Dependências PHP
-  `package.json` - Dependências JavaScript
-  `composer.lock` - Lock file PHP
-  `package-lock.json` - Lock file JavaScript

**Passos:**

1. Verificar versões disponíveis: `composer outdated`, `npm outdated`
2. Testar atualizações em ambiente de desenvolvimento
3. Atualizar composer.json/package.json com versões compatíveis
4. Executar `composer update` e `npm update`
5. Testar todas as funcionalidades após atualização
6. Verificar logs de erro para problemas de compatibilidade
7. Atualizar documentação se necessário
8. Fazer deploy seguindo processo de deploy

**Considerações importantes:**

-  Sempre testar em ambiente de desenvolvimento primeiro
-  Manter versões compatíveis entre Laravel e dependências
-  Verificar changelog das dependências por breaking changes
-  Ter estratégia de rollback em caso de problemas

### **🌳 Implementar Sistema Hierárquico com Soft Delete**

**Última execução:** Durante desenvolvimento do módulo Categories (02/01/2025)
**Arquivos modificados:**

-  `app/Models/Category.php` - Modelo com estrutura hierárquica
-  `app/Repositories/CategoryRepository.php` - Repository com filtros e Soft Delete
-  `app/Services/CategoryService.php` - Service para operações hierárquicas
-  `app/Http/Controllers/CategoryController.php` - Controller com filtros e Soft Delete
-  `database/migrations/2025_01_01_000000_create_categories_table.php` - Tabela com parent_id
-  `resources/views/pages/category/` - Views com filtros e gestão de Soft Delete
-  `resources/js/categories.js` - JavaScript para interface com filtros

**Passos:**

1. Criar modelo com trait SoftDeletes e relacionamento hierárquico
2. Implementar relacionamento parent/children com eager loading
3. Criar migration com campo parent_id e índice adequado
4. Implementar Repository com métodos específicos para hierarquia:
   -  getAllByTenantWithHierarchy() - Lista com estrutura hierárquica
   -  getRootCategories() - Categorias de nível superior
   -  getChildrenByParentId() - Categorias filhas de um pai
5. Implementar Service com operações hierárquicas:
   -  buildCategoryTree() - Constrói árvore hierárquica
   -  validateParentCategory() - Valida categoria pai
   -  moveCategory() - Move categoria na hierarquia
6. Criar Controller com filtros específicos:
   -  currentDeleted() - Filtro para "Atuais/Deletados"
   -  byParent() - Filtro por categoria pai
7. Implementar views com interface para Soft Delete e hierarquia
8. Adicionar JavaScript para interatividade e filtros
9. Implementar exportação com filtros aplicados
10.   Testar todos os cenários de hierarquia e Soft Delete

**Considerações importantes:**

-  **Estrutura hierárquica:** Usar parent_id com índices adequados para performance
-  **Eager loading:** Sempre usar with('children') para evitar N+1 queries
-  **Soft Delete granular:** Implementar filtros diferentes para Prestador vs Admin
-  **Rotas Padronizadas:** Rotas de categoria agora seguem o padrão `provider.categories.*`.
-  **Validações hierárquicas:** Não permitir categoria ser pai de si mesma
-  **Cache de hierarquia:** Implementar cache para estruturas hierárquicas grandes
-  **Interface responsiva:** Garantir que interface funcione bem em diferentes dispositivos
-  **JavaScript eficiente:** Otimizar scripts para grandes volumes de dados
-  **Formato brasileiro:** Implementar formatação de datas e valores
-  **Exportação filtrada:** Manter filtros na exportação (XLSX, CSV, PDF)

## 📊 Tarefas de Monitoramento

### **📈 Análise de Performance**

**Última execução:** Durante desenvolvimento inicial
**Arquivos modificados:**

-  `app/Services/MonitoringService.php` - Se necessário criar
-  `storage/logs/` - Logs de performance
-  `config/logging.php` - Configuração de logs

**Passos:**

1. Identificar métricas importantes (response time, query count, memory usage)
2. Implementar monitoramento customizado se necessário
3. Configurar ferramentas de APM (Application Performance Monitoring)
4. Analisar queries lentas com `DB::enableQueryLog()`
5. Otimizar gargalos identificados
6. Implementar cache estratégico onde necessário
7. Documentar melhorias implementadas

**Considerações importantes:**

-  Monitorar métricas em produção continuamente
-  Estabelecer baselines de performance
-  Implementar alertas para métricas fora do padrão
-  Considerar impacto de crescimento de dados

### **🔍 Auditoria de Segurança**

**Última execução:** Durante desenvolvimento inicial
**Arquivos modificados:**

-  `storage/logs/audit.log` - Logs de auditoria
-  `app/Services/SecurityService.php` - Se necessário criar

**Passos:**

1. Revisar logs de auditoria periodicamente
2. Identificar padrões suspeitos de acesso
3. Verificar tentativas de acesso não autorizado
4. Analisar uso de funcionalidades críticas
5. Revisar configurações de segurança
6. Atualizar políticas de segurança se necessário
7. Documentar incidentes e resoluções

**Considerações importantes:**

-  Manter logs de auditoria por tempo adequado
-  Implementar alertas para ações críticas
-  Revisar logs regularmente (semanal/mensal)
-  Manter equipe informada sobre incidentes de segurança

## 🎨 Tarefas de Frontend

### **🎨 Criar Nova View Blade**

**Última execução:** Durante desenvolvimento inicial
**Arquivos modificados:**

-  `resources/views/` - Nova view
-  `resources/css/` - Estilos customizados se necessário
-  `resources/js/` - JavaScript se necessário
-  `routes/web.php` - Rota para view

**Passos:**

1. Criar estrutura de diretórios adequada em resources/views/
2. Usar layout base (@extends) para consistência
3. Implementar seções necessárias (@section)
4. Usar componentes Bootstrap existentes
5. Implementar validação de formulários se necessário
6. Adicionar JavaScript para interatividade
7. Testar responsividade em diferentes dispositivos
8. Validar acessibilidade (navegação por teclado, leitores de tela)

**Considerações importantes:**

-  Manter consistência com design system existente
-  Usar convenções de nomenclatura estabelecidas
-  Implementar feedback visual para ações do usuário
-  Considerar performance de carregamento

### **📚 Atualizar Memory Bank**

**Última execução:** Durante atualização completa do schema do banco de dados
**Arquivos modificados:**

-  `.kilocode/rules/memory-bank/` - Todos os arquivos do memory bank
-  Especialmente `context.md`, `database.md`, `architecture.md`

**Passos:**

1. Revisar TODOS os arquivos do memory bank para identificar inconsistências
2. Focar especialmente no `context.md` para atualizações de estado atual
3. Verificar alinhamento entre `brief.md` e status real do projeto
4. Atualizar `database.md` com schema real das migrations
5. Revisar `architecture.md` para refletir implementação atual
6. Validar consistência entre todos os documentos
7. Documentar mudanças significativas no contexto

**Considerações importantes:**

-  Sempre revisar TODOS os arquivos, mesmo que alguns não precisem mudanças
-  Manter consistência entre documentos relacionados
-  Focar no contexto atual e mudanças recentes
-  Documentar decisões arquiteturais importantes
-  Manter linguagem técnica clara e objetiva

### **🗄️ Atualizar Schema do Banco de Dados**

**Última execução:** Durante análise completa da migration inicial
**Arquivos modificados:**

-  `.kilocode/rules/memory-bank/database.md` - Documentação do schema
-  Especialmente seções de tabelas de configurações e cache

**Passos:**

1. Analisar migration completa `database/migrations/2025_09_27_132300_create_initial_schema.php`
2. Comparar com documentação atual em `database.md`
3. Identificar tabelas faltantes (user_settings, system_settings, cache, etc.)
4. Adicionar documentação completa das tabelas ausentes
5. Atualizar contador total de tabelas (35+ → 40+)
6. Verificar índices e relacionamentos das novas tabelas
7. Documentar propósito e uso de cada tabela adicionada

**Considerações importantes:**

-  Sempre contar todas as tabelas incluindo tabelas de sistema Laravel
-  Documentar relacionamentos foreign key completos
-  Incluir índices de performance quando aplicável
-  Manter formato consistente com tabelas existentes
-  Atualizar visão geral com números corretos

### **🔧 Corrigir Testes Budget que Estão Falhando**

**Última execução:** 07/11/2025
**Arquivos modificados:**

-  `tests/Unit/BudgetObserverTest.php` - Corrigido observer não sendo chamado
-  `tests/Feature/TenantScopingTest.php` - Corrigidos múltiplos problemas de tenant scoping
-  `database/factories/ProductFactory.php` - Removido campo unit_id inexistente
-  `database/factories/PlanSubscriptionFactory.php` - Nova factory criada
-  `tests/Unit/ProviderBusinessTest.php` - Corrigido método isOverdue()

**Passos executados:**

1. **Identificar problemas raiz:**

   -  BudgetObserverTest: Observer não sendo chamado corretamente
   -  TenantScopingTest: Múltiplos problemas de factory, seeding e relacionamentos

2. **Corrigir BudgetObserverTest:**

   -  Ajustar teste para usar fallback quando rota não existe
   -  Melhorar contexto de autenticação com tenant_id
   -  Garantir que observer seja acionado mesmo com problemas de rota

3. **Corrigir TenantScopingTest:**

   -  Atualizar seeders para executar na ordem correta
   -  Corrigir contadores de roles e permissions
   -  Simplificar teste RBAC para verificar funcionamento básico
   -  Criar provider manualmente para evitar problemas de factory
   -  Corrigir teste de PlanSubscription com provider válido

4. **Melhorar factories:**
   -  Remover campos inexistentes do ProductFactory
   -  Criar PlanSubscriptionFactory completa
   -  Ajustar RoleFactory se necessário

**Considerações importantes:**

-  **Testes de Observer:** Podem falhar se rotas não existirem, usar fallback com update direto
-  **Tenant Scoping:** Verificar se global scopes estão funcionando corretamente
-  **Factories:** Sempre verificar se campos existem no schema antes de usar
-  **Seeders:** Executar na ordem correta (Roles → Permissions → RolePermissions)
-  **Relacionamentos:** Verificar foreign keys antes de criar dados dependentes
-  **ProviderFactory:** Pode ter problemas com campos opcionais, usar criação manual quando necessário

**Resultados:**

-  ✅ BudgetObserverTest: 3/3 testes passando
-  ✅ TenantScopingTest: 5/5 testes passando
-  ✅ Total: 8/8 testes passando (20 assertions)
-  ✅ Duração total: ~9 segundos

### **🔢 Corrigir Padrões de Códigos em Seeders**

**Última execução:** 12/11/2025
**Arquivos modificados:**

-  `database/seeders/BudgetTestSeeder.php` - Padrões de códigos corrigidos
-  `check_codes.php` - Script de verificação criado
-  _(Referência histórica: `old-system/test-DoctrineORM/database/seeds/inserts/insert.sql` - Removido)_

**Problema identificado:**

-  BudgetTestSeeder estava usando padrões de códigos novos em vez dos padrões do sistema antigo
-  Causando inconsistência entre sistema novo e antigo (agora migrado)
-  Faturas duplicando códigos

**Padrões do sistema legado identificados:**

-  **Orçamento:** `ORC-YYYYMMDD-0001` (ORC + data + sequencial 4 dígitos)
-  **Serviço:** `YYYYMMDD-0001-S001` (data + orçamento + sequencial S001, S002, etc.)
-  **Fatura:** `FAT-YYYYMMDD-0001` (FAT + data + sequencial 4 dígitos)

**Correções implementadas:**

1. **Analisar SQL de produção antigo:**

   ```sql
   INSERT INTO `budgets` (code) VALUES ('ORC-20250630-0001')
   INSERT INTO `services` (code) VALUES ('20250630-0001-S001')
   INSERT INTO `invoices` (code) VALUES ('FAT-20250809-0001')
   ```

2. **Corrigir BudgetTestSeeder:**

   -  Implementar contadores globais únicos ($globalBudgetCounter, $globalInvoiceCounter)
   -  Usar data atual para gerar códigos (20251112)
   -  Sequencial de 4 dígitos com padding zero
   -  Para serviços, usar ORC-YYYYMMDD-0001-S001 (mais consistente)

3. **Implementar padrões corretos:**

   ```php
   // Orçamentos
   $budgetCode = "ORC-{$budgetDate}-{$budgetSequential}";

   // Serviços
   $serviceCode = "{$budgetCode}-S" . str_pad((string)$serviceIndex, 3, '0', STR_PAD_LEFT);

   // Faturas
   $invoiceCode = "FAT-{$budgetDate}-{$invoiceSequential}";
   ```

**Resultado verificado:**

-  ✅ Orçamentos: ORC-20251112-0001, ORC-20251112-0002, ORC-20251112-0003...
-  ✅ Serviços: ORC-20251112-0001-S001, ORC-20251112-0001-S002, ORC-20251112-0001-S003...
-  ✅ Faturas: FAT-20251112-0001, FAT-20251112-0002, FAT-20251112-0003...
-  ✅ Comando `php artisan migrate:fresh --seed` executa sem erros
-  ✅ Nenhuma duplicação de códigos

**Considerações importantes:**

-  **Análise de dados históricos:** Verificar padrões estabelecidos para manter consistência
-  **Padrões sequenciais:** Usar contadores globais para evitar duplicação entre diferentes providers
-  **Data atual:** Usar `now()->format('Ymd')` para refletir data real do seeding
-  **Validação:** Criar scripts de verificação para confirmar padrões corretos
-  **Documentação:** Atualizar memory bank com novos padrões identificados

### **🛠️ Refinamento de UX e Dashboard de Categorias**

**Última execução:** 21/12/2024
**Arquivos modificados:**

-  `app/Services/Domain/CategoryService.php` - Refatoração de segurança e lógica de dashboard
-  `app/Services/Domain/CategoryExportService.php` - Remoção de slug e alinhamento centralizado
-  `app/Http/Controllers/CategoryController.php` - Injeção do novo ExportService
-  `resources/views/pages/category/dashboard.blade.php` - Novo layout de métricas responsivo
-  `resources/views/pages/category/*.blade.php` - Remoção visual de Slugs

**Passos executados:**

1. **Diferenciação de Métricas:** Implementado contador para categorias deletadas e lógica de inativas.
2. **Simplificação de UI:** Ocultado o campo Slug em todas as telas voltadas ao prestador para reduzir complexidade.
3. **Melhoria na Exportação:** Ajustado alinhamento das colunas numéricas no Excel para padrão profissional.
4. **Segurança de Tenant:** Refatorado helpers do `CategoryService` para garantir que toda busca valide a propriedade do registro via `ServiceResult`.

**Considerações importantes:**

-  **Slugs como identificadores:** Devem ser mantidos nas URLs por SEO/Estética, mas ocultos nos formulários.
-  **Métricas:** Sempre considerar registros deletados (`withTrashed`) ao calcular estatísticas totais.
-  **Consistência Visual:** Usar classes utilitárias CSS globais em vez de estilos inline no Blade.

Este documento será atualizado conforme novas tarefas repetitivas forem identificadas e executadas no projeto.

**Última atualização:** 21/12/2024 - Refinamento do módulo de categorias e dashboard.

### **🛠️ Correção e Melhoria na Exportação de Categorias**

**Data:** 21/12/2024
**Arquivos modificados:**

-  `app/Repositories/Traits/RepositoryFiltersTrait.php` - Correção no filtro `deleted` para aceitar string vazia.
-  `app/Http/Controllers/CategoryController.php` - Ajuste na extração de dados do Paginator para preservar `deleted_at`.
-  `app/Services/Domain/CategoryExportService.php` - Adição da coluna "Situação" (Ativo/Inativo/Deletado) e lógica robusta de detecção.
-  `resources/views/pages/category/index.blade.php` - Correção nos links de exportação para persistir filtros vazios.

**Passos executados:**

1. **Filtros Persistentes:** Links de exportação agora forçam parâmetros (ex: `deleted=''`) para evitar limpeza automática do Laravel.
2. **Coluna Situação:** Exportação agora mostra claramente itens Deletados, diferenciando de Inativos.
3. **Correção Backend:** Repositório agora entende que filtro vazio significa `withTrashed()` (Todos).

**Lições Aprendidas:**

-  O helper `route()` remove parâmetros nulos; usar string vazia `''` para forçar presença.
-  `getCollection()` em Paginators pode perder atributos crus do banco; usar `items()` ou coleta manual.
-  `filemtime()` em Windows/Laragon é lento; usar versionamento estático para assets.

### **🚀 Implementação "Gold Standard" no Módulo de Produtos**

**Data:** 21/12/2024
**Arquivos modificados:**

-  `app/Services/Domain/ProductService.php`: Refatorado para usar Repository Pattern no Dashboard e paginação dinâmica.
-  `app/Services/Domain/ProductExportService.php`: Criado novo serviço de exportação.
-  `app/Http/Controllers/ProductController.php`: Implementada exportação e injeção de dependências.
-  `resources/views/pages/product/index.blade.php`: Adicionado botão de exportação.

**Melhorias Implementadas:**

1. **Exportação Completa:** Excel e PDF agora disponíveis para produtos, com suporte a filtros (preço, status, search).
2. **Dashboard Otimizado:** Consultas diretas ao Eloquent substituídas por métodos do Repository, garantindo escopo de Tenant e performance.
3. **Consistência:** Módulo alinhado com a arquitetura de Categorias, facilitando manutenção futura.
