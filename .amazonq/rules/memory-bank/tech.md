# Technology Stack

## Programming Languages

### PHP 8.2+
- **Version**: PHP 8.2 or higher (optimized for PHP 8.3+)
- **Features Used**:
  - Typed properties and return types
  - Constructor property promotion
  - Named arguments
  - Match expressions
  - Enumerations (Enums)
  - Readonly properties
  - Null-safe operator

### JavaScript ES6+
- Modern JavaScript with ES6+ features
- Module-based architecture
- Async/await for asynchronous operations
- Arrow functions and destructuring

### SQL
- MySQL 8.0+ compatible
- InnoDB storage engine
- Foreign key constraints
- Full-text search capabilities

## Backend Framework

### Laravel 12
- **Version**: ^12.0
- **Core Features**:
  - Eloquent ORM for database operations
  - Blade templating engine
  - Artisan CLI for commands
  - Migration system for database versioning
  - Seeding for test data
  - Queue system for background jobs
  - Event-driven architecture
  - Service container for dependency injection

### Laravel Packages

#### Authentication & Authorization
- **Laravel Sanctum** (^4.2) - API token authentication
- **Laravel Socialite** (^5.23) - Social authentication (Google, Facebook, etc.)

#### Multi-tenancy
- **Stancl/Tenancy** (^3.7) - Multi-tenant architecture with automatic tenant scoping

#### Development Tools
- **Laravel Breeze** (^2.3) - Authentication scaffolding
- **Laravel Pail** (^1.2.2) - Log viewer
- **Laravel Pint** (^1.24) - Code style fixer
- **Laravel Sail** (^1.41) - Docker development environment
- **Laravel Tinker** (^2.10.1) - REPL for Laravel

#### Utilities
- **Spatie Laravel Directory Cleanup** (^1.10) - Automatic directory cleanup

### PHP Libraries

#### Database & ORM
- **Doctrine DBAL** (^4.3) - Database abstraction layer
- **Doctrine ORM** (3.5) - Object-relational mapping

#### Image Processing
- **Intervention Image** (3) - Image manipulation and processing

#### Payment Integration
- **Mercado Pago DX PHP** (3) - Payment gateway integration

#### Document Generation
- **mPDF** (8.2) - PDF generation
- **PhpSpreadsheet** (4) - Excel file generation and manipulation

#### Development & Testing
- **PHPUnit** (^11.5.3) - Unit testing framework
- **Mockery** (^1.6) - Mocking framework
- **PHPStan** (^2.1) - Static analysis tool
- **Faker** (^1.23) - Fake data generation
- **Barryvdh Laravel Debugbar** (^3.16) - Debug toolbar

## Frontend Technologies

### Build Tools
- **Vite** (^5.0.0) - Modern build tool with HMR (Hot Module Replacement)
- **Laravel Vite Plugin** (^1.0.0) - Laravel integration for Vite

### CSS Frameworks & Tools
- **Tailwind CSS** (^3.1.0) - Utility-first CSS framework
- **@tailwindcss/forms** (^0.5.2) - Form styling plugin
- **PostCSS** (^8.4.31) - CSS processing
- **Autoprefixer** (^10.4.2) - CSS vendor prefixing

### JavaScript Libraries
- **Alpine.js** (^3.15.0) - Lightweight JavaScript framework
- **Axios** (^1.6.4) - HTTP client for API requests
- **Bootstrap Icons** (^1.13.1) - Icon library

## Database

### MySQL 8.0+
- **Storage Engine**: InnoDB
- **Features**:
  - ACID compliance
  - Foreign key constraints
  - Full-text indexing
  - JSON data type support
  - Spatial data support

### Redis 7.0+
- **Usage**:
  - Session storage
  - Cache management
  - Queue backend
  - Real-time data

## Development Environment

### Local Development
- **XAMPP** - Apache, MySQL, PHP stack
- **Composer** - PHP dependency management
- **NPM** - Node.js package management
- **Git** - Version control

### Development Commands

#### PHP/Laravel Commands
```bash
# Install PHP dependencies
composer install

# Run database migrations
php artisan migrate

# Seed database with test data
php artisan db:seed

# Start development server
php artisan serve

# Run queue worker
php artisan queue:listen

# View logs in real-time
php artisan pail

# Run tests
php artisan test

# Code style fixing
./vendor/bin/pint

# Static analysis
./vendor/bin/phpstan analyse
```

