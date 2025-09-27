<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $categories = [
            ['slug' => 'hidraulica',        'name' => 'Hidráulica',        'created_at' => $now],
            ['slug' => 'eletrica',          'name' => 'Elétrica',          'created_at' => $now],
            ['slug' => 'pintura',           'name' => 'Pintura',           'created_at' => $now],
            ['slug' => 'alvenaria',         'name' => 'Alvenaria',         'created_at' => $now],
            ['slug' => 'revestimentos',     'name' => 'Revestimentos',     'created_at' => $now],
            ['slug' => 'forro',             'name' => 'Forro',             'created_at' => $now],
            ['slug' => 'drywall',           'name' => 'Drywall',           'created_at' => $now],
            ['slug' => 'marcenaria',        'name' => 'Marcenaria',        'created_at' => $now],
            ['slug' => 'serralheria',       'name' => 'Serralheria',       'created_at' => $now],
            ['slug' => 'impermeabilizacao', 'name' => 'Impermeabilização', 'created_at' => $now],
            ['slug' => 'telhado',           'name' => 'Telhado',           'created_at' => $now],
            ['slug' => 'jardinagem',        'name' => 'Jardinagem',        'created_at' => $now],
            ['slug' => 'limpeza',           'name' => 'Limpeza',           'created_at' => $now],
            ['slug' => 'climatizacao',      'name' => 'Climatização',      'created_at' => $now],
            ['slug' => 'automacao',         'name' => 'Automação',         'created_at' => $now],
            ['slug' => 'vidracaria',        'name' => 'Vidraçaria',        'created_at' => $now],
            ['slug' => 'gesso',             'name' => 'Gesso',             'created_at' => $now],
            ['slug' => 'demolicao',         'name' => 'Demolição',         'created_at' => $now],
            ['slug' => 'moveis-planejados', 'name' => 'Móveis Planejados', 'created_at' => $now],
            ['slug' => 'iluminacao',        'name' => 'Iluminação',        'created_at' => $now],
            ['slug' => 'paisagismo',        'name' => 'Paisagismo',        'created_at' => $now],
            ['slug' => 'calhas',            'name' => 'Calhas',            'created_at' => $now],
            ['slug' => 'portas-janelas',    'name' => 'Portas e Janelas',  'created_at' => $now],
            ['slug' => 'piso-elevado',      'name' => 'Piso Elevado',      'created_at' => $now],
            ['slug' => 'marmoraria',        'name' => 'Marmoraria',        'created_at' => $now],
            ['slug' => 'outros',            'name' => 'Outros',            'created_at' => $now],
        ];

        DB::table('categories')->upsert($categories, ['slug'], ['name']);
    }
}
