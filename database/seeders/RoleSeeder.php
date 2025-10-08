<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $now  = now();
        $data = [
            [ 'name' => 'Admin', 'description' => 'Acesso total ao sistema', 'created_at' => $now ],
            [ 'name' => 'Manager', 'description' => 'Gerencia operações e aprovações', 'created_at' => $now ],
            [ 'name' => 'Staff', 'description' => 'Executa tarefas operacionais', 'created_at' => $now ],
            [ 'name' => 'Viewer', 'description' => 'Somente leitura', 'created_at' => $now ],
            [ 'name' => 'Provider', 'description' => 'Provedor de serviços - acesso completo', 'created_at' => $now ],
        ];

        DB::table( 'roles' )->upsert( $data, [ 'name' ], [ 'description' ] );
    }

}
