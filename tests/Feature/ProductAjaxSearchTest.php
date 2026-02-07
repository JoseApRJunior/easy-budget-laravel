<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Database\Factories\ProductFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductAjaxSearchTest extends TestCase
{
    use RefreshDatabase;

    protected $tenant;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $providerRole = Role::firstOrCreate(['name' => 'provider'], ['description' => 'Provider']);
        $this->user->roles()->attach($providerRole->id, ['tenant_id' => $this->tenant->id]);
    }

    public function test_normalizes_min_price_with_brl_mask()
    {
        // p1 abaixo do limite, p2 acima do limite
        $p1 = ProductFactory::new()->withTenant()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Barato',
            'price' => 10.00,
        ]);
        $p2 = ProductFactory::new()->withTenant()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Caro',
            'price' => 20.00,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('api.ajax.products.search', [
                'min_price' => 'R$ 12,00',
            ]));

        $response->assertStatus(200);
        $json = $response->json();
        $this->assertTrue($json['success']);

        $items = $json['data']['data'] ?? [];
        $names = array_map(fn ($i) => $i['name'], $items);
        $this->assertContains('Caro', $names);
        $this->assertNotContains('Barato', $names);
    }

    public function test_normalizes_max_price_with_brl_mask()
    {
        $p1 = ProductFactory::new()->withTenant()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Barato',
            'price' => 10.00,
        ]);
        $p2 = ProductFactory::new()->withTenant()->create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Caro',
            'price' => 20.00,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('api.ajax.products.search', [
                'max_price' => 'R$ 12,00',
            ]));

        $response->assertStatus(200);
        $json = $response->json();
        $this->assertTrue($json['success']);

        $items = $json['data']['data'] ?? [];
        $names = array_map(fn ($i) => $i['name'], $items);
        $this->assertContains('Barato', $names);
        $this->assertNotContains('Caro', $names);
    }
}
