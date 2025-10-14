# Sistema de E-mail - Easy Budget Laravel

## 📋 Visão Geral Completa do Sistema de E-mail

O **Easy Budget Laravel** possui um sistema avançado e completo de gerenciamento de e-mail, projetado especificamente para ambientes multi-tenant com foco em segurança, performance e usabilidade.

### 🎯 Características Principais

#### ✅ **Arquitetura Multi-tenant Robusta**

-  **Isolamento completo** de configurações por empresa
-  **Sistema de remetentes personalizáveis** por tenant
-  **Validação rigorosa** de domínios e endereços
-  **Cache inteligente** de configurações por empresa

#### ✅ **Sistema de Segurança Avançado**

-  **Rate limiting inteligente** (usuário, tenant, global)
-  **Sanitização automática** de conteúdo HTML e texto
-  **Validação de domínios** com verificação DNS
-  **Headers de segurança obrigatórios** em todos os e-mails
-  **Logging completo** de eventos de segurança

#### ✅ **Internacionalização Completa**

-  **Suporte a múltiplos idiomas** (Português-BR, Inglês, Espanhol)
-  **Sistema de preview responsivo** para diferentes dispositivos
-  **Formatação automática** de moeda e data por locale
-  **Templates Blade** com componentes reutilizáveis

#### ✅ **Processamento Assíncrono**

-  **Sistema de filas** para envio não-bloqueante
-  **Monitoramento em tempo real** de processamento
-  **Retry inteligente** com backoff exponencial
-  **Tratamento robusto** de falhas críticas

---

## 🛣️ Lista Detalhada de Todas as Rotas Implementadas

### **📧 Rotas de Autenticação e Verificação (8 rotas)**

| Método | Rota                               | Controller                                      | Descrição                                |
| ------ | ---------------------------------- | ----------------------------------------------- | ---------------------------------------- |
| `GET`  | `/email/verify`                    | `CustomVerifyEmailController@show`              | Página de verificação de e-mail          |
| `GET`  | `/email/verify/{id}/{hash}`        | `CustomVerifyEmailController@confirmAccount`    | Confirmação de e-mail com assinatura     |
| `GET`  | `/confirm-account`                 | `CustomVerifyEmailController@confirmAccount`    | Confirmação de conta (compatibilidade)   |
| `GET`  | `/verify-email`                    | `EmailVerificationPromptController`             | Prompt de verificação (usuários logados) |
| `GET`  | `/verify-email/{id}/{hash}`        | `VerifyEmailController`                         | Verificação padrão Laravel               |
| `POST` | `/email/verification-notification` | `EmailVerificationNotificationController@store` | Reenvio de e-mail de verificação         |
| `GET`  | `/email-verification`              | `EmailVerificationController@show`              | Página de gerenciamento de verificação   |
| `POST` | `/email-verification/resend`       | `EmailVerificationController@resend`            | Reenvio personalizado de verificação     |

### **🔐 Rotas de Autenticação (5 rotas)**

| Método | Rota        | Controller                                | Descrição                 |
| ------ | ----------- | ----------------------------------------- | ------------------------- |
| `GET`  | `/register` | `EnhancedRegisteredUserController@create` | Formulário de registro    |
| `POST` | `/register` | `EnhancedRegisteredUserController@store`  | Processamento de registro |
| `GET`  | `/login`    | `AuthenticatedSessionController@create`   | Formulário de login       |
| `POST` | `/login`    | `AuthenticatedSessionController@store`    | Processamento de login    |
| `POST` | `/logout`   | `AuthenticatedSessionController@destroy`  | Logout do usuário         |

### **🔑 Rotas de Redefinição de Senha (4 rotas)**

| Método | Rota                      | Controller                           | Descrição                   |
| ------ | ------------------------- | ------------------------------------ | --------------------------- |
| `GET`  | `/forgot-password`        | `PasswordResetLinkController@create` | Formulário de esqueci senha |
| `POST` | `/forgot-password`        | `PasswordResetLinkController@store`  | Solicitação de reset        |
| `GET`  | `/reset-password/{token}` | `NewPasswordController@create`       | Formulário de nova senha    |
| `POST` | `/reset-password`         | `NewPasswordController@store`        | Processamento de reset      |

