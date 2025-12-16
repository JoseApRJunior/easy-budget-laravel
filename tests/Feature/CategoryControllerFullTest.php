<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CategoryControllerFullTest extends TestCase
{
    use \Illuminate\Foundation\Testing\RefreshDatabase;

    protected $tenant;
    protected $tenantUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup Tenant
        $this->tenant = Tenant::factory()->create();

        // Setup Tenant User
        $this->tenantUser = User::factory()->create( [
            'tenant_id'         => $this->tenant->id,
            'email_verified_at' => now(), // Mark as verified to avoid redirect
        ] );

        // Assign 'provider' role to tenantUser
        $providerRole = \App\Models\Role::firstOrCreate( [ 'name' => 'provider' ], [ 'description' => 'Provider' ] );
        $this->tenantUser->roles()->attach( $providerRole->id, [ 'tenant_id' => $this->tenant->id ] );
    }

    public function test_full_controller_flow_with_duplicate_slug()
    {
        echo "\n=== TESTING FULL CONTROLLER FLOW ===\n";

        // Create first category
        $firstCategory = Category::create( [
            'name'      => 'Serviços Gerais',
            'slug'      => 'servicos-gerais',
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
        ] );

        echo "Created first category: " . $firstCategory->slug . "\n";

        // Make the request through the controller
        echo "\n=== MAKING HTTP REQUEST ===\n";

        try {
            $response = $this->actingAs( $this->tenantUser )
                ->post( route( 'categories.store' ), [
                    'name'      => 'Serviços Diferentes',
                    'slug'      => 'servicos-gerais', // Explicit duplicate slug
                    'is_active' => true,
                ] );

            echo "Response status: " . $response->getStatusCode() . "\n";
            echo "Response location: " . ( $response->headers->get( 'Location' ) ?: 'No location header' ) . "\n";

            // Check session errors
            $session       = app( 'session.store' );
            $sessionErrors = $session->get( 'errors' );
            echo "Session errors object: " . ( $sessionErrors ? get_class( $sessionErrors ) : 'null' ) . "\n";
            echo "Session errors count: " . ( $sessionErrors ? $sessionErrors->count() : 0 ) . "\n";

            if ( $sessionErrors ) {
                foreach ( $sessionErrors->getMessages() as $field => $messages ) {
                    echo "  Field '{$field}' errors: " . implode( ', ', $messages ) . "\n";
                }
            }

            // Check if the response is a redirect
            if ( $response->isRedirect() ) {
                echo "Response is a redirect to: " . $response->headers->get( 'Location' ) . "\n";
            }

            // Try to follow the redirect and see what we get
            if ( $response->isRedirect() ) {
                echo "\n=== FOLLOWING REDIRECT ===\n";
                $followResponse = $this->get( $response->headers->get( 'Location' ) );
                echo "Follow response status: " . $followResponse->getStatusCode() . "\n";

                // Check for error messages in the follow response
                $content = $followResponse->getContent();
                echo "Content contains error: " . ( strpos( $content, 'error' ) !== false ? 'YES' : 'NO' ) . "\n";
                echo "Content contains slug: " . ( strpos( $content, 'slug' ) !== false ? 'YES' : 'NO' ) . "\n";
            }

        } catch ( \Exception $e ) {
            echo "EXCEPTION CAUGHT: " . get_class( $e ) . ": " . $e->getMessage() . "\n";
            echo "Stack trace: " . $e->getTraceAsString() . "\n";
        }
    }

}
