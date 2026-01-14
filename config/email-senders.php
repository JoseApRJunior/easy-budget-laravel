<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Configurações de Remetente Global de E-mail
    |--------------------------------------------------------------------------
    |
    | Esta configuração controla as configurações de remetente para o sistema
    | de e-mail do Easy Budget Laravel, incluindo remetentes padrão,
    | personalização por tenant e validações de segurança.
    |
    */

    'global' => [
        /*
        |--------------------------------------------------------------------------
        | Remetente Padrão Global
        |--------------------------------------------------------------------------
        |
        | Configurações padrão usadas quando não há remetente personalizado
        | definido para um tenant específico.
        |
        */
        'default' => [
            'name' => env('MAIL_FROM_NAME', 'Easy Budget'),
            'email' => env('MAIL_FROM_ADDRESS', 'noreply@easybudget.com'),
            'reply_to' => env('MAIL_REPLY_TO', null),
        ],

        /*
        |--------------------------------------------------------------------------
        | Headers de Segurança Obrigatórios
        |--------------------------------------------------------------------------
        |
        | Headers que devem ser incluídos em todos os e-mails enviados
        | pelo sistema para melhorar a segurança e entregabilidade.
        |
        */
        'security_headers' => [
            'X-Mailer' => 'Easy Budget Laravel Mail System',
            'X-Application' => 'Easy Budget',
            'List-Unsubscribe' => null, // Será definido dinamicamente se necessário
            'Return-Path' => null, // Será definido com o remetente padrão
        ],

        /*
        |--------------------------------------------------------------------------
        | Validações de Segurança
        |--------------------------------------------------------------------------
        |
        | Configurações para validação de remetentes e conteúdo de e-mail.
        |
        */
        'validation' => [
            'allowed_domains' => env('EMAIL_ALLOWED_DOMAINS', 'easybudget.com,localhost'),
            'blocked_domains' => env('EMAIL_BLOCKED_DOMAINS', ''),
            'require_domain_verification' => env('EMAIL_REQUIRE_DOMAIN_VERIFICATION', false),
            'max_email_length' => env('EMAIL_MAX_LENGTH', 320), // RFC 5321
            'max_name_length' => env('EMAIL_MAX_NAME_LENGTH', 100),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Configurações por Tenant
    |--------------------------------------------------------------------------
    |
    | Permite personalização de remetentes por empresa/tenant.
    | Cada tenant pode ter seu próprio remetente configurado.
    |
    */
    'tenants' => [
        /*
        |--------------------------------------------------------------------------
        | Habilita Remetentes Personalizáveis
        |--------------------------------------------------------------------------
        |
        | Define se os tenants podem configurar seus próprios remetentes
        | ou se devem usar apenas o remetente global padrão.
        |
        */
        'customizable' => env('EMAIL_TENANT_CUSTOMIZABLE', true),

        /*
        |--------------------------------------------------------------------------
        | Validação de Remetentes por Tenant
        |--------------------------------------------------------------------------
        |
        | Regras específicas para validação de remetentes personalizados
        | por empresa.
        |
        */
        'validation' => [
            'require_verification' => env('EMAIL_TENANT_VERIFICATION_REQUIRED', true),
            'auto_verify_domains' => env('EMAIL_TENANT_AUTO_VERIFY', false),
            'allowed_tenant_domains' => env('EMAIL_TENANT_ALLOWED_DOMAINS', ''),
        ],

        /*
        |--------------------------------------------------------------------------
        | Cache de Configurações
        |--------------------------------------------------------------------------
        |
        | Configurações de cache para remetentes por tenant.
        |
        */
        'cache' => [
            'enabled' => env('EMAIL_TENANT_CACHE_ENABLED', true),
            'ttl' => env('EMAIL_TENANT_CACHE_TTL', 3600), // 1 hora
            'key_prefix' => 'email_sender_tenant_',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Controle de taxa de envio de e-mails para prevenir spam e abuso.
    |
    */
    'rate_limiting' => [
        'enabled' => env('EMAIL_RATE_LIMITING_ENABLED', true),

        /*
        |--------------------------------------------------------------------------
        | Limites por Usuário
        |--------------------------------------------------------------------------
        */
        'per_user' => [
            'max_per_minute' => env('EMAIL_RATE_LIMIT_PER_USER_MINUTE', 10),
            'max_per_hour' => env('EMAIL_RATE_LIMIT_PER_USER_HOUR', 100),
            'max_per_day' => env('EMAIL_RATE_LIMIT_PER_USER_DAY', 500),
        ],

        /*
        |--------------------------------------------------------------------------
        | Limites por Tenant
        |--------------------------------------------------------------------------
        */
        'per_tenant' => [
            'max_per_minute' => env('EMAIL_RATE_LIMIT_PER_TENANT_MINUTE', 50),
            'max_per_hour' => env('EMAIL_RATE_LIMIT_PER_TENANT_HOUR', 500),
            'max_per_day' => env('EMAIL_RATE_LIMIT_PER_TENANT_DAY', 2000),
        ],

        /*
        |--------------------------------------------------------------------------
        | Limites Globais
        |--------------------------------------------------------------------------
        */
        'global' => [
            'max_per_minute' => env('EMAIL_RATE_LIMIT_GLOBAL_MINUTE', 200),
            'max_per_hour' => env('EMAIL_RATE_LIMIT_GLOBAL_HOUR', 2000),
            'max_per_day' => env('EMAIL_RATE_LIMIT_GLOBAL_DAY', 10000),
        ],

        /*
        |--------------------------------------------------------------------------
        | Configurações de Bloqueio
        |--------------------------------------------------------------------------
        */
        'blocking' => [
            'block_duration_minutes' => env('EMAIL_RATE_LIMIT_BLOCK_DURATION', 15),
            'max_blocked_attempts' => env('EMAIL_RATE_LIMIT_MAX_BLOCKED_ATTEMPTS', 5),
            'notification_enabled' => env('EMAIL_RATE_LIMIT_NOTIFICATION_ENABLED', true),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Sanitização de Conteúdo
    |--------------------------------------------------------------------------
    |
    | Configurações para limpeza e validação de conteúdo de e-mail.
    |
    */
    'content_sanitization' => [
        'enabled' => env('EMAIL_CONTENT_SANITIZATION_ENABLED', true),

        /*
        |--------------------------------------------------------------------------
        | HTML Sanitization
        |--------------------------------------------------------------------------
        */
        'html' => [
            'allowed_tags' => '<p><br><strong><em><u><h1><h2><h3><h4><h5><h6><ul><ol><li><a><img><table><thead><tbody><tfoot><tr><th><td><caption><blockquote><cite><code><pre><span><div>',
            'allowed_attributes' => 'href,src,alt,title,class,id,style,width,height,border,cellpadding,cellspacing,bgcolor',
            'remove_empty_tags' => true,
            'fix_invalid_html' => true,
        ],

        /*
        |--------------------------------------------------------------------------
        | Text Sanitization
        |--------------------------------------------------------------------------
        */
        'text' => [
            'max_length' => env('EMAIL_TEXT_MAX_LENGTH', 10000),
            'remove_null_bytes' => true,
            'normalize_line_endings' => true,
            'strip_dangerous_chars' => true,
        ],

        /*
        |--------------------------------------------------------------------------
        | Attachment Sanitization
        |--------------------------------------------------------------------------
        */
        'attachments' => [
            'max_size' => env('EMAIL_ATTACHMENT_MAX_SIZE', 10485760), // 10MB
            'allowed_extensions' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'jpg', 'jpeg', 'png', 'gif'],
            'scan_for_viruses' => env('EMAIL_ATTACHMENT_SCAN_VIRUSES', false),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging de Segurança
    |--------------------------------------------------------------------------
    |
    | Configurações para auditoria e monitoramento de segurança.
    |
    */
    'security_logging' => [
        'enabled' => env('EMAIL_SECURITY_LOGGING_ENABLED', true),
        'channel' => env('EMAIL_SECURITY_LOG_CHANNEL', 'daily'),
        'level' => env('EMAIL_SECURITY_LOG_LEVEL', 'warning'),

        /*
        |--------------------------------------------------------------------------
        | Eventos para Logar
        |--------------------------------------------------------------------------
        */
        'events' => [
            'sender_validation_failed' => true,
            'rate_limit_exceeded' => true,
            'suspicious_content' => true,
            'domain_verification_failed' => true,
            'unauthorized_access' => true,
            'configuration_changed' => true,
        ],

        /*
        |--------------------------------------------------------------------------
        | Dados Sensíveis para Ofuscar
        |--------------------------------------------------------------------------
        */
        'sensitive_data' => [
            'passwords' => true,
            'tokens' => true,
            'api_keys' => true,
            'email_content' => false, // Cuidado: pode impactar debugging
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Monitoramento e Alertas
    |--------------------------------------------------------------------------
    |
    | Configurações para monitoramento proativo e alertas de segurança.
    |
    */
    'monitoring' => [
        'enabled' => env('EMAIL_MONITORING_ENABLED', true),

        /*
        |--------------------------------------------------------------------------
        | Métricas para Monitorar
        |--------------------------------------------------------------------------
        */
        'metrics' => [
            'emails_sent_per_hour' => true,
            'failed_emails_per_hour' => true,
            'rate_limit_hits_per_hour' => true,
            'suspicious_activity_per_hour' => true,
        ],

        /*
        |--------------------------------------------------------------------------
        | Alertas Automáticos
        |--------------------------------------------------------------------------
        */
        'alerts' => [
            'high_failure_rate' => [
                'enabled' => true,
                'threshold_percent' => 10,
                'notification_channel' => 'email',
            ],
            'rate_limit_abuse' => [
                'enabled' => true,
                'threshold_per_hour' => 50,
                'notification_channel' => 'email',
            ],
            'suspicious_patterns' => [
                'enabled' => true,
                'notification_channel' => 'email',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Configurações de Desenvolvimento/Teste
    |--------------------------------------------------------------------------
    |
    | Configurações específicas para ambientes de desenvolvimento e teste.
    |
    */
    'development' => [
        'override_recipients' => env('EMAIL_DEV_OVERRIDE_RECIPIENTS', false),
        'log_all_emails' => env('EMAIL_DEV_LOG_ALL', false),
        'disable_rate_limiting' => env('EMAIL_DEV_DISABLE_RATE_LIMITING', false),
        'test_recipients' => explode(',', env('EMAIL_TEST_RECIPIENTS', '')),
    ],
];
