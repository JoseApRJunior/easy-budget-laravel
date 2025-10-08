<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InvoiceStatusSeeder extends Seeder
{
    public function run(): void
    {
        $now = now();
        $data = [
            [ 'slug' => 'pending',  'name' => 'Pendente', 'description' => 'Aguardando pagamento', 'color' => '#F59E0B', 'icon' => 'mdi-timer-sand', 'order_index' => 1, 'is_active' => true, 'created_at' => $now ],
            [ 'slug' => 'paid',     'name' => 'Paga',     'description' => 'Pagamento confirmado',  'color' => '#10B981', 'icon' => 'mdi-cash-check','order_index' => 2, 'is_active' => true, 'created_at' => $now ],
            [ 'slug' => 'overdue',  'name' => 'Vencida',  'description' => 'Pagamento em atraso',   'color' => '#DC2626', 'icon' => 'mdi-alert',     'order_index' => 3, 'is_active' => true, 'created_at' => $now ],
            [ 'slug' => 'cancelled','name' => 'Cancelada','description' => 'Fatura cancelada',      'color' => '#6B7280', 'icon' => 'mdi-cancel',    'order_index' => 4, 'is_active' => true, 'created_at' => $now ],
        ];

        DB::table('invoice_statuses')->upsert(
            $data,
            ['slug'],
            ['name','description','color','icon','order_index','is_active']
        );
    }
}
