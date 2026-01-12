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
