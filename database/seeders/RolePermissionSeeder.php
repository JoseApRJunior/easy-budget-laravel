<?php

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $tenantId = Tenant::query()->value( 'id' );
        if ( !$tenantId ) {
            return; // precisa do tenant criado
        }

        $roles = DB::table( 'roles' )->pluck( 'id', 'name' );
        $perms = DB::table( 'permissions' )->pluck( 'id', 'name' );

        $assign = [
            'Admin'    => array_values( $perms->toArray() ), // todas permissões
            'Provider' => array_values( $perms->toArray() ), // todas permissões
            'Manager'  => array_values( $perms->filter( function ( $id, $name ) {
                return !str_ends_with( $name, '.delete' );
            } )->toArray() ),
            'Staff'    => array_values( $perms->filter( function ( $id, $name ) {
                return str_contains( $name, 'budgets.' ) || str_contains( $name, 'services.' );
            } )->filter( function ( $id, $name ) {
                return !str_ends_with( $name, '.delete' );
            } )->toArray() ),
            'Viewer'   => array_values( $perms->filter( function ( $id, $name ) {
                return str_ends_with( $name, '.view' );
            } )->toArray() ),
        ];

        foreach ( $assign as $roleName => $permIds ) {
            $roleId = $roles[ $roleName ] ?? null;
            if ( !$roleId ) continue;
            foreach ( $permIds as $permId ) {
                DB::table( 'role_permissions' )->updateOrInsert(
                    [ 'tenant_id' => $tenantId, 'role_id' => $roleId, 'permission_id' => $permId ],
                    [],
                );
            }
        }
    }

}
