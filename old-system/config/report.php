<?php

return [ 
    'reports' => [ 
        'base_dir'        => BASE_PATH . '/storage/reports',
        'allowed_formats' => [ 'pdf', 'csv', 'xlsx' ],
        'max_size'        => 10 * 1024 * 1024, // 10MB
        'retention_days'  => 30,
    ]
];
