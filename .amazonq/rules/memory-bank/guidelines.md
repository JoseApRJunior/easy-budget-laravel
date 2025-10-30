# Development Guidelines

## Code Quality Standards

### Strict Type Declarations
**Frequency: 100% of analyzed files**

All PHP files MUST start with strict type declaration:
```php
<?php

declare(strict_types=1);

namespace App\Services\Application;
```

This ensures type safety and prevents implicit type conversions that could lead to bugs.

### Current Project Status (Updated 2025)
**Laravel Version**: 12.x with PHP 8.2+
**Asset Management**: Migrated to Vite 5.0 with Hot Module Replacement
**Frontend Stack**: Tailwind CSS 3.1, Alpine.js 3.15, Bootstrap Icons 1.13
**Multi-tenant**: Stancl/Tenancy 3.7 with complete data isolation
**Payment Integration**: Mercado Pago DX PHP 3.0
**Testing**: PHPUnit 11.5.3, Laravel Dusk 8.3, PHPStan 2.1

### Namespace Organization
**Pattern observed in all files**

Namespaces follow PSR-4 autoloading standard:
```php
// Services layer
namespace App\Services\Core\Abstracts;
namespace App\Services\Application;
namespace App\Services\Infrastructure;

// Design patterns documentation
namespace App\DesignPatterns\Views;

// Tests
namespace Tests\Feature\Auth;
```

### Class Documentation
**Frequency: 100% of analyzed files**

Every class MUST have comprehensive PHPDoc blocks:
```php
/**
 * Service description explaining purpose and responsibility.
 *
 * Detailed explanation of what the class does, its role in the system,
 * and key characteristics or patterns it implements.
 *
 * @package App\Services\Application
 *
 * @example Basic usage example:
 * ```php
 * $service = new CustomerInteractionService();
 * $interaction = $service->createInteraction($customer, $data, $user);
 * ```
 */
class CustomerInteractionService
{
    // Implementation
}
```

### Method Documentation
**Pattern: Comprehensive documentation for all public methods**

```php
/**
 * Creates a new customer interaction.
 *
 * @param Customer $customer Customer entity
 * @param array $data Interaction data
 * @param User $user User creating the interaction
 * @return CustomerInteraction Created interaction
 *
 * @throws \Exception If creation fails
 */
public function createInteraction(Customer $customer, array $data, User $user): CustomerInteraction
{
    // Implementation
}
```

### Type Hints
**Frequency: 100% - Strict typing enforced**

All method parameters and return types MUST be explicitly typed:
```php
// ✅ CORRECT - Explicit types
public function findById(int $id, array $with = []): ServiceResult
public function formatCurrency(float $amount, ?string $locale = null): string
public function validateInteractionData(array $data): array

// ❌ INCORRECT - Missing types
public function findById($id, $with = [])
public function formatCurrency($amount, $locale = null)
```

### Property Visibility and Types
**Pattern: Typed properties with appropriate visibility**

```php
// ✅ CORRECT - Typed protected properties
protected BaseRepositoryInterface $repository;
private const CACHE_TTL = 3600;
private const DEFAULT_LOCALE = 'pt-BR';

// ❌ INCORRECT - Untyped properties
protected $repository;
private $cacheTtl = 3600;
```

## Structural Conventions

### Service Layer Architecture
**Pattern: Abstract base with concrete implementations**

Services follow a three-tier hierarchy:
1. **Abstract Base Service** - Common CRUD operations and utilities
2. **Domain Services** - Business logic specific to domain
3. **Application Services** - Orchestration and workflow management

```php
// Base service with common operations
abstract class AbstractBaseService implements CrudServiceInterface
{
    protected BaseRepositoryInterface $repository;
    
    public function __construct(BaseRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }
    
    // Common CRUD methods
    public function findById(int $id, array $with = []): ServiceResult
    public function create(array $data): ServiceResult
    public function update(int $id, array $data): ServiceResult
    public function delete(int $id): ServiceResult
}

// Concrete service extending base
class CustomerInteractionService extends AbstractBaseService
{
    // Domain-specific methods
    public function createInteraction(Customer $customer, array $data, User $user): CustomerInteraction
    public function getInteractionStats(User $user): array
}
```

