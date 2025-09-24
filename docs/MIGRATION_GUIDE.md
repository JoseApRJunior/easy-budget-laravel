# 🚀 Guia de Migração - Sistema Legado para Laravel

## Visão Geral

Este guia fornece instruções detalhadas para migrar do sistema legado para a implementação Laravel, incluindo passos de preparação, execução e validação.

## 📋 Pré-requisitos

### ✅ **Requisitos de Sistema**

-  PHP 8.1 ou superior
-  Composer instalado
-  Laravel 10.x instalado
-  Banco de dados configurado (MySQL/PostgreSQL)
-  Extensões PHP necessárias: `pdo`, `mbstring`, `xml`, `curl`

### ✅ **Backup Obrigatório**

-  Backup completo do banco de dados
-  Backup dos arquivos do sistema legado
-  Backup da configuração atual

## 🗂️ Estrutura da Migração

### Fase 1: Preparação (1-2 dias)

#### 1.1 **Configuração do Ambiente**

```bash
# 1. Instalar dependências
composer install --no-dev --optimize-autoloader

# 2. Configurar ambiente
cp .env.example .env
php artisan key:generate

# 3. Configurar banco de dados
php artisan config:clear
php artisan cache:clear
```

#### 1.2 **Configuração de Multi-tenancy**

```php
# Configurar TenantScope no config/tenant.php
'resolver' => [
    'driver' => 'auth', // ou 'request' ou 'custom'
    'testing_id' => null,
],
```

#### 1.3 **Verificação de Models**

-  ✅ Todos os 37 models criados
-  ✅ TenantScoped trait aplicado (9 models)
-  ✅ Método boot() implementado (33 models)
-  ✅ Casting configurado corretamente

### Fase 2: Migração de Dados (2-3 dias)

#### 2.1 **Migração de Estrutura**

```bash
# Executar migrations
php artisan migrate --seed

# Verificar tabelas criadas
php artisan tinker
>>> DB::select("SHOW TABLES");
```

#### 2.2 **Migração de Dados por Entidade**

##### **Usuários e Tenants**

```php
# 1. Migrar tenants primeiro
php artisan db:seed --class=TenantSeeder

# 2. Migrar usuários
php artisan db:seed --class=UserSeeder

# 3. Migrar roles e permissions
php artisan db:seed --class=RoleSeeder
```

##### **Produtos e Serviços**

```php
# 4. Migrar dados base
php artisan db:seed --class=BaseDataSeeder

# 5. Migrar produtos
php artisan db:seed --class=ProductSeeder

# 6. Migrar serviços
php artisan db:seed --class=ServiceSeeder
```

##### **Dados Operacionais**

```php
# 7. Migrar orçamentos
php artisan db:seed --class=BudgetSeeder

# 8. Migrar clientes
php artisan db:seed --class=CustomerSeeder

# 9. Migrar invoices
php artisan db:seed --class=InvoiceSeeder
```

#### 2.3 **Validação da Migração**

```bash
# Verificar integridade dos dados
php artisan tinker
>>> App\Models\Tenant::count(); // Deve retornar número de tenants
>>> App\Models\User::count();   // Deve retornar número de usuários
>>> App\Models\Product::count(); // Deve retornar número de produtos
```

### Fase 3: Testes e Validação (1-2 dias)

#### 3.1 **Testes Automatizados**

```bash
# Executar todos os testes
php artisan test

# Testes específicos
php artisan test --filter=TenantScopingTest
php artisan test --filter=ModelIntegrityTest
```

#### 3.2 **Testes Manuais**

##### **Teste de Multi-tenancy**

```php
# 1. Criar tenant de teste
php artisan tinker
>>> $tenant = App\Models\Tenant::factory()->create();

# 2. Testar isolamento de dados
>>> App\Models\Product::all(); // Deve estar vazio
>>> App\Models\Product::withoutTenant()->count(); // Deve mostrar todos
```

##### **Teste de Relacionamentos**

```php
# 3. Testar relacionamentos
>>> $user = App\Models\User::first();
>>> $user->tenant; // Deve funcionar
>>> $user->roles;  // Deve funcionar
```

##### **Teste de Casting**

```php
# 4. Testar casting
>>> $product = App\Models\Product::first();
>>> $product->price; // Deve ser decimal
>>> $product->active; // Deve ser boolean
```

#### 3.3 **Performance e Otimização**

```bash
# Verificar queries N+1
php artisan tinker
>>> DB::enableQueryLog();
>>> $products = App\Models\Product::with('tenant')->get();
>>> dd(DB::getQueryLog());
```

### Fase 4: Deploy para Produção (1 dia)

