# Tech - Easy Budget Laravel

## 🛠️ Tecnologias Utilizadas

### **🏗️ Backend**

-  **Framework:** Laravel 12 (PHP 8.2+)
-  **Linguagem:** PHP 8.2+
-  **Web Server:** Apache/Nginx
-  **Banco de Dados:** MySQL 8.0+
-  **Cache:** Redis 7.0+
-  **Queue:** Laravel Queue (Database driver)

### **🎨 Frontend**

-  **Framework:** Blade Templates
-  **CSS:** Bootstrap 5.3
-  **JavaScript:** Vanilla JS + jQuery 3.7
-  **Gráficos:** Chart.js 4.4
-  **Ícones:** Font Awesome 6.4

### **🔧 Ferramentas de Desenvolvimento**

-  **Composer:** Gerenciamento de dependências PHP
-  **NPM:** Gerenciamento de dependências JavaScript
-  **Artisan:** CLI do Laravel
-  **Git:** Controle de versão
-  **VS Code:** IDE principal

### **📊 Ambiente de Desenvolvimento**

-  **Sistema Operacional:** Windows 11 / Linux
-  **XAMPP/LAMP:** Ambiente local
-  **Docker:** Containerização (opcional)
-  **phpMyAdmin:** Administração de banco

## ⚙️ Configuração de Desenvolvimento

### **📋 Requisitos de Sistema**

```bash
# PHP Requirements
PHP >= 8.2
PDO Extension
Mbstring Extension
OpenSSL Extension
Tokenizer Extension
XML Extension
Ctype Extension
JSON Extension
BCMath Extension

# Database
MySQL >= 8.0
InnoDB Engine

# Cache (Optional)
Redis >= 7.0

# Web Server
Apache 2.4+ / Nginx 1.20+
mod_rewrite enabled
```

### **🚀 Instalação e Setup**

```bash
# 1. Clone do projeto
git clone <repository-url>
cd easy-budget-laravel

# 2. Instalação de dependências
composer install
npm install

# 3. Configuração de ambiente
cp .env.example .env
php artisan key:generate

# 4. Configuração do banco de dados
# Editar .env com as credenciais do banco

# 5. Execução das migrations
php artisan migrate
php artisan db:seed

# 6. Build dos assets
npm run build

# 7. Inicialização do servidor
php artisan serve
```

## 📦 Dependências Principais

### **🔧 Laravel Packages**

```json
{
   "require": {
      "php": "^8.3",
      "laravel/framework": "^12.0",
      "laravel/sanctum": "^4.2",
      "laravel/tinker": "^2.10.1",
      "mercadopago/dx-php": "3",
      "mpdf/mpdf": "8.2",
      "phpoffice/phpspreadsheet": "4",
      "stancl/tenancy": "^3.7"
   },
   "require-dev": {
      "barryvdh/laravel-debugbar": "^3.16",
      "fakerphp/faker": "^1.23",
      "laravel/breeze": "^2.3",
      "laravel/pail": "^1.2.2",
      "laravel/pint": "^1.24",
      "laravel/sail": "^1.41",
      "mockery/mockery": "^1.6",
      "nunomaduro/collision": "^8.6",
      "phpstan/phpstan": "^2.1",
      "phpunit/phpunit": "^11.5.3"
   }
}
```

### **🎨 Frontend Dependencies**

```json
{
   "dependencies": {
      "bootstrap": "^5.3.0",
      "jquery": "^3.7.0",
      "chart.js": "^4.4.0",
      "@fortawesome/fontawesome-free": "^6.4.0"
   },
   "devDependencies": {
      "vite": "^5.0.0",
      "laravel-vite-plugin": "^1.0.0"
   }
}
```

## 🔒 Configurações de Segurança

### **🔐 Environment Variables**

```env
# Application
APP_NAME="Easy Budget Laravel"
APP_ENV=local
APP_KEY=base64:xxxxxxxxxxxxxxxxxxxxxxxxx
APP_DEBUG=false
APP_URL=https://dev.easybudget.net.br

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=easy_budget
DB_USERNAME=your_username
DB_PASSWORD=your_password

# Cache
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=database

# Redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Mail (para notificações)
MAIL_MAILER=log
MAIL_HOST=127.0.0.1
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
```

