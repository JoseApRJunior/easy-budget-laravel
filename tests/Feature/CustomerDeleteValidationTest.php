<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\CustomerStatus;
use App\Enums\OperationStatus;
use App\Models\Budget;
use App\Models\Customer;
use App\Models\CustomerInteraction;
use App\Models\Invoice;
use App\Models\Service;
use App\Support\ServiceResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerDeleteValidationTest extends TestCase
{
    use RefreshDatabase;

    private int $tenantId = 1;

    protected function setUp(): void
    {
        parent::setUp();

        // Criar tenant
        $this->tenant = \App\Models\Tenant::factory()->create( [ 'id' => $this->tenantId ] );
    }

    /** @test */
    public function customer_without_relationships_can_be_deleted(): void
    {
        // Arrange
        $customer = Customer::factory()->create( [
            'tenant_id' => $this->tenantId,
            'status'    => CustomerStatus::ACTIVE->value
        ] );

        // Act
        $result = $this->customerService()->deleteCustomer( $customer->id, $this->tenantId );

        // Assert
        $this->assertTrue( $result->isSuccess() );
        $this->assertEquals( 'Cliente excluído com sucesso', $result->getMessage() );
        $this->assertSoftDeleted( 'customers', [ 'id' => $customer->id ] );
    }

    /** @test */
    public function customer_with_budget_cannot_be_deleted(): void
    {
        // Arrange
        $customer = Customer::factory()->create( [
            'tenant_id' => $this->tenantId,
            'status'    => CustomerStatus::ACTIVE->value
        ] );

        Budget::factory()->create( [
            'tenant_id'   => $this->tenantId,
            'customer_id' => $customer->id
        ] );

        // Act
        $result = $this->customerService()->deleteCustomer( $customer->id, $this->tenantId );

        // Assert
        $this->assertFalse( $result->isSuccess() );
        $this->assertEquals( OperationStatus::VALIDATION_ERROR, $result->getStatus() );
        $this->assertStringContainsString( 'Customer possui: 1 orçamento(s)', $result->getMessage() );
        $this->assertDatabaseHas( 'customers', [ 'id' => $customer->id ] );
    }

    /** @test */
    public function customer_with_service_cannot_be_deleted(): void
    {
        // Arrange
        $customer = Customer::factory()->create( [
            'tenant_id' => $this->tenantId,
            'status'    => CustomerStatus::ACTIVE->value
        ] );

        $budget = Budget::factory()->create( [
            'tenant_id'   => $this->tenantId,
            'customer_id' => $customer->id
        ] );

        Service::factory()->create( [
            'tenant_id' => $this->tenantId,
            'budget_id' => $budget->id
        ] );

        // Act
        $result = $this->customerService()->deleteCustomer( $customer->id, $this->tenantId );

        // Assert
        $this->assertFalse( $result->isSuccess() );
        $this->assertEquals( OperationStatus::VALIDATION_ERROR, $result->getStatus() );
        $this->assertStringContainsString( 'Customer possui: 1 serviço(s)', $result->getMessage() );
        $this->assertDatabaseHas( 'customers', [ 'id' => $customer->id ] );
    }

    /** @test */
    public function customer_with_invoice_cannot_be_deleted(): void
    {
        // Arrange
        $customer = Customer::factory()->create( [
            'tenant_id' => $this->tenantId,
            'status'    => CustomerStatus::ACTIVE->value
        ] );

        $budget = Budget::factory()->create( [
            'tenant_id'   => $this->tenantId,
            'customer_id' => $customer->id
        ] );

        $service = Service::factory()->create( [
            'tenant_id' => $this->tenantId,
            'budget_id' => $budget->id
        ] );

        Invoice::factory()->create( [
            'tenant_id'   => $this->tenantId,
            'customer_id' => $customer->id,
            'service_id'  => $service->id
        ] );

        // Act
        $result = $this->customerService()->deleteCustomer( $customer->id, $this->tenantId );

        // Assert
        $this->assertFalse( $result->isSuccess() );
        $this->assertEquals( OperationStatus::VALIDATION_ERROR, $result->getStatus() );
        $this->assertStringContainsString( 'Customer possui: 1 fatura(s)', $result->getMessage() );
        $this->assertDatabaseHas( 'customers', [ 'id' => $customer->id ] );
    }

    /** @test */
    public function customer_with_interaction_cannot_be_deleted(): void
    {
        // Arrange
        $customer = Customer::factory()->create( [
            'tenant_id' => $this->tenantId,
            'status'    => CustomerStatus::ACTIVE->value
        ] );

        CustomerInteraction::factory()->create( [
            'tenant_id'   => $this->tenantId,
            'customer_id' => $customer->id
        ] );

        // Act
        $result = $this->customerService()->deleteCustomer( $customer->id, $this->tenantId );

        // Assert
        $this->assertFalse( $result->isSuccess() );
        $this->assertEquals( OperationStatus::VALIDATION_ERROR, $result->getStatus() );
        $this->assertStringContainsString( 'Customer possui: 1 interação(ões)', $result->getMessage() );
        $this->assertDatabaseHas( 'customers', [ 'id' => $customer->id ] );
    }

    /** @test */
    public function customer_with_multiple_relationships_shows_all_reasons(): void
    {
        // Arrange
        $customer = Customer::factory()->create( [
            'tenant_id' => $this->tenantId,
            'status'    => CustomerStatus::ACTIVE->value
        ] );

        // Adicionar orçamento
        $budget = Budget::factory()->create( [
            'tenant_id'   => $this->tenantId,
            'customer_id' => $customer->id
        ] );

        // Adicionar serviço
        Service::factory()->create( [
            'tenant_id' => $this->tenantId,
            'budget_id' => $budget->id
        ] );

        // Adicionar fatura
        $service = Service::where( 'budget_id', $budget->id )->first();
        Invoice::factory()->create( [
            'tenant_id'   => $this->tenantId,
            'customer_id' => $customer->id,
            'service_id'  => $service->id
        ] );

        // Adicionar interação
        CustomerInteraction::factory()->create( [
            'tenant_id'   => $this->tenantId,
            'customer_id' => $customer->id
        ] );

        // Act
        $result = $this->customerService()->deleteCustomer( $customer->id, $this->tenantId );

        // Assert
        $this->assertFalse( $result->isSuccess() );
        $this->assertEquals( OperationStatus::VALIDATION_ERROR, $result->getStatus() );
        $this->assertStringContainsString( 'Customer possui: 1 orçamento(s), 1 serviço(s), 1 fatura(s), 1 interação(ões)', $result->getMessage() );
        $this->assertDatabaseHas( 'customers', [ 'id' => $customer->id ] );
    }

    /** @test */
    public function repository_canDelete_method_returns_correct_structure(): void
    {
        // Arrange
        $customer = Customer::factory()->create( [
            'tenant_id' => $this->tenantId,
            'status'    => CustomerStatus::ACTIVE->value
        ] );

        Budget::factory()->create( [
            'tenant_id'   => $this->tenantId,
            'customer_id' => $customer->id
        ] );

        // Act
        $result = $this->customerRepository()->canDelete( $customer->id, $this->tenantId );

        // Assert
        $this->assertIsArray( $result );
        $this->assertArrayHasKey( 'canDelete', $result );
        $this->assertArrayHasKey( 'reason', $result );
        $this->assertArrayHasKey( 'budgetsCount', $result );
        $this->assertArrayHasKey( 'servicesCount', $result );
        $this->assertArrayHasKey( 'invoicesCount', $result );
        $this->assertArrayHasKey( 'interactionsCount', $result );
        $this->assertArrayHasKey( 'totalRelationsCount', $result );

        $this->assertFalse( $result[ 'canDelete' ] );
        $this->assertEquals( 1, $result[ 'budgetsCount' ] );
        $this->assertEquals( 1, $result[ 'totalRelationsCount' ] );
    }

    private function customerService(): \App\Services\Domain\CustomerService
    {
        return app( \App\Services\Domain\CustomerService::class);
    }

    private function customerRepository(): \App\Repositories\CustomerRepository
    {
        return app( \App\Repositories\CustomerRepository::class);
    }

}
