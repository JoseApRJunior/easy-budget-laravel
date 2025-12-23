# Easy Budget Laravel - Stack Tecnológico

## Tecnologias Principais

### Framework Backend
- **Laravel 12.x** - Framework Laravel mais recente
- **PHP 8.2+** - PHP moderno com tipos estritos, enums e atributos
- **Composer** - Gestão de dependências

### Stack Frontend
- **Vite 5.x** - Ferramenta de build moderna com HMR
- **Bootstrap 5.3** - Framework CSS responsivo
- **Alpine.js 3.x** - Framework JavaScript leve
- **Vanilla JavaScript ES6+** - Recursos JavaScript modernos
- **Axios** - Cliente HTTP para requisições AJAX

### Banco de Dados e Cache
- **MySQL 8.0+** - Banco de dados principal com engine InnoDB
- **Redis 7.0+** - Armazenamento de cache e sessões
- **Doctrine DBAL 4.3** - Camada de abstração de banco de dados
- **Doctrine ORM 3.5** - Mapeamento objeto-relacional

### Multi-Tenancy
- **stancl/tenancy 3.7** - Pacote multi-tenant
- Identificação automática de tenant
- Consultas de banco de dados com escopo de tenant
- Roteamento baseado em domínio/subdomínio

## Dependências Principais

### Dependências de Produção

#### Ecossistema Laravel
```json
"laravel/framework": "^12.0"
"laravel/sanctum": "^4.2"        // Autenticação de API
"laravel/socialite": "^5.23"     // Login social (Google, Facebook)
"laravel/tinker": "^2.10.1"      // REPL para debugging
```

#### Pagamento e Financeiro
```json
"mercadopago/dx-php": "3"        // Integração Mercado Pago
```

#### Geração de Documentos
```json
"mpdf/mpdf": "8.2"               // Geração de PDF
"mpdf/qrcode": "^1.2"            // Geração de QR code
"phpoffice/phpspreadsheet": "4"  // Exportação Excel/CSV
```

#### Processamento de Imagens
```json
"intervention/image": "3"        // Manipulação de imagens
```

#### Utilitários
```json
"spatie/laravel-directory-cleanup": "^1.10"  // Limpeza automática
```

### Dependências de Desenvolvimento

#### Ferramentas de Desenvolvimento Laravel
```json
"laravel/boost": "^1.8"          // Assistente de desenvolvimento com IA
"laravel/breeze": "^2.3"         // Scaffolding de autenticação
"laravel/dusk": "^8.3"           // Testes de navegador
"laravel/pail": "^1.2.2"         // Visualizador de logs
"laravel/pint": "^1.24"          // Corretor de estilo de código
"laravel/sail": "^1.41"          // Ambiente de desenvolvimento Docker
```

#### Testes e Qualidade
```json
"phpunit/phpunit": "^11.5.3"     // Framework de testes
"mockery/mockery": "^1.6"        // Biblioteca de mocking
"fakerphp/faker": "^1.23"        // Geração de dados falsos
"phpstan/phpstan": "^2.1"        // Análise estática
```

#### Debugging
```json
"barryvdh/laravel-debugbar": "^3.16"  // Barra de debug
"nunomaduro/collision": "^8.6"        // Relatório de erros
```

### Dependências Frontend

#### Core
```json
"vite": "^5.0.0"                 // Ferramenta de build
"laravel-vite-plugin": "^1.0.0"  // Integração Laravel
"axios": "^1.6.4"                // Cliente HTTP
```

#### Framework UI
```json
"alpinejs": "^3.15.0"            // Framework reativo
"bootstrap-icons": "^1.13.1"    // Biblioteca de ícones
"@tailwindcss/forms": "^0.5.2"  // Estilização de formulários
"tailwindcss": "^3.1.0"          // CSS utility-first
```

#### Ferramentas de Build
```json
"autoprefixer": "^10.4.2"        // Prefixos de vendor CSS
"postcss": "^8.4.31"             // Processamento CSS
```

## Ambiente de Desenvolvimento

### Software Necessário
- **PHP 8.2+** com extensões:
  - OpenSSL, PDO, Mbstring, Tokenizer, XML, Ctype, JSON
  - BCMath, Fileinfo, GD/Imagick
- **Composer 2.x**
- **Node.js 18+** e npm
- **MySQL 8.0+** ou MariaDB 10.3+
- **Redis 7.0+** (opcional mas recomendado)

### Comandos de Desenvolvimento

#### Configuração Inicial
```bash
# Instalar dependências PHP
composer install

# Instalar dependências Node.js
npm install

# Copiar arquivo de ambiente
cp .env.example .env

# Gerar chave da aplicação
php artisan key:generate

# Executar migrations
php artisan migrate

# Popular banco de dados (opcional)
php artisan db:seed
```

#### Fluxo de Desenvolvimento
```bash
# Iniciar servidor de desenvolvimento com todos os serviços
composer dev
# Isso executa: servidor, queue worker, visualizador de logs e Vite

# Ou executar individualmente:
php artisan serve              # Servidor de desenvolvimento
php artisan queue:listen       # Queue worker
php artisan pail               # Visualizador de logs
npm run dev                    # Servidor dev Vite com HMR
```

