<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Services\Domain\CustomerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerServiceUpdateTest extends TestCase
{
    use RefreshDatabase;

    private CustomerService $customerService;
    private User $user;
    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();
        $this->user   = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'email'     => 'provider@example.com',
            'password'  => bcrypt('password'),
        ]);

        auth()->login($this->user);
        $this->customerService = app(CustomerService::class);
    }

    /** @test */
    public function it_updates_pf_customer_and_logs_audit()
    {
        // Create PF customer first
        $createResult = $this->customerService->create([
            'type'         => 'persona_fisica',
            'first_name'   => 'Carlos',
            'last_name'    => 'Pereira',
            'cpf'          => '123.456.789-09',
            'email'        => 'carlos@example.com',
            'phone'        => '11988887777',
            'cep'          => '01310-100',
            'neighborhood' => 'Centro',
            'city'         => 'São Paulo',
            'state'        => 'SP',
        ]);

        $this->assertTrue($createResult->isSuccess());
        $customer = $createResult->getData();

        // Update
        $updateResult = $this->customerService->update($customer->id, [
            'first_name' => 'Carlos Alberto',
            'phone'      => '11999998888',
            'cep'        => '01310-100',
            'city'       => 'São Paulo',
            'state'      => 'SP',
        ]);

        $this->assertTrue($updateResult->isSuccess());
        $updated = $updateResult->getData();
        $this->assertEquals('Carlos Alberto', $updated->commonData->first_name);

        // Audit
        $this->assertDatabaseHas('audit_logs', [
            'tenant_id'  => $this->tenant->id,
            'model_type' => get_class($updated),
            'model_id'   => $updated->id,
            'action'     => 'updated',
        ]);
    }

    /** @test */
    public function it_rejects_pj_update_with_invalid_cnpj()
    {
        // Create PJ customer first
        $createResult = $this->customerService->create([
            'type'          => 'persona_juridica',
            'company_name'  => 'Empresa XYZ LTDA',
            'cnpj'          => '11.222.333/0001-81',
            'email'         => 'contato@xyz.com',
            'phone'         => '1133334444',
            'cep'           => '04567-890',
            'address'       => 'Rua Alfa',
            'address_number'=> '100',
            'neighborhood'  => 'Centro',
            'city'          => 'São Paulo',
            'state'         => 'SP',
        ]);

        $this->assertTrue($createResult->isSuccess());
        $customer = $createResult->getData();

        // Try to update with invalid CNPJ
        $result = $this->customerService->update($customer->id, [
            'type' => 'persona_juridica',
            'cnpj' => '11.222.333/0001-80', // invalid
            'cep'  => '04567-890',
            'city' => 'São Paulo',
            'state'=> 'SP',
        ]);

        $this->assertFalse($result->isSuccess());
    }
}

