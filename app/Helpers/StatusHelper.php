<?php

namespace App\Helpers;

use App\Enums\BudgetStatus;
use App\Enums\InvoiceStatus;
use App\Enums\ServiceStatus;

class StatusHelper
{
    public static function status_badge( $status )
    {
        if ( $status instanceof BudgetStatus ) {
            $status_color = $status->getColor();
            $status_icon  = $status->getIcon();
            $status_name  = $status->getName();
        } elseif ( $status instanceof ServiceStatus ) {
            $status_color = $status->getColor();
            $status_icon  = $status->getIcon();
            $status_name  = $status->getName();
        } elseif ( $status instanceof InvoiceStatus ) {
            $status_color = $status->getColor();
            $status_icon  = $status->getIcon();
            $status_name  = $status->getName();
        } elseif ( is_array( $status ) && !empty( $status ) ) {
            // Fallback para arrays legados
            $status_color       = $status[ 'status_color' ] ?? '#6c757d';
            $status_icon        = $status[ 'status_icon' ] ?? 'bi-question-circle';
            $status_name        = $status[ 'status_name' ] ?? 'N/A';
            $status_description = $status[ 'status_description' ] ?? '';
        } else {
            return '';
        }

        $description_html = '';
        if ( isset( $status_description ) && $status_description ) {
            $description_html = sprintf( '<span class="d-none d-md-inline ms-1 small">(%s)</span>', e( $status_description ) );
        }

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
        if ( $currentStatus instanceof ServiceStatus ) {
            return ServiceStatus::getAllowedTransitions( $currentStatus->value );
        } elseif ( is_array( $currentStatus ) && !empty( $currentStatus[ 'slug' ] ) ) {
            return ServiceStatus::getAllowedTransitions( $currentStatus[ 'slug' ] );
        }

        return [];
    }

    public static function service_status_options( $selectedStatus = '' )
    {
        $options = [ '<option value="">Todos</option>' ];

        foreach ( ServiceStatus::cases() as $status ) {
            $selected  = $selectedStatus === $status->value ? 'selected' : '';
            $options[] = sprintf(
                '<option value="%s" %s>%s</option>',
                e( $status->value ),
                $selected,
                e( $status->getName() ),
            );
        }

        return implode( "\n", $options );
    }

    public static function status_progress( $status )
    {
        if ( is_string( $status ) ) {
            $status = BudgetStatus::tryFrom( $status );
        }

        if ( $status instanceof BudgetStatus ) {
            return match ( $status ) {
                BudgetStatus::DRAFT     => 10,
                BudgetStatus::PENDING   => 25,
                BudgetStatus::APPROVED  => 50,
                BudgetStatus::REJECTED  => 0,
                BudgetStatus::EXPIRED   => 0,
                BudgetStatus::CANCELLED => 0,
                BudgetStatus::COMPLETED => 100,
            };
        }

        return 0;
    }

    public static function status_color_class( $status )
    {
        if ( is_string( $status ) ) {
            $status = BudgetStatus::tryFrom( $status );
        }

        if ( $status instanceof BudgetStatus ) {
            return match ( $status ) {
                BudgetStatus::DRAFT     => 'secondary',
                BudgetStatus::PENDING   => 'primary',
                BudgetStatus::APPROVED  => 'success',
                BudgetStatus::REJECTED  => 'danger',
                BudgetStatus::EXPIRED   => 'warning',
                BudgetStatus::CANCELLED => 'dark',
                BudgetStatus::COMPLETED => 'info',
            };
        }

        return 'secondary';
    }

    public static function budget_next_statuses( $currentStatus )
    {
        if ( $currentStatus instanceof BudgetStatus ) {
            return BudgetStatus::getAllowedTransitions( $currentStatus->value );
        } elseif ( is_array( $currentStatus ) && !empty( $currentStatus[ 'slug' ] ) ) {
            return BudgetStatus::getAllowedTransitions( $currentStatus[ 'slug' ] );
        }

        return [];
    }

    public static function status_allows_edit( $status ): bool
    {
        if ( is_string( $status ) ) {
            $status = BudgetStatus::tryFrom( $status );
        }

        if ( $status instanceof BudgetStatus ) {
            return match ( $status ) {
                BudgetStatus::DRAFT     => true,
                BudgetStatus::PENDING   => true,
                BudgetStatus::APPROVED  => false,
                BudgetStatus::REJECTED  => false,
                BudgetStatus::EXPIRED   => false,
                BudgetStatus::CANCELLED => false,
                BudgetStatus::COMPLETED => false,
            };
        }

        return false;
    }

    public static function is_final_status( $status ): bool
    {
        if ( is_string( $status ) ) {
            $status = BudgetStatus::tryFrom( $status );
        }

        if ( $status instanceof BudgetStatus ) {
            return in_array( $status->value, BudgetStatus::getFinalStatuses() );
        }

        return false;
    }

    public static function budget_status_options( $selectedStatus = '' )
    {
        $options = [ '<option value="">Todos</option>' ];

        foreach ( BudgetStatus::cases() as $status ) {
            $selected  = $selectedStatus === $status->value ? 'selected' : '';
            $options[] = sprintf(
                '<option value="%s" %s>%s</option>',
                e( $status->value ),
                $selected,
                e( $status->getName() ),
            );
        }

        return implode( "\n", $options );
    }

    public static function is_active_status( $status ): bool
    {
        if ( is_string( $status ) ) {
            $status = BudgetStatus::tryFrom( $status );
        }

        if ( $status instanceof BudgetStatus ) {
            return !in_array( $status->value, BudgetStatus::getInactiveStatuses() );
        }

        return false;
    }

    public static function invoice_status_options( $selectedStatus = '' )
    {
        $options = [ '<option value="">Todos</option>' ];

        foreach ( InvoiceStatus::cases() as $status ) {
            $selected  = $selectedStatus === $status->value ? 'selected' : '';
            $options[] = sprintf(
                '<option value="%s" %s>%s</option>',
                e( $status->value ),
                $selected,
                e( $status->getName() ),
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
            // Verificar se é enum instance
            if (
                $service[ 'status' ] instanceof ServiceStatus &&
                $service[ 'status' ] === ServiceStatus::COMPLETED
            ) {
                $completed_services++;
            }
            // Fallback para arrays legados
            elseif (
                is_array( $service[ 'status' ] ) &&
                ( $service[ 'status' ][ 'slug' ] === 'completed' ||
                    $service[ 'status' ][ 'slug' ] === 'COMPLETED' )
            ) {
                $completed_services++;
            }
        }

        return ( $completed_services / $total_services ) * 100;
    }

}