### **📊 Rotas de Preview de E-mail (3 rotas)**

| Método | Rota                         | Controller                      | Descrição                |
| ------ | ---------------------------- | ------------------------------- | ------------------------ |
| `GET`  | `/email-preview`             | `EmailPreviewController@index`  | Lista de tipos de e-mail |
| `GET`  | `/email-preview/{emailType}` | `EmailPreviewController@show`   | Preview específico       |
| `GET`  | `/email-preview/config/data` | `EmailPreviewController@config` | Configurações de preview |

### **🛠️ Rotas de Gerenciamento de Filas (4 rotas)**

| Método | Rota                 | Controller                            | Descrição                |
| ------ | -------------------- | ------------------------------------- | ------------------------ |
| `GET`  | `/queues`            | `QueueManagementController@index`     | Dashboard de filas       |
| `GET`  | `/queues/stats`      | `QueueManagementController@stats`     | Estatísticas de filas    |
| `GET`  | `/queues/health`     | `QueueManagementController@health`    | Status de saúde          |
| `POST` | `/queues/test-email` | `QueueManagementController@testEmail` | Teste de envio de e-mail |

### **📧 Rotas de Mailtrap (8 rotas)**

| Método | Rota                                   | Controller                          | Descrição                |
| ------ | -------------------------------------- | ----------------------------------- | ------------------------ |
| `GET`  | `/mailtrap`                            | `MailtrapController@index`          | Dashboard Mailtrap       |
| `GET`  | `/mailtrap/providers`                  | `MailtrapController@providers`      | Lista de provedores      |
| `GET`  | `/mailtrap/tests`                      | `MailtrapController@tests`          | Testes realizados        |
| `GET`  | `/mailtrap/logs`                       | `MailtrapController@logs`           | Logs de e-mail           |
| `GET`  | `/mailtrap/report`                     | `MailtrapController@generateReport` | Relatório de e-mails     |
| `POST` | `/mailtrap/test-provider`              | `MailtrapController@testProvider`   | Teste de provedor        |
| `POST` | `/mailtrap/run-test`                   | `MailtrapController@runTest`        | Execução de teste        |
| `GET`  | `/mailtrap/provider/{provider}/config` | `MailtrapController@providerConfig` | Configuração de provedor |

### **🚀 Rotas de API (12 rotas)**

#### **API de Templates de E-mail (9 rotas)**

| Método   | Rota                                       | Controller                                | Descrição             |
| -------- | ------------------------------------------ | ----------------------------------------- | --------------------- |
| `GET`    | `/api/email-templates`                     | `EmailTemplateApiController@index`        | Lista templates       |
| `POST`   | `/api/email-templates`                     | `EmailTemplateApiController@store`        | Criar template        |
| `GET`    | `/api/email-templates/{template}`          | `EmailTemplateApiController@show`         | Detalhes template     |
| `PUT`    | `/api/email-templates/{template}`          | `EmailTemplateApiController@update`       | Atualizar template    |
| `DELETE` | `/api/email-templates/{template}`          | `EmailTemplateApiController@destroy`      | Remover template      |
| `GET`    | `/api/email-templates/{template}/preview`  | `EmailTemplateApiController@preview`      | Preview template      |
| `POST`   | `/api/email-templates/{template}/test`     | `EmailTemplateApiController@sendTest`     | Testar template       |
| `GET`    | `/api/email-templates/variables/available` | `EmailTemplateApiController@getVariables` | Variáveis disponíveis |
| `GET`    | `/api/email-templates/analytics`           | `EmailTemplateApiController@getAnalytics` | Analytics geral       |

#### **API de Orçamentos com E-mail (3 rotas)**

| Método | Rota                          | Controller                           | Descrição                   |
| ------ | ----------------------------- | ------------------------------------ | --------------------------- |
| `GET`  | `/api/budgets/{budget}/pdf`   | `BudgetApiController@generatePdf`    | Gerar PDF do orçamento      |
| `POST` | `/api/budgets/{budget}/email` | `BudgetApiController@emailBudget`    | Enviar orçamento por e-mail |
| `POST` | `/api/budgets/{budget}/send`  | `BudgetApiController@sendToCustomer` | Enviar para aprovação       |

