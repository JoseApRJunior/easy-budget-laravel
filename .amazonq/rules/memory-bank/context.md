# Current Project Context

## Recent Development Status (2025)

### Asset Migration Completed
- **Vite Integration**: Successfully migrated from legacy asset management to Vite 5.0
- **Hot Module Replacement**: Development environment now supports instant updates
- **Modern Build Process**: Optimized asset compilation with tree shaking and minification
- **Frontend Stack**: Tailwind CSS 3.1 + Alpine.js 3.15 + Bootstrap Icons 1.13

### Current Active Development (Updated 2025-01-02)
- **Provider Business Edit Page**: ✅ COMPLETED - Advanced form with comprehensive validation
- **Real-time Validation**: ✅ COMPLETED - JavaScript-based form validation for better UX
- **File Upload System**: ✅ COMPLETED - Logo upload with preview functionality
- **Address Integration**: ✅ COMPLETED - CEP-based automatic address completion
- **Multi-step Forms**: ✅ COMPLETED - Complex business data management with multiple sections
- **Database Schema Migration**: ✅ COMPLETED - Inverted FK relationships (1:1 pattern)
- **PF/PJ Dynamic Forms**: ✅ COMPLETED - Toggle between individual and company fields

### Technology Stack Status
- **Laravel**: 12.x with PHP 8.2+ support
- **Database**: MySQL 8.0+ with InnoDB engine
- **Caching**: Redis 7.0+ for sessions and cache
- **Multi-tenancy**: Stancl/Tenancy 3.7 with complete data isolation
- **Payment**: Mercado Pago DX PHP 3.0 integration
- **Testing**: PHPUnit 11.5.3, Laravel Dusk 8.3, PHPStan 2.1

### Service Architecture
- **50+ Services**: Organized in 4-layer architecture
  - Application Layer: 15 services (auth, calculations, templates)
  - Core Layer: Abstract base services and contracts
  - Domain Layer: 15 business logic services
  - Infrastructure Layer: 20+ technical services
  - Shared Layer: Common utilities

### Current Form Patterns
- **Three-tier Validation**: Server-side + Client-side + Real-time
- **Modern JavaScript**: ES6+ with async/await and modern APIs
- **File Handling**: Multi-format support with size validation (5MB max)
- **User Experience**: Progressive enhancement with graceful degradation
- **Dynamic Field Toggle**: PF/PJ fields show/hide based on person_type selection
- **Mask Integration**: VanillaMask for CPF, CNPJ, phone, and CEP formatting
- **Logo Preview**: Real-time image preview before upload

### Recent Improvements (2025-01-02)
- **Birth Date Validation**: Comprehensive age verification (18+ years)
- **Document Formatting**: Real-time CPF/CNPJ formatting with VanillaMask
- **Error Handling**: Improved user feedback with contextual messages
- **Responsive Design**: Mobile-first approach with Bootstrap 5.3
- **Database Architecture**: Inverted FK relationships for 1:1 patterns
- **Type Detection**: Automatic PF/PJ detection based on CNPJ presence
- **Business Data Management**: Conditional BusinessData creation for PJ only
- **Contact Consolidation**: Single source of truth for all contact information

### Development Workflow
- **Vite Development**: `npm run dev` for HMR-enabled development
- **Combined Development**: `composer dev` runs server + queue + logs + vite
- **Testing Suite**: Comprehensive unit and feature tests
- **Code Quality**: PHPStan static analysis + Laravel Pint formatting

### Current Challenges & Solutions
- ✅ **Complex Forms**: SOLVED - Multi-section forms with interdependent validation
- ✅ **File Management**: SOLVED - Secure upload with preview and validation
- ✅ **Real-time Feedback**: SOLVED - JavaScript validation without page refresh
- ✅ **Multi-tenant Data**: SOLVED - Proper scoping and isolation across all operations
- ✅ **Database Relationships**: SOLVED - Inverted FK pattern for 1:1 relationships
- ✅ **PF/PJ Handling**: SOLVED - Dynamic form fields with type detection

### Next Development Priorities
- **Testing**: Expanded test coverage for Provider Business Edit functionality
- **Customer Management**: Apply same patterns to customer CRUD operations
- **Performance**: Asset optimization and caching strategies
- **Documentation**: ✅ Memory bank updated with latest implementations
- **Code Review**: Validate all implementations against best practices