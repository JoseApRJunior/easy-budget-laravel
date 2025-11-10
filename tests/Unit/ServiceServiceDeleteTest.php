<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\OperationStatus;
use App\Enums\ServiceStatus;
use App\Models\Budget;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Service;
use App\Models\ServiceItem;
use App\Models\Tenant;
use App\Services\Domain\ServiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ServiceServiceDeleteTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    private ServiceService $serviceService;
    private Tenant         $tenant;
    private Customer       $customer;
    private Budget         $budget;
    private Category       $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->serviceService = app( ServiceService::class);

        $this->tenant   = Tenant::factory()->create();
        $this->customer = Customer::factory()->create( [
            'tenant_id' => $this->tenant->id,
        ] );
        $this->budget   = Budget::factory()->create( [
            'tenant_id'   => $this->tenant->id,
            'customer_id' => $this->customer->id,
        ] );

        // Usar categoria existente ou criar uma sem tenant_id
        $this->category = Category::first() ?? Category::factory()->create();
    }

    public function test_delete_by_code_success(): void
    {
        // Arrange
        $service = Service::factory()->create( [
            'tenant_id'   => $this->tenant->id,
            'budget_id'   => $this->budget->id,
            'category_id' => $this->category->id,
            'status'      => ServiceStatus::SCHEDULED,
            'total'       => 100.0,
        ] );

        // Act
        $result = $this->serviceService->deleteByCode( $service->code );

        // Assert
        $this->assertTrue( $result->isSuccess() );
        $this->assertEquals( 'Serviço excluído com sucesso', $result->getMessage() );
        $this->assertNull( $result->getData() );
        $this->assertDatabaseMissing( 'services', [ 'id' => $service->id ] );
    }

    public function test_delete_by_code_not_found(): void
    {
        // Act
        $result = $this->serviceService->deleteByCode( 'NON_EXISTENT_CODE' );

        // Assert
        $this->assertTrue( $result->isError() );
        $this->assertEquals( \App\Enums\OperationStatus::NOT_FOUND, $result->getStatus() );
        $this->assertStringContainsString( 'não encontrado', $result->getMessage() );
    }

    public function test_delete_by_code_with_invoices_fails(): void
    {
        // Arrange
        $service = Service::factory()->create( [
            'tenant_id'   => $this->tenant->id,
            'budget_id'   => $this->budget->id,
            'category_id' => $this->category->id,
            'status'      => ServiceStatus::SCHEDULED->value,
        ] );

        // Criar fatura associada ao serviço
        $invoice = \App\Models\Invoice::factory()->create( [
            'tenant_id'   => $this->tenant->id,
            'service_id'  => $service->id,
            'customer_id' => $this->customer->id,
        ] );

        // Act
        $result = $this->serviceService->deleteByCode( $service->code );

        // Assert
        $this->assertTrue( $result->isError() );
        $this->assertEquals( \App\Enums\OperationStatus::VALIDATION_ERROR, $result->getStatus() );
        $this->assertStringContainsString( 'dependências', $result->getMessage() );
        $this->assertDatabaseHas( 'services', [ 'id' => $service->id ] );
    }

    public function test_delete_by_code_with_final_status_fails(): void
    {
        // Arrange
        $service = Service::factory()->create( [
            'tenant_id'   => $this->tenant->id,
            'budget_id'   => $this->budget->id,
            'category_id' => $this->category->id,
            'status'      => ServiceStatus::COMPLETED, // Status final
        ] );

        // Act
        $result = $this->serviceService->deleteByCode( $service->code );

        // Assert
        $this->assertTrue( $result->isError() );
        $this->assertEquals( \App\Enums\OperationStatus::VALIDATION_ERROR, $result->getStatus() );
        $this->assertStringContainsString( 'dependências', $result->getMessage() );
        $this->assertDatabaseHas( 'services', [ 'id' => $service->id ] );
    }

    public function test_delete_by_code_with_future_schedules_fails(): void
    {
        // Arrange
        $service = Service::factory()->create( [
            'tenant_id'   => $this->tenant->id,
            'budget_id'   => $this->budget->id,
            'category_id' => $this->category->id,
            'status'      => ServiceStatus::SCHEDULED->value,
        ] );

        // Criar agendamento futuro
        \App\Models\Schedule::factory()->create( [
            'tenant_id'       => $this->tenant->id,
            'service_id'      => $service->id,
            'start_date_time' => now()->addDays( 7 ), // Futuro
            'end_date_time'   => now()->addDays( 7 )->addHours( 2 ),
        ] );

        // Act
        $result = $this->serviceService->deleteByCode( $service->code );

        // Assert
        $this->assertTrue( $result->isError() );
        $this->assertEquals( \App\Enums\OperationStatus::VALIDATION_ERROR, $result->getStatus() );
        $this->assertStringContainsString( 'agendamentos futuros', $result->getMessage() );
        $this->assertDatabaseHas( 'services', [ 'id' => $service->id ] );
    }

    public function test_delete_by_code_cascades_service_items(): void
    {
        // Arrange
        $service = Service::factory()->create( [
            'tenant_id'   => $this->tenant->id,
            'budget_id'   => $this->budget->id,
            'category_id' => $this->category->id,
            'status'      => ServiceStatus::SCHEDULED->value,
        ] );

        $serviceItem = ServiceItem::factory()->create( [
            'tenant_id'  => $this->tenant->id,
            'service_id' => $service->id,
        ] );

        // Act
        $result = $this->serviceService->deleteByCode( $service->code );

        // Assert
        $this->assertTrue( $result->isSuccess() );
        $this->assertDatabaseMissing( 'services', [ 'id' => $service->id ] );
        $this->assertDatabaseMissing( 'service_items', [ 'id' => $serviceItem->id ] );
    }

}
