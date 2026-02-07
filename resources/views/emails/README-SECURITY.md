# Sistema de Segurança de E-mail - Easy Budget Laravel

Este documento descreve o sistema completo de segurança implementado para o sistema de e-mail do Easy Budget Laravel.

## 📋 Funcionalidades de Segurança Implementadas

### ✅ Configurações de Remetente Global

-  **Configuração segura de remetente padrão** com variáveis de ambiente
-  **Sistema de remetentes personalizáveis por tenant** com validação rigorosa
-  **Validação de endereços de e-mail** com verificação de domínio
-  **Headers de segurança obrigatórios** em todos os e-mails
-  **Cache inteligente** de configurações por tenant

### ✅ Sistema de Segurança Avançado

-  **Rate limiting para envio de e-mails** por usuário, tenant e global
-  **Proteção contra spam e abuso** com limites configuráveis
-  **Validação de conteúdo de e-mail** antes do envio
-  **Sanitização automática de dados** HTML e texto
-  **Middleware de segurança** para validação de requisições

### ✅ Middleware de Segurança

-  **Validação de remetentes** antes do processamento
-  **Controle de acesso** para operações de e-mail
-  **Logging de segurança detalhado** com auditoria completa
-  **Monitoramento de tentativas suspeitas** em tempo real

## 🛠️ Arquivos de Configuração

### `config/email-senders.php`

Arquivo central de configuração com todas as opções de segurança:

```php
<?php

return [
    'global' => [
        'default' => [
            'name' => env('MAIL_FROM_NAME', 'Easy Budget'),
            'email' => env('MAIL_FROM_ADDRESS', 'noreply@easybudget.com'),
            'reply_to' => env('MAIL_REPLY_TO', null),
        ],
        'security_headers' => [
            'X-Mailer' => 'Easy Budget Laravel Mail System',
            'X-Application' => 'Easy Budget',
            'List-Unsubscribe' => null,
            'Return-Path' => null,
        ],
        'validation' => [
            'allowed_domains' => env('EMAIL_ALLOWED_DOMAINS', 'easybudget.com,localhost'),
            'blocked_domains' => env('EMAIL_BLOCKED_DOMAINS', ''),
            'require_domain_verification' => env('EMAIL_REQUIRE_DOMAIN_VERIFICATION', false),
            'max_email_length' => env('EMAIL_MAX_LENGTH', 320),
            'max_name_length' => env('EMAIL_MAX_NAME_LENGTH', 100),
        ],
    ],
    'rate_limiting' => [
        'enabled' => env('EMAIL_RATE_LIMITING_ENABLED', true),
        'per_user' => [
            'max_per_minute' => env('EMAIL_RATE_LIMIT_PER_USER_MINUTE', 10),
            'max_per_hour' => env('EMAIL_RATE_LIMIT_PER_USER_HOUR', 100),
            'max_per_day' => env('EMAIL_RATE_LIMIT_PER_USER_DAY', 500),
        ],
        'per_tenant' => [
            'max_per_minute' => env('EMAIL_RATE_LIMIT_PER_TENANT_MINUTE', 50),
            'max_per_hour' => env('EMAIL_RATE_LIMIT_PER_TENANT_HOUR', 500),
            'max_per_day' => env('EMAIL_RATE_LIMIT_PER_TENANT_DAY', 2000),
        ],
        'global' => [
            'max_per_minute' => env('EMAIL_RATE_LIMIT_GLOBAL_MINUTE', 200),
            'max_per_hour' => env('EMAIL_RATE_LIMIT_GLOBAL_HOUR', 2000),
            'max_per_day' => env('EMAIL_RATE_LIMIT_GLOBAL_DAY', 10000),
        ],
    ],
    'content_sanitization' => [
        'enabled' => env('EMAIL_CONTENT_SANITIZATION_ENABLED', true),
        'html' => [
            'allowed_tags' => '<p><br><strong><em><u><h1><h2><h3><h4><h5><h6><ul><ol><li><a><img><table><thead><tbody><tfoot><tr><th><td><caption><blockquote><cite><code><pre><span><div>',
            'allowed_attributes' => 'href,src,alt,title,class,id,style,width,height,border,cellpadding,cellspacing,bgcolor',
            'remove_empty_tags' => true,
            'fix_invalid_html' => true,
        ],
        'text' => [
            'max_length' => env('EMAIL_TEXT_MAX_LENGTH', 10000),
            'remove_null_bytes' => true,
            'normalize_line_endings' => true,
            'strip_dangerous_chars' => true,
        ],
    ],
    'security_logging' => [
        'enabled' => env('EMAIL_SECURITY_LOGGING_ENABLED', true),
        'channel' => env('EMAIL_SECURITY_LOG_CHANNEL', 'daily'),
        'level' => env('EMAIL_SECURITY_LOG_LEVEL', 'warning'),
        'events' => [
            'sender_validation_failed' => true,
            'rate_limit_exceeded' => true,
            'suspicious_content' => true,
            'domain_verification_failed' => true,
            'unauthorized_access' => true,
            'configuration_changed' => true,
        ],
    ],
];
```

