<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\BudgetStatus;
use App\Models\AuditLog;
use App\Models\Budget;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetObserverTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_log_created_on_budget_creation(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs( $user );

        $tenant   = \App\Models\Tenant::factory()->create();
        $customer = \App\Models\Customer::factory()->create( [ 'tenant_id' => $tenant->id ] );

        $budget = Budget::factory()->create( [
            'tenant_id'   => $tenant->id,
            'customer_id' => $customer->id
        ] );

        $this->assertDatabaseHas( 'audit_logs', [
            'model_type' => Budget::class,
            'model_id'   => $budget->id,
            'action'     => 'budget_created',
            'user_id'    => $user->id
        ] );
    }

    public function test_audit_log_includes_old_new_values_on_update(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs( $user );

        $tenant   = \App\Models\Tenant::factory()->create();
        $customer = \App\Models\Customer::factory()->create( [ 'tenant_id' => $tenant->id ] );

        $budget = Budget::factory()->create( [
            'tenant_id'   => $tenant->id,
            'customer_id' => $customer->id,
            'status'      => BudgetStatus::DRAFT->value
        ] );

        // Limpar logs anteriores
        AuditLog::truncate();

        // Forçar o usuário a pertencer ao tenant para auth() funcionar corretamente
        $user->update( [ 'tenant_id' => $tenant->id ] );

        // Simular uma requisição HTTP para acionar o observer (rota não existe, mas o test está no contexto correto)
        $this->put( "/budgets/{$budget->id}/status", [
            'status' => BudgetStatus::PENDING->value
        ] );

        // Fallback: usar update direto se a rota não existir (404)
        $budget->update( [ 'status' => BudgetStatus::PENDING->value ] );

        // Verificar se o audit log foi criado
        $auditLog = AuditLog::latest()->first();

        $this->assertNotNull( $auditLog, 'Audit log should be created on status change' );
        $this->assertEquals( 'budget_status_changed', $auditLog->action );

        // Verificar se os valores estão salvos corretamente no metadata
        $metadata = $auditLog->metadata;
        $this->assertEquals( BudgetStatus::DRAFT->value, $metadata[ 'old_values' ][ 'status' ] );
        $this->assertEquals( BudgetStatus::PENDING->value, $metadata[ 'new_values' ][ 'status' ] );
    }

    public function test_audit_log_records_ip_and_user_agent(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $this->actingAs( $user );

        $tenant   = \App\Models\Tenant::factory()->create();
        $customer = \App\Models\Customer::factory()->create( [ 'tenant_id' => $tenant->id ] );

        $budget = Budget::factory()->create( [
            'tenant_id'   => $tenant->id,
            'customer_id' => $customer->id
        ] );

        $auditLog = AuditLog::where( 'model_id', $budget->id )->first();

        $this->assertNotNull( $auditLog->ip_address );
        $this->assertNotNull( $auditLog->user_agent );
    }

}