---

## 🏗️ Arquitetura Técnica do Sistema

### **📐 Arquitetura Geral**

```
┌─────────────────────────────────────────────────────────────────┐
│                    Sistema de E-mail - Easy Budget              │
├─────────────────────────────────────────────────────────────────┤
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐              │
│  │Controllers  │  │ Services    │  │  Models     │              │
│  │             │  │             │  │             │              │
│  │ • Auth      │  │ • Mailer    │  │ • User      │              │
│  │ • Preview   │  │ • Sender    │  │ • Tenant    │              │
│  │ • Queue     │  │ • RateLimit │  │ • Budget    │              │
│  │ • Mailtrap  │  │ • Preview   │  │ • Customer  │              │
│  └─────────────┘  └─────────────┘  └─────────────┘              │
├─────────────────────────────────────────────────────────────────┤
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐              │
│  │  Views      │  │  Mailables  │  │  Events     │              │
│  │             │  │             │  │             │              │
│  │ • Templates │  │ • Welcome   │  │ • UserReg   │              │
│  │ • Preview   │  │ • Verify    │  │ • EmailVer  │              │
│  │ • Components│  │ • Budget    │  │ • BudgetNot │              │
│  └─────────────┘  └─────────────┘  └─────────────┘              │
├─────────────────────────────────────────────────────────────────┤
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────┐              │
│  │  Queue      │  │   Cache     │  │    Logs     │              │
│  │             │  │             │  │             │              │
│  │ • Database  │  │ • Redis     │  │ • Laravel   │              │
│  │ • Failed    │  │ • File      │  │ • Security  │              │
│  │ • Retry     │  │ • Tags      │  │ • Audit     │              │
│  └─────────────┘  └─────────────┘  └─────────────┘              │
└─────────────────────────────────────────────────────────────────┘
```

### **🏢 Camadas da Arquitetura**

#### **1. Controllers (Camada de Apresentação)**

-  **`CustomVerifyEmailController`** - Gerenciamento personalizado de verificação
-  **`EmailVerificationController`** - Controle de verificação de usuários logados
-  **`EmailPreviewController`** - Sistema de preview de templates
-  **`QueueManagementController`** - Monitoramento de filas de e-mail

#### **2. Services (Camada de Negócio)**

-  **`MailerService`** - Serviço principal de envio de e-mails
-  **`EmailSenderService`** - Gerenciamento seguro de remetentes
-  **`EmailRateLimitService`** - Controle de taxa de envio
-  **`EmailPreviewService`** - Sistema de preview avançado
-  **`EmailLocalizationService`** - Internacionalização de e-mails

#### **3. Models (Camada de Dados)**

-  **`User`** - Usuários com verificação de e-mail
-  **`Tenant`** - Empresas com configurações personalizadas
-  **`UserConfirmationToken`** - Tokens de verificação
-  **`Budget`** - Orçamentos com notificações
-  **`Customer`** - Clientes para notificações

#### **4. Mailables (Templates de E-mail)**

-  **`WelcomeUser`** - E-mail de boas-vindas
-  **`EmailVerificationMail`** - Verificação de e-mail
-  **`BudgetNotificationMail`** - Notificações de orçamento
-  **`InvoiceNotification`** - Notificações de fatura
-  **`StatusUpdate`** - Atualizações de status
-  **`SupportResponse`** - Respostas de suporte

#### **5. Events (Sistema de Eventos)**

-  **`UserRegistered`** - Dispara e-mail de boas-vindas
-  **`EmailVerificationRequested`** - Solicita verificação de e-mail
-  **`BudgetCreated`** - Notifica criação de orçamento
-  **`InvoiceCreated`** - Notifica criação de fatura

### **🔄 Fluxos de Dados Principais**

#### **🔐 Fluxo de Verificação de E-mail**

