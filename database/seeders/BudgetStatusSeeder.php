<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BudgetStatusSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $data = [
            [ 'slug' => 'draft',     'name' => 'Rascunho',  'description' => 'Orçamento em rascunho',            'color' => '#9CA3AF', 'icon' => 'mdi-file-document-edit', 'order_index' => 1, 'is_active' => true, 'created_at' => $now ],
            [ 'slug' => 'sent',      'name' => 'Enviado',    'description' => 'Orçamento enviado ao cliente',     'color' => '#3B82F6', 'icon' => 'mdi-send',                 'order_index' => 2, 'is_active' => true, 'created_at' => $now ],
            [ 'slug' => 'approved',  'name' => 'Aprovado',   'description' => 'Orçamento aprovado',               'color' => '#10B981', 'icon' => 'mdi-check-circle',        'order_index' => 3, 'is_active' => true, 'created_at' => $now ],
            [ 'slug' => 'rejected',  'name' => 'Rejeitado',  'description' => 'Orçamento rejeitado',              'color' => '#EF4444', 'icon' => 'mdi-close-circle',        'order_index' => 4, 'is_active' => true, 'created_at' => $now ],
            [ 'slug' => 'expired',   'name' => 'Expirado',   'description' => 'Validade expirada',                'color' => '#F59E0B', 'icon' => 'mdi-timer-off',           'order_index' => 5, 'is_active' => true, 'created_at' => $now ],
            [ 'slug' => 'revised',   'name' => 'Revisado',   'description' => 'Orçamento revisado',               'color' => '#8B5CF6', 'icon' => 'mdi-file-compare',        'order_index' => 6, 'is_active' => true, 'created_at' => $now ],
            [ 'slug' => 'cancelled', 'name' => 'Cancelado',  'description' => 'Orçamento cancelado',              'color' => '#6B7280', 'icon' => 'mdi-cancel',              'order_index' => 7, 'is_active' => true, 'created_at' => $now ],
        ];

        DB::table('budget_statuses')->upsert(
            $data,
            ['slug'],
            ['name','description','color','icon','order_index','is_active']
        );
    }
}
