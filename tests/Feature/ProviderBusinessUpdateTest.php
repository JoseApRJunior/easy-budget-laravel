<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Provider;
use App\Models\CommonData;
use App\Models\Contact;
use App\Models\Address;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProviderBusinessUpdateTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Provider $provider;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['email_verified_at' => now()]);

        \App\Models\AreaOfActivity::create(['id' => 1, 'name' => 'Test Area', 'slug' => 'test-area']);
        \App\Models\Profession::create(['id' => 1, 'name' => 'Test Profession', 'slug' => 'test-profession']);

        $commonData = CommonData::create([
            'tenant_id' => $this->user->tenant_id,
            'first_name' => 'Test',
            'last_name' => 'User',
        ]);

        $contact = Contact::create([
            'tenant_id' => $this->user->tenant_id,
            'email' => 'test@example.com',
        ]);
        $address = Address::create([
            'tenant_id' => $this->user->tenant_id,
            'address' => 'Test Street',
            'neighborhood' => 'Test Neighborhood',
            'city' => 'Test City',
            'state' => 'SP',
            'zip_code' => '12345678',
            'cep' => '12345678',
        ]);

        $this->provider = Provider::create([
            'user_id' => $this->user->id,
            'tenant_id' => $this->user->tenant_id,
            'common_data_id' => $commonData->id,
            'contact_id' => $contact->id,
            'address_id' => $address->id,
            'terms_accepted' => true,
        ]);

        $this->user->update([
            'common_data_id' => $commonData->id,
            'contact_id' => $contact->id,
            'address_id' => $address->id,
            'role' => 'provider',
        ]);
    }

    public function test_update_basic_data(): void
    {
        $response = $this->actingAs($this->user)->put('/provider/business', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'company_name' => 'Updated Company',
            'cnpj' => '12.345.678/0001-90',
            'area_of_activity_id' => 1,
            'profession_id' => 1,
            'email_business' => 'business@test.com',
            'phone_business' => '11999999999',
            'address' => 'Test Street',
            'number' => '123',
            'neighborhood' => 'Test Neighborhood',
            'city' => 'Test City',
            'state' => 'SP',
            'zip_code' => '12345678',
            'cep' => '12345678',
        ]);

        $response->assertRedirect();
        
        if ($response->getSession()->has('error')) {
            $this->fail('Error in session: ' . $response->getSession()->get('error'));
        }
        
        $this->provider->commonData->refresh();
        $this->assertEquals('Updated Company', $this->provider->commonData->company_name);
        $this->assertEquals('John', $this->provider->commonData->first_name);
        $this->assertEquals('Doe', $this->provider->commonData->last_name);
    }

    public function test_update_with_logo(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('logo.jpg');

        $response = $this->actingAs($this->user)->put('/provider/business', [
            'logo' => $file,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'company_name' => 'Test Company',
            'area_of_activity_id' => 1,
            'profession_id' => 1,
            'address' => 'Test Street',
            'number' => '123',
            'neighborhood' => 'Test Neighborhood',
            'city' => 'Test City',
            'state' => 'SP',
            'zip_code' => '12345678',
            'cep' => '12345678',
        ]);

        $response->assertRedirect();
        $this->assertNotNull($this->user->fresh()->logo);
    }

    public function test_validation_fails_without_required_fields(): void
    {
        $response = $this->actingAs($this->user)->put('/provider/business', []);

        $response->assertSessionHasErrors(['first_name', 'last_name', 'address', 'neighborhood', 'city', 'state', 'cep']);
    }

    public function test_unauthenticated_user_cannot_update(): void
    {
        $response = $this->put('/provider/business', []);

        $response->assertRedirect('/login');
    }
}