### **🛡️ Configurações de Segurança**

-  **APP_DEBUG=false** em produção
-  **Rate limiting** implementado via middleware
-  **CSRF protection** ativa em formulários
-  **XSS protection** via Blade directives
-  **SQL injection** prevenida pelo Eloquent ORM

## 📊 Configurações de Banco de Dados

### **🏗️ Estrutura do Banco**

```sql
-- Main database: easy_budget
-- Charset: utf8mb4
-- Collation: utf8mb4_unicode_ci
-- Engine: InnoDB

-- Tabelas principais:
-- tenants, users, customers, products, budgets,
-- budget_items, invoices, payments, audit_logs,
-- permissions, roles, sessions, jobs, etc.
```

### **⚡ Configurações de Performance**

```php
// config/database.php
'connections' => [
    'mysql' => [
        'host' => env('DB_HOST', '127.0.0.1'),
        'port' => env('DB_PORT', '3306'),
        'database' => env('DB_DATABASE', 'easy_budget'),
        'username' => env('DB_USERNAME', 'forge'),
        'password' => env('DB_PASSWORD', ''),
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
        'strict' => true,
        'engine' => 'InnoDB',
        'options' => [
            PDO::ATTR_TIMEOUT => 30,
        ],
    ],
],
```

## 🔧 Padrões de Desenvolvimento

### **📝 Coding Standards**

-  **PSR-12** para código PHP
-  **Laravel Pint** para formatação automática
-  **Blade templates** para views
-  **Semantic commit messages** para Git

### **🏗️ Arquitetura Patterns**

-  **Repository Pattern** para acesso a dados
-  **Service Layer** para lógica de negócio
-  **Observer Pattern** para eventos
-  **Strategy Pattern** para algoritmos variáveis

### **🧪 Testing Standards**

-  **PHPUnit** para testes unitários
-  **Feature tests** para integração
-  **Browser tests** com Laravel Dusk (futuro)
-  **Cobertura mínima 80%** (meta futura)

## 📋 Comandos Úteis

### **🚀 Desenvolvimento Diário**

```bash
# Servidor de desenvolvimento
php artisan serve --host=0.0.0.0 --port=8000

# Limpeza de cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Monitoramento de logs
tail -f storage/logs/laravel.log

# Queue worker (se necessário)
php artisan queue:work
```

### **📦 Deploy**

```bash
# Build de produção
composer install --optimize-autoloader --no-dev
npm run build

# Migrations
php artisan migrate --force

# Cache de configuração
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 🔍 Debugging e Troubleshooting

### **🐛 Problemas Comuns**

```bash
# Permissões de storage
chmod -R 755 storage
chmod -R 755 bootstrap/cache

# Problemas de composer
composer dump-autoload

# Problemas de migração
php artisan migrate:status
php artisan migrate:rollback

# Problemas de cache
php artisan cache:forget "tenant:*"
```

### **📊 Monitoramento**

```bash
# Logs de auditoria
tail -f storage/logs/audit.log

# Performance queries (se habilitado)
DB::enableQueryLog();
dd(DB::getQueryLog());

# Monitoramento de memória
memory_get_usage()
```

## 🚀 Performance Optimization

### **⚡ Estratégias Implementadas**

-  **Eager Loading** para relacionamentos N+1
-  **Cache inteligente** com Redis
-  **Índices compostos** para queries frequentes
-  **Pagination** para grandes datasets
-  **Processamento assíncrono** para tarefas pesadas

### **📈 Métricas de Performance**

-  **Response time** < 200ms para APIs
-  **Page load** < 2s para views
-  **Database queries** otimizadas
-  **Memory usage** monitorado

Este documento detalha toda a stack tecnológica utilizada no Easy Budget Laravel, incluindo configurações, dependências e padrões de desenvolvimento estabelecidos.
