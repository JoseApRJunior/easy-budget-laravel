<?php

// Bootstrap Laravel
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make( \Illuminate\Contracts\Console\Kernel::class)->bootstrap();

try {
    // Create a test user
    $user = new App\Models\User( [
        'id'         => 2,
        'email'      => 'test@example.com',
        'created_at' => now(),
    ] );

    // Set the key explicitly
    $user->id = 2;

    // Create notification instance
    $notification = new App\Notifications\VerifyEmailNotification();

    // Generate verification URL
    $verificationUrl = $notification->verificationUrl( $user );

    echo "SUCCESS: Verification URL generated!\n";
    echo "URL: " . $verificationUrl . "\n";

    // Test if URL contains expected components
    if ( strpos( $verificationUrl, 'signature=' ) !== false ) {
        echo "SUCCESS: URL contains signature parameter!\n";
    } else {
        echo "ERROR: URL missing signature parameter!\n";
    }

    if ( strpos( $verificationUrl, 'expires=' ) !== false ) {
        echo "SUCCESS: URL contains expires parameter!\n";
    } else {
        echo "ERROR: URL missing expires parameter!\n";
    }

} catch ( Exception $e ) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
