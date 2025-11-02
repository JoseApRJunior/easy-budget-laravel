<?php

use app\enums\BudgetStatus;
use app\enums\ServiceStatusEnum;

return [
    // Filtro para status
    'status_badge'          =>
        function ( $status ) {
            if ( !$status ) return '';

            return sprintf(
                '<span class="badge" style="background-color: %s">
                <i class="bi %s"></i> %s %s
            </span>',
                $status[ 'color' ],
                $status[ 'icon' ],
                $status[ 'name' ],
                $status[ 'description' ] ? sprintf(
                    '<span class="d-none d-md-inline ms-1 small">(%s)</span>',
                    $status[ 'description' ],
                ) : ''
            );
        },

    // Filtro para próximos status permitidos em serviços
    'service_next_statuses' =>
        function ( $currentStatus ) {
            if ( !$currentStatus ) return [];
            return ServiceStatusEnum::getAllowedTransitions( $currentStatus[ 'slug' ] );
        },

    // Filtro para próximos status permitidos em orçamentos
    'budget_next_statuses'  =>
        function ( $currentStatus ) {
            if ( !$currentStatus ) return [];
            return BudgetStatus::getAllowedTransitions( $currentStatus[ 'slug' ] );
        },

    // Filtro genérico para verificar se um status permite edição
    'status_allows_edit'    =>
        function ( $status, string $type = 'budget' ) {
            if ( !$status ) return false;

            if ( $type === 'budget' ) {
                return BudgetStatus::isEditable( $status[ 'slug' ] );
            }

            if ( $type === 'service' ) {
                return in_array( $status[ 'slug' ], [
                    ServiceStatusEnum::PENDING,
                    ServiceStatusEnum::SCHEDULED,
                    ServiceStatusEnum::ON_HOLD
                ] );
            }

            return false;
        },

    // Filtro para verificar se é status final
    'is_final_status'       =>
        function ( $status, string $type = 'budget' ) {
            if ( !$status ) return false;

            if ( $type === 'budget' ) {
                return BudgetStatus::isFinalStatus( $status[ 'slug' ] );
            }

            if ( $type === 'service' ) {
                return ServiceStatusEnum::isFinalStatus( $status[ 'slug' ] );
            }

            return false;
        },

    // Filtro para opções de status do orçamento
    'budget_status_options' => function ( $selectedStatus = '' ) {
        $statuses = [
            ''            => 'Todos',
            'DRAFT'       => 'Rascunho',
            'PENDING'     => 'Pendente',
            'APPROVED'    => 'Aprovado',
            'IN_PROGRESS' => 'Em Progresso',
            'COMPLETED'   => 'Concluído',
            'REJECTED'    => 'Rejeitado',
            'CANCELLED'   => 'Cancelado',
            'EXPIRED'     => 'Expirado'
        ];

        $options = [];
        foreach ( $statuses as $value => $label ) {
            $selected  = $selectedStatus === $value ? 'selected' : '';
            $options[] = sprintf(
                '<option value="%s" %s>%s</option>',
                $value,
                $selected,
                $label,
            );
        }

        return implode( "\n", $options );
    },

    // Filtro para verificar se status está ativo
    'is_active_status'      => function ( $status, string $type = 'budget' ) {
        if ( !$status ) return false;

        if ( $type === 'budget' ) {
            return BudgetStatus::isActive( $status[ 'slug' ] );
        }

        return false;
    }

];
