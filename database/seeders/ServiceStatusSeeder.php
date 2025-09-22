<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\ServiceStatus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ServiceStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $statuses = [ 
            [ 
                'id'          => 1,
                'slug'        => 'draft',
                'name'        => 'Rascunho',
                'description' => 'Serviço em elaboração, permite modificações',
                'color'       => '#6c757d',
                'icon'        => 'bi-pencil-square',
                'order_index' => 0,
                'is_active'   => 1,
            ],
            [ 
                'id'          => 2,
                'slug'        => 'pending',
                'name'        => 'Pendente',
                'description' => 'Serviço registrado aguardando aprovação',
                'color'       => '#ffc107',
                'icon'        => 'bi-clock',
                'order_index' => 1,
                'is_active'   => 1,
            ],
            [ 
                'id'          => 3,
                'slug'        => 'scheduling',
                'name'        => 'Agendamento',
                'description' => 'Data e hora a serem definidas para execução do serviço',
                'color'       => '#007bff',
                'icon'        => 'bi-calendar-check',
                'order_index' => 2,
                'is_active'   => 1,
            ],
            [ 
                'id'          => 4,
                'slug'        => 'preparing',
                'name'        => 'Em Preparação',
                'description' => 'Equipe está preparando recursos e materiais',
                'color'       => '#ffc107',
                'icon'        => 'bi-tools',
                'order_index' => 3,
                'is_active'   => 1,
            ],
            [ 
                'id'          => 5,
                'slug'        => 'in_progress',
                'name'        => 'Em Andamento',
                'description' => 'Serviço está sendo executado no momento',
                'color'       => '#007bff',
                'icon'        => 'bi-gear',
                'order_index' => 4,
                'is_active'   => 1,
            ],
            [ 
                'id'          => 6,
                'slug'        => 'on_hold',
                'name'        => 'Em Espera',
                'description' => 'Serviço temporariamente pausado',
                'color'       => '#6c757d',
                'icon'        => 'bi-pause-circle',
                'order_index' => 5,
                'is_active'   => 1,
            ],
            [ 
                'id'          => 7,
                'slug'        => 'scheduled',
                'name'        => 'Agendado',
                'description' => 'Serviço com data marcada',
                'color'       => '#007bff',
                'icon'        => 'bi-calendar-plus',
                'order_index' => 6,
                'is_active'   => 1,
            ],
            [ 
                'id'          => 8,
                'slug'        => 'completed',
                'name'        => 'Concluído',
                'description' => 'Serviço finalizado com sucesso',
                'color'       => '#28a745',
                'icon'        => 'bi-check-circle',
                'order_index' => 7,
                'is_active'   => 1,
            ],
            [ 
                'id'          => 9,
                'slug'        => 'partial',
                'name'        => 'Concluído Parcial',
                'description' => 'Serviço finalizado parcialmente',
                'color'       => '#28a745',
                'icon'        => 'bi-check-circle-fill',
                'order_index' => 8,
                'is_active'   => 1,
            ],
            [ 
                'id'          => 10,
                'slug'        => 'cancelled',
                'name'        => 'Cancelado',
                'description' => 'Serviço cancelado antes da execução',
                'color'       => '#dc3545',
                'icon'        => 'bi-x-circle',
                'order_index' => 9,
                'is_active'   => 1,
            ],
            [ 
                'id'          => 11,
                'slug'        => 'not_performed',
                'name'        => 'Não Realizado',
                'description' => 'Não foi possível realizar o serviço',
                'color'       => '#dc3545',
                'icon'        => 'bi-slash-circle',
                'order_index' => 10,
                'is_active'   => 1,
            ],
            [ 
                'id'          => 12,
                'slug'        => 'expired',
                'name'        => 'Expirado',
                'description' => 'Prazo de validade do orçamento expirado',
                'color'       => '#dc3545',
                'icon'        => 'bi-calendar-x',
                'order_index' => 11,
                'is_active'   => 1,
            ],
        ];

        foreach ( $statuses as $status ) {
            ServiceStatus::updateOrCreate(
                [ 'slug' => $status[ 'slug' ] ],
                $status,
            );
        }
    }

}
