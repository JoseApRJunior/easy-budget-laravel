<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction( function () {
            $permissions = [ 
                [ 
                    'id'          => 1,
                    'name'        => 'create_user',
                    'description' => 'Criar novos usuários',
                    'created_at'  => '2025-05-29 09:44:31',
                    'updated_at'  => '2025-05-29 09:44:31',
                ],
                [ 
                    'id'          => 2,
                    'name'        => 'edit_user',
                    'description' => 'Editar usuários existentes',
                    'created_at'  => '2025-05-29 09:44:31',
                    'updated_at'  => '2025-05-29 09:44:31',
                ],
                [ 
                    'id'          => 3,
                    'name'        => 'delete_user',
                    'description' => 'Excluir usuários',
                    'created_at'  => '2025-05-29 09:44:31',
                    'updated_at'  => '2025-05-29 09:44:31',
                ],
                [ 
                    'id'          => 4,
                    'name'        => 'view_reports',
                    'description' => 'Visualizar relatórios',
                    'created_at'  => '2025-05-29 09:44:31',
                    'updated_at'  => '2025-05-29 09:44:31',
                ],
                [ 
                    'id'          => 5,
                    'name'        => 'manage_budget',
                    'description' => 'Gerenciar orçamentos',
                    'created_at'  => '2025-05-29 09:44:31',
                    'updated_at'  => '2025-05-29 09:44:31',
                ],
            ];

            foreach ( $permissions as $permissionData ) {
                Permission::updateOrCreate(
                    [ 'id' => $permissionData[ 'id' ] ],
                    $permissionData,
                );
            }
        } );
    }

}