<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CategoryControllerDebugTest extends TestCase
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

    public function test_debug_slug_validation()
    {
        echo "\n=== DEBUGGING SLUG VALIDATION ===\n";

        // Create first category
        $firstCategory = Category::create( [
            'name'      => 'Serviços Gerais',
            'slug'      => 'servicos-gerais',
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
        ] );

        echo "Created first category: " . $firstCategory->slug . "\n";

        // Try to create category with explicit duplicate slug
        echo "Attempting to create duplicate slug category...\n";

        $response = $this->actingAs( $this->tenantUser )
            ->post( route( 'categories.store' ), [
                'name'      => 'Serviços Diferentes',
                'slug'      => 'servicos-gerais', // Explicit duplicate slug
                'is_active' => true,
            ] );

        echo "Response status: " . $response->getStatusCode() . "\n";
        echo "Response content: " . $response->getContent() . "\n";

        $errors = session()->get( 'errors' );
        echo "Session errors: " . ( $errors ? $errors->first() : 'No errors in session' ) . "\n";

        if ( $errors ) {
            echo "All errors: ";
            foreach ( $errors->all() as $error ) {
                echo " - " . $error . "\n";
            }
        }

        // Check if the error is in the response content
        $responseContent = $response->getContent();
        echo "Response contains slug error: " . ( strpos( $responseContent, 'slug' ) !== false ? 'YES' : 'NO' ) . "\n";
        echo "Response contains duplicate error: " . ( strpos( $responseContent, 'duplicado' ) !== false ? 'YES' : 'NO' ) . "\n";

        return $response;
    }

}