## 🔧 Serviços Implementados

### `EmailSenderService`

Serviço principal para gerenciamento seguro de remetentes:

```php
use App\Services\Infrastructure\EmailSenderService;

// Obter configuração de remetente
$senderService = new EmailSenderService();
$config = $senderService->getSenderConfiguration($tenantId);

// Validar remetente
$validation = $senderService->validateSender($email, $name, $tenantId);

// Configurar remetente personalizado
$result = $senderService->setTenantSenderConfiguration($tenantId, $email, $name);

// Sanitizar conteúdo
$sanitized = $senderService->sanitizeEmailContent($content, 'html');
```

### `EmailRateLimitService`

Serviço para controle de taxa de envio:

```php
use App\Services\Infrastructure\EmailRateLimitService;

// Verificar limite de taxa
$rateLimitService = new EmailRateLimitService();
$check = $rateLimitService->checkRateLimit($user, $tenant, 'normal');

// Registrar tentativa
$rateLimitService->recordEmailAttempt($user, $tenant, 'normal');

// Obter estatísticas
$stats = $rateLimitService->getRateLimitStats();
```

## 🛡️ Middleware de Segurança

### `EmailSecurityMiddleware`

Middleware aplicado em rotas de e-mail:

```php
// Em routes/web.php ou routes/api.php
Route::middleware(['auth', 'email.security'])->group(function () {
    Route::post('/email/send', [EmailController::class, 'send']);
    Route::post('/email/bulk', [EmailController::class, 'bulkSend']);
});
```

## 📊 Monitoramento e Auditoria

### Logging de Segurança

Todos os eventos de segurança são registrados automaticamente:

-  **Validação de remetente falhada**
-  **Excesso de limite de taxa**
-  **Conteúdo suspeito detectado**
-  **Tentativas de acesso não autorizado**
-  **Mudanças de configuração**

### Estatísticas de Uso

```php
// Obter estatísticas do sistema
$senderService = new EmailSenderService();
$stats = $senderService->getUsageStatistics();

// Estatísticas incluem:
// - Cache habilitado e tenants em cache
// - Sanitização habilitada
// - Rate limiting habilitado
// - Logging de segurança habilitado
```

## 🚀 Como Usar

### 1. Configuração Básica

```php
// .env
MAIL_FROM_ADDRESS=noreply@easybudget.com
MAIL_FROM_NAME="Easy Budget"
EMAIL_RATE_LIMITING_ENABLED=true
EMAIL_CONTENT_SANITIZATION_ENABLED=true
```

### 2. Em Controllers

```php
<?php

class EmailController extends Controller
{
    public function send(Request $request)
    {
        // O middleware EmailSecurityMiddleware já validou:
        // - Autenticação e autorização
        // - Rate limiting
        // - Validação de remetente
        // - Sanitização de conteúdo

        $mailerService = app(MailerService::class);
        $result = $mailerService->send(
            $request->to,
            $request->subject,
            $request->content
        );

        return response()->json($result);
    }
}
```

### 3. Validação Manual

```php
$senderService = new EmailSenderService();

// Validar remetente antes de usar
$validation = $senderService->validateSender($email, $name, $tenantId);
if (!$validation->isSuccess()) {
    return back()->withErrors($validation->getMessage());
}

// Sanitizar conteúdo
$sanitized = $senderService->sanitizeEmailContent($content);
if ($sanitized->isSuccess()) {
    $content = $sanitized->getData()['sanitized_content'];
}
```

