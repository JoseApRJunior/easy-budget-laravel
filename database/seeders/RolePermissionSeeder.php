<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\RolePermission;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        $roles = Role::all();
        $perms = Permission::all();

        $assign = [
            'admin' => $perms, // todas permissões
            'provider' => $perms, // todas permissões
            'manager' => $perms->filter(function ($permission) {
                return ! str_ends_with($permission->name, '.delete');
            }),
            'staff' => $perms->filter(function ($permission) {
                return str_contains($permission->name, 'budgets.') || str_contains($permission->name, 'services.');
            })->filter(function ($permission) {
                return ! str_ends_with($permission->name, '.delete');
            }),
            'viewer' => $perms->filter(function ($permission) {
                return str_ends_with($permission->name, '.view');
            }),
        ];

        foreach ($assign as $roleName => $permissions) {
            $role = $roles->where('name', $roleName)->first();
            if (! $role) {
                continue;
            }

            foreach ($permissions as $permission) {
                RolePermission::firstOrCreate(
                    [
                        'role_id' => $role->id,
                        'permission_id' => $permission->id,
                    ],
                    [
                        'role_id' => $role->id,
                        'permission_id' => $permission->id,
                    ],
                );
            }
        }
    }
}
