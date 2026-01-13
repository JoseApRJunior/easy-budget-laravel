<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Theme Colors & Config
    |--------------------------------------------------------------------------
    |
    | Centraliza as cores e configurações visuais do sistema, sincronizadas
    | com o arquivo variables.css para uso em PHP, Blade e PDF.
    |
    */

    'colors' => [
        'primary' => '#093172', // --light-primary
        'text' => '#1e293b',    // --light-text
        'secondary' => '#94a3b8', // --light-secondary
        'background' => '#c3d0dd', // --light-background
        'surface' => '#9facb9',    // --light-surface
        'border' => '#e2e8f0',
        'dark' => '#1a1a1a',
        'white' => '#ffffff',

        // Cores vindas do variables.css
        'form_bg' => '#9facb9', // --form-bg (mesmo que surface)
        'form_text' => '#1e293b', // --form-text
        'form_border' => '#b4c2d3', // --form-border
        'form_input_bg' => '#e2e8f0', // --form-input-bg
        'form_input_border' => '#9fb3c8', // --form-input-border
        'small_text' => '#475569', // --small-text

        // Cores de Contraste (para fundos escuros/gradientes)
        'contrast_text' => '#ffffff',
        'contrast_text_secondary' => 'rgba(255, 255, 255, 0.8)',
        'contrast_overlay' => 'rgba(255, 255, 255, 0.2)',

        // Status (Sincronizado com variables.css)
        'success' => '#059669',
        'error' => '#dc2626',
        'danger' => '#dc2626',
        'warning' => '#d97706',
        'info' => '#163881',
    ],

    'fonts' => [
        'primary' => "'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Arial, sans-serif",
    ],

    /*
    |--------------------------------------------------------------------------
    | PDF Specific Config
    |--------------------------------------------------------------------------
    */
    'pdf' => [
        'margins' => [
            'left' => 8,
            'right' => 8,
            'top' => 10,
            'bottom' => 10,
            'header' => 5,
            'footer' => 5,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Dashboard Config
    |--------------------------------------------------------------------------
    */
    'dashboard' => [
        'stat_card' => [
            'gradient' => true,
            'shadow' => true,
        ],
    ],
];
