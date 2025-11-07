<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\AuditLog;
use App\Models\Budget;
use Illuminate\Support\Facades\Log;

class BudgetObserver
{
    public function created( Budget $budget ): void
    {
        $this->log( $budget, 'budget_created', 'Orçamento criado' );
    }

    public function updated( Budget $budget ): void
    {
        $changes     = $budget->getChanges();
        $action      = 'budget_updated';
        $description = 'Orçamento atualizado';

        if ( isset( $changes[ 'status' ] ) ) {
            $action      = 'budget_status_changed';
            $description = "Status alterado para: {$changes[ 'status' ]}";
        }

        // Debug: verificar se observer está sendo chamado
        Log::info( 'BudgetObserver::updated called', [
            'budget_id' => $budget->id,
            'changes'   => $changes,
            'original'  => $budget->getOriginal(),
            'tenant_id' => $budget->tenant_id
        ] );

        $this->log( $budget, $action, $description, [
            'old_values' => $budget->getOriginal(),
            'new_values' => $changes,
        ] );
    }

    public function deleted( Budget $budget ): void
    {
        $this->log( $budget, 'budget_deleted', 'Orçamento excluído' );
    }

    public function restored( Budget $budget ): void
    {
        $this->log( $budget, 'budget_restored', 'Orçamento restaurado' );
    }

    private function log( Budget $budget, string $action, string $description, array $extra = [] ): void
    {
        try {
            AuditLog::withoutTenant()->create( [
                'tenant_id'   => $budget->tenant_id,
                'user_id'     => auth()->id(),
                'action'      => $action,
                'model_type'  => Budget::class,
                'model_id'    => $budget->id,
                'description' => $description,
                'ip_address'  => request()->ip(),
                'user_agent'  => request()->userAgent(),
                'metadata'    => $extra,
            ] );
        } catch ( \Exception $e ) {
            Log::error( 'Failed to create audit log', [
                'action'    => $action,
                'budget_id' => $budget->id,
                'error'     => $e->getMessage(),
            ] );
        }
    }

}
