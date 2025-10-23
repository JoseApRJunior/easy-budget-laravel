# Quickstart: Easy Budget Platform

**Date**: 2025-10-23 | **Input**: Technology stack from memory bank

## Overview

This quickstart guide provides the essential setup instructions for developing the Easy Budget Platform using Laravel 12 and the specified technology stack.

## Prerequisites

### System Requirements

-  PHP >= 8.3
-  MySQL >= 8.0 with InnoDB engine
-  Redis >= 7.0 (optional, for caching)
-  Composer
-  NPM
-  Git

### Extensions

-  PDO
-  Mbstring
-  OpenSSL
-  Tokenizer
-  XML
-  Ctype
-  JSON
-  BCMath

## Installation

### 1. Clone the Repository

```bash
git clone <repository-url>
cd easy-budget-laravel
```

### 2. Install Dependencies

```bash
composer install
npm install
```

### 3. Environment Configuration

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` with your database credentials:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=easy_budget
DB_USERNAME=your_username
DB_PASSWORD=your_password

CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=database

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=log
```

### 4. Database Setup

```bash
php artisan migrate
php artisan db:seed
```

### 5. Build Assets

```bash
npm run build
```

### 6. Start Development Server

```bash
php artisan serve
```

Access the application at `http://localhost:8000`

## Development Workflow

### Code Standards

-  PSR-12 for PHP code
-  Laravel Pint for automatic formatting
-  Blade templates for views
-  Semantic commit messages

### Architecture Patterns

-  Repository Pattern for data access
-  Service Layer for business logic
-  Observer Pattern for events
-  Strategy Pattern for algorithms

### Testing

```bash
# Run all tests
php artisan test

# Run specific test types
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit

# Generate coverage report
php artisan test --coverage
```

### Key Commands

```bash
# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Queue worker
php artisan queue:work

# Database operations
php artisan migrate:status
php artisan migrate:rollback

# Asset compilation
npm run dev  # Development
npm run build  # Production
```

## Project Structure

### Backend (Laravel)

-  `app/Http/Controllers/` - HTTP controllers
-  `app/Services/` - Business logic services
-  `app/Repositories/` - Data access repositories
-  `app/Models/` - Eloquent models
-  `database/migrations/` - Database migrations
-  `routes/` - Route definitions

### Frontend

-  `resources/views/` - Blade templates
-  `resources/css/` - Stylesheets
-  `resources/js/` - JavaScript files
-  `public/assets/` - Compiled assets

### Configuration

-  `config/` - Laravel configuration files
-  `.env` - Environment variables

## Security Setup

### Authentication

-  Laravel Sanctum for API authentication
-  Google OAuth 2.0 via Laravel Socialite
-  Multi-factor authentication support

### Environment

-  Set `APP_DEBUG=false` in production
-  Configure rate limiting in middleware
-  Enable CSRF protection
-  Implement XSS protection via Blade

## Deployment

### Production Build

```bash
composer install --optimize-autoloader --no-dev
npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Web Server Configuration

-  Apache/Nginx with mod_rewrite enabled
-  SSL/HTTPS required
-  Proper permissions on storage and bootstrap/cache

## Troubleshooting

### Common Issues

-  **Permissions**: `chmod -R 755 storage bootstrap/cache`
-  **Composer**: `composer dump-autoload`
-  **Cache**: Clear all caches after changes
-  **Database**: Check migration status and rollback if needed

### Monitoring

-  Check `storage/logs/laravel.log` for errors
-  Monitor `storage/logs/audit.log` for security events
-  Use Laravel Debugbar in development

## Next Steps

1. Review the implementation plan in `plan.md`
2. Follow the task breakdown in `tasks.md` (to be created)
3. Implement features incrementally with testing
4. Update documentation as changes are made

This quickstart provides the foundation for developing the Easy Budget Platform. Refer to the detailed documentation in the memory bank for architecture patterns, database schema, and business requirements.
