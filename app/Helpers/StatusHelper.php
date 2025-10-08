<?php

namespace App\Helpers;

use App\Enums\BudgetStatusEnum;
use App\Enums\ServiceStatusEnum;

class StatusHelper
{
    public static function status_badge( $status )
    {
        if ( !is_array( $status ) || empty( $status ) ) {
            return '';
        }

        $status_color       = $status[ 'status_color' ] ?? '#6c757d';
        $status_icon        = $status[ 'status_icon' ] ?? 'bi-question-circle';
        $status_name        = $status[ 'status_name' ] ?? 'N/A';
        $status_description = $status[ 'status_description' ] ?? '';

        $description_html = $status_description ?
            sprintf( '<span class="d-none d-md-inline ms-1 small">(%s)</span>', e( $status_description ) ) :
            '';

        return sprintf(
            '<span class="badge" style="background-color: %s">
                <i class="bi %s"></i> %s %s
            </span>',
            e( $status_color ),
            e( $status_icon ),
            e( $status_name ),
            $description_html,
        );
    }

    public static function service_next_statuses( $currentStatus )
    {
        if ( !is_array( $currentStatus ) || empty( $currentStatus[ 'slug' ] ) ) {
            return [];
        }

        return ServiceStatusEnum::getAllowedTransitions( $currentStatus[ 'slug' ] );
    }

    public static function service_status_options( $selectedStatus = '' )
    {
        $statuses = [
            ''              => 'Todos',
            'PENDING'       => 'Pendente',
            'SCHEDULING'    => 'Agendamento',
            'PREPARING'     => 'Em Preparação',
            'IN_PROGRESS'   => 'Em Progresso',
            'ON_HOLD'       => 'Em Espera',
            'SCHEDULED'     => 'Agendado',
            'COMPLETED'     => 'Concluído',
            'PARTIAL'       => 'Concluído Parcial',
            'CANCELLED'     => 'Cancelado',
            'NOT_PERFORMED' => 'Não Realizado',
            'EXPIRED'       => 'Expirado',
        ];

        $options = [];
        foreach ( $statuses as $value => $label ) {
            $selected  = $selectedStatus === $value ? 'selected' : '';
            $options[] = sprintf(
                '<option value="%s" %s>%s</option>',
                e( $value ),
                $selected,
                e( $label ),
            );
        }

        return implode( "\n", $options );
    }

    public static function status_progress( $status )
    {
        if ( is_string( $status ) ) {
            $status = constant( BudgetStatusEnum::class . '::' . $status );
        }

        return $status[ 'progress' ] ?? 0;
    }

    public static function status_color_class( $status )
    {
        if ( is_string( $status ) ) {
            $status = constant( BudgetStatusEnum::class . '::' . $status );
        }

        return $status[ 'color_class' ] ?? 'secondary';
    }

    public static function budget_next_statuses( $currentStatus )
    {
        if ( !is_array( $currentStatus ) || empty( $currentStatus[ 'slug' ] ) ) {
            return [];
        }

        return BudgetStatusEnum::getAllowedTransitions( $currentStatus[ 'slug' ] );
    }

    public static function status_allows_edit( $status ): bool
    {
        if ( is_string( $status ) ) {
            $status = constant( BudgetStatusEnum::class . '::' . $status );
        }

        return $status[ 'allow_edit' ] ?? false;
    }

    public static function is_final_status( $status ): bool
    {
        if ( is_string( $status ) ) {
            $status = constant( BudgetStatusEnum::class . '::' . $status );
        }

        return in_array( $status[ 'slug' ], BudgetStatusEnum::getFinalStatuses() );
    }

    public static function budget_status_options( $selectedStatus = '' )
    {
        $statuses = [
            ''                 => 'Todos',
            'DRAFT'            => 'Rascunho',
            'PENDING_APPROVAL' => 'Pendente Aprovação',
            'APPROVED'         => 'Aprovado',
            'REJECTED'         => 'Rejeitado',
            'IN_PROGRESS'      => 'Em Andamento',
            'COMPLETED'        => 'Concluído',
            'CANCELLED'        => 'Cancelado',
            'ON_HOLD'          => 'Em Espera',
        ];

        $options = [];
        foreach ( $statuses as $value => $label ) {
            $selected  = $selectedStatus === $value ? 'selected' : '';
            $options[] = sprintf(
                '<option value="%s" %s>%s</option>',
                e( $value ),
                $selected,
                e( $label ),
            );
        }

        return implode( "\n", $options );
    }

    public static function is_active_status( $status ): bool
    {
        if ( is_string( $status ) ) {
            $status = constant( BudgetStatusEnum::class . '::' . $status );
        }

        return !in_array( $status[ 'slug' ], BudgetStatusEnum::getInactiveStatuses() );
    }

    public static function invoice_status_options( $selectedStatus = '' )
    {
        $statuses = [
            ''          => 'Todos',
            'pending'   => 'Pendente',
            'paid'      => 'Paga',
            'cancelled' => 'Cancelada',
            'overdue'   => 'Vencida',
        ];

        $options = [];
        foreach ( $statuses as $value => $label ) {
            $selected  = $selectedStatus === $value ? 'selected' : '';
            $options[] = sprintf(
                '<option value="%s" %s>%s</option>',
                e( $value ),
                $selected,
                e( $label ),
            );
        }

        return implode( "\n", $options );
    }

    public static function budget_progress_from_services( array $services ): int
    {
        if ( empty( $services ) ) {
            return 0;
        }

        $total_services     = count( $services );
        $completed_services = 0;

        foreach ( $services as $service ) {
            if ( $service[ 'status' ][ 'slug' ] === 'COMPLETED' ) {
                $completed_services++;
            }
        }

        return ( $completed_services / $total_services ) * 100;
    }

}
