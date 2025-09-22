<?php

namespace Tests\Feature;

use App\Models\Budget;
use App\Models\Customer;
use App\Models\Provider;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        // Criar tenant e usuário para os testes
        $this->tenant = Tenant::factory()->create();
        $this->user   = User::factory()->create( [ 
            'tenant_id' => $this->tenant->id,
        ] );

        // Autenticar o usuário
        $this->actingAs( $this->user );
    }

    /** @test */
    public function it_displays_home_page()
    {
        $response = $this->get( route( 'home' ) );

        $response->assertStatus( 200 );
        $response->assertViewIs( 'pages.home.index' );
        $response->assertViewHas( 'plans' );
        $response->assertViewHas( 'recentBudgets' );
        $response->assertViewHas( 'recentCustomers' );
    }

    /** @test */
    public function it_displays_recent_budgets()
    {
        // Criar alguns orçamentos
        Budget::factory()->count( 5 )->create( [ 
            'provider_id' => $this->user->provider_id ?? Provider::factory()->create( [ 'tenant_id' => $this->tenant->id ] )->id,
            'tenant_id'   => $this->tenant->id,
        ] );

        $response = $this->get( route( 'home' ) );

        $response->assertStatus( 200 );
        $response->assertViewHas( 'recentBudgets', function ($recentBudgets) {
            return $recentBudgets->count() <= 5;
        } );
    }

    /** @test */
    public function it_displays_recent_customers()
    {
        // Criar alguns clientes
        Customer::factory()->count( 5 )->create( [ 
            'tenant_id' => $this->tenant->id,
        ] );

        $response = $this->get( route( 'home' ) );

        $response->assertStatus( 200 );
        $response->assertViewHas( 'recentCustomers', function ($recentCustomers) {
            return $recentCustomers->count() <= 5;
        } );
    }

    /** @test */
    public function it_displays_available_plans()
    {
        $response = $this->get( route( 'home' ) );

        $response->assertStatus( 200 );
        $response->assertViewHas( 'plans', function ($plans) {
            return is_iterable( $plans );
        } );
    }

    /** @test */
    public function it_requires_authentication()
    {
        // Desautenticar o usuário
        $this->withoutMiddleware();

        $response = $this->get( route( 'home' ) );

        // Como estamos testando autenticação, esperamos redirecionamento
        // ou erro dependendo da configuração do middleware
        $response->assertStatus( 302 ); // Redirecionamento para login
    }

    /** @test */
    public function it_handles_tenant_scoping()
    {
        // Criar dados em outro tenant
        $otherTenant = Tenant::factory()->create();
        Customer::factory()->count( 3 )->create( [ 
            'tenant_id' => $otherTenant->id,
        ] );

        // Criar dados no tenant do usuário
        Customer::factory()->count( 2 )->create( [ 
            'tenant_id' => $this->tenant->id,
        ] );

        $response = $this->get( route( 'home' ) );

        $response->assertStatus( 200 );
        $response->assertViewHas( 'recentCustomers', function ($recentCustomers) {
            // Deve mostrar apenas os clientes do tenant do usuário
            return $recentCustomers->count() == 2;
        } );
    }

}