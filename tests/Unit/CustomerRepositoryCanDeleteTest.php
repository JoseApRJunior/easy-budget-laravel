<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Budget;
use App\Models\Customer;
use App\Models\CustomerInteraction;
use App\Models\Invoice;
use App\Models\Service;
use App\Models\Tenant;
use App\Repositories\CustomerRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerRepositoryCanDeleteTest extends TestCase
{
    use RefreshDatabase;

    private int $tenantId = 1;

    protected function setUp(): void
    {
        parent::setUp();

        // Criar tenant
        $this->tenant = Tenant::factory()->create( [ 'id' => $this->tenantId ] );
    }

    /** @test */
    public function customer_without_relationships_can_be_deleted(): void
    {
        // Arrange
        $customer = Customer::factory()->create( [
            'tenant_id' => $this->tenantId,
        ] );

        // Act
        $result = $this->customerRepository()->canDelete( $customer->id, $this->tenantId );

        // Assert
        $this->assertTrue( $result[ 'canDelete' ] );
        $this->assertNull( $result[ 'reason' ] );
        $this->assertEquals( 0, $result[ 'totalRelationsCount' ] );
        $this->assertEquals( 0, $result[ 'budgetsCount' ] );
        $this->assertEquals( 0, $result[ 'servicesCount' ] );
        $this->assertEquals( 0, $result[ 'invoicesCount' ] );
        $this->assertEquals( 0, $result[ 'interactionsCount' ] );
    }

    /** @test */
    public function customer_with_budget_cannot_be_deleted(): void
    {
        // Arrange
        $customer = Customer::factory()->create( [
            'tenant_id' => $this->tenantId,
        ] );

        Budget::factory()->create( [
            'tenant_id'   => $this->tenantId,
            'customer_id' => $customer->id
        ] );

        // Act
        $result = $this->customerRepository()->canDelete( $customer->id, $this->tenantId );

        // Assert
        $this->assertFalse( $result[ 'canDelete' ] );
        $this->assertStringContainsString( '1 orçamento(s)', $result[ 'reason' ] );
        $this->assertEquals( 1, $result[ 'budgetsCount' ] );
        $this->assertEquals( 1, $result[ 'totalRelationsCount' ] );
    }

    /** @test */
    public function customer_with_multiple_relationships_shows_detailed_reasons(): void
    {
        // Arrange
        $customer = Customer::factory()->create( [
            'tenant_id' => $this->tenantId,
        ] );

        // Adicionar múltiplos relacionamentos
        Budget::factory()->create( [
            'tenant_id'   => $this->tenantId,
            'customer_id' => $customer->id
        ] );

        $budget = Budget::factory()->create( [
            'tenant_id'   => $this->tenantId,
            'customer_id' => $customer->id
        ] );

        Service::factory()->create( [
            'tenant_id' => $this->tenantId,
            'budget_id' => $budget->id
        ] );

        CustomerInteraction::factory()->create( [
            'tenant_id'   => $this->tenantId,
            'customer_id' => $customer->id
        ] );

        // Act
        $result = $this->customerRepository()->canDelete( $customer->id, $this->tenantId );

        // Assert
        $this->assertFalse( $result[ 'canDelete' ] );
        $this->assertStringContainsString( '2 orçamento(s)', $result[ 'reason' ] );
        $this->assertStringContainsString( '1 serviço(s)', $result[ 'reason' ] );
        $this->assertStringContainsString( '1 interação(ões)', $result[ 'reason' ] );
        $this->assertEquals( 4, $result[ 'totalRelationsCount' ] );
    }

    /** @test */
    public function checkRelationships_method_returns_complete_structure(): void
    {
        // Arrange
        $customer = Customer::factory()->create( [
            'tenant_id' => $this->tenantId,
        ] );

        Budget::factory()->create( [
            'tenant_id'   => $this->tenantId,
            'customer_id' => $customer->id
        ] );

        // Act
        $result = $this->customerRepository()->checkRelationships( $customer->id, $this->tenantId );

        // Assert
        $this->assertIsArray( $result );
        $this->assertTrue( $result[ 'hasRelationships' ] );
        $this->assertEquals( 1, $result[ 'budgets' ] );
        $this->assertEquals( 0, $result[ 'services' ] );
        $this->assertEquals( 0, $result[ 'invoices' ] );
        $this->assertEquals( 0, $result[ 'interactions' ] );
        $this->assertEquals( 1, $result[ 'totalRelations' ] );
        $this->assertStringContainsString( '1 orçamento(s)', $result[ 'reason' ] );
    }

    /** @test */
    public function customer_not_found_returns_correct_error(): void
    {
        // Act
        $result = $this->customerRepository()->canDelete( 999, $this->tenantId );

        // Assert
        $this->assertFalse( $result[ 'canDelete' ] );
        $this->assertEquals( 'Customer não encontrado', $result[ 'reason' ] );
        $this->assertEquals( 0, $result[ 'totalRelationsCount' ] );
    }

    private function customerRepository(): CustomerRepository
    {
        return app( CustomerRepository::class);
    }

}
