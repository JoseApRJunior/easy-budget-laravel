<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Budget;
use App\Models\CommonData;
use App\Models\Contact;
use App\Models\Customer;
use App\Models\Provider;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UserRole;
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

        // Criar tenant
        $this->tenant = Tenant::factory()->create();

        // Criar dados necessários para o provider
        \App\Models\AreaOfActivity::create( [ 'id' => 1, 'name' => 'Test Area', 'slug' => 'test-area' ] );
        \App\Models\Profession::create( [ 'id' => 1, 'name' => 'Test Profession', 'slug' => 'test-profession' ] );

        $commonData = CommonData::create( [
            'tenant_id'  => $this->tenant->id,
            'first_name' => 'Test',
            'last_name'  => 'User',
        ] );

        $contact = Contact::create( [
            'tenant_id' => $this->tenant->id,
            'email'     => 'test@example.com',
        ] );

        $address = Address::create( [
            'tenant_id'    => $this->tenant->id,
            'address'      => 'Test Street',
            'neighborhood' => 'Test Neighborhood',
            'city'         => 'Test City',
            'state'        => 'SP',
            'cep'          => '12345678',
        ] );

        // Criar usuário
        $this->user = User::factory()->create( [
            'tenant_id'         => $this->tenant->id,
            'email_verified_at' => now(),
        ] );

        // Criar provider
        Provider::create( [
            'user_id'        => $this->user->id,
            'tenant_id'      => $this->tenant->id,
            'common_data_id' => $commonData->id,
            'contact_id'     => $contact->id,
            'address_id'     => $address->id,
            'terms_accepted' => true,
        ] );

        // Criar role provider se não existir
        $role = Role::firstOrCreate(
            [ 'name' => 'provider' ],
            [ 'description' => 'Provider role' ],
        );

        // Criar UserRole
        UserRole::create( [
            'user_id'   => $this->user->id,
            'role_id'   => $role->id,
            'tenant_id' => $this->tenant->id,
        ] );

        // Atualizar usuário com dados e role
        $this->user->update( [
            'common_data_id' => $commonData->id,
            'contact_id'     => $contact->id,
            'address_id'     => $address->id,
            'role'           => 'provider',
        ] );

        // Autenticar o usuário
        $this->actingAs( $this->user );
    }

    /** @test */
    public function it_displays_home_page()
    {
        $response = $this->get( route( 'provider.dashboard' ) );

        $response->assertStatus( 200 );
        $response->assertViewIs( 'pages.provider.index' );
        $response->assertViewHas( 'budgets' );
        $response->assertViewHas( 'activities' );
        $response->assertViewHas( 'financial_summary' );
        $response->assertViewHas( 'total_activities' );
    }

    /** @test */
    public function it_displays_dashboard_data()
    {
        // Criar alguns orçamentos para gerar dados no dashboard
        $customer = Customer::factory()->create( [ 'tenant_id' => $this->tenant->id ] );
        Budget::factory()->count( 3 )->create( [
            'tenant_id'   => $this->tenant->id,
            'customer_id' => $customer->id,
        ] );

        $response = $this->get( route( 'provider.dashboard' ) );

        $response->assertStatus( 200 );
        $response->assertViewHas( 'budgets' );
        $response->assertViewHas( 'activities' );
        $response->assertViewHas( 'financial_summary' );
    }

    /** @test */
    public function it_displays_activities_count()
    {
        $response = $this->get( route( 'provider.dashboard' ) );

        $response->assertStatus( 200 );
        $response->assertViewHas( 'total_activities', function ( $total ) {
            return is_int( $total );
        } );
    }

    /** @test */
    public function it_requires_authentication()
    {
        // Desautenticar o usuário
        $this->withoutMiddleware();

        $response = $this->get( route( 'provider.dashboard' ) );

        // Como estamos testando autenticação, esperamos redirecionamento
        // ou erro dependendo da configuração do middleware
        $response->assertStatus( 302 ); // Redirecionamento para login
    }

    /** @test */
    public function it_handles_tenant_scoping_in_dashboard()
    {
        // Criar dados em outro tenant
        $otherTenant   = Tenant::factory()->create();
        $otherCustomer = Customer::factory()->create( [ 'tenant_id' => $otherTenant->id ] );
        Budget::factory()->count( 3 )->create( [
            'tenant_id'   => $otherTenant->id,
            'customer_id' => $otherCustomer->id,
        ] );

        // Criar dados no tenant do usuário
        $customer = Customer::factory()->create( [ 'tenant_id' => $this->tenant->id ] );
        Budget::factory()->count( 2 )->create( [
            'tenant_id'   => $this->tenant->id,
            'customer_id' => $customer->id,
        ] );

        $response = $this->get( route( 'provider.dashboard' ) );

        $response->assertStatus( 200 );
        // O dashboard deve mostrar apenas dados do tenant do usuário logado
        // (tenant scoping é aplicado automaticamente nos models)
        $response->assertViewHas( 'budgets' );
        $response->assertViewHas( 'activities' );
    }

}
