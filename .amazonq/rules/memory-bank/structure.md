# Project Structure

## Directory Organization

### Core Application (`app/`)
The main application logic organized by architectural layers and concerns.

#### **Console/** - Artisan Commands
- Custom CLI commands for maintenance and automation
- `Kernel.php` - Command scheduling and registration

#### **Contracts/Interfaces/** - Interface Definitions
- Service contracts and repository interfaces
- Defines contracts for dependency injection

#### **DesignPatterns/** - Architectural Patterns & Documentation
- `Controllers/` - Controller pattern examples and documentation
- `Models/` - Model pattern examples and documentation
- `Repositories/` - Repository pattern examples and documentation
- `Services/` - Service pattern examples and documentation
- `Views/` - View pattern examples and documentation
- `Stubs/` - Code generation templates
- `README-GERAL.md` - Comprehensive pattern documentation

#### **Enums/** - Enumeration Types
- `BudgetStatus.php`, `BudgetStatusEnum.php` - Budget state management
- `InvoiceStatus.php`, `InvoiceStatusEnum.php` - Invoice state management
- `ServiceStatus.php`, `ServiceStatusEnum.php` - Service state management
- `OperationStatus.php` - General operation states
- `SupportStatus.php` - Support ticket states
- `TokenType.php` - Authentication token types

#### **Events/** - Domain Events
- `EmailVerificationRequested.php` - Email verification trigger
- `InvoiceCreated.php` - Invoice creation event
- `PasswordResetRequested.php` - Password reset trigger
- `SocialAccountLinked.php` - Social login connection
- `StatusUpdated.php` - Entity status changes
- `SupportTicketCreated.php`, `SupportTicketResponded.php` - Support events
- `UserRegistered.php` - New user registration

#### **Exceptions/** - Error Handling
- `Handler.php` - Global exception handler with custom error responses

#### **Helpers/** - Utility Functions
- `BackupHelper.php` - Backup operations
- `BladeHelper.php` - Blade directive utilities
- `CurrencyHelper.php` - Currency formatting and conversion
- `DateHelper.php` - Date manipulation and formatting
- `FlashHelper.php` - Flash message management
- `MathHelper.php` - Mathematical operations
- `ModelHelper.php` - Model utility functions
- `StatusHelper.php` - Status management utilities

#### **Http/** - HTTP Layer
- `Controllers/` - Request handlers organized by domain
- `Middleware/` - Request/response filters
- `Requests/` - Form request validation classes

#### **Jobs/** - Background Tasks
- `SendEmailJob.php` - Asynchronous email sending

#### **Listeners/** - Event Handlers
- Email notification listeners for various events
- Status update notification handlers
- Support system notification handlers

#### **Mail/** - Email Templates
- `Concerns/` - Shared email traits
- Mailable classes for all email types
- Email verification, password reset, invoices, etc.

