<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UnitSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $data = [
            ['slug' => 'un',  'name' => 'Unidade',     'is_active' => true, 'created_at' => $now],
            ['slug' => 'kg',  'name' => 'Quilograma',  'is_active' => true, 'created_at' => $now],
            ['slug' => 'g',   'name' => 'Grama',       'is_active' => true, 'created_at' => $now],
            ['slug' => 'mg',  'name' => 'Miligrama',   'is_active' => true, 'created_at' => $now],
            ['slug' => 'l',   'name' => 'Litro',       'is_active' => true, 'created_at' => $now],
            ['slug' => 'ml',  'name' => 'Mililitro',   'is_active' => true, 'created_at' => $now],
            ['slug' => 'm',   'name' => 'Metro',       'is_active' => true, 'created_at' => $now],
            ['slug' => 'cm',  'name' => 'Centímetro',  'is_active' => true, 'created_at' => $now],
            ['slug' => 'mm',  'name' => 'Milímetro',   'is_active' => true, 'created_at' => $now],
            ['slug' => 'm2',  'name' => 'Metro Quadrado', 'is_active' => true, 'created_at' => $now],
            ['slug' => 'm3',  'name' => 'Metro Cúbico',   'is_active' => true, 'created_at' => $now],
            ['slug' => 'h',   'name' => 'Hora',        'is_active' => true, 'created_at' => $now],
            ['slug' => 'dia', 'name' => 'Dia',         'is_active' => true, 'created_at' => $now],
        ];

        DB::table('units')->upsert($data, ['slug'], ['name','is_active']);
    }
}
