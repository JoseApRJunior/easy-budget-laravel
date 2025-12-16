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
        echo "Tenant ID: " . $this->tenant->id . "\n";

        // Test the validation method directly
        echo "\n=== TESTING VALIDATION METHOD DIRECTLY ===\n";
        $isUnique = Category::validateUniqueSlug( 'servicos-gerais', $this->tenant->id );
        echo "validateUniqueSlug('servicos-gerais', {$this->tenant->id}): " . ( $isUnique ? 'TRUE (unique)' : 'FALSE (duplicate)' ) . "\n";

        $isUnique2 = Category::validateUniqueSlug( 'outro-slug', $this->tenant->id );
        echo "validateUniqueSlug('outro-slug', {$this->tenant->id}): " . ( $isUnique2 ? 'TRUE (unique)' : 'FALSE (duplicate)' ) . "\n";

        // Check what's in the database
        echo "\n=== CHECKING DATABASE ===\n";
        $categories = Category::where( 'tenant_id', $this->tenant->id )->get();
        echo "Categories in database: " . $categories->count() . "\n";
        foreach ( $categories as $cat ) {
            echo "  - ID: {$cat->id}, Name: {$cat->name}, Slug: {$cat->slug}\n";
        }

        // Try to create category with explicit duplicate slug
        echo "\n=== ATTEMPTING TO CREATE DUPLICATE SLUG CATEGORY ===\n";

        $response = $this->actingAs( $this->tenantUser )
            ->post( route( 'categories.store' ), [
                'name'      => 'Serviços Diferentes',
                'slug'      => 'servicos-gerais', // Explicit duplicate slug
                'is_active' => true,
            ] );

        echo "Response status: " . $response->getStatusCode() . "\n";
        echo "Response location: " . $response->headers->get( 'Location' ) . "\n";

        // Check session errors more thoroughly
        $session       = app( 'session.store' );
        $sessionErrors = $session->get( 'errors' );
        echo "Session errors object: " . ( $sessionErrors ? get_class( $sessionErrors ) : 'null' ) . "\n";
        echo "Session errors count: " . ( $sessionErrors ? $sessionErrors->count() : 0 ) . "\n";

        if ( $sessionErrors ) {
            foreach ( $sessionErrors->getMessages() as $field => $messages ) {
                echo "  Field '{$field}' errors: " . implode( ', ', $messages ) . "\n";
            }
        }

        // Check if the response has validation errors in its data
        if ( method_exists( $response, 'getSession' ) ) {
            $responseSession = $response->getSession();
            $responseErrors  = $responseSession->get( 'errors' );
            echo "Response session errors: " . ( $responseErrors ? $responseErrors->count() : 0 ) . "\n";
        }

        return $response;
    }

}
