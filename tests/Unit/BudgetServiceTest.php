<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Budget;
use App\Models\BudgetStatus;
use App\Models\Tenant;
use App\Models\User;
use App\Services\BudgetService;
use App\Support\ServiceResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class BudgetServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $budgetService;
    protected $tenant;
    protected $user;
    protected $budgetStatus;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed necessário para BudgetStatus
        $this->seed(\Database\Seeders\BudgetStatusSeeder::class);

        $this->budgetService = app(BudgetService::class);
        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
        ]);
        $this->budgetStatus = BudgetStatus::where('slug', 'pending')->first();
    }

    /** @test */
    public function it_can_get_budgets_for_provider_with_pagination()
    {
        Budget::factory()->count(15)->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->user->id,
            'budget_statuses_id' => $this->budgetStatus->id,
        ]);

        $result = $this->budgetService->getBudgetsForProvider($this->user->id, []);

        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(15, $result->total());
        $this->assertEquals(10, $result->perPage()); // Default per page
    }

    /** @test */
    public function it_can_filter_budgets_by_status()
    {
        $approvedStatus = BudgetStatus::where('slug', 'approved')->first();

        Budget::factory()->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->user->id,
            'budget_statuses_id' => $this->budgetStatus->id, // pending
        ]);

        Budget::factory()->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->user->id,
            'budget_statuses_id' => $approvedStatus->id, // approved
        ]);

        $result = $this->budgetService->getBudgetsForProvider($this->user->id, [
            'status' => ['approved']
        ]);

        $this->assertEquals(1, $result->total());
        $this->assertEquals('approved', $result->first()->budgetStatus->slug);
    }

    /** @test */
    public function it_can_filter_budgets_by_date_range()
    {
        Budget::factory()->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->user->id,
            'budget_statuses_id' => $this->budgetStatus->id,
            'created_at' => now()->subDays(10),
        ]);

        Budget::factory()->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->user->id,
            'budget_statuses_id' => $this->budgetStatus->id,
            'created_at' => now()->subDays(2),
        ]);

        $result = $this->budgetService->getBudgetsForProvider($this->user->id, [
            'date_from' => now()->subDays(5)->format('Y-m-d'),
            'date_to' => now()->format('Y-m-d'),
        ]);

        $this->assertEquals(1, $result->total());
    }

    /** @test */
    public function it_can_create_budget_successfully()
    {
        $budgetData = [
            'customer_id' => $this->user->id,
            'title' => 'Test Budget',
            'description' => 'Test budget description',
            'total' => 1500.00,
            'budget_statuses_id' => $this->budgetStatus->id,
        ];

        $result = $this->budgetService->createBudget($budgetData, $this->tenant->id);

        $this->assertInstanceOf(ServiceResult::class, $result);
        $this->assertTrue($result->isSuccess());
        $this->assertInstanceOf(Budget::class, $result->getData());
        $this->assertEquals('Test Budget', $result->getData()->title);
        $this->assertEquals(1500.00, $result->getData()->total);
    }

    /** @test */
    public function it_generates_unique_budget_code_on_creation()
    {
        $budgetData = [
            'customer_id' => $this->user->id,
            'title' => 'Test Budget',
            'total' => 1000.00,
            'budget_statuses_id' => $this->budgetStatus->id,
        ];

        $result1 = $this->budgetService->createBudget($budgetData, $this->tenant->id);
        $result2 = $this->budgetService->createBudget($budgetData, $this->tenant->id);

        $this->assertTrue($result1->isSuccess());
        $this->assertTrue($result2->isSuccess());
        $this->assertNotEquals($result1->getData()->code, $result2->getData()->code);
    }

    /** @test */
    public function it_can_update_budget_successfully()
    {
        $budget = Budget::factory()->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->user->id,
            'budget_statuses_id' => $this->budgetStatus->id,
            'title' => 'Original Title',
            'total' => 1000.00,
        ]);

        $updateData = [
            'title' => 'Updated Title',
            'total' => 1500.00,
        ];

        $result = $this->budgetService->updateBudget($budget->id, $updateData, $this->tenant->id);

        $this->assertInstanceOf(ServiceResult::class, $result);
        $this->assertTrue($result->isSuccess());
        $this->assertEquals('Updated Title', $result->getData()->title);
        $this->assertEquals(1500.00, $result->getData()->total);
    }

    /** @test */
    public function it_returns_error_when_updating_non_existent_budget()
    {
        $result = $this->budgetService->updateBudget(999999, ['title' => 'Test'], $this->tenant->id);

        $this->assertInstanceOf(ServiceResult::class, $result);
        $this->assertFalse($result->isSuccess());
        $this->assertStringContainsString('não encontrado', $result->getMessage());
    }

    /** @test */
    public function it_can_change_budget_status()
    {
        $approvedStatus = BudgetStatus::where('slug', 'approved')->first();
        
        $budget = Budget::factory()->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->user->id,
            'budget_statuses_id' => $this->budgetStatus->id,
        ]);

        $result = $this->budgetService->changeStatus(
            $budget->id,
            'approved',
            'Budget approved for processing',
            $this->tenant->id
        );

        $this->assertTrue($result->isSuccess());
        
        $budget->refresh();
        $this->assertEquals($approvedStatus->id, $budget->budget_statuses_id);
    }

    /** @test */
    public function it_validates_status_when_changing_budget_status()
    {
        $budget = Budget::factory()->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->user->id,
            'budget_statuses_id' => $this->budgetStatus->id,
        ]);

        $result = $this->budgetService->changeStatus(
            $budget->id,
            'invalid_status',
            'Test comment',
            $this->tenant->id
        );

        $this->assertFalse($result->isSuccess());
        $this->assertStringContainsString('inválido', $result->getMessage());
    }

    /** @test */
    public function it_can_duplicate_budget()
    {
        $originalBudget = Budget::factory()->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->user->id,
            'budget_statuses_id' => $this->budgetStatus->id,
            'title' => 'Original Budget',
            'total' => 1000.00,
        ]);

        $result = $this->budgetService->duplicateBudget($originalBudget->id, $this->tenant->id);

        $this->assertTrue($result->isSuccess());
        
        $duplicatedBudget = $result->getData();
        $this->assertNotEquals($originalBudget->id, $duplicatedBudget->id);
        $this->assertNotEquals($originalBudget->code, $duplicatedBudget->code);
        $this->assertEquals($originalBudget->title, $duplicatedBudget->title);
        $this->assertEquals($originalBudget->total, $duplicatedBudget->total);
        $this->assertEquals($this->budgetStatus->id, $duplicatedBudget->budget_statuses_id); // Reset to pending
    }

    /** @test */
    public function it_can_soft_delete_budget()
    {
        $budget = Budget::factory()->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->user->id,
            'budget_statuses_id' => $this->budgetStatus->id,
        ]);

        $result = $this->budgetService->deleteBudget($budget->id, $this->tenant->id);

        $this->assertTrue($result->isSuccess());
        $this->assertSoftDeleted('budgets', ['id' => $budget->id]);
    }

    /** @test */
    public function it_can_bulk_update_budget_status()
    {
        $budgets = Budget::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->user->id,
            'budget_statuses_id' => $this->budgetStatus->id,
        ]);

        $budgetIds = $budgets->pluck('id')->toArray();

        $result = $this->budgetService->bulkUpdateStatus(
            $budgetIds,
            'approved',
            'Bulk approval',
            true,
            $this->tenant->id
        );

        $this->assertTrue($result->isSuccess());
        
        $data = $result->getData();
        $this->assertEquals(3, $data['updated_count']);
        $this->assertEquals(0, $data['failed_count']);

        $approvedStatus = BudgetStatus::where('slug', 'approved')->first();
        
        foreach ($budgetIds as $budgetId) {
            $budget = Budget::find($budgetId);
            $this->assertEquals($approvedStatus->id, $budget->budget_statuses_id);
        }
    }

    /** @test */
    public function it_handles_partial_failures_in_bulk_update()
    {
        $validBudget = Budget::factory()->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->user->id,
            'budget_statuses_id' => $this->budgetStatus->id,
        ]);

        $budgetIds = [$validBudget->id, 999999]; // One valid, one invalid

        $result = $this->budgetService->bulkUpdateStatus(
            $budgetIds,
            'approved',
            'Bulk approval',
            false,
            $this->tenant->id
        );

        $this->assertTrue($result->isSuccess());
        
        $data = $result->getData();
        $this->assertEquals(1, $data['updated_count']);
        $this->assertEquals(1, $data['failed_count']);
    }

    /** @test */
    public function it_can_get_budget_statistics()
    {
        $approvedStatus = BudgetStatus::where('slug', 'approved')->first();

        // Create budgets with different statuses
        Budget::factory()->count(3)->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->user->id,
            'budget_statuses_id' => $this->budgetStatus->id, // pending
            'total' => 1000.00,
        ]);

        Budget::factory()->count(2)->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->user->id,
            'budget_statuses_id' => $approvedStatus->id, // approved
            'total' => 2000.00,
        ]);

        $result = $this->budgetService->getBudgetStats($this->tenant->id);

        $this->assertTrue($result->isSuccess());
        
        $stats = $result->getData();
        $this->assertEquals(5, $stats['total_budgets']);
        $this->assertEquals(7000.00, $stats['total_value']);
        $this->assertArrayHasKey('status_breakdown', $stats);
        $this->assertEquals(3, $stats['status_breakdown']['pending']);
        $this->assertEquals(2, $stats['status_breakdown']['approved']);
    }

    /** @test */
    public function it_respects_tenant_scoping_in_all_operations()
    {
        $otherTenant = Tenant::factory()->create();
        $otherUser = User::factory()->create(['tenant_id' => $otherTenant->id]);

        // Create budget for other tenant
        Budget::factory()->create([
            'tenant_id' => $otherTenant->id,
            'customer_id' => $otherUser->id,
            'budget_statuses_id' => $this->budgetStatus->id,
        ]);

        // Create budget for current tenant
        $budget = Budget::factory()->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->user->id,
            'budget_statuses_id' => $this->budgetStatus->id,
        ]);

        // Test getBudgetsForProvider respects tenant scoping
        $result = $this->budgetService->getBudgetsForProvider($this->user->id, []);
        $this->assertEquals(1, $result->total());

        // Test getBudgetStats respects tenant scoping
        $statsResult = $this->budgetService->getBudgetStats($this->tenant->id);
        $this->assertTrue($statsResult->isSuccess());
        $this->assertEquals(1, $statsResult->getData()['total_budgets']);
    }
}