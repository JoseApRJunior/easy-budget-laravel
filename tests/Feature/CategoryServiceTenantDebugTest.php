<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Support\ServiceResult;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CategoryServiceTenantDebugTest extends TestCase
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

        // Get or create provider role
        $providerRole = Role::firstOrCreate( [ 'name' => 'provider' ], [ 'description' => 'Provider' ] );
        $this->tenantUser->roles()->attach( $providerRole->id, [ 'tenant_id' => $this->tenant->id ] );

        // Create the required permission
        $managePermission = Permission::firstOrCreate( [ 'name' => 'manage-custom-categories' ], [ 'description' => 'Manage custom categories' ] );

        // Assign the permission to provider role
        $providerRole->permissions()->syncWithoutDetaching( [ $managePermission->id ] );
    }

    public function test_tenant_authentication_debug()
    {
        echo "\n=== TENANT AUTHENTICATION DEBUG ===\n";

        // First check auth state
        echo "Auth check before login:\n";
        echo "  auth()->check(): " . ( auth()->check() ? 'YES' : 'NO' ) . "\n";
        echo "  auth()->user(): " . ( auth()->user() ? auth()->user()->email : 'null' ) . "\n";

        // Login the user
        auth()->login( $this->tenantUser );

        echo "Auth check after login:\n";
        echo "  auth()->check(): " . ( auth()->check() ? 'YES' : 'NO' ) . "\n";
        echo "  auth()->user(): " . ( auth()->user() ? auth()->user()->email : 'null' ) . "\n";
        echo "  auth()->user()->tenant_id: " . ( auth()->user() ? auth()->user()->tenant_id : 'null' ) . "\n";

        // Create first category
        $firstCategory = Category::create( [
            'name'      => 'Serviços Gerais',
            'slug'      => 'servicos-gerais',
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
        ] );

        echo "Created first category: " . $firstCategory->slug . "\n";

        // Test the service directly with the logged in user
        echo "\n=== TESTING SERVICE WITH LOGGED IN USER ===\n";

        $categoryService = app( \App\Services\Domain\CategoryService::class);

        echo "CategoryService instance created\n";

        $serviceResult = $categoryService->createCategory( [
            'name'      => 'Serviços Diferentes',
            'slug'      => 'servicos-gerais', // Duplicate slug
            'is_active' => true,
        ] );

        echo "ServiceResult isError: " . ( $serviceResult->isError() ? 'YES' : 'NO' ) . "\n";
        echo "ServiceResult message: " . $serviceResult->getMessage() . "\n";

        // Now test through the controller
        echo "\n=== TESTING THROUGH CONTROLLER ===\n";

        $response = $this->actingAs( $this->tenantUser )
            ->post( route( 'categories.store' ), [
                'name'      => 'Serviços Diferentes',
                'slug'      => 'servicos-gerais', // Explicit duplicate slug
                'is_active' => true,
            ] );

        echo "Response status: " . $response->getStatusCode() . "\n";
        echo "Response location: " . ( $response->headers->get( 'Location' ) ?: 'No location header' ) . "\n";

        // Check session content
        $session = $response->getSession();
        echo "Session content:\n";
        foreach ( $session->all() as $key => $value ) {
            echo "  {$key}: " . ( is_array( $value ) ? json_encode( $value ) : $value ) . "\n";
        }
    }

}
