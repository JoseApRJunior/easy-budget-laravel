<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProfessionSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $data = [
            ['slug' => 'administrador',          'name' => 'Administrador',          'is_active' => true, 'created_at' => $now],
            ['slug' => 'analista-financeiro',    'name' => 'Analista Financeiro',    'is_active' => true, 'created_at' => $now],
            ['slug' => 'assistente-administrativo', 'name' => 'Assistente Administrativo', 'is_active' => true, 'created_at' => $now],
            ['slug' => 'atendente',              'name' => 'Atendente',              'is_active' => true, 'created_at' => $now],
            ['slug' => 'carpinteiro',            'name' => 'Carpinteiro',            'is_active' => true, 'created_at' => $now],
            ['slug' => 'consultor',              'name' => 'Consultor',              'is_active' => true, 'created_at' => $now],
            ['slug' => 'contador',               'name' => 'Contador',               'is_active' => true, 'created_at' => $now],
            ['slug' => 'designer',               'name' => 'Designer',               'is_active' => true, 'created_at' => $now],
            ['slug' => 'eletricista',            'name' => 'Eletricista',            'is_active' => true, 'created_at' => $now],
            ['slug' => 'encanador',              'name' => 'Encanador',              'is_active' => true, 'created_at' => $now],
            ['slug' => 'engenheiro',             'name' => 'Engenheiro',             'is_active' => true, 'created_at' => $now],
            ['slug' => 'estagiario',             'name' => 'Estagiário',             'is_active' => true, 'created_at' => $now],
            ['slug' => 'faxineiro',              'name' => 'Faxineiro',              'is_active' => true, 'created_at' => $now],
            ['slug' => 'ferramenteiro',          'name' => 'Ferramenteiro',          'is_active' => true, 'created_at' => $now],
            ['slug' => 'gerente',                'name' => 'Gerente',                'is_active' => true, 'created_at' => $now],
            ['slug' => 'jardineiro',             'name' => 'Jardineiro',             'is_active' => true, 'created_at' => $now],
            ['slug' => 'mecanico',               'name' => 'Mecânico',               'is_active' => true, 'created_at' => $now],
            ['slug' => 'motorista',              'name' => 'Motorista',              'is_active' => true, 'created_at' => $now],
            ['slug' => 'pedreiro',               'name' => 'Pedreiro',               'is_active' => true, 'created_at' => $now],
            ['slug' => 'pintor',                 'name' => 'Pintor',                 'is_active' => true, 'created_at' => $now],
            ['slug' => 'recepcionista',          'name' => 'Recepcionista',          'is_active' => true, 'created_at' => $now],
            ['slug' => 'soldador',               'name' => 'Soldador',               'is_active' => true, 'created_at' => $now],
            ['slug' => 'supervisor',             'name' => 'Supervisor',             'is_active' => true, 'created_at' => $now],
            ['slug' => 'tecnico-informatica',    'name' => 'Técnico de Informática', 'is_active' => true, 'created_at' => $now],
            ['slug' => 'tecnico-seguranca',      'name' => 'Técnico de Segurança',   'is_active' => true, 'created_at' => $now],
            ['slug' => 'tecnico-eletrica',       'name' => 'Técnico em Elétrica',    'is_active' => true, 'created_at' => $now],
            ['slug' => 'tecnico-hidraulica',     'name' => 'Técnico em Hidráulica',  'is_active' => true, 'created_at' => $now],
            ['slug' => 'vendedor',               'name' => 'Vendedor',               'is_active' => true, 'created_at' => $now],
            ['slug' => 'zelador',                'name' => 'Zelador',                'is_active' => true, 'created_at' => $now],
            ['slug' => 'arquiteto',              'name' => 'Arquiteto',              'is_active' => true, 'created_at' => $now],
            ['slug' => 'marceneiro',             'name' => 'Marceneiro',             'is_active' => true, 'created_at' => $now],
            ['slug' => 'montador',               'name' => 'Montador',               'is_active' => true, 'created_at' => $now],
            ['slug' => 'refrigeracao',           'name' => 'Técnico de Refrigeração', 'is_active' => true, 'created_at' => $now],
            ['slug' => 'limpeza',                'name' => 'Profissional de Limpeza', 'is_active' => true, 'created_at' => $now],
        ];

        DB::table('professions')->upsert($data, ['slug'], ['name', 'is_active']);
    }
}
