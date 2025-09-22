<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction( function () {
            $roles = [ 
                [ 
                    'id'          => 1,
                    'name'        => 'admin',
                    'description' => 'Administrador com acesso total',
                    'created_at'  => '2025-05-29 09:44:31',
                    'updated_at'  => '2025-05-29 09:44:31',
                ],
                [ 
                    'id'          => 2,
                    'name'        => 'manager',
                    'description' => 'Gerente com acesso parcial',
                    'created_at'  => '2025-05-29 09:44:31',
                    'updated_at'  => '2025-05-29 09:44:31',
                ],
                [ 
                    'id'          => 3,
                    'name'        => 'provider',
                    'description' => 'Prestador padrão',
                    'created_at'  => '2025-05-29 09:44:31',
                    'updated_at'  => '2025-05-29 09:44:31',
                ],
                [ 
                    'id'          => 4,
                    'name'        => 'user',
                    'description' => 'Usuário padrão',
                    'created_at'  => '2025-05-29 09:44:31',
                    'updated_at'  => '2025-05-29 09:44:31',
                ],
            ];

            foreach ( $roles as $roleData ) {
                Role::updateOrCreate(
                    [ 'id' => $roleData[ 'id' ] ],
                    $roleData,
                );
            }
        } );
    }

}