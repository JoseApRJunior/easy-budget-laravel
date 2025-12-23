# Easy Budget Laravel - Estrutura do Projeto

## Organização de Diretórios

### `/app` - Núcleo da Aplicação

#### `/app/Http/Controllers`
Manipuladores de requisições HTTP organizados por domínio:
- **CustomerController** - CRUD e gestão de clientes
- **BudgetController** - Operações de orçamento/cotação
- **InvoiceController** - Geração e gestão de faturas
- **ProductController** - Catálogo de produtos/serviços
- **ReportController** - Geração de relatórios
- **SettingsController** - Configuração do sistema
- **Auth Controllers** - Fluxos de autenticação

#### `/app/Services`
Camada de lógica de negócio organizada por domínio arquitetural:
- **`/Domain`** - Serviços de negócio principais (BudgetService, InvoiceService, CustomerService)
- **`/Application`** - Serviços de nível de aplicação (CustomerInteractionService)
- **`/Infrastructure`** - Integrações externas (GeolocationService, MercadoPagoService)
- **`/Core`** - Serviços principais compartilhados (AuthService, EmailService)
- **`/Admin`** - Serviços administrativos
- **`/Shared`** - Preocupações transversais

#### `/app/Repositories`
Camada de acesso a dados com arquitetura dual:
- **`/Abstracts`** - Classes base de repositório
- **`/Contracts`** - Interfaces de repositório
- **`/Traits`** - Comportamentos reutilizáveis de repositório
- **Repositórios Tenant** - Acesso a dados com escopo de tenant (CustomerRepository, BudgetRepository)
- **Repositórios Globais** - Acesso a dados em todo o sistema (UserRepository, TenantRepository)

#### `/app/Models`
Modelos Eloquent ORM:
- **Modelos Principais:** User, Tenant, Customer, Budget, Invoice, Product
- **Modelos de Suporte:** Address, Contact, CommonData, BusinessData
- **Modelos Financeiros:** Payment, PaymentMercadoPago, PlanSubscription
- **Modelos de Sistema:** AuditLog, EmailLog, Notification, Session
- **`/Traits`** - Comportamentos de modelo (BelongsToTenant, SlugGenerator)
- **`/Pivots`** - Modelos de tabela pivot

#### `/app/DesignPatterns`
Padrões e templates padronizados:
- **`/Controllers`** - Templates de padrão de controller (3 níveis)
- **`/Services`** - Templates de padrão de serviço (3 níveis)
- **`/Repositories`** - Templates de padrão de repositório (arquitetura dual)
- **`/Models`** - Templates de padrão de modelo (3 níveis)
- **`/Views`** - Templates de padrão de view (3 níveis)
- **`/Stubs`** - Stubs de geração de código
- **README-GERAL.md** - Documentação de padrões

#### `/app/Events`
Eventos de domínio:
- BudgetStatusChanged, InvoiceCreated
- EmailVerificationRequested, PasswordResetRequested
- UserRegistered, SocialAccountLinked
- CategoryCreated/Updated/Deleted

#### `/app/Listeners`
Manipuladores de eventos:
- Listeners de notificação por e-mail
- Listeners de gestão de cache
- Listeners de registro de auditoria

#### `/app/Observers`
Observadores de ciclo de vida de modelo:
- BudgetObserver, InvoiceObserver
- CustomerObserver, ProductObserver
- TenantObserver, UserObserver

#### `/app/Policies`
Políticas de autorização:
- CustomerPolicy, ProductPolicy
- BudgetPolicy, InvoicePolicy
- TenantPolicy, CategoryPolicy

#### `/app/Middleware`
Middleware HTTP:
- Identificação e escopo de tenant
- Autenticação e autorização
- Registro e monitoramento de requisições

#### `/app/Providers`
Provedores de serviço:
- AppServiceProvider - Configuração principal da aplicação
- AuthServiceProvider - Configuração de autenticação
- EventServiceProvider - Registro de eventos/listeners
- TenancyServiceProvider - Configuração multi-tenant
- BladeDirectiveServiceProvider - Diretivas Blade customizadas

#### `/app/Helpers`
Funções utilitárias:
- CurrencyHelper, DateHelper, MaskHelper
- DocumentHelper, ValidationHelper
- StatusHelper, ModelHelper

#### `/app/Enums`
Enumerações type-safe:
- BudgetStatus, InvoiceStatus, PaymentStatus
- CustomerStatus, ServiceStatus
- AlertTypeEnum, AlertSeverityEnum

#### `/app/Support`
Utilitários de suporte:
- **helpers.php** - Funções helper globais
- **ServiceResult.php** - Wrapper de resposta de serviço padronizado

### `/config` - Arquivos de Configuração
- **database.php** - Conexões de banco de dados (MySQL, Redis)
- **tenancy.php** - Configuração multi-tenant
- **services.php** - Credenciais de serviços de terceiros
- **mail.php** - Configuração de e-mail
- **auth.php** - Configurações de autenticação

### `/database` - Camada de Banco de Dados

#### `/database/migrations`
Definições de schema:
- **2025_09_27_132300_create_initial_schema.php** - Schema inicial completo
- **2025_11_28_210700_update_categories_slug_unique_per_tenant.php** - Atualizações de categoria

#### `/database/factories`
Factories de modelo para testes:
- UserFactory, TenantFactory, CustomerFactory
- BudgetFactory, InvoiceFactory, ProductFactory
- Cobertura completa de factory para todos os modelos