## 🔒 Recursos de Segurança

### Validações Implementadas

-  **Formato de e-mail:** RFC 5321 compliance
-  **Domínios permitidos/bloqueados:** Lista configurável
-  **Verificação DNS:** MX records para domínios personalizados
-  **Tamanho de conteúdo:** Limites configuráveis
-  **Caracteres especiais:** Sanitização automática

### Headers de Segurança

Todos os e-mails incluem headers obrigatórios:

```php
X-Mailer: Easy Budget Laravel Mail System
X-Application: Easy Budget
Return-Path: noreply@easybudget.com
```

### Rate Limiting por Nível

-  **Usuário:** 10/min, 100/hora, 500/dia
-  **Tenant:** 50/min, 500/hora, 2000/dia
-  **Global:** 200/min, 2000/hora, 10000/dia

## 📈 Monitoramento

### Logs de Segurança

```bash
# Verificar logs de segurança
tail -f storage/logs/laravel.log | grep "security"

# Logs específicos de e-mail
tail -f storage/logs/laravel.log | grep "email"
```

### Métricas Importantes

-  **Taxa de sucesso de envio**
-  **Tentativas bloqueadas por rate limiting**
-  **Validações de segurança falhadas**
-  **Conteúdo sanitizado/modificado**

## 🚨 Tratamento de Erros

### Códigos de Status

-  `400` - Dados inválidos (remetente, conteúdo)
-  `401` - Não autenticado
-  `403` - Não autorizado
-  `429` - Rate limit excedido
-  `500` - Erro interno de segurança

### Respostas Padronizadas

```json
{
   "success": false,
   "error": "Erro de segurança",
   "message": "Mensagem específica do erro",
   "timestamp": "2024-01-01T12:00:00.000000Z"
}
```

## 🔧 Configuração de Produção

### Variáveis de Ambiente Obrigatórias

```bash
# Configurações básicas
MAIL_FROM_ADDRESS=seu-dominio.com
MAIL_FROM_NAME="Nome da Empresa"

# Rate limiting
EMAIL_RATE_LIMITING_ENABLED=true
EMAIL_RATE_LIMIT_PER_USER_MINUTE=10
EMAIL_RATE_LIMIT_PER_TENANT_MINUTE=50

# Segurança de conteúdo
EMAIL_CONTENT_SANITIZATION_ENABLED=true

# Logging
EMAIL_SECURITY_LOGGING_ENABLED=true
```

### Configurações Recomendadas

```php
// Para produção
'require_domain_verification' => true,
'allowed_domains' => 'seus-dominio.com,outro-dominio.com',
'rate_limiting' => [
    'per_user' => ['max_per_minute' => 5],
    'per_tenant' => ['max_per_minute' => 25],
],
```

## 📋 Checklist de Segurança

### Antes do Deploy

-  [ ] Configurar remetente padrão seguro
-  [ ] Definir domínios permitidos
-  [ ] Configurar rate limiting apropriado
-  [ ] Habilitar sanitização de conteúdo
-  [ ] Configurar logging de segurança
-  [ ] Testar todas as validações

### Monitoramento Contínuo

-  [ ] Verificar logs de segurança diariamente
-  [ ] Monitorar taxa de falhas de envio
-  [ ] Ajustar limites de rate limiting conforme necessário
-  [ ] Revisar tentativas bloqueadas regularmente

## 🚀 Próximas Melhorias

### Melhorias Planejadas

-  [ ] Verificação SPF/DKIM automática
-  [ ] Análise de conteúdo com IA para spam
-  [ ] Sistema de reputação de remetentes
-  [ ] Integração com serviços de entrega de e-mail
-  [ ] Dashboard de segurança em tempo real

### Recursos Avançados

-  [ ] Machine learning para detecção de abuso
-  [ ] Integração com honeypots
-  [ ] Sistema de challenge/response
-  [ ] Análise comportamental de usuários

---

**Sistema implementado em:** {{ date('d/m/Y H:i:s') }}
**Versão:** 1.0.0
**Status:** ✅ **Produção**
**Segurança:** 🔒 **Alta**

Este sistema fornece proteção completa contra abuso de e-mail, mantendo a funcionalidade e usabilidade para usuários legítimos.
