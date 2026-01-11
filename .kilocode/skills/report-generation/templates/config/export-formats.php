<?php

// Configuração de formatos de exportação de relatórios
return [
    // PDF Export
    'pdf' => [
        'enabled' => true,
        'description' => 'Portable Document Format',
        'extension' => 'pdf',
        'mime_type' => 'application/pdf',
        'template' => 'reports.pdf.default',
        'orientation' => 'portrait', // portrait, landscape
        'format' => 'A4',
        'margin' => [
            'top' => 10,
            'bottom' => 10,
            'left' => 10,
            'right' => 10,
            'header' => 5,
            'footer' => 5,
        ],
        'font' => [
            'family' => 'Arial, sans-serif',
            'size' => '12pt',
            'color' => '#333333',
        ],
        'header' => [
            'enabled' => true,
            'template' => 'reports.pdf.header',
            'include_logo' => true,
            'include_date' => true,
            'include_filters' => true,
        ],
        'footer' => [
            'enabled' => true,
            'template' => 'reports.pdf.footer',
            'include_page_numbers' => true,
            'include_company_info' => true,
        ],
        'charts' => [
            'enabled' => true,
            'format' => 'png', // png, jpeg, svg
            'width' => 800,
            'height' => 400,
        ],
        'tables' => [
            'border' => true,
            'striped' => true,
            'responsive' => true,
        ],
        'compression' => [
            'enabled' => true,
            'level' => 6, // 0-9
        ],
        'security' => [
            'password' => null,
            'permissions' => [
                'print' => true,
                'modify' => false,
                'copy' => false,
                'annotate' => false,
            ],
        ],
    ],

    // Excel Export
    'excel' => [
        'enabled' => true,
        'description' => 'Microsoft Excel',
        'extension' => 'xlsx',
        'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'template' => 'reports.excel.default',
        'include_charts' => true,
        'auto_size_columns' => true,
        'freeze_panes' => true,
        'filter_enabled' => true,
        'styles' => [
            'header' => [
                'font' => ['bold' => true, 'size' => 12],
                'fill' => ['color' => ['rgb' => 'E7E7E7']],
                'borders' => ['allBorders' => ['borderStyle' => 'thin']],
            ],
            'data' => [
                'font' => ['size' => 11],
                'borders' => ['outline' => ['borderStyle' => 'thin']],
            ],
            'totals' => [
                'font' => ['bold' => true],
                'fill' => ['color' => ['rgb' => 'F2F2F2']],
            ],
        ],
        'number_format' => [
            'currency' => 'R$ #,##0.00',
            'percentage' => '0.00%',
            'date' => 'dd/mm/yyyy',
            'datetime' => 'dd/mm/yyyy hh:mm',
        ],
        'charts' => [
            'enabled' => true,
            'type' => 'column', // column, line, pie, bar, area
            'position' => 'after_data',
            'size' => ['width' => 600, 'height' => 300],
        ],
        'pivot_tables' => [
            'enabled' => false,
            'fields' => [],
        ],
    ],

    // CSV Export
    'csv' => [
        'enabled' => true,
        'description' => 'Comma Separated Values',
        'extension' => 'csv',
        'mime_type' => 'text/csv',
        'template' => 'reports.csv.default',
        'delimiter' => ';',
        'enclosure' => '"',
        'escape_character' => '\\',
        'encoding' => 'UTF-8',
        'include_headers' => true,
        'include_bom' => true, // Byte Order Mark para Excel
        'date_format' => 'd/m/Y',
        'time_format' => 'H:i:s',
        'number_format' => [
            'decimal_separator' => ',',
            'thousands_separator' => '.',
            'currency_symbol' => 'R$',
        ],
        'null_value' => '',
        'empty_value' => '',
    ],

    // Word Export
    'word' => [
        'enabled' => false, // Desativado por padrão
        'description' => 'Microsoft Word',
        'extension' => 'docx',
        'mime_type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'template' => 'reports.word.default',
        'include_charts' => true,
        'include_tables' => true,
        'include_images' => true,
        'page_setup' => [
            'orientation' => 'portrait',
            'paper_size' => 'A4',
            'margins' => [
                'top' => 2.54,
                'bottom' => 2.54,
                'left' => 2.54,
                'right' => 2.54,
            ],
        ],
        'styles' => [
            'title' => [
                'font' => ['size' => 16, 'bold' => true],
                'alignment' => ['horizontal' => 'center'],
            ],
            'heading' => [
                'font' => ['size' => 14, 'bold' => true],
            ],
            'body' => [
                'font' => ['size' => 12],
                'alignment' => ['horizontal' => 'left'],
            ],
        ],
    ],

    // JSON Export
    'json' => [
        'enabled' => true,
        'description' => 'JavaScript Object Notation',
        'extension' => 'json',
        'mime_type' => 'application/json',
        'template' => 'reports.json.default',
        'pretty_print' => true,
        'include_metadata' => true,
        'include_summary' => true,
        'encoding' => 'UTF-8',
        'indentation' => 4,
        'date_format' => 'Y-m-d H:i:s',
    ],

    // XML Export
    'xml' => [
        'enabled' => false, // Desativado por padrão
        'description' => 'eXtensible Markup Language',
        'extension' => 'xml',
        'mime_type' => 'application/xml',
        'template' => 'reports.xml.default',
        'include_schema' => false,
        'encoding' => 'UTF-8',
        'indentation' => 2,
        'root_element' => 'report',
        'item_element' => 'item',
    ],

    // HTML Export
    'html' => [
        'enabled' => true,
        'description' => 'HyperText Markup Language',
        'extension' => 'html',
        'mime_type' => 'text/html',
        'template' => 'reports.html.default',
        'include_styles' => true,
        'include_scripts' => true,
        'responsive' => true,
        'print_styles' => true,
        'encoding' => 'UTF-8',
    ],

    // Google Sheets Export
    'google_sheets' => [
        'enabled' => false, // Requer integração com Google API
        'description' => 'Google Sheets',
        'extension' => 'gsheet',
        'mime_type' => 'application/vnd.google-apps.spreadsheet',
        'template' => 'reports.google_sheets.default',
        'api_key' => env('GOOGLE_SHEETS_API_KEY'),
        'spreadsheet_title' => 'Relatório Easy Budget',
        'include_charts' => true,
        'share_with' => [],
        'permissions' => [
            'read' => true,
            'write' => false,
        ],
    ],

    // Power BI Export
    'power_bi' => [
        'enabled' => false, // Requer integração com Power BI API
        'description' => 'Microsoft Power BI',
        'extension' => 'pbix',
        'mime_type' => 'application/octet-stream',
        'template' => 'reports.power_bi.default',
        'api_key' => env('POWER_BI_API_KEY'),
        'workspace_id' => env('POWER_BI_WORKSPACE_ID'),
        'dataset_name' => 'Easy Budget Dataset',
        'include_visuals' => true,
        'include_filters' => true,
    ],
];
