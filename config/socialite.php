<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Socialite Services
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for the socialite services that
    | are available in your application. You can add or modify services
    | as needed for your application.
    |
    */

    'services' => [
        'google' => [
            'client_id' => env('GOOGLE_CLIENT_ID'),
            'client_secret' => env('GOOGLE_CLIENT_SECRET'),
            'redirect' => env('GOOGLE_REDIRECT_URI'),
        ],
    ],

];
