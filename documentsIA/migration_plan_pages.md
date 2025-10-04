# Plano de Migração: `resources/views/pages` (Twig para Blade)

## 🎯 Objetivo

Este documento detalha a estratégia e a ordem de migração dos arquivos de visualização do diretório `resources/views/pages`, convertendo-os do motor de templates Twig para o Blade do Laravel. O objetivo é garantir uma transição estruturada, modular e controlada.

## 📋 Estratégia Geral

A migração será dividida em etapas, agrupando os arquivos por funcionalidade ou módulo. A abordagem será a seguinte:

1. **Migração Incremental:** Focar em um módulo por vez (ex: Autenticação, Clientes, Orçamentos).
2. **Do Simples ao Complexo:** Começar com páginas mais simples e estáticas (ex: páginas de erro, legais) para depois avançar para as mais complexas com lógica de negócios (ex: admin, orçamentos).
3. **Validação Contínua:** Após a migração de cada módulo, é recomendado testar as funcionalidades correspondentes para garantir que nada foi quebrado.
   ra

## ⚡ Ordem de Migração Sugerida

A seguir está a ordem recomendada para a migração, agrupada por diretórios/módulos.

### Etapa 1: Páginas Básicas e de Erro

Páginas com pouco ou nenhum processamento dinâmico. São ideais para começar.

-  **Diretório:** `pages/error/`
   -  `internalError.twig`
   -  `notAllowed.twig`
   -  `notFound.twig`
-  **Diretório:** `pages/legal/`
   -  `privacy_policy.twig`
   -  `terms_of_service.twig`
-  **Diretório:** `pages/home/`
   -  _Observação: Estes arquivos já são `.blade.php`. Apenas revisar se necessário._

### Etapa 2: Autenticação e Contas de Usuário

Fluxos essenciais para o acesso e gerenciamento de contas.

-  **Diretório:** `pages/login/`
   -  `index.twig`
   -  `forgot_password.twig`
-  **Diretório:** `pages/user/`
   -  `confirm-account.twig`
   -  `resend-confirmation.twig`
   -  `block-account.twig`

### Etapa 3: CRUDs (Cadastro, Leitura, Atualização e Deleção)

Migração das entidades principais do sistema. Recomenda-se migrar um CRUD de cada vez.

1. **Unidades (`pages/unit/`)**
   -  `index.twig`
   -  `create.twig`
2. **Categorias (`pages/category/`)**
   -  `index.twig`
   -  `create.twig`
   -  `edit.twig`
   -  `show.twig`
3. **Áreas de Atuação (`pages/area-of-activity/`)**
   -  `index.twig`
   -  `create.twig`
4. **Profissões (`pages/profession/`)**
   -  `index.twig`
   -  `create.twig`
5. **Clientes (`pages/customer/`)**
   -  `index.twig`
   -  `create.twig`
   -  `show.twig`
   -  `update.twig`
   -  `services_and_quotes.twig`
6. **Fornecedores (`pages/provider/`) - Concluído**
   -  `index.twig`
   -  `update.twig`
   -  `change_password.twig`
7. **Produtos (`pages/product/`) - Concluído**
   -  `index.twig`
   -  `create.twig`
   -  `show.twig`
   -  `update.twig`
8. **Serviços (`pages/service/`) - Concluído**
   -  `index.twig`
   -  `create.twig`
   -  `show.twig`
   -  `update.twig`
   -  `pdf_service_print.twig`
   -  `view_service_status.twig`

### Etapa 4: Funcionalidades Centrais

Módulos com maior complexidade e lógica de negócios.

1. **Orçamentos (`pages/budget/`) - Concluído**
2. **Faturas e Pagamentos (`pages/invoice/` e `pages/payment/`) - Concluído**
3. **Planos (`pages/plan/`) - Concluído**

### Etapa 5: Relatórios

Páginas de geração e visualização de relatórios.

-  **Diretório:** `pages/report/`
   -  `index.twig`
   -  E todos os subdiretórios (`budget`, `customer`, `product`, `service`)

### Etapa 6: Painel de Administração

A seção mais complexa, que deve ser migrada por último e, se possível, dividida em sub-etapas.

-  **Diretório:** `pages/admin/`
   1. Páginas principais: `dashboard.twig`, `home.twig`, `executive-dashboard.twig`.
   2. Módulos de gestão: `tenant/`, `user/`, `plan/`, `settings/`.
   3. Módulos de monitoramento: `logs/`, `metrics/`, `monitoring/`, `analysis/`, `alerts/`, `ai/`.

## 🔧 Padrões de Conversão (Twig para Blade)

Durante a migração, preste atenção aos seguintes padrões:

