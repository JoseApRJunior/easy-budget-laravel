<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\PlanSubscription;
use App\Models\Provider;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProviderMiddlewareTrialExpiredTest extends TestCase
{
    use RefreshDatabase;

    private User     $user;
    private Provider $provider;
    private Tenant   $tenant;
    private Plan     $trialPlan;

    protected function setUp(): void
    {
        parent::setUp();

        // Criar tenant
        $this->tenant = Tenant::create( [
            'name'      => 'Test Tenant',
            'is_active' => true,
        ] );

        // Criar usuário
        $this->user = User::create( [
            'tenant_id' => $this->tenant->id,
            'email'     => 'provider@test.com',
            'password'  => bcrypt( 'password' ),
            'is_active' => true,
        ] );

        // Criar role provider
        $role = \App\Models\Role::firstOrCreate(
            [ 'name' => 'provider' ],
            [ 'description' => 'Provider Role' ],
        );

        // Associar role ao usuário
        UserRole::create( [
            'user_id'   => $this->user->id,
            'role_id'   => $role->id,
            'tenant_id' => $this->tenant->id,
        ] );

        // Criar provider
        $this->provider = Provider::create( [
            'tenant_id'      => $this->tenant->id,
            'user_id'        => $this->user->id,
            'terms_accepted' => true,
        ] );

        // Criar plano trial
        $this->trialPlan = Plan::create( [
            'name'        => 'Trial',
            'slug'        => 'trial',
            'description' => 'Trial Plan',
            'price'       => 0,
            'status'      => true,
            'max_budgets' => 10,
            'max_clients' => 50,
            'features'    => json_encode( [ 'Plano experimental gratuito', 'Período de teste: 7 dias' ] ),
        ] );
    }

    /**
     * Teste: Usuário com trial expirado pode acessar dashboard
     */
    public function test_user_with_expired_trial_can_access_dashboard()
    {
        // Criar assinatura com trial expirado
        PlanSubscription::create( [
            'tenant_id'          => $this->tenant->id,
            'provider_id'        => $this->provider->id,
            'plan_id'            => $this->trialPlan->id,
            'status'             => 'active',
            'transaction_amount' => 0,
            'payment_method'     => 'trial',
            'start_date'         => now()->subDays( 10 ),
            'end_date'           => now()->subDays( 3 ), // Trial expirado
        ] );

        $this->actingAs( $this->user )
            ->get( route( 'provider.dashboard' ) )
            ->assertStatus( 200 )
            ->assertSessionHas( 'trial_expired_warning', true );
    }

    /**
     * Teste: Usuário com trial expirado pode acessar settings
     */
    public function test_user_with_expired_trial_can_access_settings()
    {
        PlanSubscription::create( [
            'tenant_id'          => $this->tenant->id,
            'provider_id'        => $this->provider->id,
            'plan_id'            => $this->trialPlan->id,
            'status'             => 'active',
            'transaction_amount' => 0,
            'payment_method'     => 'trial',
            'start_date'         => now()->subDays( 10 ),
            'end_date'           => now()->subDays( 3 ),
        ] );

        $this->actingAs( $this->user )
            ->get( route( 'settings.index' ) )
            ->assertStatus( 200 )
            ->assertSessionHas( 'trial_expired_warning', true );
    }

    /**
     * Teste: Usuário com trial expirado é redirecionado ao acessar customers
     */
    public function test_user_with_expired_trial_redirected_from_customers()
    {
        PlanSubscription::create( [
            'tenant_id'          => $this->tenant->id,
            'provider_id'        => $this->provider->id,
            'plan_id'            => $this->trialPlan->id,
            'status'             => 'active',
            'transaction_amount' => 0,
            'payment_method'     => 'trial',
            'start_date'         => now()->subDays( 10 ),
            'end_date'           => now()->subDays( 3 ),
        ] );

        $this->actingAs( $this->user )
            ->get( route( 'provider.customers.index' ) )
            ->assertRedirect( route( 'plans.index' ) )
            ->assertSessionHas( 'warning' );
    }

    /**
     * Teste: Usuário com trial expirado é redirecionado ao acessar budgets
     */
    public function test_user_with_expired_trial_redirected_from_budgets()
    {
        PlanSubscription::create( [
            'tenant_id'          => $this->tenant->id,
            'provider_id'        => $this->provider->id,
            'plan_id'            => $this->trialPlan->id,
            'status'             => 'active',
            'transaction_amount' => 0,
            'payment_method'     => 'trial',
            'start_date'         => now()->subDays( 10 ),
            'end_date'           => now()->subDays( 3 ),
        ] );

        $this->actingAs( $this->user )
            ->get( route( 'provider.budgets.index' ) )
            ->assertRedirect( route( 'plans.index' ) )
            ->assertSessionHas( 'warning' );
    }

    /**
     * Teste: Usuário com trial expirado é redirecionado ao acessar invoices
     */
    public function test_user_with_expired_trial_redirected_from_invoices()
    {
        PlanSubscription::create( [
            'tenant_id'          => $this->tenant->id,
            'provider_id'        => $this->provider->id,
            'plan_id'            => $this->trialPlan->id,
            'status'             => 'active',
            'transaction_amount' => 0,
            'payment_method'     => 'trial',
            'start_date'         => now()->subDays( 10 ),
            'end_date'           => now()->subDays( 3 ),
        ] );

        $this->actingAs( $this->user )
            ->get( route( 'provider.invoices.index' ) )
            ->assertRedirect( route( 'plans.index' ) )
            ->assertSessionHas( 'warning' );
    }

    /**
     * Teste: Usuário com trial expirado pode acessar plans
     */
    public function test_user_with_expired_trial_can_access_plans()
    {
        PlanSubscription::create( [
            'tenant_id'          => $this->tenant->id,
            'provider_id'        => $this->provider->id,
            'plan_id'            => $this->trialPlan->id,
            'status'             => 'active',
            'transaction_amount' => 0,
            'payment_method'     => 'trial',
            'start_date'         => now()->subDays( 10 ),
            'end_date'           => now()->subDays( 3 ),
        ] );

        $this->actingAs( $this->user )
            ->get( route( 'plans.index' ) )
            ->assertStatus( 200 );
    }

    /**
     * Teste: Usuário com trial ativo pode acessar todas as rotas
     */
    public function test_user_with_active_trial_can_access_all_routes()
    {
        PlanSubscription::create( [
            'tenant_id'          => $this->tenant->id,
            'provider_id'        => $this->provider->id,
            'plan_id'            => $this->trialPlan->id,
            'status'             => 'active',
            'transaction_amount' => 0,
            'payment_method'     => 'trial',
            'start_date'         => now()->subDays( 2 ),
            'end_date'           => now()->addDays( 5 ), // Trial ainda ativo
        ] );

        // Dashboard
        $this->actingAs( $this->user )
            ->get( route( 'provider.dashboard' ) )
            ->assertStatus( 200 )
            ->assertSessionMissing( 'trial_expired_warning' );

        // Customers
        $this->actingAs( $this->user )
            ->get( route( 'provider.customers.index' ) )
            ->assertStatus( 200 )
            ->assertSessionMissing( 'trial_expired_warning' );

        // Budgets
        $this->actingAs( $this->user )
            ->get( route( 'provider.budgets.index' ) )
            ->assertStatus( 200 )
            ->assertSessionMissing( 'trial_expired_warning' );
    }

}