### Transaction Management
**Pattern: Database transactions for complex operations**

```php
public function createInteraction(Customer $customer, array $data, User $user): CustomerInteraction
{
    return DB::transaction(function () use ($customer, $data, $user) {
        // Create interaction
        $interaction = CustomerInteraction::create([...]);
        
        // Update related entities
        $customer->increment('total_interactions');
        $customer->update(['last_interaction_at' => now()]);
        
        // Create reminders if needed
        if ($interaction->next_action && $interaction->next_action_date) {
            $this->createReminder($interaction, $user);
        }
        
        return $interaction;
    });
}
```

### Error Handling and Logging
**Pattern: Try-catch with comprehensive logging**

```php
public function translate(string $key, array $replace = [], ?string $locale = null): string
{
    try {
        // Attempt operation
        $translation = __($key, $replace, $locale);
        
        // Verify success
        if ($translation !== $key) {
            return $translation;
        }
        
        // Fallback logic
        if ($locale !== self::DEFAULT_LOCALE) {
            Log::info('Fallback de tradução usado', [
                'key' => $key,
                'original_locale' => $locale,
                'fallback_locale' => self::DEFAULT_LOCALE,
            ]);
            return $fallbackTranslation;
        }
        
        // Log warning if no translation found
        Log::warning('Tradução não encontrada', [
            'key' => $key,
            'locale' => $locale,
        ]);
        
        return $key;
    } catch (\Exception $e) {
        Log::error('Erro na tradução de e-mail', [
            'key' => $key,
            'locale' => $locale,
            'error' => $e->getMessage(),
        ]);
        
        return $key;
    }
}
```

### Service Result Pattern
**Pattern: Standardized response wrapper**

```php
// Success response
protected function success(mixed $data = null, string $message = ''): ServiceResult
{
    return ServiceResult::success($data, $message);
}

// Error response with status and exception
protected function error(
    OperationStatus|string $status,
    string $message = '',
    mixed $data = null,
    ?Exception $exception = null
): ServiceResult {
    $finalStatus = is_string($status) ? OperationStatus::ERROR : $status;
    return ServiceResult::error($finalStatus, $message, $data, $exception);
}

// Usage in methods
public function findById(int $id, array $with = []): ServiceResult
{
    try {
        $entity = $this->repository->find($id);
        
        if (!$entity) {
            return $this->error(
                OperationStatus::NOT_FOUND,
                "Recurso com ID {$id} não encontrado."
            );
        }
        
        return $this->success($entity, 'Busca realizada com sucesso.');
    } catch (Exception $e) {
        return $this->error(
            OperationStatus::ERROR,
            "Erro ao buscar recurso.",
            null,
            $e
        );
    }
}
```

## Semantic Patterns

### Dependency Injection
**Pattern: Constructor injection for dependencies**

```php
class CustomerInteractionService
{
    // Dependencies injected via constructor
    public function __construct(
        private CustomerRepository $customerRepository,
        private NotificationService $notificationService
    ) {}
    
    // Methods use injected dependencies
    public function createInteraction(Customer $customer, array $data, User $user): CustomerInteraction
    {
        // Use dependencies
        $interaction = $this->customerRepository->create($data);
        $this->notificationService->notify($customer, $interaction);
        
        return $interaction;
    }
}
```

### Caching Strategy
**Pattern: Cache with TTL and key namespacing**