#### Node.js/Frontend Commands
```bash
# Install Node.js dependencies
npm install

# Start Vite development server (with HMR)
npm run dev

# Build for production
npm run build
```

#### Combined Development
```bash
# Run all development services concurrently
composer dev
# This runs: server, queue worker, log viewer, and Vite
```

### Testing Tools
- **PHPUnit** - Unit and feature testing
- **Pest** - Alternative testing framework (optional)
- **Browser Testing** - Laravel Dusk (if needed)

### Code Quality Tools
- **Laravel Pint** - Opinionated PHP code style fixer
- **PHPStan** - Static analysis for type safety
- **PHP CS Fixer** - Code style fixer (alternative)

## Third-Party Services

### Payment Gateway
- **Mercado Pago** - Payment processing for Latin America
  - Credit/debit card processing
  - Boleto bancário
  - PIX integration
  - Subscription management

### Email Services
- **SMTP** - Configurable email sending
- **Mailtrap** - Email testing in development
- **Queue-based sending** - Asynchronous email delivery

### Social Authentication
- **Google OAuth** - Google login integration
- **Facebook OAuth** - Facebook login integration
- Extensible for other providers via Socialite

## Asset Management

### Vite Configuration
- **Entry Points**: 
  - `resources/js/app.js` - Main JavaScript
  - `resources/css/app.css` - Main CSS
- **Features**:
  - Hot Module Replacement (HMR)
  - Automatic asset versioning
  - CSS/JS minification
  - Tree shaking for optimal bundle size
  - Source maps for debugging

### Asset Structure
```
resources/
├── css/
│   └── app.css
├── js/
│   ├── app.js
│   └── bootstrap.js
└── views/
```

### Public Assets
```
public/
├── assets/
│   ├── css/
│   ├── js/
│   └── img/
└── build/ (generated by Vite)
```

## Environment Configuration

### Required Environment Variables
```env
# Application
APP_NAME="Easy Budget Laravel"
APP_ENV=local
APP_KEY=base64:...
APP_DEBUG=true
APP_URL=http://localhost

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=easy_budget
DB_USERNAME=root
DB_PASSWORD=

# Redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Mail
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null

# Mercado Pago
MERCADOPAGO_PUBLIC_KEY=
MERCADOPAGO_ACCESS_TOKEN=

# Social Authentication
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI=

# Queue
QUEUE_CONNECTION=redis
```

## Performance Optimization

### Caching
- **Redis** - Application cache
- **OPcache** - PHP opcode caching
- **Query caching** - Database query results
- **View caching** - Compiled Blade templates

### Database Optimization
- **Eager loading** - Prevent N+1 queries
- **Indexing** - Optimized database indexes
- **Query optimization** - Efficient query building

### Asset Optimization
- **Vite bundling** - Optimized asset delivery
- **Lazy loading** - On-demand resource loading
- **CDN ready** - Asset URL configuration

## Security Features

### Application Security
- **CSRF Protection** - Token-based form protection
- **XSS Prevention** - Blade template escaping
- **SQL Injection Prevention** - Eloquent ORM parameterization
- **Password Hashing** - Bcrypt algorithm
- **Rate Limiting** - API and route throttling

### Authentication Security
- **Token-based authentication** - Sanctum tokens
- **Email verification** - Confirmed email addresses
- **Password reset** - Secure token-based reset
- **Session management** - Secure session handling

## Deployment

### Production Requirements
- PHP 8.2+ with required extensions
- MySQL 8.0+ or compatible database
- Redis 7.0+ for caching and queues
- Composer for dependency management
- Node.js for asset compilation
- Web server (Apache/Nginx)

### Production Optimization
```bash
# Optimize autoloader
composer install --optimize-autoloader --no-dev

# Cache configuration
php artisan config:cache

# Cache routes
php artisan route:cache

# Cache views
php artisan view:cache

# Build production assets
npm run build
```

## Version Control

### Git Configuration
- `.gitignore` - Excludes vendor, node_modules, .env, etc.
- `.gitattributes` - Line ending normalization
- Branch strategy for feature development

## Documentation Tools

### Code Documentation
- PHPDoc comments for classes and methods
- Inline comments for complex logic
- README files in key directories

### API Documentation
- Route documentation
- API endpoint specifications
- Request/response examples