#### 4.1 **Preparação para Produção**

```bash
# 1. Otimizar para produção
composer install --no-dev --optimize-autoloader
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 2. Configurar cache
php artisan cache:clear
php artisan config:clear
```

#### 4.2 **Configuração de Produção**

```php
# .env de produção
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_CONNECTION=mysql
DB_HOST=your_db_host
DB_DATABASE=your_db_name
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

#### 4.3 **Configuração de Multi-tenancy em Produção**

```php
# Configurar middleware de tenant
php artisan make:middleware TenantMiddleware

# Registrar middleware no Kernel
protected $middleware = [
    \App\Http\Middleware\TenantMiddleware::class,
];
```

## 🔧 Configurações Específicas

### Multi-tenancy Configuration

```php
// config/tenant.php
<?php

return [
    'resolver' => [
        'driver' => 'auth', // auth, request, custom
        'testing_id' => null,
    ],
    'models' => [
        'tenant' => App\Models\Tenant::class,
        'user' => App\Models\User::class,
    ],
];
```

### Database Configuration

```php
// config/database.php - Adicionar tenant_id a todas as tabelas
'tenant_scoped_tables' => [
    'activities',
    'addresses',
    'budgets',
    'categories',
    'customers',
    'invoices',
    'products',
    'services',
    'users',
    // ... todas as tabelas tenant-scoped
],
```

## ⚠️ Considerações de Segurança

### 1. **Autenticação e Autorização**

-  Implementar middleware de tenant em todas as rotas
-  Validar tenant_id em todas as operações
-  Configurar políticas de acesso adequadas

### 2. **Validação de Dados**

-  Validar tenant_id em todos os requests
-  Implementar rate limiting
-  Configurar CSRF protection

### 3. **Auditoria e Logs**

-  Implementar logs de todas as operações
-  Monitorar acesso entre tenants
-  Configurar alertas de segurança

## 📊 Monitoramento e Manutenção

### 1. **Monitoramento de Performance**

```bash
# Instalar ferramentas de monitoramento
composer require spatie/laravel-health
php artisan health:check
```

### 2. **Logs de Sistema**

```php
# Configurar logs detalhados
LOG_LEVEL=debug
LOG_CHANNEL=stack
```

### 3. **Backup e Recovery**

```bash
# Configurar backups automáticos
php artisan backup:run
php artisan backup:list
```

## 🚨 Troubleshooting

### Problema: TenantScope não está funcionando

```php
# Solução: Verificar configuração
php artisan tinker
>>> config('tenant.testing_id'); // Deve retornar null em produção
>>> Auth::user()->tenant_id; // Deve retornar tenant_id correto
```

### Problema: Casting não está funcionando

```php
# Solução: Verificar casts no model
php artisan tinker
>>> $model = App\Models\Product::first();
>>> $model->getCasts(); // Deve mostrar casts configurados
```

### Problema: Relacionamentos não estão funcionando

```php
# Solução: Verificar foreign keys
php artisan tinker
>>> Schema::hasColumn('products', 'tenant_id'); // Deve retornar true
```

## 📋 Checklist de Migração

### ✅ **Pré-migração**

-  [ ] Backup completo realizado
-  [ ] Ambiente de desenvolvimento configurado
-  [ ] Todos os models criados e testados
-  [ ] TenantScoped implementado
-  [ ] Casting configurado

### ✅ **Durante a Migração**

-  [ ] Estrutura do banco migrada
-  [ ] Dados migrados por entidade
-  [ ] Relacionamentos validados
-  [ ] Multi-tenancy testada
-  [ ] Performance otimizada

### ✅ **Pós-migração**

-  [ ] Testes automatizados passando
-  [ ] Testes manuais realizados
-  [ ] Configuração de produção aplicada
-  [ ] Monitoramento configurado
-  [ ] Documentação atualizada

## 🎯 Próximos Passos Recomendados

1. **Treinamento da Equipe**: Treinar desenvolvedores sobre mudanças
2. **Documentação Técnica**: Atualizar documentação do sistema
3. **Monitoramento Contínuo**: Implementar dashboards de monitoramento
4. **Otimização**: Otimizar performance baseado em uso real
5. **Expansão**: Planejar novas funcionalidades

## 📞 Suporte e Contato

Para suporte durante a migração:

-  **Equipe Técnica**: [contato@empresa.com]
-  **Documentação**: [docs.empresa.com]
-  **GitHub Issues**: [github.com/empresa/projeto/issues]

---

_Guia criado em: 24/09/2025_
_Versão: 1.0.0_
_Equipe de Migração: Easy Budget Laravel Team_