```
1. Usuário registra conta
   ↓
2. UserRegistrationService::register()
   ↓
3. EmailVerificationService::createConfirmationToken()
   ↓
4. Remove tokens antigos automaticamente
   ↓
5. Cria novo token (30 min expiração)
   ↓
6. Dispara evento EmailVerificationRequested
   ↓
7. Listener SendEmailVerificationNotification processa
   ↓
8. Usa MailerService para envio
   ↓
9. Usuário recebe e-mail com link de verificação
   ↓
10. Usuário clica no link
    ↓
11. Rota de verificação valida token
    ↓
12. Marca e-mail como verificado
    ↓
13. Remove token usado
    ↓
14. Redireciona para dashboard
```

#### **📧 Fluxo de Envio de Notificação**

```
1. Evento disparado (BudgetCreated, etc.)
   ↓
2. Listener correspondente processa evento
   ↓
3. Validação de segurança (rate limiting)
   ↓
4. Sanitização de conteúdo
   ↓
5. Preparação de dados para template
   ↓
6. Criação de Mailable instance
   ↓
7. Enfileiramento para processamento assíncrono
   ↓
8. Queue worker processa job
   ↓
9. Envio efetivo via Laravel Mail
   ↓
10. Logging de resultado
```

---

## 🚀 Guia de Uso para Desenvolvedores

### **📝 1. Envio Básico de E-mail**

```php
use App\Services\Infrastructure\MailerService;
use App\Support\ServiceResult;

// Injeção de dependência automática
public function sendEmail(MailerService $mailerService)
{
    $result = $mailerService->send(
        'destinatario@exemplo.com',
        'Assunto do E-mail',
        '<h1>Conteúdo HTML</h1><p>Corpo da mensagem</p>',
        null, // Sem anexo
        'remetente@empresa.com',
        'Nome do Remetente'
    );

    if ($result->isSuccess()) {
        return response()->json(['message' => 'E-mail enviado com sucesso']);
    }

    return response()->json(['error' => $result->getMessage()], 400);
}
```

### **🔐 2. Verificação de E-mail**

```php
use App\Services\Application\EmailVerificationService;

// Criar token de verificação
$verificationService = new EmailVerificationService();
$result = $verificationService->createConfirmationToken($user);

if ($result->isSuccess()) {
    $token = $result->getData()['token'];

    // E-mail será enviado automaticamente via evento
    return response()->json(['message' => 'Token criado com sucesso']);
}
```

### **📊 3. Sistema de Preview**

```php
use App\Services\Infrastructure\EmailPreviewService;

// Gerar dados de preview
$previewService = new EmailPreviewService();
$data = $previewService->generatePreviewData('welcome', 'pt-BR', $tenantId);

// Renderizar preview
$result = $previewService->renderEmailPreview('welcome', $data, 'desktop');
if ($result['success']) {
    echo $result['html'];
}
```

### **🛡️ 4. Configuração de Remetente Seguro**

```php
use App\Services\Infrastructure\EmailSenderService;

// Configurar remetente personalizado
$senderService = new EmailSenderService();
$result = $senderService->setTenantSenderConfiguration(
    $tenantId,
    'contato@minhaempresa.com',
    'Minha Empresa',
    'suporte@minhaempresa.com'
);

if ($result->isSuccess()) {
    return response()->json(['message' => 'Remetente configurado']);
}
```

### **📈 5. Monitoramento de Filas**

```php
use App\Services\Infrastructure\MailerService;

// Obter estatísticas da fila
$mailerService = new MailerService();
$stats = $mailerService->getEmailQueueStats();

return response()->json([
    'queued_emails' => $stats['queued_emails'],
    'processing_emails' => $stats['processing_emails'],
    'failed_emails' => $stats['failed_emails'],
    'queue_status' => $stats['queue_status']
]);
```

---

## ⚙️ Configurações Necessárias

### **📧 Configurações de E-mail (.env)**

```env
# Configurações básicas de e-mail
MAIL_MAILER=smtp
MAIL_HOST=mail.empresa.com
MAIL_PORT=587
MAIL_USERNAME=seu-email@empresa.com
MAIL_PASSWORD=sua-senha
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@empresa.com
MAIL_FROM_NAME="Easy Budget"

# Configurações de segurança
EMAIL_RATE_LIMITING_ENABLED=true
EMAIL_RATE_LIMIT_PER_USER_MINUTE=10
EMAIL_RATE_LIMIT_PER_TENANT_MINUTE=50
EMAIL_RATE_LIMIT_GLOBAL_MINUTE=200

# Sanitização de conteúdo
EMAIL_CONTENT_SANITIZATION_ENABLED=true

# Logging de segurança
EMAIL_SECURITY_LOGGING_ENABLED=true
EMAIL_SECURITY_LOG_CHANNEL=daily
```

