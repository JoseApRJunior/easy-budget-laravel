<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $adminRole = Role::updateOrCreate(
            [ 'name' => 'Administrador' ],
            [
                'guard_name'  => 'web'
            ],
        );

        $userRole = Role::updateOrCreate(
            [ 'name' => 'Usuário' ],
            [
                'guard_name'  => 'web'
            ],
        );

        $providerRole = Role::updateOrCreate(
            [ 'name' => 'Prestador' ],
            [
                'guard_name'  => 'web'
            ],
        );

        // Criar permissions
        $permissionNames = [
            'access-dashboard',
            'manage-users',
            'manage-budgets',
            'manage-customers',
            'manage-reports',
            'system-settings',
        ];

        foreach ( $permissionNames as $name ) {
            Permission::updateOrCreate(
                [ 'name' => $name ],
                [
                    'guard_name'  => 'web'
                ],
            );
        }

        // Associar permissions aos roles
        $adminPermissions    = [ 'access-dashboard', 'manage-users', 'manage-budgets', 'manage-customers', 'manage-reports', 'system-settings' ];
        $userPermissions     = [ 'access-dashboard', 'manage-budgets' ];
        $providerPermissions = [ 'access-dashboard', 'manage-budgets', 'manage-customers' ];

        foreach ( $adminPermissions as $name ) {
            $permission = Permission::where( 'name', $name )->first();
            if ( $permission ) {
                $adminRole->permissions()->attach( $permission->id );
            }
        }

        foreach ( $userPermissions as $name ) {
            $permission = Permission::where( 'name', $name )->first();
            if ( $permission ) {
                $userRole->permissions()->attach( $permission->id );
            }
        }

        foreach ( $providerPermissions as $name ) {
            $permission = Permission::where( 'name', $name )->first();
            if ( $permission ) {
                $providerRole->permissions()->attach( $permission->id );
            }
        }

        /**
         * Seeder para RBAC custom: cria roles e permissions globais, attach via relationships Eloquent.
         * Sem dependências Spatie. Roles: Administrador, Usuário, Prestador. Permissions básicas.
         * Integração com tenants: assignments scoped via pivot tenant_id em user_roles.
         */
    }

}
