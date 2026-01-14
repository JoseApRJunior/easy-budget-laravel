<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Configurações Gerais do Sistema de Email Templates
    |--------------------------------------------------------------------------
    */

    'enabled' => env('EMAIL_TEMPLATES_ENABLED', true),

    'default_sender' => [
        'name' => env('MAIL_FROM_NAME', 'Easy Budget'),
        'email' => env('MAIL_FROM_ADDRESS', 'noreply@easybudget.com'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Configurações do Editor Visual (TinyMCE)
    |--------------------------------------------------------------------------
    */

    'editor' => [
        'enabled' => env('EMAIL_TEMPLATES_EDITOR_ENABLED', true),
        'cdn_url' => env('EMAIL_TEMPLATES_EDITOR_CDN', 'https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js'),
        'api_key' => env('TINYMCE_API_KEY'), // Para recursos premium
        'default_height' => 500,
        'max_height' => 800,
        'min_height' => 300,
        'language' => 'pt_BR',
        'plugins' => [
            'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
            'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
            'insertdatetime', 'media', 'table', 'help', 'wordcount', 'emoticons',
            'template', 'codesample', 'hr', 'pagebreak', 'nonbreaking', 'toc',
            'imagetools', 'textpattern', 'paste', 'importcss', 'autosave',
            'save', 'directionality', 'visualchars', 'quickbars',
        ],
        'toolbar' => [
            'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough',
            'alignleft aligncenter alignright alignjustify | bullist numlist outdent indent',
            'forecolor backcolor | link image media table | hr pagebreak',
            'template codesample | preview fullscreen | code',
        ],
        'menubar' => 'edit view insert format tools table help',
        'contextmenu' => 'link image table configurepermanentpen',
        'image_advtab' => true,
        'image_title' => true,
        'automatic_uploads' => true,
        'file_picker_types' => 'image media',
        'paste_data_images' => true,
        'paste_as_text' => false,
        'smart_paste' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Configurações de Variáveis
    |--------------------------------------------------------------------------
    */

    'variables' => [
        'cache_enabled' => env('EMAIL_TEMPLATES_VARIABLES_CACHE', true),
        'cache_ttl' => env('EMAIL_TEMPLATES_VARIABLES_CACHE_TTL', 3600), // 1 hora
        'system_variables' => [
            'company_name' => [
                'name' => 'Nome da Empresa',
                'description' => 'Nome da empresa/organização',
                'category' => 'company',
                'type' => 'string',
                'required' => false,
            ],
            'company_email' => [
                'name' => 'Email da Empresa',
                'description' => 'Email de contato da empresa',
                'category' => 'company',
                'type' => 'string',
                'required' => false,
            ],
            'company_phone' => [
                'name' => 'Telefone da Empresa',
                'description' => 'Telefone de contato da empresa',
                'category' => 'company',
                'type' => 'string',
                'required' => false,
            ],
            'current_date' => [
                'name' => 'Data Atual',
                'description' => 'Data atual no formato DD/MM/YYYY',
                'category' => 'system',
                'type' => 'date',
                'required' => false,
            ],
            'current_year' => [
                'name' => 'Ano Atual',
                'description' => 'Ano atual (YYYY)',
                'category' => 'system',
                'type' => 'number',
                'required' => false,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Configurações de Rastreamento de Emails
    |--------------------------------------------------------------------------
    */

    'tracking' => [
        'enabled' => env('EMAIL_TEMPLATES_TRACKING_ENABLED', true),
        'track_opens' => env('EMAIL_TEMPLATES_TRACK_OPENS', true),
        'track_clicks' => env('EMAIL_TEMPLATES_TRACK_CLICKS', true),
        'pixel_expiry' => env('EMAIL_TEMPLATES_TRACKING_EXPIRY', 2592000), // 30 dias
        'anonymize_ip' => env('EMAIL_TEMPLATES_ANONYMIZE_IP', false),
        'geolocation' => env('EMAIL_TEMPLATES_GEOLOCATION', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Configurações de Queue e Processamento
    |--------------------------------------------------------------------------
    */

    'queue' => [
        'enabled' => env('EMAIL_TEMPLATES_QUEUE_ENABLED', true),
        'connection' => env('EMAIL_TEMPLATES_QUEUE_CONNECTION', 'database'),
        'queue_name' => env('EMAIL_TEMPLATES_QUEUE_NAME', 'emails'),
        'retry_attempts' => env('EMAIL_TEMPLATES_QUEUE_RETRY', 3),
        'retry_delay' => env('EMAIL_TEMPLATES_QUEUE_RETRY_DELAY', 60), // segundos
        'batch_size' => env('EMAIL_TEMPLATES_QUEUE_BATCH_SIZE', 100),
        'rate_limiting' => [
            'enabled' => env('EMAIL_TEMPLATES_RATE_LIMITING', false),
            'max_per_minute' => env('EMAIL_TEMPLATES_RATE_LIMIT_MAX', 60),
            'max_per_hour' => env('EMAIL_TEMPLATES_RATE_LIMIT_HOUR', 1000),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Configurações de Templates
    |--------------------------------------------------------------------------
    */

    'templates' => [
        'categories' => [
            'transactional' => 'Transacional',
            'promotional' => 'Promocional',
            'notification' => 'Notificação',
            'system' => 'Sistema',
        ],
        'default_category' => 'transactional',
        'max_content_size' => env('EMAIL_TEMPLATES_MAX_CONTENT_SIZE', 1048576), // 1MB
        'allowed_extensions' => ['html', 'htm', 'txt'],
        'auto_save' => [
            'enabled' => env('EMAIL_TEMPLATES_AUTO_SAVE', true),
            'interval' => env('EMAIL_TEMPLATES_AUTO_SAVE_INTERVAL', 30000), // 30 segundos
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Configurações de Segurança
    |--------------------------------------------------------------------------
    */

    'security' => [
        'sanitize_html' => env('EMAIL_TEMPLATES_SANITIZE_HTML', true),
        'allowed_tags' => '<p><br><strong><em><u><h1><h2><h3><h4><h5><h6><ul><ol><li><a><img><table><thead><tbody><tfoot><tr><th><td><caption><blockquote><cite><code><pre><span><div><style><script>',
        'allowed_attributes' => 'href,src,alt,title,class,id,style,width,height,border,cellpadding,cellspacing,bgcolor',
        'xss_protection' => env('EMAIL_TEMPLATES_XSS_PROTECTION', true),
        'content_validation' => env('EMAIL_TEMPLATES_CONTENT_VALIDATION', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Configurações de Preview
    |--------------------------------------------------------------------------
    */

    'preview' => [
        'devices' => [
            'desktop' => [
                'name' => 'Desktop',
                'width' => 800,
                'height' => 600,
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
            ],
            'tablet' => [
                'name' => 'Tablet',
                'width' => 768,
                'height' => 1024,
                'user_agent' => 'Mozilla/5.0 (iPad; CPU OS 14_0 like Mac OS X) AppleWebKit/605.1.15',
            ],
            'mobile' => [
                'name' => 'Mobile',
                'width' => 375,
                'height' => 667,
                'user_agent' => 'Mozilla/5.0 (iPhone; CPU iPhone OS 14_0 like Mac OS X) AppleWebKit/605.1.15',
            ],
        ],
        'default_device' => 'desktop',
        'cache_enabled' => env('EMAIL_TEMPLATES_PREVIEW_CACHE', true),
        'cache_ttl' => env('EMAIL_TEMPLATES_PREVIEW_CACHE_TTL', 300), // 5 minutos
    ],

    /*
    |--------------------------------------------------------------------------
    | Configurações de Logs e Auditoria
    |--------------------------------------------------------------------------
    */

    'logging' => [
        'enabled' => env('EMAIL_TEMPLATES_LOGGING_ENABLED', true),
        'level' => env('EMAIL_TEMPLATES_LOG_LEVEL', 'info'),
        'channel' => env('EMAIL_TEMPLATES_LOG_CHANNEL', 'stack'),
        'retention_days' => env('EMAIL_TEMPLATES_LOG_RETENTION', 90),
        'audit_enabled' => env('EMAIL_TEMPLATES_AUDIT_ENABLED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Configurações de Testes
    |--------------------------------------------------------------------------
    */

    'testing' => [
        'enabled' => env('EMAIL_TEMPLATES_TESTING_ENABLED', true),
        'default_recipient' => env('EMAIL_TEMPLATES_TEST_RECIPIENT', 'teste@easybudget.com'),
        'max_test_emails' => env('EMAIL_TEMPLATES_MAX_TEST_EMAILS', 5),
        'test_data' => [
            'company_name' => 'Easy Budget',
            'company_email' => 'contato@easybudget.com',
            'company_phone' => '(11) 99999-9999',
            'customer_name' => 'Cliente Teste',
            'customer_email' => 'cliente@teste.com',
            'budget_number' => 'ORC2024001',
            'budget_value' => '5.000,00',
            'invoice_number' => 'FAT2024001',
            'invoice_amount' => '5.000,00',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Configurações de Performance
    |--------------------------------------------------------------------------
    */

    'performance' => [
        'cache_enabled' => env('EMAIL_TEMPLATES_CACHE_ENABLED', true),
        'cache_driver' => env('EMAIL_TEMPLATES_CACHE_DRIVER', 'redis'),
        'cache_ttl' => env('EMAIL_TEMPLATES_CACHE_TTL', 3600),
        'compression_enabled' => env('EMAIL_TEMPLATES_COMPRESSION', true),
        'minify_html' => env('EMAIL_TEMPLATES_MINIFY_HTML', false),
        'lazy_loading' => env('EMAIL_TEMPLATES_LAZY_LOADING', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Configurações de Integração
    |--------------------------------------------------------------------------
    */

    'integrations' => [
        'mailgun' => [
            'enabled' => env('MAILGUN_ENABLED', false),
            'api_key' => env('MAILGUN_SECRET'),
            'domain' => env('MAILGUN_DOMAIN'),
        ],
        'ses' => [
            'enabled' => env('AWS_SES_ENABLED', false),
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
        ],
        'sendgrid' => [
            'enabled' => env('SENDGRID_ENABLED', false),
            'api_key' => env('SENDGRID_API_KEY'),
        ],
    ],
];
