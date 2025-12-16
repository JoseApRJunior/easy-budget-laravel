<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CategoryPermissionTest extends TestCase
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

    public function test_user_has_required_permission()
    {
        echo "\n=== CHECKING USER PERMISSIONS ===\n";

        $user = $this->tenantUser;
        echo "User can('manage-custom-categories'): " . ( $user->can( 'manage-custom-categories' ) ? 'YES' : 'NO' ) . "\n";

        $userRoles = $user->roles()->with( 'permissions' )->get();
        echo "User roles and permissions:\n";
        foreach ( $userRoles as $role ) {
            echo "  Role: {$role->name}\n";
            $permissions = $role->permissions;
            foreach ( $permissions as $permission ) {
                echo "    Permission: {$permission->name}\n";
            }
        }

        // Now test the controller with proper permissions
        echo "\n=== TESTING CONTROLLER WITH PROPER PERMISSIONS ===\n";

        // Create first category
        $firstCategory = Category::create( [
            'name'      => 'Serviços Gerais',
            'slug'      => 'servicos-gerais',
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
        ] );

        echo "Created first category: " . $firstCategory->slug . "\n";

        // Make the request through the controller
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
    }

}
