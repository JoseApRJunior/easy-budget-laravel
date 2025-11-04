<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Address;
use App\Models\CommonData;
use App\Models\Contact;
use App\Models\Provider;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProviderBusinessControllerTest extends TestCase
{
    use RefreshDatabase;

    private User     $authenticatedUser;
    private Provider $testProvider;
    private Tenant   $testTenant;

    private const VALID_BUSINESS_DATA = [
        'first_name'          => 'João',
        'last_name'           => 'Silva',
        'birth_date'          => '1990-01-01',
        'email_personal'      => 'joao@example.com',
        'phone_personal'      => '(11) 99999-9999',
        'company_name'        => 'Empresa Teste Ltda',
        'cnpj'                => '12.345.678/0001-90',
        'area_of_activity_id' => 1,
        'profession_id'       => 1,
        'description'         => 'Descrição da empresa',
        'email_business'      => 'contato@empresa.com',
        'phone_business'      => '(11) 88888-8888',
        'website'             => 'https://empresa.com',
        'address'             => 'Rua Teste',
        'address_number'      => '123',
        'neighborhood'        => 'Centro',
        'city'                => 'São Paulo',
        'state'               => 'SP',
        'cep'                 => '01234-567',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        // Desabilitar middleware provider para focar na lógica do controller
        $this->withoutMiddleware( \App\Http\Middleware\ProviderMiddleware::class);

        $this->testTenant        = Tenant::factory()->create();
        $this->authenticatedUser = User::factory()->create( [
            'tenant_id'         => $this->testTenant->id,
            'email_verified_at' => now(),
        ] );

        // Criar registros necessários para validação
        DB::table( 'areas_of_activity' )->insert( [
            'id'         => 1,
            'slug'       => 'construcao-civil',
            'name'       => 'Construção Civil',
            'is_active'  => true,
            'created_at' => now(),
            'updated_at' => now(),
        ] );

        DB::table( 'professions' )->insert( [
            'id'         => 1,
            'slug'       => 'administrador',
            'name'       => 'Administrador',
            'is_active'  => true,
            'created_at' => now(),
            'updated_at' => now(),
        ] );

        $this->createProviderRoleAndAssociate();
        $this->createProviderWithRelationships();
    }

    private function createProviderRoleAndAssociate(): void
    {
        $providerRole = Role::firstOrCreate(
            [ 'name' => 'provider' ],
            [ 'description' => 'Provedor de serviços - acesso completo' ],
        );

        UserRole::factory()->create( [
            'user_id'   => $this->authenticatedUser->id,
            'role_id'   => $providerRole->id,
            'tenant_id' => $this->testTenant->id,
        ] );
    }

    private function createProviderWithRelationships(): void
    {
        // Criar dados relacionados primeiro
        $commonData = CommonData::factory()->create( [
            'tenant_id' => $this->testTenant->id,
        ] );

        $contact = Contact::factory()->create( [
            'tenant_id' => $this->testTenant->id,
        ] );

        $address = Address::factory()->create( [
            'tenant_id' => $this->testTenant->id,
        ] );

        $this->testProvider = Provider::factory()->create( [
            'tenant_id'      => $this->testTenant->id,
            'user_id'        => $this->authenticatedUser->id,
            'common_data_id' => $commonData->id,
            'contact_id'     => $contact->id,
            'address_id'     => $address->id,
        ] );
    }

    public function test_edit_page_is_displayed(): void
    {
        $response = $this
            ->actingAs( $this->authenticatedUser )
            ->get( '/provider/business/edit' );

        $response->assertOk();
        $response->assertViewHas( 'provider' );
        $response->assertViewHas( 'areas_of_activity' );
        $response->assertViewHas( 'professions' );
    }

    public function test_edit_redirects_when_provider_not_found(): void
    {
        $this->testProvider->delete();

        $response = $this
            ->actingAs( $this->authenticatedUser )
            ->get( '/provider/business/edit' );

        $response->assertRedirect( '/provider' );
        $response->assertSessionHas( 'error', 'Provider não encontrado' );
    }

    public function test_update_business_datas_successfully(): void
    {
        Storage::fake( 'public' );

        $updateData = self::VALID_BUSINESS_DATA;

        $response = $this
            ->actingAs( $this->authenticatedUser )
            ->put( '/provider/business', $updateData );

        $response->assertRedirect( '/settings' );
        $response->assertSessionHas( 'success', 'Dados empresariais atualizados com sucesso!' );

        $this->testProvider->refresh();
        $this->assertEquals( 'João', $this->testProvider->commonData->first_name );
        $this->assertEquals( 'Empresa Teste Ltda', $this->testProvider->commonData->company_name );
        $this->assertEquals( 'contato@empresa.com', $this->testProvider->contact->email_business );
        $this->assertEquals( 'Rua Teste', $this->testProvider->address->address );
    }

    public function test_update_with_logo_upload(): void
    {
        Storage::fake( 'public' );

        $logo       = UploadedFile::fake()->image( 'logo.jpg' );
        $updateData = array_merge( self::VALID_BUSINESS_DATA, [ 'logo' => $logo ] );

        $response = $this
            ->actingAs( $this->authenticatedUser )
            ->put( '/provider/business', $updateData );

        $response->assertRedirect( '/settings' );
        $response->assertSessionHas( 'success', 'Dados empresariais atualizados com sucesso!' );

        $this->authenticatedUser->refresh();
        $this->assertNotNull( $this->authenticatedUser->logo );
        $this->assertTrue( Storage::disk( 'public' )->exists( $this->authenticatedUser->logo ) );
    }

    public function test_update_with_invalid_data(): void
    {
        $invalidData = array_merge( self::VALID_BUSINESS_DATA, [
            'first_name'     => '', // Campo obrigatório vazio
            'email_business' => 'invalid-email', // Email inválido
        ] );

        $response = $this
            ->actingAs( $this->authenticatedUser )
            ->put( '/provider/business', $invalidData );

        $response->assertRedirect( '/settings' );
        $response->assertSessionHasErrors( [ 'first_name', 'email_business' ] );
    }

    public function test_update_handles_exception_gracefully(): void
    {
        $this->testProvider->update( [ 'common_data_id' => null ] );

        $response = $this
            ->actingAs( $this->authenticatedUser )
            ->put( '/provider/business', self::VALID_BUSINESS_DATA );

        $response->assertRedirect( '/provider/business/edit' );
        $response->assertSessionHas( 'error' );
    }

    public function test_update_only_modifies_changed_fields(): void
    {
        // Criar dados relacionados primeiro
        $commonData = CommonData::factory()->create( [
            'tenant_id'    => $this->testTenant->id,
            'first_name'   => 'Nome Original',
            'company_name' => 'Empresa Original Ltda'
        ] );
        $contact    = Contact::factory()->create( [ 'tenant_id' => $this->testTenant->id ] );
        $address    = Address::factory()->create( [ 'tenant_id' => $this->testTenant->id ] );

        // Atualizar provider com os IDs
        $this->testProvider->update( [
            'common_data_id' => $commonData->id,
            'contact_id'     => $contact->id,
            'address_id'     => $address->id,
        ] );

        $originalFirstName   = $this->testProvider->commonData->first_name;
        $originalCompanyName = $this->testProvider->commonData->company_name;

        $updateData = [
            'first_name'          => 'Novo Nome',
            'last_name'           => $this->testProvider->commonData->last_name,
            'company_name'        => $originalCompanyName,
            'cnpj'                => $this->testProvider->commonData->cnpj,
            'area_of_activity_id' => $this->testProvider->commonData->area_of_activity_id ?? 1,
            'profession_id'       => $this->testProvider->commonData->profession_id ?? 1,
            'description'         => $this->testProvider->commonData->description,
            'email_business'      => $this->testProvider->contact->email_business,
            'phone_business'      => $this->testProvider->contact->phone_business,
            'website'             => $this->testProvider->contact->website,
            'address'             => $this->testProvider->address->address,
            'address_number'      => $this->testProvider->address->address_number,
            'neighborhood'        => $this->testProvider->address->neighborhood,
            'city'                => $this->testProvider->address->city,
            'state'               => $this->testProvider->address->state,
            'cep'                 => $this->testProvider->address->cep,
        ];

        $response = $this
            ->actingAs( $this->authenticatedUser )
            ->put( '/provider/business', $updateData );

        $response->assertRedirect( '/settings' );

        $this->testProvider->refresh();
        $this->assertEquals( 'Novo Nome', $this->testProvider->commonData->first_name );
        $this->assertEquals( $originalCompanyName, $this->testProvider->commonData->company_name );
    }

}
