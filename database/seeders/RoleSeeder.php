<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $data = [
            ['name' => 'admin', 'description' => 'Acesso total ao sistema', 'created_at' => $now],
            ['name' => 'manager', 'description' => 'Gerencia operações e aprovações', 'created_at' => $now],
            ['name' => 'staff', 'description' => 'Executa tarefas operacionais', 'created_at' => $now],
            ['name' => 'viewer', 'description' => 'Somente leitura', 'created_at' => $now],
            ['name' => 'provider', 'description' => 'Provedor de serviços - acesso completo', 'created_at' => $now],
        ];

        DB::table('roles')->upsert($data, ['name'], ['description']);
    }
}