#### Build para Produção
```bash
# Build de assets otimizados
npm run build

# Otimizar Laravel
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

#### Testes
```bash
# Executar todos os testes
composer test
# Ou: php artisan test

# Executar suite de teste específica
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit

# Executar testes de navegador
php artisan dusk

# Análise estática
vendor/bin/phpstan analyse

# Verificar/corrigir estilo de código
vendor/bin/pint
```

#### Operações de Banco de Dados
```bash
# Executar migrations
php artisan migrate

# Reverter migrations
php artisan migrate:rollback

# Migration fresh com seeding
php artisan migrate:fresh --seed

# Criar nova migration
php artisan make:migration create_table_name
```

#### Geração de Código
```bash
# Gerar controller
php artisan make:controller NameController

# Gerar model com migration
php artisan make:model ModelName -m

# Gerar service
php artisan make:service ServiceName

# Gerar repository
php artisan make:repository RepositoryName
```

## Arquivos de Configuração

### Variáveis de Ambiente (.env)
```ini
APP_NAME="Easy Budget"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=easy_budget
DB_USERNAME=root
DB_PASSWORD=

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025

MERCADOPAGO_PUBLIC_KEY=
MERCADOPAGO_ACCESS_TOKEN=

GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI=
```

### Configuração Vite (vite.config.js)
```javascript
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
            ],
            refresh: true,
        }),
    ],
});
```

### Scripts Composer
```json
"scripts": {
    "dev": "Servidor, queue, logs e vite concorrentes",
    "test": "Limpar config e executar testes",
    "post-autoload-dump": "Descoberta de pacotes",
    "post-update-cmd": "Publicar assets"
}
```

## Suporte a Navegadores

### Navegadores Alvo
```json
"browserslist": [
    ">0.5%",
    "not dead",
    "Chrome >= 90",
    "Firefox >= 90",
    "iOS >= 12"
]
```

## Otimizações de Performance

### Backend
- **Cache Redis** para sessões e cache de aplicação
- **Otimização de consultas** via padrão repository
- **Eager loading** para prevenir consultas N+1
- **Indexação de banco de dados** em colunas frequentemente consultadas
- **Queue workers** para operações assíncronas

### Frontend
- **Vite HMR** para atualizações instantâneas de desenvolvimento
- **Code splitting** para tamanhos de bundle otimizados
- **Versionamento de assets** para cache busting
- **Lazy loading** para imagens e componentes
- **Minificação** de CSS e JavaScript

## Recursos de Segurança

### Autenticação
- Laravel Sanctum para tokens de API
- Sistema customizado de verificação de e-mail
- Login social via Socialite
- Hash de senha com bcrypt
- Proteção CSRF em todos os formulários

### Autorização
- Controle de acesso baseado em funções (RBAC)
- Autorização baseada em políticas
- Acesso a dados com escopo de tenant
- Middleware de verificação de permissões

### Proteção de Dados
- Prevenção de injeção SQL via Eloquent ORM
- Proteção XSS via escape Blade
- Aplicação de HTTPS em produção
- Manipulação segura de sessões
- Validação e sanitização de entrada

## Logging e Monitoramento

### Canais de Log
- **laravel.log** - Logs da aplicação
- **security.log** - Eventos de segurança
- **browser.log** - Erros de frontend

### Monitoramento
- Laravel Debugbar para desenvolvimento
- Registro de auditoria para operações críticas
- Registro de e-mails enviados
- Rastreamento de sessões

## Integrações de Terceiros

### Gateway de Pagamento
- **Mercado Pago** - Processamento de pagamento
- Manipulação de webhook para notificações de pagamento
- Geração de QR code para pagamentos

### Autenticação Social
- **Google OAuth** - Login Google
- **Facebook OAuth** - Login Facebook

### Serviços de E-mail
- Configuração SMTP
- Envio de e-mail baseado em fila
- E-mails baseados em templates

## Ferramentas de Desenvolvimento

### Suporte IDE
- PHPStan para análise estática
- Laravel IDE Helper para autocompletar
- Debugbar para debugging
- Pint para formatação de código

### Controle de Versão
- Git com .gitignore configurado
- Arquivo lock do Composer para versões de dependências
- Arquivo lock de pacote para dependências npm

## Considerações de Deploy

### Requisitos de Produção
- PHP 8.2+ com extensões necessárias
- MySQL 8.0+ ou banco de dados compatível
- Redis para cache (recomendado)
- Certificado HTTPS/SSL
- Espaço em disco suficiente para uploads e logs
- Cron job para tarefas agendadas

### Comandos de Otimização
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
npm run build
```

### Queue Workers
```bash
# Configuração Supervisor para queue workers
php artisan queue:work --tries=3 --timeout=90
```

### Tarefas Agendadas
```bash
# Adicionar ao crontab
* * * * * cd /caminho-do-projeto && php artisan schedule:run >> /dev/null 2>&1
```
