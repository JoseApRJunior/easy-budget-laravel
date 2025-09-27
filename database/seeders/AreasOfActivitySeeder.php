<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AreasOfActivitySeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $areas = [
            ['slug' => 'construcao-civil',     'name' => 'Construção Civil',      'is_active' => true, 'created_at' => $now],
            ['slug' => 'hidraulica-saneamento','name' => 'Hidráulica e Saneamento','is_active' => true, 'created_at' => $now],
            ['slug' => 'eletrica',             'name' => 'Elétrica',               'is_active' => true, 'created_at' => $now],
            ['slug' => 'pintura',              'name' => 'Pintura',                'is_active' => true, 'created_at' => $now],
            ['slug' => 'marcenaria',           'name' => 'Marcenaria',             'is_active' => true, 'created_at' => $now],
            ['slug' => 'jardinagem',           'name' => 'Jardinagem',             'is_active' => true, 'created_at' => $now],
            ['slug' => 'limpeza',              'name' => 'Limpeza',                'is_active' => true, 'created_at' => $now],
            ['slug' => 'refrigeracao',         'name' => 'Refrigeração',           'is_active' => true, 'created_at' => $now],
            ['slug' => 'alvenaria',            'name' => 'Alvenaria',              'is_active' => true, 'created_at' => $now],
            ['slug' => 'acabamentos',          'name' => 'Acabamentos',            'is_active' => true, 'created_at' => $now],
            ['slug' => 'manutencao',           'name' => 'Manutenção',             'is_active' => true, 'created_at' => $now],
            ['slug' => 'consultoria',          'name' => 'Consultoria',            'is_active' => true, 'created_at' => $now],
            ['slug' => 'projetos',             'name' => 'Projetos',               'is_active' => true, 'created_at' => $now],
            ['slug' => 'automacao',            'name' => 'Automação',              'is_active' => true, 'created_at' => $now],
            ['slug' => 'seguranca',            'name' => 'Segurança',              'is_active' => true, 'created_at' => $now],
            ['slug' => 'outros',               'name' => 'Outros',                 'is_active' => true, 'created_at' => $now],
        ];

        DB::table('areas_of_activity')->upsert($areas, ['slug'], ['name','is_active']);
    }
}
