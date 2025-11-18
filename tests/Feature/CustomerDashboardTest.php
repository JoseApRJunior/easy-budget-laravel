<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CommonData;
use App\Models\Contact;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use Illuminate\Support\Facades\DB;

/**
 * Testes de feature para o Dashboard de Clientes
 * 
 * @covers \App\Http\Controllers\CustomerController::dashboard
 */
class CustomerDashboardTest extends TestCase
{
    use DatabaseTransactions;

    protected $user;
    protected $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        // Criar tenant e usuário para os testes
        $this->tenant = Tenant::factory()->create();
        $this->user   = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'type' => 'provider',
        ]);

        // Autenticar o usuário
        $this->actingAs($this->user);
    }

    /**
     * Testa que o dashboard de clientes carrega corretamente com estatísticas
     */
    public function test_dashboard_loads_with_customer_statistics(): void
    {
        // Criar clientes de teste
        $this->createTestCustomers();

        // Fazer requisição para o dashboard
        $response = $this->get(route('provider.customers.dashboard'));

        // Verificar que a resposta é bem-sucedida
        $response->assertStatus(200);
        
        // Verificar que a view está sendo usada corretamente
        $response->assertViewIs('pages.customer.dashboard');
        
        // Verificar que as estatísticas estão presentes na view
        $response->assertViewHas('stats');
        
        // Verificar estrutura das estatísticas
        $stats = $response->viewData('stats');
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_customers', $stats);
        $this->assertArrayHasKey('active_customers', $stats);
        $this->assertArrayHasKey('inactive_customers', $stats);
        $this->assertArrayHasKey('recent_customers', $stats);
        
        // Verificar tipos dos dados
        $this->assertIsInt($stats['total_customers']);
        $this->assertIsInt($stats['active_customers']);
        $this->assertIsInt($stats['inactive_customers']);
        $this->assertInstanceOf(\Illuminate\Support\Collection::class, $stats['recent_customers']);
    }

    /**
     * Testa dashboard com tenant vazio (sem clientes)
     */
    public function test_dashboard_with_empty_tenant(): void
    {
        // Fazer requisição para o dashboard sem clientes
        $response = $this->get(route('provider.customers.dashboard'));

        // Verificar que a resposta é bem-sucedida
        $response->assertStatus(200);
        
        // Verificar estatísticas vazias
        $stats = $response->viewData('stats');
        $this->assertEquals(0, $stats['total_customers']);
        $this->assertEquals(0, $stats['active_customers']);
        $this->assertEquals(0, $stats['inactive_customers']);
        $this->assertTrue($stats['recent_customers']->isEmpty());
    }

    /**
     * Testa isolamento entre tenants (usuário só vê clientes do seu tenant)
     */
    public function test_tenant_isolation(): void
    {
        // Criar outro tenant e clientes
        $otherTenant = Tenant::factory()->create();
        $otherUser = User::factory()->create([
            'tenant_id' => $otherTenant->id,
            'type' => 'provider',
        ]);

        // Criar clientes no outro tenant
        Customer::factory()->count(5)->create([
            'tenant_id' => $otherTenant->id,
        ]);

        // Criar clientes no tenant do usuário autenticado
        $this->createTestCustomers();

        // Fazer requisição com o usuário autenticado
        $response = $this->get(route('provider.customers.dashboard'));

        // Verificar que só vê clientes do seu tenant
        $stats = $response->viewData('stats');
        $this->assertEquals(3, $stats['total_customers']); // Apenas os 3 do seu tenant
        
        // Verificar que os clientes recentes são do tenant correto
        $recentCustomers = $stats['recent_customers'];
        foreach ($recentCustomers as $customer) {
            $this->assertEquals($this->tenant->id, $customer->tenant_id);
        }
    }

    /**
     * Testa que usuário não autenticado é redirecionado
     */
    public function test_unauthenticated_user_is_redirected(): void
    {
        // Deslogar o usuário
        auth()->logout();

        // Fazer requisição para o dashboard
        $response = $this->get(route('provider.customers.dashboard'));

        // Verificar que foi redirecionado para login
        $response->assertRedirect('/login');
    }

    /**
     * Testa presença de elementos HTML importantes no dashboard
     */
    public function test_dashboard_contains_essential_elements(): void
    {
        // Criar clientes de teste
        $this->createTestCustomers();

        // Fazer requisição para o dashboard
        $response = $this->get(route('provider.customers.dashboard'));

        // Verificar elementos principais
        $response->assertSee('Dashboard de Clientes');
        $response->assertSee('Total de Clientes');
        $response->assertSee('Clientes Ativos');
        $response->assertSee('Clientes Inativos');
        $response->assertSee('Taxa de Atividade');
        $response->assertSee('Clientes Recentes');
        
        // Verificar links de navegação
        $response->assertSee(route('provider.customers.index'));
        $response->assertSee(route('provider.customers.create'));
        $response->assertSee(route('provider.reports.customers'));
    }

    /**
     * Testa cálculo correto da taxa de atividade
     */
    public function test_activity_rate_calculation(): void
    {
        // Criar clientes com diferentes status
        Customer::factory()->count(8)->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
        ]);

        Customer::factory()->count(2)->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => false,
        ]);

        // Fazer requisição para o dashboard
        $response = $this->get(route('provider.customers.dashboard'));

        // Verificar cálculo da taxa de atividade
        $stats = $response->viewData('stats');
        $this->assertEquals(10, $stats['total_customers']);
        $this->assertEquals(8, $stats['active_customers']);
        $this->assertEquals(2, $stats['inactive_customers']);
        
        // A taxa de atividade deve ser 80% (8 ativos de 10 total)
        $expectedRate = 80.0;
        $actualRate = ($stats['active_customers'] / $stats['total_customers']) * 100;
        $this->assertEquals($expectedRate, $actualRate);
    }

    /**
     * Cria clientes de teste para os testes
     */
    private function createTestCustomers(): void
    {
        // Criar clientes ativos
        Customer::factory()->count(2)->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => true,
        ])->each(function ($customer) {
            // Adicionar dados relacionados
            CommonData::factory()->create([
                'customer_id' => $customer->id,
                'tenant_id' => $this->tenant->id,
            ]);
            
            Contact::factory()->create([
                'customer_id' => $customer->id,
                'tenant_id' => $this->tenant->id,
            ]);
        });

        // Criar cliente inativo
        Customer::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_active' => false,
        ]);
    }
}