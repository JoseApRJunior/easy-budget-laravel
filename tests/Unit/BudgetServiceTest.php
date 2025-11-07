<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\BudgetStatus;
use App\Models\Budget;
use App\Models\Service;
use App\Services\Domain\BudgetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetServiceTest extends TestCase
{
    use RefreshDatabase;

    private BudgetService $budgetService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->budgetService = app( BudgetService::class);
    }

    public function test_generate_unique_code_creates_sequential_codes(): void
    {
        $tenant = \App\Models\Tenant::factory()->create();

        // Criar orçamento existente
        Budget::factory()->forTenant( $tenant )->create( [ 'code' => 'ORC-' . date( 'Ymd' ) . '0001' ] );

        // Gerar novo código
        $reflection = new \ReflectionClass( $this->budgetService );
        $method     = $reflection->getMethod( 'generateUniqueCode' );
        $method->setAccessible( true );

        $newCode = $method->invoke( $this->budgetService );

        $this->assertEquals( 'ORC-' . date( 'Ymd' ) . '0002', $newCode );
    }

    public function test_handle_status_change_updates_related_services(): void
    {
        $tenant   = \App\Models\Tenant::factory()->create();
        $category = \App\Models\Category::create( [
            'name' => 'Categoria Teste',
            'slug' => 'categoria-teste',
        ] );
        $budget   = Budget::factory()->forTenant( $tenant )->withStatus( BudgetStatus::PENDING )->create();

        // Criar serviço relacionado manualmente
        $service = Service::create( [
            'budget_id'   => $budget->id,
            'tenant_id'   => $tenant->id,
            'name'        => 'Serviço teste',
            'status'      => 'scheduled',
            'code'        => 'SVC-001',
            'category_id' => $category->id,
        ] );

        $result = $this->budgetService->handleStatusChange( $budget, 'approved' );

        $this->assertTrue( $result->isSuccess() );
        $this->assertEquals( 'approved', $budget->fresh()->status->value );
        $this->assertEquals( 'in-progress', $service->fresh()->status->value );
    }

    public function test_find_by_code_returns_budget(): void
    {
        $tenant = \App\Models\Tenant::factory()->create();
        $budget = Budget::factory()->forTenant( $tenant )->create( [ 'code' => 'ORC-202501010001' ] );

        $result = $this->budgetService->findByCode( 'ORC-202501010001' );

        $this->assertTrue( $result->isSuccess() );
        $this->assertEquals( $budget->id, $result->getData()->id );
    }

}