#### `/database/seeders`
Seeders de banco de dados:
- DatabaseSeeder - Orquestrador principal de seeder
- AdminTenantSeeder, PublicTenantSeeder
- CategorySeeder, ProfessionSeeder, UnitSeeder
- Seeders de dados de teste para desenvolvimento

### `/resources` - Assets Frontend

#### `/resources/views`
Templates Blade organizados por funcionalidade:
- **`/layouts`** - Layouts base (app, guest, admin)
- **`/pages`** - Páginas de funcionalidade (customer, budget, invoice, product)
- **`/components`** - Componentes reutilizáveis (alerts, forms, tables)
- **`/partials`** - Views parciais (header, footer, sidebar)
- **`/auth`** - Views de autenticação
- **`/emails`** - Templates de e-mail

#### `/resources/js`
Módulos JavaScript:
- **app.js** - Entrada principal da aplicação
- **bootstrap.js** - Bootstrap e dependências
- **`/modules`** - Módulos específicos de funcionalidade
- **`/pages`** - Scripts específicos de página

#### `/resources/css`
Folhas de estilo:
- **app.css** - Estilos principais da aplicação
- CSS customizado para funcionalidades específicas

### `/routes` - Definições de Rotas
- **web.php** - Rotas web públicas
- **tenant.php** - Rotas com escopo de tenant
- **auth.php** - Rotas de autenticação
- **api.php** - Endpoints de API
- **console.php** - Comandos Artisan

### `/tests` - Suite de Testes

#### `/tests/Feature`
Testes de integração:
- CategoryControllerTest, ProductAjaxSearchTest
- CategoryHierarchyTest, CategorySoftDeleteTest
- Cobertura completa de funcionalidades

#### `/tests/Unit`
Testes unitários:
- **`/Controllers`** - Testes unitários de controller
- **`/Services`** - Testes unitários de serviço
- **`/Validation`** - Testes de validação

### `/public` - Assets Públicos
- **index.php** - Ponto de entrada da aplicação
- **`/assets`** - Assets compilados (CSS, JS, imagens)
- **.htaccess** - Configuração Apache

### `/storage` - Camada de Armazenamento
- **`/app`** - Arquivos da aplicação (uploads, arquivos gerados)
- **`/logs`** - Logs da aplicação (laravel.log, security.log, browser.log)
- **`/framework`** - Cache e sessões do framework

### `/docs` - Documentação
- Documentação técnica
- Guias de migração
- Especificações do sistema

### `/specs` - Especificações de Funcionalidades
- **`/001-login-google`** - Especificação de login Google
- **`/002-easy-budget-platform`** - Especificação da plataforma
- Especificações detalhadas de funcionalidades com contratos e checklists

## Padrões Arquiteturais

### Arquitetura em Camadas
```
Controller → Service → Repository → Model → Database
```

### Arquitetura Multi-Tenant
- Identificação de tenant via domínio/subdomínio
- Escopo automático de tenant em consultas
- Armazenamento de dados isolado por tenant
- Repositórios Globais vs Tenant

### Padrão de Camada de Serviço
- Lógica de negócio centralizada em serviços
- Serviços retornam objetos ServiceResult
- Três níveis de complexidade: Básico, Intermediário, Avançado
- Separação clara de controllers

### Padrão Repository
- Abstração de acesso a dados
- Arquitetura dual: Tenant vs Global
- Contratos baseados em interface
- Otimização de consultas e cache

### Arquitetura Orientada a Eventos
- Eventos de domínio para ações de negócio
- Processamento assíncrono via listeners
- Notificações por e-mail via eventos
- Registro de auditoria via observers

### Padrões de Design em Uso
- **Padrão Factory** - Factories de modelo para testes
- **Padrão Observer** - Hooks de ciclo de vida de modelo
- **Padrão Strategy** - Processamento de pagamento
- **Padrão Repository** - Acesso a dados
- **Padrão Service Layer** - Lógica de negócio
- **Injeção de Dependência** - Em toda a aplicação

## Relacionamentos de Componentes

### Fluxo de Gestão de Clientes
```
CustomerController → CustomerService → CustomerRepository → Customer Model
                                    ↓
                            CommonData, Contact, Address Models
```

### Fluxo de Orçamento para Fatura
```
BudgetController → BudgetService → BudgetRepository → Budget Model
                                                    ↓
InvoiceController → InvoiceService → InvoiceRepository → Invoice Model
                                                       ↓
                                            PaymentService (Mercado Pago)
```

### Fluxo de Autenticação
```
AuthController → AuthService → UserRepository → User Model
                            ↓
                    EmailService → EmailVerificationMail
                            ↓
                    UserConfirmationTokenRepository
```

### Fluxo Multi-Tenant
```
Request → TenantMiddleware → Identificação de Tenant
                           ↓
                    Escopo de Tenant Aplicado
                           ↓
                    Controller → Service → Repository (Com escopo de tenant)
```

## Decisões Arquiteturais Principais

1. **Multi-Tenant Primeiro:** Todos os dados de tenant são automaticamente escopados
2. **Camada de Serviço Obrigatória:** Controllers delegam para serviços
3. **Abstração de Repository:** Sem consultas diretas de modelo em serviços
4. **Notificações Orientadas a Eventos:** Todas as notificações via eventos
5. **Respostas Padronizadas:** ServiceResult para todos os métodos de serviço
6. **Registro Abrangente:** Trilha de auditoria para todas as operações críticas
7. **Segurança de Tipo:** Tipos PHP 8.2+, enums e tipagem estrita
8. **Frontend Moderno:** Vite para desenvolvimento rápido e builds otimizados