```php
private const CACHE_TTL = 3600;

public function getSupportedLocales(): array
{
    return Cache::remember('supported_email_locales', self::CACHE_TTL, function () {
        $locales = [];
        $langPath = resource_path('lang');
        
        // Build locales array
        foreach (scandir($langPath) as $dir) {
            if ($dir !== '.' && $dir !== '..' && is_dir($langPath . '/' . $dir)) {
                $locales[$dir] = $this->getLocaleName($dir);
            }
        }
        
        return $locales;
    });
}

public function clearLocaleCache(): bool
{
    return Cache::forget('supported_email_locales');
}
```

### Query Scopes and Filters
**Pattern: Chainable query methods with scopes**

```php
public function getCustomerInteractions(Customer $customer, array $filters = []): LengthAwarePaginator
{
    $query = $customer->interactions()
        ->with(['user'])
        ->orderBy('interaction_date', 'desc');
    
    // Apply filters using scopes
    if (!empty($filters['type'])) {
        $query->ofType($filters['type']);
    }
    
    if (!empty($filters['direction'])) {
        $query->ofDirection($filters['direction']);
    }
    
    if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
        $query->inDateRange($filters['start_date'], $filters['end_date']);
    }
    
    if (!empty($filters['pending_actions'])) {
        $query->pendingActions();
    }
    
    return $query->paginate($filters['per_page'] ?? 15);
}
```

### Match Expressions (PHP 8+)
**Pattern: Modern switch replacement**

```php
private function getLocaleName(string $locale): string
{
    return match ($locale) {
        'pt-BR' => 'Português (Brasil)',
        'en' => 'English',
        default => ucfirst(str_replace(['-', '_'], ' ', $locale)),
    };
}
```

### Validation Methods
**Pattern: Dedicated validation with error collection**

```php
public function validateInteractionData(array $data): array
{
    $errors = [];
    
    if (empty($data['type'])) {
        $errors[] = 'Tipo de interação é obrigatório.';
    }
    
    if (empty($data['title'])) {
        $errors[] = 'Título da interação é obrigatório.';
    }
    
    if (!empty($data['next_action_date']) && !empty($data['interaction_date'])) {
        $interactionDate = strtotime($data['interaction_date']);
        $nextActionDate = strtotime($data['next_action_date']);
        
        if ($nextActionDate <= $interactionDate) {
            $errors[] = 'Data da próxima ação deve ser posterior à data da interação.';
        }
    }
    
    return $errors;
}
```

## View Patterns (Blade Templates)

### Three-Level View Architecture
**Pattern: Basic → Form → Advanced**

#### Level 1 - Basic Views
For simple static pages:
```blade
@extends('layouts.app')

@section('content')
<main class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-5">
                    @yield('page-content')
                </div>
            </div>
        </div>
    </div>
</main>
@endsection

@push('styles')
<style>
    /* Page-specific styles */
</style>
@endpush

@push('scripts')
<script>
    // Page-specific scripts
</script>
@endpush
```

#### Level 2 - Form Views
For create/edit pages:
```blade
@extends('layouts.app')

@section('content')
<div class="container py-4">
    <!-- Header with breadcrumbs -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">@yield('title', 'Form Title')</h1>
            <p class="text-muted mb-0">@yield('subtitle', 'Form description')</p>
        </div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                @yield('breadcrumbs')
            </ol>
        </nav>
    </div>

    <!-- Form -->
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <form method="POST" action="@yield('action', '#')">
                        @csrf
                        @method(@yield('method', 'POST'))
                        
                        @yield('form-fields')
                        
                        <!-- Action buttons -->
                        <div class="d-flex justify-content-between pt-4">
                            <a href="@yield('back-url', 'javascript:history.back()')" 
                               class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-left me-2"></i>Voltar
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-lg me-2"></i>@yield('submit-text', 'Salvar')
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
```

