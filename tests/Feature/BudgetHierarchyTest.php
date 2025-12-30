<?php

namespace Tests\Feature;

use App\Models\Budget;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Role;
use App\Models\Service;
use App\Models\ServiceItem;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetHierarchyTest extends TestCase
{
    use RefreshDatabase;

    protected $tenant;
    protected $providerUser;
    protected $customer;
    protected $category;
    protected $products;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->providerUser = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'email_verified_at' => now(),
            'is_active' => true,
        ]);
        $this->actingAs($this->providerUser);

        \App\Models\Provider::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $this->providerUser->id,
            'terms_accepted' => true,
        ]);
        $role = Role::firstOrCreate(['name' => 'provider'], ['description' => 'Provider']);
        $this->providerUser->roles()->attach($role->id, ['tenant_id' => $this->tenant->id]);

        $this->customer = Customer::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->category = Category::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->products = Product::factory()->count(3)->create(['tenant_id' => $this->tenant->id]);
    }

    public function test_can_create_budget_with_nested_services_and_items()
    {
        $payload = [
            'customer_id' => $this->customer->id,
            'due_date' => now()->addDays(7)->format('Y-m-d'),
            'description' => 'Test Budget',
            'discount' => 10.00,
            'services' => [
                [
                    'category_id' => $this->category->id,
                    'description' => 'Service 1',
                    'items' => [
                        [
                            'product_id' => $this->products[0]->id,
                            'unit_value' => 100.00,
                            'quantity' => 1,
                        ],
                        [
                            'product_id' => $this->products[1]->id,
                            'unit_value' => 50.00,
                            'quantity' => 2,
                        ],
                    ],
                ],
                [
                    'category_id' => $this->category->id,
                    'description' => 'Service 2',
                    'items' => [
                        [
                            'product_id' => $this->products[2]->id,
                            'unit_value' => 200.00,
                            'quantity' => 1,
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($this->providerUser)
            ->withoutMiddleware()
            ->withoutExceptionHandling()
            ->post(route('provider.budgets.store'), $payload);

        $response->assertStatus(302);
        \Illuminate\Support\Facades\Log::info("Test Debug", [
            'status' => $response->status(),
            'error' => session('error'),
            'errors' => session('errors') ? session('errors')->getMessages() : null,
            'location' => $response->headers->get('Location'),
        ]);
        $budget = Budget::first();
        if ($response->status() !== 201 && $response->status() !== 302 || !$budget) {
            dd([
                'status' => $response->status(),
                'session' => session()->all(),
                'headers' => $response->headers->all(),
            ]);
        }
        $this->assertNotNull($budget);
        $this->assertEquals(2, $budget->services()->count());
        $this->assertEquals(3, ServiceItem::count());

        // Subtotal = (100*1 + 50*2) + (200*1) = 200 + 200 = 400
        // Total = 400 - 10 = 390
        $this->assertEquals(400.00, $budget->subtotal);
        $this->assertEquals(390.00, $budget->total);
    }

    public function test_can_update_budget_recreating_services()
    {
        $budget = Budget::factory()->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->customer->id,
        ]);

        $service = Service::factory()->create([
            'budget_id' => $budget->id,
            'tenant_id' => $this->tenant->id,
            'category_id' => $this->category->id,
        ]);

        ServiceItem::factory()->create([
            'service_id' => $service->id,
            'product_id' => $this->products[0]->id,
            'quantity' => 1,
            'unit_value' => 100,
            'total_value' => 100
        ]);

        $payload = [
            'customer_id' => $this->customer->id,
            'due_date' => now()->addDays(7)->format('Y-m-d'),
            'description' => 'Updated Budget',
            'status' => $budget->status->value,
            'services' => [
                [
                    'category_id' => $this->category->id,
                    'description' => 'New Service',
                    'items' => [
                        [
                            'product_id' => $this->products[1]->id,
                            'unit_value' => 150.00,
                            'quantity' => 2,
                        ],
                    ],
                ],
            ],
        ];

        // Custom post route as defined in web.php
        $response = $this->actingAs($this->providerUser)
            ->post(route('provider.budgets.update', $budget->code), $payload);

        $response->assertStatus(302);

        $budget->refresh();
        $this->assertEquals(1, $budget->services()->count());
        $this->assertEquals('New Service', $budget->services->first()->description);
        $this->assertEquals(300.00, $budget->total);
    }

    public function test_deleting_budget_deletes_services_and_items()
    {
        $budget = Budget::factory()->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->customer->id,
        ]);

        $service = Service::factory()->create(['budget_id' => $budget->id, 'tenant_id' => $this->tenant->id]);
        ServiceItem::factory()->create(['service_id' => $service->id]);

        $this->actingAs($this->providerUser)
            ->delete(route('provider.budgets.destroy', $budget->code));

        $this->assertDatabaseMissing('budgets', ['id' => $budget->id]);
        // Services should be deleted
        $this->assertEquals(0, Service::where('budget_id', $budget->id)->count());
        $this->assertEquals(0, ServiceItem::count());
    }
}