#### **Models/** - Eloquent Models
Core business entities with relationships and scopes:
- **User Management**: `User.php`, `UserRole.php`, `UserSettings.php`, `UserConfirmationToken.php`
- **Tenant System**: `Tenant.php`, `Provider.php`, `ProviderCredential.php`
- **CRM**: `Customer.php`, `CustomerAddress.php`, `CustomerContact.php`, `CustomerInteraction.php`, `CustomerTag.php`
- **Financial**: `Budget.php`, `BudgetItem.php`, `Invoice.php`, `InvoiceItem.php`
- **Inventory**: `Product.php`, `ProductInventory.php`, `InventoryMovement.php`
- **Services**: `Service.php`, `ServiceItem.php`
- **System**: `AuditLog.php`, `EmailLog.php`, `Notification.php`, `Session.php`
- **Traits/**: Shared model behaviors

#### **Notifications/** - Laravel Notifications
- `BudgetStatusNotification.php` - Budget status change notifications

#### **Providers/** - Service Providers
- `AppServiceProvider.php` - Application bootstrapping
- `EventServiceProvider.php` - Event-listener registration
- `RouteServiceProvider.php` - Route configuration
- `TenancyServiceProvider.php` - Multi-tenant setup
- `BladeDirectiveServiceProvider.php` - Custom Blade directives
- `FlashMessageServiceProvider.php` - Flash message system
- `MailViewServiceProvider.php` - Email view configuration

#### **Repositories/** - Data Access Layer
Organized with dual architecture (Tenant vs Global):
- `Abstracts/` - Base repository classes
- `Contracts/` - Repository interfaces
- `Traits/` - Shared repository behaviors
- Domain-specific repositories for all major entities

#### **Services/** - Business Logic Layer
Organized by architectural layers:
- `Application/` - Application-specific services (15 services)
  - Auth/, BudgetCalculationService, EmailVerificationService, etc.
- `Core/` - Core business services with abstracts
  - `Abstracts/AbstractBaseService.php` - Base service implementation
  - `Contracts/` - Service interfaces (5 interfaces)
  - `Traits/ServiceValidationHelpers.php` - Validation utilities
- `Domain/` - Domain-specific business logic (15 services)
  - ActivityService, BudgetService, CustomerService, etc.
- `Infrastructure/` - Infrastructure services (20+ services)
  - OAuth/, Email services, Payment services, PDF generation
- `Shared/` - Shared service utilities
  - CacheService, NotificationService
- Root level: `ChartService.php`, `MetricsService.php` - Analytics services

#### **Support/** - Support Utilities
- `helpers.php` - Global helper functions
- `ServiceResult.php` - Service response wrapper

#### **Traits/** - Reusable Behaviors
- `BelongsToTenant.php` - Multi-tenant scoping
- `SlugGenerator.php` - URL slug generation

#### **View/Components/** - Blade Components
- Reusable UI components

### Configuration (`config/`)
Application configuration files:
- `app.php` - Application settings
- `auth.php` - Authentication configuration
- `database.php` - Database connections
- `mail.php` - Email configuration
- `tenancy.php` - Multi-tenant settings
- `services.php` - Third-party service credentials
- `socialite.php` - Social authentication
- Custom configs: `email-templates.php`, `email-senders.php`

### Database (`database/`)
- `factories/` - Model factories for testing and seeding
- `migrations/` - Database schema migrations
- `seeders/` - Database seeders for initial data

### Resources (`resources/`)
Frontend assets and views:

#### **css/** - Stylesheets
- `app.css` - Main application styles

#### **js/** - JavaScript
- `app.js` - Main application JavaScript
- `bootstrap.js` - Bootstrap configuration

#### **lang/** - Translations
- `en/` - English translations
- `pt-BR/` - Brazilian Portuguese translations

#### **views/** - Blade Templates
- `admin/` - Admin panel views
- `auth/` - Authentication views
- `budgets/` - Budget management views
- `components/` - Reusable components
- `dashboard/` - Dashboard views
- `emails/` - Email templates
- `errors/` - Error pages
- `invoices/` - Invoice views
- `layouts/` - Layout templates
- `pages/` - Static pages
- `partials/` - Partial views
- `profile/` - User profile views
- `services/` - Service management views
- `settings/` - Settings views

### Routes (`routes/`)
- `web.php` - Web routes
- `api.php` - API routes
- `auth.php` - Authentication routes
- `tenant.php` - Tenant-specific routes
- `console.php` - Console commands

### Tests (`tests/`)
- `Feature/` - Feature tests
  - `Auth/` - Authentication tests
  - `Contract/` - Contract tests
  - `Integration/` - Integration tests
  - Domain-specific feature tests
- `Unit/` - Unit tests
  - Service tests
  - Event tests
  - Listener tests

### Public (`public/`)
Publicly accessible files:
- `index.php` - Application entry point
- `assets/` - Compiled assets (CSS, JS, images)

### Storage (`storage/`)
- `app/` - Application files
- `framework/` - Framework cache and sessions
- `logs/` - Application logs

### Vendor (`vendor/`)
Third-party dependencies managed by Composer

## Architectural Patterns

### Layered Architecture
```
Controllers → Services → Repositories → Models
```

**Controller Layer**
- Handles HTTP requests and responses
- Validates input through Form Requests
- Delegates business logic to Services
- Returns views or JSON responses

**Service Layer**
- Contains business logic and workflows
- Orchestrates multiple repositories
- Handles transactions and complex operations
- Returns ServiceResult objects

**Repository Layer**
- Abstracts data access
- Provides query methods
- Handles tenant scoping
- Returns Eloquent models or collections

**Model Layer**
- Represents database entities
- Defines relationships
- Contains accessors/mutators
- Implements model events

### Multi-tenant Architecture
- **Tenant Scoping**: Automatic filtering by tenant_id
- **Global Scopes**: Applied to all tenant-aware models
- **Tenant Middleware**: Identifies and sets current tenant
- **Isolated Data**: Complete separation between tenants

### Event-Driven Architecture
- **Events**: Triggered by domain actions
- **Listeners**: Handle event consequences
- **Jobs**: Asynchronous processing
- **Notifications**: User communication

### Repository Pattern Variants
- **Tenant Repositories**: Automatically scope by tenant
- **Global Repositories**: Access all data (admin operations)
- **Abstract Base**: Shared repository functionality

## Key Components

### Authentication System
- Laravel Sanctum for API authentication
- Custom email verification with tokens
- Social authentication via Socialite
- Role-based access control

### Multi-tenant System
- Stancl/Tenancy package integration
- Automatic tenant identification
- Tenant-specific database scoping
- Provider-tenant relationship

### Email System
- Queue-based email sending
- Customizable email templates
- Email variable substitution
- Email logging and tracking

### Audit System
- Comprehensive action logging
- User activity tracking
- Change history for entities
- Security event logging

### Reporting System
- Dynamic report generation
- Scheduled report execution
- Multiple export formats
- Chart and metric visualization

## Design Pattern Documentation
The `app/DesignPatterns/` directory contains comprehensive documentation and examples for:
- Controller patterns (3 levels: Simple, Filtered, Hybrid)
- Service patterns (3 levels: Basic, Intermediate, Advanced)
- Repository patterns (Tenant vs Global architecture)
- Model patterns (3 levels: Basic, Relationships, Advanced)
- View patterns (3 levels: Basic, Form, AJAX)

Each pattern includes:
- Detailed documentation
- Code examples
- Best practices
- Implementation guidelines
