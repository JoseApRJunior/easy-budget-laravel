<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ServiceStatusSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $data = [
            [ 'slug' => 'scheduled',           'name' => 'Agendado',            'description' => 'Serviço agendado',                 'color' => '#3B82F6', 'icon' => 'mdi-calendar-clock', 'order_index' => 1, 'is_active' => true, 'created_at' => $now ],
            [ 'slug' => 'preparing',           'name' => 'Em Preparação',       'description' => 'Em preparação',                    'color' => '#06B6D4', 'icon' => 'mdi-hammer-wrench',  'order_index' => 2, 'is_active' => true, 'created_at' => $now ],
            [ 'slug' => 'on-hold',             'name' => 'Em Espera',           'description' => 'Aguardando liberação/recursos',    'color' => '#F59E0B', 'icon' => 'mdi-pause-circle',   'order_index' => 3, 'is_active' => true, 'created_at' => $now ],
            [ 'slug' => 'in-progress',         'name' => 'Em Andamento',        'description' => 'Serviço em execução',              'color' => '#6366F1', 'icon' => 'mdi-progress-clock','order_index' => 4, 'is_active' => true, 'created_at' => $now ],
            [ 'slug' => 'partially-completed', 'name' => 'Concluído Parcial',   'description' => 'Entrega parcial concluída',        'color' => '#8B5CF6', 'icon' => 'mdi-progress-check','order_index' => 5, 'is_active' => true, 'created_at' => $now ],
            [ 'slug' => 'completed',           'name' => 'Concluído',           'description' => 'Serviço concluído',                'color' => '#10B981', 'icon' => 'mdi-check-circle',   'order_index' => 6, 'is_active' => true, 'created_at' => $now ],
            [ 'slug' => 'cancelled',           'name' => 'Cancelado',           'description' => 'Serviço cancelado',                'color' => '#6B7280', 'icon' => 'mdi-cancel',         'order_index' => 7, 'is_active' => true, 'created_at' => $now ],
        ];

        DB::table('service_statuses')->upsert(
            $data,
            ['slug'],
            ['name','description','color','icon','order_index','is_active']
        );
    }
}