### **🔒 Configurações de Segurança (config/email-senders.php)**

```php
<?php

return [
    'global' => [
        'default' => [
            'name' => env('MAIL_FROM_NAME', 'Easy Budget'),
            'email' => env('MAIL_FROM_ADDRESS', 'noreply@easybudget.com'),
        ],
        'security_headers' => [
            'X-Mailer' => 'Easy Budget Laravel Mail System',
            'X-Application' => 'Easy Budget',
        ],
        'validation' => [
            'allowed_domains' => env('EMAIL_ALLOWED_DOMAINS', 'empresa.com'),
            'blocked_domains' => env('EMAIL_BLOCKED_DOMAINS', ''),
            'max_email_length' => 320,
            'max_name_length' => 100,
        ],
    ],
    'rate_limiting' => [
        'enabled' => env('EMAIL_RATE_LIMITING_ENABLED', true),
        'per_user' => [
            'max_per_minute' => env('EMAIL_RATE_LIMIT_PER_USER_MINUTE', 10),
            'max_per_hour' => 100,
            'max_per_day' => 500,
        ],
        'per_tenant' => [
            'max_per_minute' => 50,
            'max_per_hour' => 500,
            'max_per_day' => 2000,
        ],
    ],
];
```

### **🌐 Configurações de Internacionalização (config/app.php)**

```php
<?php

return [
    'locale' => env('APP_LOCALE', 'pt-BR'),
    'fallback_locale' => 'pt-BR',
    'supported_locales' => ['pt-BR', 'en', 'es'],

    // Configurações de e-mail internacionalizado
    'email' => [
        'default_locale' => 'pt-BR',
        'cache_translations' => true,
        'cache_ttl' => 3600,
    ],
];
```

---

## 💡 Exemplos Práticos de Uso

### **📧 Exemplo 1: Envio de Notificação de Orçamento**

```php
<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\Customer;
use App\Services\Infrastructure\MailerService;

class BudgetController extends Controller
{
    public function sendNotification(Budget $budget, Customer $customer)
    {
        $mailerService = app(MailerService::class);

        $result = $mailerService->sendBudgetNotificationMail(
            $budget,
            $customer,
            'created', // Tipo de notificação
            auth()->user()->tenant,
            null, // Dados da empresa
            route('budgets.public.show', $budget->code), // URL pública
            'Orçamento personalizado enviado', // Mensagem customizada
            'pt-BR' // Locale
        );

        if ($result->isSuccess()) {
            return back()->with('success', 'Notificação enviada com sucesso!');
        }

        return back()->with('error', $result->getMessage());
    }
}
```

### **🔐 Exemplo 2: Sistema de Verificação Personalizado**

```php
<?php

namespace App\Services\Application;

use App\Models\User;
use App\Services\Infrastructure\EmailVerificationService;
use App\Support\ServiceResult;

class CustomVerificationService
{
    private EmailVerificationService $verificationService;

    public function __construct(EmailVerificationService $verificationService)
    {
        $this->verificationService = $verificationService;
    }

    public function verifyUserWithCustomLogic(User $user): ServiceResult
    {
        // Lógica de negócio personalizada antes da verificação
        if (!$user->is_active) {
            return ServiceResult::error(
                OperationStatus::INVALID_DATA,
                'Usuário deve estar ativo para verificação.'
            );
        }

        // Criar token de verificação
        $result = $this->verificationService->createConfirmationToken($user);
        if (!$result->isSuccess()) {
            return $result;
        }

        // E-mail será enviado automaticamente via evento
        return ServiceResult::success([
            'token' => $result->getData()['token'],
            'expires_at' => $result->getData()['expires_at']
        ], 'Token de verificação criado com sucesso.');
    }
}
```

### **📊 Exemplo 3: Monitoramento de Performance**

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Services\Infrastructure\MailerService;
use App\Services\Infrastructure\EmailRateLimitService;

