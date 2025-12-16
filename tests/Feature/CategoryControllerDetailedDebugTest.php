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

class CategoryControllerDetailedDebugTest extends TestCase
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

    public function test_detailed_duplicate_slug_flow()
    {
        echo "\n=== DETAILED DUPLICATE SLUG DEBUG ===\n";

        // Create first category
        $firstCategory = Category::create( [
            'name'      => 'Serviços Gerais',
            'slug'      => 'servicos-gerais',
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
        ] );

        echo "Created first category: " . $firstCategory->slug . "\n";

        // Check if user can access the store route
        echo "User can access categories.store: " . ( $this->tenantUser->can( 'manage-custom-categories' ) ? 'YES' : 'NO' ) . "\n";

        // Make the request through the controller
        echo "Making POST request to categories.store...\n";
        $response = $this->actingAs( $this->tenantUser )
            ->post( route( 'categories.store' ), [
                'name'      => 'Serviços Diferentes',
                'slug'      => 'servicos-gerais', // Explicit duplicate slug
                'is_active' => true,
            ] );

        echo "Response status: " . $response->getStatusCode() . "\n";
        echo "Response location: " . ( $response->headers->get( 'Location' ) ?: 'No location header' ) . "\n";

        // Check what's in the session
        $session = $response->getSession();
        echo "Session all data: " . json_encode( $session->all(), JSON_PRETTY_PRINT ) . "\n";

        // Check for errors in different ways
        $sessionErrors = $session->get( 'errors' );
        echo "Session errors (get): " . ( $sessionErrors ? get_class( $sessionErrors ) : 'null' ) . "\n";

        $oldSessionErrors = session( 'errors' );
        echo "Session errors (session()): " . ( $oldSessionErrors ? get_class( $oldSessionErrors ) : 'null' ) . "\n";

        $flashErrors = session()->get( '_old_input' );
        echo "Flash old input: " . json_encode( $flashErrors, JSON_PRETTY_PRINT ) . "\n";

        // Check if there's a general error message
        $generalError = $session->get( 'error' );
        echo "General error message: " . ( $generalError ?: 'null' ) . "\n";

        // Now let's see if we can get more details about the request
        echo "\n=== TESTING SERVICE DIRECTLY ===\n";

        // Test the CategoryService directly to see if it returns the right ServiceResult
        $categoryService = app( \App\Services\Domain\CategoryService::class);

        echo "Testing CategoryService with duplicate slug...\n";
        $serviceResult = $categoryService->createCategory( [
            'name'      => 'Serviços Diferentes',
            'slug'      => 'servicos-gerais', // Duplicate slug
            'is_active' => true,
        ], $this->tenant->id );

        echo "ServiceResult isError: " . ( $serviceResult->isError() ? 'YES' : 'NO' ) . "\n";
        echo "ServiceResult message: " . $serviceResult->getMessage() . "\n";

        // Also test what happens when we use the validation method directly
        echo "\n=== TESTING VALIDATION DIRECTLY ===\n";

        $isUnique = Category::validateUniqueSlug( 'servicos-gerais', $this->tenant->id );
        echo "validateUniqueSlug('servicos-gerais', {$this->tenant->id}): " . ( $isUnique ? 'UNIQUE' : 'DUPLICATE' ) . "\n";

        $count = Category::where( 'tenant_id', $this->tenant->id )->where( 'slug', 'servicos-gerais' )->count();
        echo "Existing categories with slug 'servicos-gerais': {$count}\n";

        // Verify the first category exists
        echo "First category exists: " . ( $firstCategory->exists ? 'YES' : 'NO' ) . "\n";
        echo "First category tenant_id: " . $firstCategory->tenant_id . "\n";
    }

}
