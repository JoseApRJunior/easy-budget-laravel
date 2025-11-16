<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Upload Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for file uploads, including
    | image processing, storage paths, and validation rules.
    |
    */

    'images' => [
        /*
        | Maximum dimensions for uploaded images
        */
        'max_width' => env('UPLOAD_IMAGE_MAX_WIDTH', 1920),
        'max_height' => env('UPLOAD_IMAGE_MAX_HEIGHT', 1080),
        
        /*
        | Image quality for compression (1-100)
        */
        'quality' => env('UPLOAD_IMAGE_QUALITY', 85),
        
        /*
        | Thumbnail configuration
        */
        'thumbnail_width' => env('UPLOAD_THUMBNAIL_WIDTH', 300),
        'thumbnail_height' => env('UPLOAD_THUMBNAIL_HEIGHT', 300),
        
        /*
        | Watermark configuration
        */
        'watermark_enabled' => env('UPLOAD_WATERMARK_ENABLED', false),
        'watermark_path' => env('UPLOAD_WATERMARK_PATH', public_path('assets/images/watermark.png')),
        'watermark_position' => env('UPLOAD_WATERMARK_POSITION', 'bottom-right'),
        'watermark_opacity' => env('UPLOAD_WATERMARK_OPACITY', 0.5),
        
        /*
        | Allowed image extensions
        */
        'allowed_extensions' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'],
        
        /*
        | Maximum file size in KB
        */
        'max_size' => env('UPLOAD_IMAGE_MAX_SIZE', 10240), // 10MB
        
        /*
        | Generate multiple sizes
        */
        'sizes' => [
            'small' => [
                'width' => 300,
                'height' => 300,
            ],
            'medium' => [
                'width' => 600,
                'height' => 600,
            ],
            'large' => [
                'width' => 1200,
                'height' => 1200,
            ],
        ],
        
        /*
        | Storage disk for images
        */
        'disk' => env('UPLOAD_IMAGE_DISK', 'public'),
        
        /*
        | Default directory structure
        */
        'directory_structure' => 'Y/m/d',
    ],

    'documents' => [
        /*
        | Allowed document extensions
        */
        'allowed_extensions' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv'],
        
        /*
        | Maximum file size in KB
        */
        'max_size' => env('UPLOAD_DOCUMENT_MAX_SIZE', 51200), // 50MB
        
        /*
        | Storage disk for documents
        */
        'disk' => env('UPLOAD_DOCUMENT_DISK', 'local'),
        
        /*
        | Default directory structure
        */
        'directory_structure' => 'Y/m/d',
    ],

    'general' => [
        /*
        | Temporary file lifetime in hours
        */
        'temp_file_lifetime' => env('UPLOAD_TEMP_FILE_LIFETIME', 24),
        
        /*
        | Cleanup temporary files older than X hours
        */
        'cleanup_hours' => env('UPLOAD_CLEANUP_HOURS', 24),
        
        /*
        | Maximum number of files per upload batch
        */
        'max_files_per_batch' => env('UPLOAD_MAX_FILES_PER_BATCH', 10),
        
        /*
        | Sanitize filenames
        */
        'sanitize_filenames' => env('UPLOAD_SANITIZE_FILENAMES', true),
        
        /*
        | Generate unique filenames
        */
        'generate_unique_names' => env('UPLOAD_GENERATE_UNIQUE_NAMES', true),
    ],

    'security' => [
        /*
        | Scan uploaded files for malware
        */
        'scan_for_malware' => env('UPLOAD_SCAN_FOR_MALWARE', false),
        
        /*
        | Check MIME type against file extension
        */
        'validate_mime_type' => env('UPLOAD_VALIDATE_MIME_TYPE', true),
        
        /*
        | Maximum filename length
        */
        'max_filename_length' => env('UPLOAD_MAX_FILENAME_LENGTH', 255),
        
        /*
        | Disallowed characters in filenames
        */
        'disallowed_chars' => ['..', '/', '\\', '<', '>', ':', '"', '|', '?', '*'],
    ],

    'presets' => [
        /*
        | Predefined upload configurations
        */
        'avatar' => [
            'max_width' => 500,
            'max_height' => 500,
            'thumbnail_width' => 150,
            'thumbnail_height' => 150,
            'quality' => 90,
            'generate_thumbnail' => true,
        ],
        
        'product' => [
            'max_width' => 1200,
            'max_height' => 1200,
            'thumbnail_width' => 300,
            'thumbnail_height' => 300,
            'quality' => 85,
            'generate_thumbnail' => true,
            'generate_sizes' => true,
        ],
        
        'banner' => [
            'max_width' => 1920,
            'max_height' => 600,
            'quality' => 80,
            'generate_thumbnail' => false,
        ],
        
        'document_scan' => [
            'max_width' => 1200,
            'max_height' => 1600,
            'quality' => 95,
            'generate_thumbnail' => true,
            'watermark' => false,
        ],
    ],
];