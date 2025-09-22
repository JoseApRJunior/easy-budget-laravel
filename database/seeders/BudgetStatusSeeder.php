<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\BudgetStatus;
use Illuminate\Database\Seeder;

class BudgetStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $statuses = [ 
            [ 
                'name'        => 'Pending',
                'slug'        => 'pending',
                'color'       => '#ffc107',
                'icon'        => 'fa-clock',
                'order_index' => 1,
                'is_active'   => true,
            ],
            [ 
                'name'        => 'Approved',
                'slug'        => 'approved',
                'color'       => '#28a745',
                'icon'        => 'fa-check',
                'order_index' => 2,
                'is_active'   => true,
            ],
            [ 
                'name'        => 'Rejected',
                'slug'        => 'rejected',
                'color'       => '#dc3545',
                'icon'        => 'fa-times',
                'order_index' => 3,
                'is_active'   => true,
            ],
        ];

        foreach ( $statuses as $status ) {
            BudgetStatus::updateOrCreate(
                [ 'slug' => $status[ 'slug' ] ],
                $status,
            );
        }
    }

}