#### Level 3 - Advanced Views
For listings with filters and AJAX:
```blade
@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <!-- Header with actions -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">@yield('title', 'Listagem')</h1>
            <p class="text-muted mb-0">@yield('subtitle', 'Gerencie os registros')</p>
        </div>
        <div class="d-flex gap-2">
            @yield('header-actions')
        </div>
    </div>

    <!-- Filters -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <form method="GET" class="row g-3">
                @yield('filters')
            </form>
        </div>
    </div>

    <!-- Initial state -->
    <div id="initial-state" class="card border-0 shadow-sm text-center py-5">
        <div class="card-body">
            <i class="bi bi-search text-primary mb-3" style="font-size: 3rem;"></i>
            <h5>Use os filtros acima para buscar</h5>
        </div>
    </div>

    <!-- Loading state -->
    <div id="loading-state" class="d-none">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <div class="spinner-border text-primary mb-3"></div>
                <p class="text-muted mb-0">Processando...</p>
            </div>
        </div>
    </div>

    <!-- Results -->
    <div id="results-container" class="d-none">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            @yield('table-header')
                        </thead>
                        <tbody id="results-body">
                            @yield('table-body')
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    class AdvancedListing {
        constructor() {
            this.initializeComponents();
            this.bindEvents();
        }
        
        async loadData(filters = {}) {
            this.showLoading();
            try {
                const response = await fetch(url);
                this.showResults(await response.json());
            } catch (error) {
                this.showError(error.message);
            }
        }
    }
    
    document.addEventListener('DOMContentLoaded', () => {
        new AdvancedListing();
    });
</script>
@endpush
```

### Blade Conventions
**Pattern: Consistent structure and organization**

```blade
{{-- Always use Blade comments for documentation --}}

{{-- 1. Extend layout --}}
@extends('layouts.app')

{{-- 2. Define sections --}}
@section('content')
    <!-- Content here -->
@endsection

{{-- 3. Push styles --}}
@push('styles')
<style>
    /* Component-specific styles */
</style>
@endpush

{{-- 4. Push scripts --}}
@push('scripts')
<script>
    // Component-specific JavaScript with modern ES6+
    document.addEventListener('DOMContentLoaded', function() {
        // Initialization code
    });
</script>
@endpush

{{-- 5. Use components for reusability --}}
@component('partials.components.card', ['title' => 'Card Title'])
    <p>Card content</p>
@endcomponent

{{-- 6. Conditional rendering --}}
@if($data->isEmpty())
    <div class="alert alert-info">Nenhum registro encontrado.</div>
@else
    <div class="table-responsive">
        <!-- Table content -->
    </div>
@endif

{{-- 7. Loop with consistent structure --}}
@foreach($items as $item)
    <div class="item-card">
        <h3>{{ $item->name }}</h3>
        <p>{{ $item->description }}</p>
    </div>
@endforeach

{{-- 8. Format data consistently --}}
<span class="badge bg-{{ $status->color }}">
    {{ $status->label }}
</span>
```

### Modern JavaScript Integration
**Pattern: Vanilla JavaScript with Alpine.js components**

```javascript
// Form validation with modern JavaScript
function validateRequiredField(input, fieldName) {
    const value = input.value.trim();
    
    if (!value) {
        input.classList.add('is-invalid');
        showError(input, `O ${fieldName} é obrigatório.`);
    } else {
        input.classList.remove('is-invalid');
        clearError(input);
    }
}

// Date validation with comprehensive checks
function isValidBirthDate(value) {
    if (!isValidDateFormat(value)) return false;
    
    const parts = value.split('/');
    const birthDate = new Date(parts[2], parts[1] - 1, parts[0]);
    const today = new Date();
    
    // Age validation (18+ years)
    let age = today.getFullYear() - birthDate.getFullYear();
    const monthDiff = today.getMonth() - birthDate.getMonth();
    
    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
        age--;
    }
    
    return age >= 18 && birthDate < today;
}

// File upload with preview
document.getElementById('logo')?.addEventListener('change', function(e) {
    const file = e.target.files[0];
    const maxSize = 5242880; // 5MB
    
    if (file && file.size <= maxSize) {
        const reader = new FileReader();
        reader.onload = e => {
            document.getElementById('logo-preview').src = e.target.result;
        };
        reader.readAsDataURL(file);
    }
});
```

