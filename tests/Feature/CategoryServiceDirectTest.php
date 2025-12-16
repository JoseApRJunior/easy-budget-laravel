<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Domain\CategoryService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CategoryServiceDirectTest extends TestCase
{
    use \Illuminate\Foundation\Testing\RefreshDatabase;

    protected $tenant;
    protected $tenantUser;
    protected $categoryService;

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

        // Get the service
        $this->categoryService = app( CategoryService::class);
    }

    public function test_direct_service_slug_validation()
    {
        echo "\n=== TESTING CATEGORY SERVICE DIRECTLY ===\n";

        // Create first category
        $firstCategory = Category::create( [
            'name'      => 'Serviços Gerais',
            'slug'      => 'servicos-gerais',
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
        ] );

        echo "Created first category: " . $firstCategory->slug . "\n";

        // Act as the user to get proper tenant context
        auth()->login( $this->tenantUser );

        // Try to create category with duplicate slug using service directly
        echo "\n=== CALLING SERVICE METHOD DIRECTLY ===\n";
        $result = $this->categoryService->createCategory( [
            'name'      => 'Serviços Diferentes',
            'slug'      => 'servicos-gerais', // Explicit duplicate slug
            'is_active' => true,
        ] );

        echo "Service result isError(): " . ( $result->isError() ? 'YES' : 'NO' ) . "\n";
        echo "Service result message: " . $result->getMessage() . "\n";

        if ( $result->isError() ) {
            echo "Service error details: ";
            $error = $result->getError();
            if ( $error ) {
                echo get_class( $error ) . ": " . $error->getMessage() . "\n";
            } else {
                echo "No error object\n";
            }
        }

        // Now test what the controller should do with this result
        echo "\n=== SIMULATING CONTROLLER ERROR HANDLING ===\n";
        if ( $result->isError() ) {
            $message = $result->getMessage();
            echo "Controller would get message: '{$message}'\n";

            $containsSlugError = strpos( $message, 'Slug já existe neste tenant' ) !== false;
            echo "String contains 'Slug já existe neste tenant': " . ( $containsSlugError ? 'YES' : 'NO' ) . "\n";

            if ( $containsSlugError ) {
                echo "Controller would return validation error for slug field\n";
            } else {
                echo "Controller would return generic error\n";
            }
        }

        return $result;
    }

}
