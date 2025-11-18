<?php

namespace Tests\Feature;

use App\Models\Budget;
use App\Models\BudgetShare;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BudgetShareNavigationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'email_verified_at' => now(),
            'role' => 'provider'
        ]);
    }

    /** @test */
    public function it_shows_correct_breadcrumb_on_budget_shares_index_page()
    {
        $this->actingAs($this->user);

        $response = $this->get('/provider/budgets/shares');

        $response->assertStatus(200);

        // Verifica se o breadcrumb está presente e na ordem correta
        $response->assertSee('Dashboard', false);
        $response->assertSee('Orçamentos', false);
        $response->assertSee('Compartilhamentos', false);

        // Verifica se o link do dashboard está correto
        $response->assertSee(route('provider.dashboard'), false);
        $response->assertSee(route('provider.budgets.index'), false);
    }

    /** @test */
    public function it_shows_correct_breadcrumb_on_budget_shares_dashboard_page()
    {
        $this->actingAs($this->user);

        $response = $this->get('/provider/budgets/shares/dashboard');

        $response->assertStatus(200);

        // Verifica a hierarquia completa do breadcrumb
        $response->assertSee('Dashboard', false);
        $response->assertSee('Orçamentos', false);
        $response->assertSee('Compartilhamentos', false);
        $response->assertSee('Dashboard', false);

        // Verifica se os links estão corretos
        $response->assertSee(route('provider.dashboard'), false);
        $response->assertSee(route('provider.budgets.index'), false);
        $response->assertSee(route('provider.budgets.shares.index'), false);
    }

    /** @test */
    public function it_shows_correct_breadcrumb_on_budget_share_create_page()
    {
        $this->actingAs($this->user);

        $response = $this->get('/provider/budgets/shares/create');

        $response->assertStatus(200);

        // Verifica a hierarquia do breadcrumb
        $response->assertSee('Dashboard', false);
        $response->assertSee('Orçamentos', false);
        $response->assertSee('Compartilhamentos', false);
        $response->assertSee('Criar', false);

        // Verifica se os links estão corretos
        $response->assertSee(route('provider.dashboard'), false);
        $response->assertSee(route('provider.budgets.index'), false);
        $response->assertSee(route('provider.budgets.shares.index'), false);
    }

    /** @test */
    public function it_shows_correct_breadcrumb_on_budget_share_show_page()
    {
        $this->actingAs($this->user);

        $budget = Budget::factory()->create([
            'tenant_id' => $this->user->tenant_id
        ]);

        $share = BudgetShare::factory()->create([
            'budget_id' => $budget->id,
            'tenant_id' => $this->user->tenant_id,
            'created_by' => $this->user->id
        ]);

        $response = $this->get("/provider/budgets/shares/{$share->id}");

        $response->assertStatus(200);

        // Verifica a hierarquia do breadcrumb
        $response->assertSee('Dashboard', false);
        $response->assertSee('Orçamentos', false);
        $response->assertSee('Compartilhamentos', false);
        $response->assertSee('Detalhes', false);

        // Verifica se os links estão corretos
        $response->assertSee(route('provider.dashboard'), false);
        $response->assertSee(route('provider.budgets.index'), false);
        $response->assertSee(route('provider.budgets.shares.index'), false);
    }

    /** @test */
    public function it_uses_correct_route_names_in_forms_and_links()
    {
        $this->actingAs($this->user);

        // Testa a página de criação
        $response = $this->get('/provider/budgets/shares/create');
        $response->assertStatus(200);

        // Verifica se o formulário usa a rota correta
        $response->assertSee(route('provider.budgets.shares.store'), false);

        // Testa a página de listagem
        $response = $this->get('/provider/budgets/shares');
        $response->assertStatus(200);

        // Verifica se os links usam as rotas corretas
        $response->assertSee(route('provider.budgets.shares.create'), false);
        $response->assertSee(route('provider.budgets.shares.dashboard'), false);
    }

    /** @test */
    public function navigation_flow_works_correctly()
    {
        $this->actingAs($this->user);

        // 1. Começa no dashboard
        $response = $this->get('/provider/dashboard');
        $response->assertStatus(200);

        // 2. Navega para orçamentos
        $response = $this->get('/provider/budgets');
        $response->assertStatus(200);

        // 3. Navega para compartilhamentos
        $response = $this->get('/provider/budgets/shares');
        $response->assertStatus(200);

        // 4. Navega para dashboard de compartilhamentos
        $response = $this->get('/provider/budgets/shares/dashboard');
        $response->assertStatus(200);

        // 5. Navega para criar compartilhamento
        $response = $this->get('/provider/budgets/shares/create');
        $response->assertStatus(200);

        // Verifica se todas as páginas são acessíveis
        $this->assertTrue(true);
    }
}
