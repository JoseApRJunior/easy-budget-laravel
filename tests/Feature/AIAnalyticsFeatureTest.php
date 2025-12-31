<?php

namespace Tests\Feature;

use App\Models\Budget;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AIAnalyticsFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $tenantId = 1;

    protected function setUp(): void
    {
        parent::setUp();

        // Create Tenant first
        if (class_exists(\Database\Factories\TenantFactory::class)) {
             try {
                $tenant = \App\Models\Tenant::factory()->create();
                $this->tenantId = $tenant->id;
             } catch (\Exception $e) {
                 // Fallback if factory fails or doesn't exist
                 $tenant = \App\Models\Tenant::create([
                     'id' => 'test-tenant-' . uniqid(),
                     'data' => [],
                 ]);
                 $this->tenantId = $tenant->id;
             }
        } else {
             // Fallback if no factory class
             $tenant = \App\Models\Tenant::create([
                 'id' => 'test-tenant-' . uniqid(),
                 // Add other required fields if known, usually 'id' is key for stancl/tenancy
             ]);
             $this->tenantId = $tenant->id;
        }

        $this->user = User::factory()->create([
            'tenant_id' => $this->tenantId,
            'email_verified_at' => now(),
            'is_active' => true,
        ]);

        // Ensure provider role exists and assign it
        if (class_exists(\App\Models\Role::class)) {
            $role = \App\Models\Role::firstOrCreate(
                ['name' => 'provider'],
                ['guard_name' => 'web'] // Assuming guard_name is needed or ignored
            );
            // Check if attachRole expects distinct logic or if simple relation works
            // User model has attachRole method
            if (method_exists($this->user, 'attachRole')) {
                 $this->user->attachRole($role);
            }
        }

        // Create Provider record
        if (class_exists(\Database\Factories\ProviderFactory::class)) {
            \App\Models\Provider::factory()->create([
                'user_id' => $this->user->id,
                'tenant_id' => $this->tenantId,
            ]);
        } else {
             // Fallback
             \App\Models\Provider::create([
                 'user_id' => $this->user->id,
                 'tenant_id' => $this->tenantId,
                 'document' => '12345678901', // Dummy
                 'type' => 'individual',
             ]);
        }

        $this->actingAs($this->user);
    }

    /** @test */
    public function it_can_access_analytics_predictions()
    {
        // Arrange: Create some historical data
        // 3 months of revenue
        Invoice::factory()->create([
            'tenant_id' => $this->tenantId,
            'total' => 1000,
            'status' => \App\Enums\InvoiceStatus::PAID,
            'created_at' => now()->subMonths(3)
        ]);
        Invoice::factory()->create([
            'tenant_id' => $this->tenantId,
            'total' => 1200,
            'status' => \App\Enums\InvoiceStatus::PAID,
            'created_at' => now()->subMonths(2)
        ]);
        Invoice::factory()->create([
            'tenant_id' => $this->tenantId,
            'total' => 1400,
            'status' => \App\Enums\InvoiceStatus::PAID,
            'created_at' => now()->subMonths(1)
        ]);

        $response = $this->get(route('provider.analytics.predictions'));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'next_month_revenue' => [
                    'predicted',
                    'confidence',
                    'method',
                    'trend',
                    'slope',
                    'intercept'
                ]
            ]);

        // 1000, 1200, 1400 -> Linear growth. Slope should be roughly 200.
        // Next month (4th point) should be around 1600.
        $predicted = $response->json('next_month_revenue.predicted');
        $this->assertTrue($predicted > 1400, "Predicted value ($predicted) should be > 1400");
    }

    /** @test */
    public function it_can_access_customer_segments()
    {
        // Arrange: Create customers with specific behaviors
        // Customer 1: Champion (Recent, Frequent, High Value)
        $c1 = Customer::factory()->create(['tenant_id' => $this->tenantId]);
        Invoice::factory()->count(10)->create([
            'tenant_id' => $this->tenantId,
            'customer_id' => $c1->id,
            'status' => \App\Enums\InvoiceStatus::PAID,
            'total' => 5000,
            'created_at' => now()
        ]);
        // Also need budgets to satisfy "active" check or simple query
        Budget::factory()->create([
            'tenant_id' => $this->tenantId,
            'customer_id' => $c1->id
        ]);

        $response = $this->get(route('provider.analytics.customers'));

        if ($response->status() !== 200) {
            dump($response->json());
        }

        $response->assertStatus(200)
             ->assertJsonStructure([
                 'segments' => [
                     // 'Champions' might be present
                 ],
                 'main_segment'
             ]);

        $segments = $response->json('segments');
        $this->assertArrayHasKey('Champions', $segments);
    }
}