class EmailMonitoringController extends Controller
{
    public function dashboard()
    {
        $mailerService = app(MailerService::class);
        $rateLimitService = app(EmailRateLimitService::class);

        // Estatísticas da fila
        $queueStats = $mailerService->getEmailQueueStats();

        // Estatísticas de rate limiting
        $rateLimitStats = $rateLimitService->getRateLimitStats();

        // Configurações atuais
        $config = $mailerService->getConfiguration();

        return view('admin.email-monitoring', compact(
            'queueStats',
            'rateLimitStats',
            'config'
        ));
    }
}
```

### **🌐 Exemplo 4: Templates Internacionalizados**

```php
<?php

// resources/views/emails/welcome.blade.php
@extends('emails.layouts.master')

@section('content')
<div class="email-container">
    <x-emails::panel>
        <h1>{{ __('emails.welcome.title', [], $locale) }}</h1>

        <p>{{ __('emails.welcome.greeting', ['name' => $user->first_name], $locale) }}</p>

        <p>{{ __('emails.welcome.message', [
            'app_name' => config('app.name'),
            'company_name' => $tenant->name ?? 'Easy Budget'
        ], $locale) }}</p>

        <x-emails::button
            :url="route('dashboard')"
            :text="__('emails.welcome.button', [], $locale)"
            color="primary"
        />

        <p>{{ __('emails.welcome.footer', [
            'year' => date('Y'),
            'app_name' => config('app.name')
        ], $locale) }}</p>
    </x-emails::panel>
</div>
@endsection
```

---

## 🚨 Solução de Problemas Comuns

### **❌ Problema 1: E-mails não são enviados**

**Sintomas:**

-  Jobs ficam na fila sem processamento
-  E-mails não chegam aos destinatários
-  Logs mostram erros de conexão

**Soluções:**

```bash
# 1. Verificar configuração de fila
php artisan queue:work --tries=3 --timeout=90

# 2. Verificar conexão SMTP
php artisan tinker
>>> Mail::raw('Teste', function($message) { $message->to('teste@exemplo.com')->subject('Teste'); });

# 3. Verificar logs de erro
tail -f storage/logs/laravel.log | grep -i "mail\|smtp"

# 4. Testar configuração específica
php artisan config:cache
php artisan config:clear
```

### **❌ Problema 2: Rate limiting bloqueando envios**

**Sintomas:**

-  E-mails são rejeitados com erro 429
-  Mensagens de "limite excedido"
-  Bloqueio temporário de usuário/tenant

**Soluções:**

```php
// Verificar status do rate limiting
$rateLimitService = app(EmailRateLimitService::class);
$status = $rateLimitService->checkRateLimit($user, $tenant, 'normal');

// Limpar rate limiting se necessário (apenas para admin)
if ($isAdmin) {
    $rateLimitService->clearRateLimit($user, $tenant);
}
```

### **❌ Problema 3: Templates não renderizam corretamente**

**Sintomas:**

-  E-mails chegam com conteúdo quebrado
-  Variáveis não são substituídas
-  Problemas de encoding

**Soluções:**

```php
// 1. Verificar dados do template
$previewService = app(EmailPreviewService::class);
$data = $previewService->generatePreviewData('welcome', 'pt-BR', $tenantId);

// 2. Testar renderização
$result = $previewService->renderEmailPreview('welcome', $data, 'desktop');

// 3. Verificar variáveis disponíveis
$variables = app(VariableProcessor::class)->getAvailableVariables('welcome');
```

### **❌ Problema 4: Problemas de internacionalização**

**Sintomas:**

-  Textos aparecem em inglês ao invés de português
-  Formatação incorreta de moeda/data
-  Caracteres especiais quebrados

**Soluções:**

```php
// 1. Verificar locale do usuário
app()->setLocale($user->preferred_locale ?? 'pt-BR');

// 2. Limpar cache de traduções
$localizationService = app(EmailLocalizationService::class);
$localizationService->clearLocaleCache();

