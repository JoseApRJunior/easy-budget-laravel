<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | PDF Theme Colors
    |--------------------------------------------------------------------------
    |
    | Cores extraídas do variables.css para manter a identidade visual
    | do sistema em todos os documentos gerados.
    |
    */

    'colors' => [
        'primary' => '#093172', // --light-primary
        'text' => '#1e293b', // --light-text
        'secondary' => '#1e293b', // Cinza mais escuro para melhor leitura
        'background' => '#c3d0dd', // --light-background
        'surface' => '#9facb9', // --light-surface
        'border' => '#333333', // Linha de separação mais visível
        'dark' => '#1a1a1a', // Destaques em preto

        // Status
        'success' => '#059669',
        'error' => '#dc2626',
        'warning' => '#d97706',
        'info' => '#163881',
    ],

    'fonts' => [
        'primary' => "'Segoe UI', Arial, sans-serif",
    ],
];
