<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $units = [ 
            [ 
                'name'      => 'Unidade',
                'symbol'    => 'un',
                'type'      => 'unidade',
                'is_active' => true,
            ],
            [ 
                'name'      => 'Quilograma',
                'symbol'    => 'kg',
                'type'      => 'peso',
                'is_active' => true,
            ],
            [ 
                'name'      => 'Grama',
                'symbol'    => 'g',
                'type'      => 'peso',
                'is_active' => true,
            ],
            [ 
                'name'      => 'Litro',
                'symbol'    => 'L',
                'type'      => 'volume',
                'is_active' => true,
            ],
            [ 
                'name'      => 'Mililitro',
                'symbol'    => 'ml',
                'type'      => 'volume',
                'is_active' => true,
            ],
            [ 
                'name'      => 'Metro',
                'symbol'    => 'm',
                'type'      => 'comprimento',
                'is_active' => true,
            ],
            [ 
                'name'      => 'Centímetro',
                'symbol'    => 'cm',
                'type'      => 'comprimento',
                'is_active' => true,
            ],
            [ 
                'name'      => 'Hora',
                'symbol'    => 'h',
                'type'      => 'tempo',
                'is_active' => true,
            ],
            [ 
                'name'      => 'Minuto',
                'symbol'    => 'min',
                'type'      => 'tempo',
                'is_active' => true,
            ],
            [ 
                'name'      => 'Peça',
                'symbol'    => 'pc',
                'type'      => 'unidade',
                'is_active' => true,
            ],
        ];

        foreach ( $units as $unit ) {
            DB::table( 'units' )->insert( $unit );
        }
    }

}