## Testing Patterns

### Test Structure
**Pattern: Arrange-Act-Assert with descriptive names**

```php
/**
 * Test method with descriptive name explaining what is being tested.
 */
public function test_complete_registration_flow_success(): void
{
    // Arrange - Setup test data and mocks
    Event::fake();
    Mail::fake();
    
    $plan = Plan::factory()->create([
        'name' => 'Trial',
        'slug' => 'trial',
        'price' => 0.00,
        'status' => true,
    ]);
    
    $userData = [
        'first_name' => 'Maria',
        'last_name' => 'Santos',
        'email' => 'maria.santos@example.com',
        'password' => 'SenhaForte123@',
        'password_confirmation' => 'SenhaForte123@',
        'terms_accepted' => '1',
    ];
    
    // Act - Execute the action being tested
    $response = $this->postJson('/register', $userData);
    
    // Assert - Verify expected outcomes
    $response->assertRedirect(route('provider.dashboard', absolute: false));
    $response->assertSessionHas('success');
    
    $this->assertDatabaseHas('users', [
        'email' => 'maria.santos@example.com',
        'is_active' => true,
    ]);
    
    Event::assertDispatched(UserRegistered::class);
}
```

### Test Naming Convention
**Pattern: test_method_scenario_expectedOutcome**

```php
// ✅ CORRECT - Descriptive test names
public function test_registration_with_invalid_data(): void
public function test_registration_with_duplicate_email(): void
public function test_user_registered_event_dispatched(): void
public function test_redirect_to_dashboard_after_successful_registration(): void

// ❌ INCORRECT - Vague test names
public function testRegistration(): void
public function testEmail(): void
public function testSuccess(): void
```

### Mocking and Faking
**Pattern: Use Laravel's fake() methods for external services**

```php
public function test_send_welcome_email_listener_processes_event(): void
{
    // Fake external services
    Event::fake();
    Mail::fake();
    
    $user = User::factory()->create();
    $tenant = Tenant::factory()->create();
    
    // Dispatch event
    Event::dispatch(new UserRegistered($user, $tenant, 'token_123'));
    
    // Assert event was dispatched
    Event::assertDispatched(UserRegistered::class);
}
```

### Database Assertions
**Pattern: Verify database state after operations**

```php
// Assert record exists
$this->assertDatabaseHas('users', [
    'email' => 'test@example.com',
    'is_active' => true,
]);

// Assert record doesn't exist
$this->assertDatabaseMissing('users', [
    'email' => 'invalid@example.com',
]);

// Assert authentication state
$this->assertAuthenticated();
$this->assertAuthenticatedAs($user);
```

## Best Practices Summary

### Code Organization
1. **One class per file** - Each file contains exactly one class
2. **Logical grouping** - Related classes in same namespace
3. **Clear separation** - Controllers, Services, Repositories, Models in separate layers
4. **Consistent naming** - PascalCase for classes, camelCase for methods

### Error Handling
1. **Try-catch blocks** - Wrap risky operations
2. **Comprehensive logging** - Log errors with context
3. **Graceful degradation** - Provide fallbacks when possible
4. **User-friendly messages** - Return clear error messages

### Performance
1. **Database transactions** - Use for multi-step operations
2. **Eager loading** - Prevent N+1 queries with with()
3. **Caching** - Cache expensive operations with TTL
4. **Query optimization** - Use indexes and efficient queries

### Security
1. **Type safety** - Strict types prevent injection
2. **Input validation** - Validate all user input
3. **CSRF protection** - Use @csrf in forms
4. **SQL injection prevention** - Use Eloquent ORM

### Maintainability
1. **Comprehensive documentation** - PHPDoc for all public methods
2. **Consistent patterns** - Follow established patterns
3. **DRY principle** - Don't repeat yourself
4. **SOLID principles** - Single responsibility, dependency injection
5. **Testability** - Write testable code with dependency injection
