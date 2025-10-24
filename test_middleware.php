<?php

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

$app = require_once 'bootstrap/app.php';
$app->make( 'Illuminate\Contracts\Console\Kernel' )->bootstrap();

echo "=== MIDDLEWARE TESTING ===\n\n";

// Test 1: Public routes (should work without authentication)
echo "1. Testing public routes (no auth required):\n";
$publicRoutes = [ '/', '/about', '/support', '/terms-of-service', '/privacy-policy' ];

foreach ( $publicRoutes as $route ) {
    try {
        $response = $app->make( 'router' )->dispatch( Request::create( $route, 'GET' ) );
        $status   = $response->getStatusCode();
        echo "  {$route}: HTTP {$status} ✓\n";
    } catch ( Exception $e ) {
        echo "  {$route}: ERROR - " . $e->getMessage() . "\n";
    }
}

echo "\n";

// Test 2: Authenticated routes (should redirect to login)
echo "2. Testing authenticated routes (should redirect to login):\n";
$authRoutes = [ '/provider/dashboard', '/admin/dashboard', '/settings' ];

foreach ( $authRoutes as $route ) {
    try {
        $response = $app->make( 'router' )->dispatch( Request::create( $route, 'GET' ) );
        $status   = $response->getStatusCode();
        echo "  {$route}: HTTP {$status} ✓\n";
    } catch ( Exception $e ) {
        echo "  {$route}: ERROR - " . $e->getMessage() . "\n";
    }
}

echo "\n";

// Test 3: Test with authenticated user
echo "3. Testing with authenticated user:\n";

// Find a provider user
$provider = App\Models\Provider::with( 'user' )->first();
if ( $provider && $provider->user ) {
    echo "  Found provider: " . $provider->user->email . "\n";

    // Simulate authenticated request
    $request = Request::create( '/provider/dashboard', 'GET' );

    // Properly authenticate the user
    Auth::login( $provider->user );
    $request->setUserResolver( function () use ($provider) {
        return $provider->user;
    } );

    try {
        // Enable query logging to see what's happening
        \Illuminate\Support\Facades\DB::enableQueryLog();

        // Debug: Check if route is resolved correctly
        $route = $app->make( 'router' )->getRoutes()->getByName( 'provider.dashboard' );
        echo "    Debug: Route found: " . ( $route ? 'Yes' : 'No' ) . "\n";
        if ( $route ) {
            echo "    Debug: Route URI: " . $route->uri() . "\n";
            echo "    Debug: Route middleware: " . implode( ', ', $route->middleware() ) . "\n";
        }

        $response = $app->make( 'router' )->dispatch( $request );
        $status   = $response->getStatusCode();

        $queries = \Illuminate\Support\Facades\DB::getQueryLog();
        echo "  /provider/dashboard: HTTP {$status} ✓\n";
        echo "    Debug: Number of queries executed: " . count( $queries ) . "\n";
        foreach ( $queries as $query ) {
            echo "      Query: " . $query[ 'query' ] . "\n";
        }

        if ( $status === 302 ) {
            echo "    Debug: Redirect location: " . $response->getTargetUrl() . "\n";
        }

        // Debug: Check if user has provider role
        if ( $status === 302 ) {
            echo "    Debug: User has provider role: " . ( $provider->user->hasRole( 'provider' ) ? 'Yes' : 'No' ) . "\n";
            echo "    Debug: User tenant_id: " . $provider->user->tenant_id . "\n";
            echo "    Debug: Provider tenant_id: " . $provider->tenant_id . "\n";
            echo "    Debug: Provider is active: " . ( $provider->user->is_active ? 'Yes' : 'No' ) . "\n";
            echo "    Debug: User is active: " . ( $provider->user->is_active ? 'Yes' : 'No' ) . "\n";
            echo "    Debug: Trial expired: " . ( $provider->user->isTrialExpired() ? 'Yes' : 'No' ) . "\n";

            // Test middleware logic manually
            echo "    Debug: Manual middleware check:\n";
            $isProviderRole = \App\Models\UserRole::where( 'user_id', $provider->user->id )
                ->where( 'tenant_id', $provider->user->tenant_id )
                ->whereHas( 'role', function ( $query ) {
                    $query->where( 'name', 'provider' );
                } )
                ->exists();
            echo "      Is provider role: " . ( $isProviderRole ? 'Yes' : 'No' ) . "\n";

            $providerCheck = \App\Models\Provider::where( 'user_id', $provider->user->id )->first();
            echo "      Provider exists: " . ( $providerCheck ? 'Yes' : 'No' ) . "\n";
            echo "      User is active: " . ( $provider->user->is_active ? 'Yes' : 'No' ) . "\n";
        }
    } catch ( Exception $e ) {
        echo "  /provider/dashboard: ERROR - " . $e->getMessage() . "\n";
    }
} else {
    echo "  No provider found for testing\n";
}

echo "\n";

// Test 4: Test admin routes
echo "4. Testing admin routes:\n";

// Find admin user
$admin = App\Models\User::where( 'email', 'admin@easybudget.net.br' )->first();
if ( $admin ) {
    echo "  Found admin: " . $admin->email . "\n";

    // Simulate authenticated request as admin
    $request = Request::create( '/admin/dashboard', 'GET' );

    // Properly authenticate the user
    Auth::login( $admin );
    $request->setUserResolver( function () use ($admin) {
        return $admin;
    } );

    try {
        // Enable query logging to see what's happening
        \Illuminate\Support\Facades\DB::enableQueryLog();

        // Debug: Check if route is resolved correctly
        $route = $app->make( 'router' )->getRoutes()->getByName( 'admin.dashboard' );
        echo "    Debug: Route found: " . ( $route ? 'Yes' : 'No' ) . "\n";
        if ( $route ) {
            echo "    Debug: Route URI: " . $route->uri() . "\n";
            echo "    Debug: Route middleware: " . implode( ', ', $route->middleware() ) . "\n";
        }

        $response = $app->make( 'router' )->dispatch( $request );
        $status   = $response->getStatusCode();

        $queries = \Illuminate\Support\Facades\DB::getQueryLog();
        echo "  /admin/dashboard: HTTP {$status} ✓\n";
        echo "    Debug: Number of queries executed: " . count( $queries ) . "\n";
        foreach ( $queries as $query ) {
            echo "      Query: " . $query[ 'query' ] . "\n";
        }

        if ( $status === 302 ) {
            echo "    Debug: Redirect location: " . $response->getTargetUrl() . "\n";
        }

        // Debug: Check if user has admin role
        if ( $status === 302 ) {
            echo "    Debug: User has admin role: " . ( $admin->hasRole( 'admin' ) ? 'Yes' : 'No' ) . "\n";
            echo "    Debug: User tenant_id: " . $admin->tenant_id . "\n";
            echo "    Debug: User is active: " . ( $admin->is_active ? 'Yes' : 'No' ) . "\n";
        }
    } catch ( Exception $e ) {
        echo "  /admin/dashboard: ERROR - " . $e->getMessage() . "\n";
        echo "  Stack trace: " . $e->getTraceAsString() . "\n";
    }
} else {
    echo "  No admin found for testing\n";
}

echo "\n=== TESTING COMPLETE ===\n";
