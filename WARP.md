# WARP.md

This file provides guidance to WARP (warp.dev) when working with code in this repository.

Project overview
- Laravel application with a layered architecture (Controllers → Services → Repositories → Models) and multi-tenant support via stancl/tenancy. Frontend assets are built with Vite and Tailwind CSS. Tests use PHPUnit via php artisan test.

Quick commands
- Install dependencies
  - PHP: composer install
  - Node: npm install
- Environment setup
  - Copy env and app key: php -r "copy('.env.example', '.env');"; php artisan key:generate
  - Migrate DB: php artisan migrate
- Run development stack (single command)
  - composer run dev
  - This runs: php artisan serve, php artisan queue:listen --tries=1, php artisan pail --timeout=0, and npm run dev (from composer.json)
- Run separately (alternative)
  - App: php artisan serve
  - Vite (HMR): npm run dev
  - Queue listener: php artisan queue:listen --tries=1
  - Logs (pail): php artisan pail --timeout=0
- Build frontend assets
  - npm run build
- Tests
  - All tests: php artisan test
  - Single test class: php artisan test --filter=ClassName
  - Single test method: php artisan test --filter=ClassName::methodName
  - By path (PHPUnit): vendor/bin/phpunit tests/Feature/SomeTest.php
  - Test DB defaults (phpunit.xml): mysql on 127.0.0.1:3306 using database easybudgetlaravel_test, user root, no password
- Linting & static analysis
  - PHP formatter (Laravel Pint): vendor/bin/pint
  - PHPStan (level 5, phpstan.neon): vendor/bin/phpstan analyse --configuration=phpstan.neon

Architecture and structure (big picture)
- Routing
  - Web routes: routes/web.php for pages (welcome, dashboard, profile) guarded by auth/verified.
  - Auth routes: routes/auth.php provided by Breeze for register/login/reset/verify flows.
  - API routes: routes/api.php (auth middleware) includes search/filter endpoints (customers, products, services, budgets, invoices, CEP), admin metrics, and a RESTful Budget API under /v1/budgets with CRUD, status changes, duplication, PDF, bulk operations, reports and statistics.
  - Tenant routes: routes/tenant.php use Stancl Tenancy middlewares (InitializeTenancyByDomain, PreventAccessFromCentralDomains) to scope requests by domain.
- Providers and dependency injection
  - app/Providers/AppServiceProvider.php binds most domain Services as singletons and wires interfaces to concrete implementations (e.g., MercadoPago service interfaces). Rely on constructor injection throughout.
  - app/Providers/TenancyServiceProvider.php registers Stancl Tenancy event listeners and job pipelines: on tenant created → create and migrate tenant DB; on tenant deleted → delete DB. It also ensures tenancy middleware has highest priority and loads routes/tenant.php.
- Controllers → Services → Repositories
  - Controllers (app/Http/Controllers) are thin and delegate to Services. Notable areas include Budgets, Invoices, Customers, Products, Services, Providers, Roles/Permissions, Monitoring, Reports, Webhooks, etc.
  - Services (app/Services)
    - Base abstract: app/Services/Abstracts/BaseService.php provides helpers for success/error via ServiceResult, validation helpers, and accessors for auth user/tenant.
    - Domain services (e.g., BudgetService, InvoiceService, ProductService, UserService) encapsulate business logic and orchestrate repositories and integrations (Mailer, MercadoPago, PDF, Cache, ReportStorage, etc.).
  - Repositories (app/Repositories)
    - app/Repositories/AbstractRepository.php centralizes Eloquent access and is tenant-aware by default: queries are filtered by tenant_id via applyTenantFilter(). Provides find/save/update/delete patterns and criteria/order/limit handling, with structured logging around operations.
  - Models (app/Models)
    - Eloquent models for all domain entities. Tenant scoping helpers live in app/Traits/BelongsToTenant.php (tenant() relation and forTenant scope).
- Multi-tenancy patterns
  - Distinct base patterns for tenant-scoped vs global services exist (see app/DesignPatterns and Services/Abstracts). WithTenant vs NoTenant examples are documented in app/DesignPatterns/README.md.
- Result handling and enums
  - ServiceResult (app/Support/ServiceResult.php) standardizes service outcomes (SUCCESS/ERROR/NOT_FOUND/etc.) with message, data, and error. OperationStatus enum (app/Enums/OperationStatus.php) defines statuses/messages used by services.
- Frontend assets
  - Vite is configured in vite.config.js with multiple JS/CSS entry points under resources/js and resources/css; Tailwind is configured in tailwind.config.js and postcss.config.js.

Important notes from README.md
- Development commands for Vite: npm run dev (HMR) and npm run build (production). The README mentions npm run preview, but no preview script is defined in package.json.
- Installation sequence: composer install → npm install → configure .env → php artisan migrate → start php artisan serve and run npm run dev.
- See docs/legacy-assets-backup.md for notes about legacy assets migration.

Environment assumptions
- PHP ≥ 8.2 (composer.json), Node with Vite, and MySQL reachable locally. PHPUnit config (phpunit.xml) sets a default test database (easybudgetlaravel_test). If credentials differ locally, override via a phpunit.xml.local or environment.
