<?php

return [

    /*
    |--------------------------------------------------------------------------
    | View Storage Paths
    |--------------------------------------------------------------------------
    |
    | Most templating systems load templates from disk. Here you may specify
    | an array of paths that should be checked for your views. These paths
    | are relative to the application's root directory.
    |
    */

    'paths' => [
        resource_path('views'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Compiled View Path
    |--------------------------------------------------------------------------
    |
    | This option determines where all the compiled Blade templates will be
    | stored for your application. Typically, this is within the storage
    | directory. However, as usual, you are free to change this value.
    |
    */

    'compiled' => env(
        'VIEW_COMPILED_PATH',
        realpath(storage_path('framework/views')),
    ),

    /*
    |--------------------------------------------------------------------------
    | View Namespaces
    |--------------------------------------------------------------------------
    |
    | Blade has an "namespaces" feature so that your views can be organized
    | in subdirectories and still reference each other using dot notation.
    | This option configures the namespaces that will be recognized.
    |
    */

    'namespaces' => [
        'mail' => resource_path('views/vendor/mail'),
        'pages' => resource_path('views/pages'),
    ],

    /*
    |--------------------------------------------------------------------------
    | View Engines
    |--------------------------------------------------------------------------
    |
    | The following array lists the view engines that will be used by the
    | application. You may add custom engines to this array or remove
    | existing ones as needed.
    |
    */

    'engines' => [
        'blade' => 'Illuminate\View\Engines\BladeEngine',
    ],

];
