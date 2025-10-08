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

Este documento será atualizado conforme novas tarefas repetitivas forem identificadas e executadas no projeto.

**Última atualização:** 08/10/2025 - Melhorada tarefa "Adicionar Novo Modelo Eloquent" para incluir arquitetura completa Controller → Services → Repositories → Models e adicionada tarefa "Atualizar Schema do Banco de Dados".