-  **Herança de Layout:**
   -  `{% extends 'layouts/app.twig' %}` → `@extends('layouts.app')`
-  **Seções de Conteúdo:**
   -  `{% block content %}` ... `{% endblock %}` → `@section('content')` ... `@endsection`
-  **Inclusão de Partials:**
   -  `{% include 'partials/sidebar.twig' %}` → `@include('partials.sidebar')`
-  **Variáveis:**
   -  `{{ user.name }}` → `{{ $user->name }}`
-  **Estruturas de Controle:**
   -  `{% if condition %}` → `@if (condition)`
   -  `{% for item in items %}` → `@foreach ($items as $item)`
-  **Funções e Filtros:**
   -  `{{ 'text'|trans }}` → `__('text')`
   -  `{{ path('route_name') }}` → `{{ route('route_name') }}`
   -  `{{ flash('error')|raw }}` → Substituir por diretivas de erro do Blade, como `@error('field') <div class="alert alert-danger">{{ $message }}</div> @enderror`.

## ✅ Próximos Passos

1. Crie um novo chat para iniciar a migração.
2. Informe a primeira etapa que deseja realizar (ex: "Vamos começar pela Etapa 1: Páginas Básicas e de Erro").
3. Siga o plano, migrando um módulo de cada vez.

Este plano servirá como um guia para garantir que a migração seja feita de forma organizada e eficiente.

## Sistema de Backup

### Views Implementadas

-  **Localização**: `resources/views/pages/admin/backup/index.blade.php`
-  **Funcionalidades**:
   -  Listagem de backups existentes
   -  Criação de backup manual
   -  Restauração de backup
   -  Limpeza automática de backups antigos
   -  Exclusão individual de backups

### Service Providers a Implementar

1. **AliasServiceProvider**

```php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Foundation\AliasLoader;

class AliasServiceProvider extends ServiceProvider
{

    public function register(): void
    {  $loader = AliasLoader::getInstance();
        $loader->alias( 'Currency', \App\Helpers\CurrencyHelper::class);
        $loader->alias( 'DateHelper', \App\Helpers\DateHelper::class);
    }
}
```

2. **ViewComposerServiceProvider**

```php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

class ViewComposerServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        View::composer('*', function ($view) {
            $view->with([
                'auth' => auth()->check(),
                'user' => auth()->user(),
                'flash' => session('flash'),
            ]);
        });
    }
}
```

3. **BladeDirectiveServiceProvider**

```php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;

class BladeDirectiveServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Blade::directive('money', function ($expression) {
            return "<?php echo Currency::format($expression); ?>";
        });

        Blade::directive('date', function ($expression) {
            return "<?php echo DateHelper::format($expression); ?>";
        });
    }
}
```

### Registro dos Providers

Adicionar em `config/app.php`:

```php
'providers' => [
    // ...
    App\Providers\AliasServiceProvider::class,
    App\Providers\ViewComposerServiceProvider::class,
    App\Providers\BladeDirectiveServiceProvider::class,
],
```

helpers twig anitgo C:\xampp\htdocs\easy-budget-laravel\old-system\app-twig
C:\xampp\htdocs\easy-budget-laravel\old-system\twig analisar para migrar, as views usavam agora deve ser alterado para laravem

### Classes Helper Necessárias

1. **BackupManager**

```php
namespace App\Services;

class BackupManager
{
    public function create()
    {
        // Lógica para criar backup
    }

    public function restore($filename)
    {
        // Lógica para restaurar backup
    }

    public function cleanup($days)
    {
        // Lógica para limpar backups antigos
    }
}
```

### Rotas a Implementar

```php
Route::prefix('admin/backups')->group(function () {
    Route::get('/', [BackupController::class, 'index'])->name('admin.backups.index');
    Route::post('/create', [BackupController::class, 'create'])->name('admin.backups.create');
    Route::post('/restore', [BackupController::class, 'restore'])->name('admin.backups.restore');
    Route::post('/delete', [BackupController::class, 'delete'])->name('admin.backups.delete');
    Route::post('/cleanup', [BackupController::class, 'cleanup'])->name('admin.backups.cleanup');
});
```

### Dependências Necessárias

```bash
composer require spatie/laravel-backup
```

### Configuração de Backup

Publicar configuração:

```bash
php artisan vendor:publish --provider="Spatie\Backup\BackupServiceProvider"
```

### Tasks Agendadas

Adicionar ao `App\Console\Kernel`:

```php
protected function schedule(Schedule $schedule)
{
    $schedule->command('backup:clean')->daily()->at('01:00');
    $schedule->command('backup:run')->daily()->at('02:00');
}
```
