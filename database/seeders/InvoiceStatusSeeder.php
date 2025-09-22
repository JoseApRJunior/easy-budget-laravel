<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InvoiceStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $statuses = [ 
            [ 
                'slug'        => 'pending',
                'name'        => 'Pendente',
                'color'       => '#FFA500',
                'icon'        => 'clock',
                'order_index' => 1,
                'is_active'   => true,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [ 
                'slug'        => 'paid',
                'name'        => 'Pago',
                'color'       => '#28A745',
                'icon'        => 'check',
                'order_index' => 2,
                'is_active'   => true,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [ 
                'slug'        => 'cancelled',
                'name'        => 'Cancelado',
                'color'       => '#DC3545',
                'icon'        => 'times',
                'order_index' => 3,
                'is_active'   => true,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
            [ 
                'slug'        => 'overdue',
                'name'        => 'Vencido',
                'color'       => '#FFC107',
                'icon'        => 'exclamation-triangle',
                'order_index' => 4,
                'is_active'   => true,
                'created_at'  => now(),
                'updated_at'  => now(),
            ],
        ];

        foreach ( $statuses as $status ) {
            if ( !DB::table( 'invoice_statuses' )->where( 'slug', $status[ 'slug' ] )->exists() ) {
                DB::table( 'invoice_statuses' )->insert( $status );
            }
        }
    }

}
