# Easy Budget Laravel

Sistema de gestão empresarial completo desenvolvido em Laravel 12 com arquitetura moderna, sistema multi-tenant e funcionalidades avançadas para provedores de serviços e pequenas/médias empresas.

## ✨ Funcionalidades Principais

-  **🏢 Multi-tenant** - Isolamento completo de dados por empresa
-  **👥 CRM Completo** - Gestão de clientes pessoa física/jurídica
-  **💰 Gestão Financeira** - Orçamentos, faturas e relatórios financeiros
-  **📦 Controle de Estoque** - Produtos e serviços com inventário
-  **📊 Dashboards Executivos** - Métricas e KPIs em tempo real
-  **🔐 Sistema de Autenticação** - Verificação de e-mail híbrida (Laravel Sanctum + Custom)
-  **📧 Auditoria Avançada** - Rastreamento completo de todas as ações
-  **📱 Interface Responsiva** - Bootstrap 5.3 com UX otimizada

## 🚀 Tecnologias Utilizadas

-  **Laravel 12** - Framework PHP moderno com PHP 8.3+
-  **Vite** - Build tool moderno para assets
-  **MySQL 8.0+** - Banco de dados com InnoDB
-  **Redis 7.0+** - Cache e sessões otimizadas
-  **Bootstrap 5.3** - Framework CSS responsivo
-  **JavaScript ES6+** - Funcionalidades interativas
-  **Mercado Pago** - Integração completa para pagamentos

## 📦 Estrutura de Assets (Vite)

Este projeto foi migrado para usar **Vite** como bundler de assets, proporcionando:

-  ⚡ **Hot Module Replacement (HMR)** - Atualizações instantâneas durante desenvolvimento
-  🔧 **Build otimizado** - Minificação e otimização automática para produção
-  🏷️ **Versionamento automático** - Cache busting automático
-  📱 **Suporte moderno** - ES6+, CSS moderno, e mais

### Estrutura de Assets:

```
resources/
├── css/
│   ├── layout.css
│   ├── alerts.css
│   └── navigation-improvements.css
├── js/
│   ├── main.js
│   ├── home.js
│   └── alert/
│       └── alert.js
└── views/
```

### Comandos de Desenvolvimento:

```bash
# Desenvolvimento (com HMR)
npm run dev

# Build para produção
npm run build

# Preview do build de produção
npm run preview
```

## 🛠️ Instalação

1. Clone o repositório
2. Instale as dependências PHP: `composer install`
3. Instale as dependências Node.js: `npm install`
4. Configure o arquivo `.env`
5. Execute as migrações: `php artisan migrate`
6. Inicie o servidor de desenvolvimento: `php artisan serve`
7. Em outro terminal, inicie o Vite: `npm run dev`

## 🏗️ Arquitetura

Este projeto utiliza uma arquitetura moderna e escalável baseada em padrões consolidados:

### **📐 Padrões Arquiteturais**
- **Controller → Services → Repositories → Models** - Arquitetura em camadas bem definida
- **Service Layer Pattern** - Lógica de negócio centralizada em serviços
- **Repository Pattern** - Abstração do acesso a dados com arquitetura dual (Tenant vs Global)
- **Multi-tenant** - Isolamento completo de dados por empresa
- **Event-driven** - Sistema de eventos para operações assíncronas

### **🔐 Sistema de Autenticação**
- **Verificação híbrida de e-mail** - Laravel Sanctum integrado com sistema customizado
- **Tokens únicos por usuário** - Sistema avançado de confirmação de e-mail
- **Expiração automática** - Tokens com validade de 30 minutos
- **Tratamento robusto de erros** - Logging detalhado e recuperação automática

### **📊 Sistema de Padrões Unificados**
O projeto implementa sistema completo de padrões para todas as camadas:

- **🏗️ Controllers** - 3 níveis (Simples → Filtros → Híbrido)
- **🔧 Services** - 3 níveis (Básico → Intermediário → Avançado)
- **📦 Repositories** - Arquitetura dual (Tenant vs Global)
- **💾 Models** - 3 níveis (Básico → Relacionamentos → Avançado)
- **🎨 Views** - 3 níveis (Básica → Formulário → AJAX)

### **📚 Documentação Técnica**
- [Memory Bank](.kilocode/rules/memory-bank/) - Documentação completa do projeto
- [Arquitetura](.kilocode/rules/memory-bank/architecture.md) - Padrões e componentes técnicos
- [Contexto](.kilocode/rules/memory-bank/context.md) - Estado atual e mudanças recentes
- [Backup de Assets Legados](docs/legacy-assets-backup.md) - Assets removidos na migração para Vite

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

-  [Simple, fast routing engine](https://laravel.com/docs/routing).
-  [Powerful dependency injection container](https://laravel.com/docs/container).
-  Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
-  Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
-  Database agnostic [schema migrations](https://laravel.com/docs/migrations).
-  [Robust background job processing](https://laravel.com/docs/queues).
-  [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

-  **[Vehikl](https://vehikl.com)**
-  **[Tighten Co.](https://tighten.co)**
-  **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
-  **[64 Robots](https://64robots.com)**
-  **[Curotec](https://www.curotec.com/services/technologies/laravel)**
-  **[DevSquad](https://devsquad.com/hire-laravel-developers)**
-  **[Redberry](https://redberry.international/laravel-development)**
-  **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
