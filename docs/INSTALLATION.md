# Guia de Instalação

## Pré-requisitos

-  PHP 8.2+
-  Composer
-  MySQL 5.7+
-  Node.js (opcional)

## Passo a Passo

### 1. Clone e Configuração

```bash
git clone [url-do-repositorio]
cd easy-budget-laravel
cp .env.example .env
```

### 2. Dependências

```bash
composer install
npm install (se usar frontend)
```

### 3. Banco de Dados

```bash
php artisan migrate
php artisan db:seed
```

### 4. Servidor

```bash
php artisan serve
```

## Credenciais de Teste

-  Email: admin@easybudget.com
-  Senha: password

## Configuração do Ambiente

### Arquivo .env

```env
APP_NAME="Easy Budget Laravel"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=easy_budget
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

## Troubleshooting

### Erro de Conexão com Banco

-  Verifique se o MySQL está rodando
-  Confirme as credenciais no arquivo .env
-  Execute `php artisan config:clear`

### Permissões de Storage

```bash
chmod -R 775 storage
chmod -R 775 bootstrap/cache
```

### Composer Issues

```bash
composer clear-cache
rm -rf vendor
composer install
```
