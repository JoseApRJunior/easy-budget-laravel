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
            'status'      => BudgetStatus::DRAFT
        ] );

        // Limpar logs anteriores
        AuditLog::truncate();

        $budget->update( [ 'status' => BudgetStatus::PENDING->value ] );

        $auditLog = AuditLog::latest()->first();

        // Debug: verificar se o observer está sendo chamado
        if ( !$auditLog ) {
            $allLogs = AuditLog::all();
            dump( 'All audit logs:', $allLogs->toArray() );
            dump( 'Budget changes:', $budget->getChanges() );
            dump( 'Budget original:', $budget->getOriginal() );
        }

        $this->assertNotNull( $auditLog, 'Audit log should be created on status change' );
        $this->assertEquals( 'budget_status_changed', $auditLog->action );
        $this->assertArrayHasKey( 'status', $auditLog->old_values );
        $this->assertArrayHasKey( 'status', $auditLog->new_values );
        $this->assertEquals( 'draft', $auditLog->old_values[ 'status' ] );
        $this->assertEquals( 'pending', $auditLog->new_values[ 'status' ] );
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