// 3. Testar tradução específica
$translation = __('emails.welcome.title', [], 'pt-BR');
```

### **❌ Problema 5: Problemas de autenticação SMTP**

**Sintomas:**

-  Erro de autenticação no envio
-  Conexão recusada pelo servidor
-  Certificado SSL inválido

**Soluções:**

```php
// 1. Testar credenciais SMTP
$config = [
    'host' => 'smtp.empresa.com',
    'port' => 587,
    'username' => 'seu-email@empresa.com',
    'password' => 'sua-senha',
    'encryption' => 'tls'
];

// 2. Verificar configuração no .env
MAIL_MAILER=smtp
MAIL_HOST=smtp.empresa.com
MAIL_PORT=587
MAIL_USERNAME=seu-email@empresa.com
MAIL_PASSWORD=sua-senha
MAIL_ENCRYPTION=tls

// 3. Usar log para debug
MAIL_MAILER=log # Para desenvolvimento
```

### **📊 Monitoramento e Debugging**

#### **Logs Importantes:**

```bash
# Logs gerais de e-mail
tail -f storage/logs/laravel.log | grep -E "mail|email|smtp"

# Logs de segurança
tail -f storage/logs/laravel.log | grep "security"

# Logs de fila
tail -f storage/logs/laravel.log | grep "queue"

# Logs específicos de e-mail
tail -f storage/logs/email-security.log
```

#### **Comandos de Diagnóstico:**

```bash
# Verificar status da fila
php artisan queue:status

# Verificar jobs com falha
php artisan queue:failed

# Retentar jobs com falha
php artisan queue:retry all

# Limpar cache de configuração
php artisan config:clear && php artisan config:cache

# Testar envio de e-mail
php artisan tinker
>>> $result = app(\App\Services\Infrastructure\MailerService::class)->sendTestEmail('teste@exemplo.com');
```

---

## 📋 Checklist de Implementação

### **✅ Antes do Deploy para Produção**

-  [ ] Configurar credenciais SMTP válidas
-  [ ] Definir remetente padrão seguro
-  [ ] Configurar rate limiting apropriado
-  [ ] Habilitar sanitização de conteúdo
-  [ ] Configurar logging de segurança
-  [ ] Testar todos os tipos de e-mail
-  [ ] Verificar configurações multi-tenant
-  [ ] Validar internacionalização

### **🔍 Monitoramento Contínuo**

-  [ ] Verificar logs de segurança diariamente
-  [ ] Monitorar taxa de sucesso de envio
-  [ ] Acompanhar métricas de performance
-  [ ] Revisar tentativas bloqueadas
-  [ ] Atualizar configurações conforme necessário

### **🚀 Melhorias Futuras**

-  [ ] Implementar SPF/DKIM automático
-  [ ] Adicionar análise de conteúdo com IA
-  [ ] Implementar sistema de reputação
-  [ ] Integração com ESP externos
-  [ ] Dashboard de segurança em tempo real

---

## 📞 Suporte e Manutenção

### **🔧 Comandos Úteis para Manutenção**

```bash
# Limpeza de cache de e-mail
php artisan cache:clear
php artisan config:clear

# Limpeza de jobs antigos
php artisan queue:flush

# Verificação de saúde do sistema
php artisan about

# Backup de configurações
php artisan config:cache
```

### **📊 Métricas Importantes para Monitorar**

1. **Taxa de sucesso de envio** (> 95%)
2. **Tempo médio de processamento** (< 5s)
3. **Número de tentativas bloqueadas** (< 1%)
4. **Uso de cache** (> 80% de acerto)
5. **Erros de validação** (< 0.1%)

### **🚨 Alertas Críticos**

-  Taxa de falha de envio > 5%
-  Fila de e-mails > 1000 jobs
-  Rate limiting excedido frequentemente
-  Erros de autenticação SMTP
-  Problemas de internacionalização

---

**Sistema implementado em:** {{ date('d/m/Y H:i:s') }}
**Versão:** 1.0.0
**Status:** ✅ **Produção**
**Segurança:** 🔒 **Alta**
**Performance:** ⚡ **Otimizada**

Este sistema fornece uma solução completa, segura e escalável para gerenciamento de e-mail em ambientes multi-tenant, com foco em usabilidade para desenvolvedores e confiabilidade para usuários finais.
