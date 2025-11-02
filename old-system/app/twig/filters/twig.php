<?php

use app\enums\BudgetStatus;
use app\enums\ServiceStatusEnum;

return [
    // Filtro para status
    'status_badge'           =>
        function ( $status ) {
            if ( !$status ) {
                return '';
            }

            return sprintf(
                '<span class="badge" style="background-color: %s">
                <i class="bi %s"></i> %s %s
            </span>',
                $status[ 'status_color' ],
                $status[ 'status_icon' ],
                $status[ 'status_name' ],
                $status[ 'status_description' ] ? sprintf(
                    '<span class="d-none d-md-inline ms-1 small">(%s)</span>',
                    $status[ 'status_description' ],
                ) : ''
            );
        },

    // Filtro para próximos status permitidos em serviços
    'service_next_statuses'  =>
        function ( $currentStatus ) {
            if ( !$currentStatus ) {
                return [];
            }

            return ServiceStatusEnum::getAllowedTransitions( $currentStatus[ 'slug' ] );
        },
    // Filtro para opções de status do orçamento
    'service_status_options' => function ( $selectedStatus = '' ) {
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
                $value,
                $selected,
                $label,
            );
        }

        return implode( "\n", $options );
    },

    // Filtro para status de serviços
    'status_progress'        => function ( $status ) {
        $progressMap = [
            'SCHEDULED'     => 10,
            'PREPARING'     => 25,
            'IN_PROGRESS'   => 50,
            'ON_HOLD'       => 50,
            'PARTIAL'       => 75,
            'COMPLETED'     => 100,
            'CANCELLED'     => 100,
            'NOT_PERFORMED' => 100,
        ];

        return $progressMap[ $status ] ?? 0;
    },

    'status_color_class'     => function ( $status ) {
        $colorMap = [
            'SCHEDULED'     => 'info',
            'PREPARING'     => 'primary',
            'IN_PROGRESS'   => 'primary',
            'ON_HOLD'       => 'warning',
            'PARTIAL'       => 'info',
            'COMPLETED'     => 'success',
            'CANCELLED'     => 'danger',
            'NOT_PERFORMED' => 'secondary',
        ];

        return $colorMap[ $status ] ?? 'secondary';
    },
    // Filtro para próximos status permitidos em orçamentos
    'budget_next_statuses'   =>
        function ( $currentStatus ) {
            if ( !$currentStatus ) {
                return [];
            }

            return BudgetStatus::getAllowedTransitions( $currentStatus[ 'slug' ] );
        },

    // Filtro genérico para verificar se um status permite edição
    'status_allows_edit'     =>
        function ( $status, string $type = 'budget' ) {
            if ( !$status ) {
                return false;
            }

            if ( $type === 'budget' ) {
                return BudgetStatus::isEditable( $status[ 'slug' ] );
            }

            if ( $type === 'service' ) {
                return in_array( $status[ 'slug' ], [
                    ServiceStatusEnum::PENDING,
                    ServiceStatusEnum::SCHEDULED,
                    ServiceStatusEnum::ON_HOLD,
                ] );
            }

            return false;
        },

    // Filtro para verificar se é status final
    'is_final_status'        =>
        function ( $status, string $type = 'budget' ) {
            if ( !$status ) {
                return false;
            }

            if ( $type === 'budget' ) {
                return BudgetStatus::isFinalStatus( $status[ 'slug' ] );
            }

            if ( $type === 'service' ) {
                return ServiceStatusEnum::isFinalStatus( $status[ 'slug' ] );
            }

            return false;
        },

    // Filtro para opções de status do orçamento
    'budget_status_options'  => function ( $selectedStatus = '' ) {
        $statuses = [
            ''          => 'Todos',
            'DRAFT'     => 'Rascunho',
            'PENDING'   => 'Pendente',
            'APPROVED'  => 'Aprovado',
            'COMPLETED' => 'Concluído',
            'REJECTED'  => 'Rejeitado',
            'CANCELLED' => 'Cancelado',
            'EXPIRED'   => 'Expirado',
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
    'is_active_status'       => function ( $status, string $type = 'budget' ) {
        if ( !$status ) {
            return false;
        }

        if ( $type === 'budget' ) {
            return BudgetStatus::isActive( $status[ 'slug' ] );
        }

        return false;
    },

    // Filtro para opções de status do orçamento
    'invoice_status_options' => function ( $selectedStatus = '' ) {
        $statuses = [
            ''          => 'Todos',
            'PENDING'   => 'Pendente',
            'PAID'      => 'Paga',
            'CANCELLED' => 'Cancelada',
            'OVERDUE'   => 'Vencida',
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

];
