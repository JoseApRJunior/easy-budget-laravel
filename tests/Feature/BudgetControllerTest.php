<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\BudgetStatusEnum;
use App\Models\Budget;
use App\Models\Customer;
use App\Models\Tenant;
use App\Models\User;
use App\Services\BudgetService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class BudgetControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $tenant;
    protected $user;
    protected $customer;
    protected $budgetStatus;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant       = Tenant::factory()->create();
        $this->user         = User::factory()->create( [
            'tenant_id' => $this->tenant->id,
        ] );
        $this->customer     = Customer::factory()->create( [
            'tenant_id' => $this->tenant->id,
        ] );
        $this->budgetStatus = BudgetStatusEnum::DRAFT;
    }

    /** @test */
    public function it_can_list_budgets_with_authentication()
    {
        $this->actingAs( $this->user );

        $budget = Budget::factory()->create( [
            'tenant_id'          => $this->tenant->id,
            'customer_id'        => $this->customer->id,
            'budget_statuses_id' => $this->budgetStatus->value,
        ] );

        $response = $this->getJson( '/api/v1/budgets' );

        $response->assertStatus( 200 )
            ->assertJsonStructure( [
                'success',
                'data' => [
                    'data' => [
                        '*' => [
                            'id',
                            'code',
                            'total',
                            'status',
                            'created_at',
                        ]
                    ],
                    'pagination'
                ]
            ] );
    }

    /** @test */
    public function it_requires_authentication_for_budget_listing()
    {
        $response = $this->getJson( '/api/v1/budgets' );

        $response->assertStatus( 401 );
    }

    /** @test */
    public function it_can_create_budget_with_valid_data()
    {
        $this->actingAs( $this->user );

        $budgetData = [
            'customer_id'        => $this->customer->id,
            'title'              => 'Test Budget',
            'description'        => 'Test budget description',
            'total'              => 1500.00,
            'budget_statuses_id' => $this->budgetStatus->value,
        ];

        $response = $this->postJson( '/api/v1/budgets', $budgetData );

        $response->assertStatus( 201 )
            ->assertJsonStructure( [
                'success',
                'data' => [
                    'id',
                    'code',
                    'total',
                    'status',
                ]
            ] );

        $this->assertDatabaseHas( 'budgets', [
            'title'     => 'Test Budget',
            'total'     => 1500.00,
            'tenant_id' => $this->tenant->id,
        ] );
    }

    /** @test */
    public function it_validates_required_fields_when_creating_budget()
    {
        $this->actingAs( $this->user );

        $response = $this->postJson( '/api/v1/budgets', [] );

        $response->assertStatus( 422 )
            ->assertJsonValidationErrors( [ 'customer_id', 'title', 'total' ] );
    }

    /** @test */
    public function it_can_show_specific_budget()
    {
        $this->actingAs( $this->user );

        $budget = Budget::factory()->create( [
            'tenant_id'          => $this->tenant->id,
            'customer_id'        => $this->customer->id,
            'budget_statuses_id' => $this->budgetStatus->value,
        ] );

        $response = $this->getJson( "/api/v1/budgets/{$budget->id}" );

        $response->assertStatus( 200 )
            ->assertJsonStructure( [
                'success',
                'data' => [
                    'id',
                    'code',
                    'total',
                    'status',
                    'customer',
                    'services'
                ]
            ] );
    }

    /** @test */
    public function it_returns_404_for_non_existent_budget()
    {
        $this->actingAs( $this->user );

        $response = $this->getJson( '/api/v1/budgets/999999' );

        $response->assertStatus( 404 );
    }

    /** @test */
    public function it_can_update_budget_with_valid_data()
    {
        $this->actingAs( $this->user );

        $budget = Budget::factory()->create( [
            'tenant_id'          => $this->tenant->id,
            'customer_id'        => $this->customer->id,
            'budget_statuses_id' => $this->budgetStatus->value,
        ] );

        $updateData = [
            'title' => 'Updated Budget Title',
            'total' => 2000.00,
        ];

        $response = $this->putJson( "/api/v1/budgets/{$budget->id}", $updateData );

        $response->assertStatus( 200 )
            ->assertJsonStructure( [
                'success',
                'data' => [
                    'id',
                    'title',
                    'total',
                ]
            ] );

        $this->assertDatabaseHas( 'budgets', [
            'id'    => $budget->id,
            'title' => 'Updated Budget Title',
            'total' => 2000.00,
        ] );
    }

    /** @test */
    public function it_can_change_budget_status()
    {
        $this->actingAs( $this->user );

        $approvedStatus = BudgetStatusEnum::APPROVED;

        $budget = Budget::factory()->create( [
            'tenant_id'          => $this->tenant->id,
            'customer_id'        => $this->customer->id,
            'budget_statuses_id' => $this->budgetStatus->value,
        ] );

        $response = $this->postJson( "/api/v1/budgets/{$budget->id}/change-status", [
            'status'  => 'approved',
            'comment' => 'Budget approved for processing',
        ] );

        $response->assertStatus( 200 );

        $this->assertDatabaseHas( 'budgets', [
            'id'                 => $budget->id,
            'budget_statuses_id' => $approvedStatus->value,
        ] );
    }

    /** @test */
    public function it_can_duplicate_budget()
    {
        $this->actingAs( $this->user );

        $budget = Budget::factory()->create( [
            'tenant_id'          => $this->tenant->id,
            'customer_id'        => $this->customer->id,
            'budget_statuses_id' => $this->budgetStatus->value,
            'title'              => 'Original Budget',
        ] );

        $response = $this->postJson( "/api/v1/budgets/{$budget->id}/duplicate" );

        $response->assertStatus( 201 )
            ->assertJsonStructure( [
                'success',
                'data' => [
                    'id',
                    'code',
                    'title',
                ]
            ] );

        // Verifica se um novo orçamento foi criado
        $this->assertEquals( 2, Budget::count() );

        // Verifica se o novo orçamento tem um código diferente
        $duplicatedBudget = Budget::where( 'id', '!=', $budget->id )->first();
        $this->assertNotEquals( $budget->code, $duplicatedBudget->code );
    }

    /** @test */
    public function it_can_delete_budget()
    {
        $this->actingAs( $this->user );

        $budget = Budget::factory()->create( [
            'tenant_id'          => $this->tenant->id,
            'customer_id'        => $this->customer->id,
            'budget_statuses_id' => $this->budgetStatus->value,
        ] );

        $response = $this->deleteJson( "/api/v1/budgets/{$budget->id}" );

        $response->assertStatus( 200 );

        $this->assertSoftDeleted( 'budgets', [
            'id' => $budget->id,
        ] );
    }

    /** @test */
    public function it_can_bulk_update_budget_status()
    {
        $this->actingAs( $this->user );

        $budgets = Budget::factory()->count( 3 )->create( [
            'tenant_id'          => $this->tenant->id,
            'customer_id'        => $this->customer->id,
            'budget_statuses_id' => $this->budgetStatus->value,
        ] );

        $budgetIds = $budgets->pluck( 'id' )->toArray();

        $response = $this->postJson( '/api/v1/budgets/bulk-update-status', [
            'budget_ids'       => $budgetIds,
            'status'           => 'approved',
            'comment'          => 'Bulk approval',
            'notify_customers' => true,
        ] );

        $response->assertStatus( 200 )
            ->assertJsonStructure( [
                'success',
                'data' => [
                    'updated_count',
                    'failed_count',
                ]
            ] );

        $approvedStatus = BudgetStatusEnum::APPROVED;

        foreach ( $budgetIds as $budgetId ) {
            $this->assertDatabaseHas( 'budgets', [
                'id'                 => $budgetId,
                'budget_statuses_id' => $approvedStatus->value,
            ] );
        }
    }

    /** @test */
    public function it_validates_bulk_update_status_request()
    {
        $this->actingAs( $this->user );

        $response = $this->postJson( '/api/v1/budgets/bulk-update-status', [
            'budget_ids' => [],
            'status'     => 'invalid_status',
        ] );

        $response->assertStatus( 422 )
            ->assertJsonValidationErrors( [ 'budget_ids', 'status' ] );
    }

    /** @test */
    public function it_can_generate_budget_report()
    {
        $this->actingAs( $this->user );

        Budget::factory()->count( 5 )->create( [
            'tenant_id'          => $this->tenant->id,
            'customer_id'        => $this->customer->id,
            'budget_statuses_id' => $this->budgetStatus->value,
        ] );

        $response = $this->postJson( '/api/v1/budgets/report', [
            'period'         => 'monthly',
            'format'         => 'json',
            'include_totals' => true,
        ] );

        $response->assertStatus( 200 )
            ->assertJsonStructure( [
                'success',
                'data' => [
                    'budgets',
                    'totals',
                    'period_info',
                ]
            ] );
    }

    /** @test */
    public function it_can_get_budget_statistics()
    {
        $this->actingAs( $this->user );

        Budget::factory()->count( 10 )->create( [
            'tenant_id'          => $this->tenant->id,
            'customer_id'        => $this->customer->id,
            'budget_statuses_id' => $this->budgetStatus->value,
        ] );

        $response = $this->getJson( '/api/v1/budgets/stats' );

        $response->assertStatus( 200 )
            ->assertJsonStructure( [
                'success',
                'data' => [
                    'total_budgets',
                    'total_value',
                    'status_breakdown',
                    'monthly_trend',
                ]
            ] );
    }

    /** @test */
    public function it_filters_budgets_by_status()
    {
        $this->actingAs( $this->user );

        $approvedStatus = BudgetStatusEnum::APPROVED;

        Budget::factory()->create( [
            'tenant_id'          => $this->tenant->id,
            'customer_id'        => $this->customer->id,
            'budget_statuses_id' => $this->budgetStatus->value, // draft
        ] );

        Budget::factory()->create( [
            'tenant_id'          => $this->tenant->id,
            'customer_id'        => $this->customer->id,
            'budget_statuses_id' => $approvedStatus->value, // approved
        ] );

        $response = $this->getJson( '/api/v1/budgets?status[]=approved' );

        $response->assertStatus( 200 );

        $budgets = $response->json( 'data.data' );
        $this->assertCount( 1, $budgets );
        $this->assertEquals( 'approved', $budgets[ 0 ][ 'status' ] );
    }

    /** @test */
    public function it_respects_tenant_scoping()
    {
        $this->actingAs( $this->user );

        // Create budget for current tenant
        $budget1 = Budget::factory()->create( [
            'tenant_id'          => $this->tenant->id,
            'customer_id'        => $this->customer->id,
            'budget_statuses_id' => $this->budgetStatus->value,
        ] );

        // Create budget for different tenant
        $otherTenant   = Tenant::factory()->create();
        $otherUser     = User::factory()->create( [ 'tenant_id' => $otherTenant->id ] );
        $otherCustomer = Customer::factory()->create( [ 'tenant_id' => $otherTenant->id ] );

        Budget::factory()->create( [
            'tenant_id'          => $otherTenant->id,
            'customer_id'        => $otherCustomer->id,
            'budget_statuses_id' => $this->budgetStatus->value,
        ] );

        $response = $this->getJson( '/api/v1/budgets' );

        $response->assertStatus( 200 );

        $budgets = $response->json( 'data.data' );
        $this->assertCount( 1, $budgets );
        $this->assertEquals( $budget1->id, $budgets[ 0 ][ 'id' ] );
    }

}
