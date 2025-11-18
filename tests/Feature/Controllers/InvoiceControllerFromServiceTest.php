<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Service;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceControllerFromServiceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Tenant $tenant;
    private Customer $customer;
    private Service $service;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create tenant and user
        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'email_verified_at' => now(),
        ]);
        
        // Create customer
        $this->customer = Customer::factory()->forTenant($this->tenant)->create([
            'name' => 'Test Customer',
            'email' => 'test@example.com',
            'phone' => '1234567890',
            'document' => '12345678901',
            'zip_code' => '12345-678',
            'address' => 'Test Address',
            'number' => '123',
            'neighborhood' => 'Test Neighborhood',
            'city' => 'Test City',
            'state' => 'TS',
            'complement' => 'Test Complement'
        ]);
        
        // Create completed service
        $this->service = Service::factory()->forTenant($this->tenant)->create([
            'customer_id' => $this->customer->id,
            'status' => 'completed',
            'code' => 'SVC-202401010001',
            'name' => 'Test Service',
            'description' => 'Test Service Description',
            'total_value' => 1000.00,
            'discount_value' => 0.00,
            'discount_percentage' => 0.00,
            'final_value' => 1000.00,
        ]);
    }

    public function test_create_from_service_requires_authentication(): void
    {
        $response = $this->get('/provider/invoices/services/SVC-202401010001/create');
        
        $response->assertRedirect('/login');
    }

    public function test_create_from_service_displays_form(): void
    {
        $this->actingAs($this->user);
        
        $response = $this->get('/provider/invoices/services/SVC-202401010001/create');
        
        $response->assertStatus(200);
        $response->assertViewIs('pages.invoice.create-from-service');
        $response->assertViewHas('customer');
        $response->assertViewHas('service');
        $response->assertViewHas('items');
        $response->assertViewHas('subtotal');
        $response->assertViewHas('discount');
        $response->assertViewHas('total');
        
        // Check customer data
        $response->assertSee('Test Customer');
        $response->assertSee('test@example.com');
        
        // Check service data
        $response->assertSee('SVC-202401010001');
        $response->assertSee('Test Service');
        
        // Check discount calculation (10%)
        $response->assertSee('100.00'); // Discount
        $response->assertSee('900.00'); // Total after discount
    }

    public function test_create_from_service_with_non_existent_service(): void
    {
        $this->actingAs($this->user);
        
        $response = $this->get('/provider/invoices/services/NONEXISTENT/create');
        
        $response->assertRedirect();
        $response->assertSessionHas('error', 'Serviço não encontrado');
    }

    public function test_create_from_service_with_non_completed_service(): void
    {
        $this->actingAs($this->user);
        
        // Change service status to in-progress
        $this->service->update(['status' => 'in-progress']);
        
        $response = $this->get('/provider/invoices/services/SVC-202401010001/create');
        
        $response->assertRedirect();
        $response->assertSessionHas('error', 'Apenas serviços concluídos podem ser faturados');
    }

    public function test_store_from_service_creates_invoice_successfully(): void
    {
        $this->actingAs($this->user);
        
        $response = $this->post('/provider/invoices/store-from-service', [
            'service_code' => 'SVC-202401010001',
            'issue_date' => '2024-01-15',
            'due_date' => '2024-02-15',
            'notes' => 'Test invoice notes',
        ]);
        
        $response->assertRedirect();
        $response->assertSessionHas('success', 'Fatura criada com sucesso!');
        
        // Verify invoice was created
        $this->assertDatabaseHas('invoices', [
            'service_id' => $this->service->id,
            'customer_id' => $this->customer->id,
            'subtotal' => 1000.00,
            'discount_value' => 100.00, // 10% discount
            'total_value' => 900.00,
            'notes' => 'Test invoice notes',
        ]);
    }

    public function test_store_from_service_with_existing_invoice(): void
    {
        $this->actingAs($this->user);
        
        // Create existing invoice for the service
        Invoice::factory()->forTenant($this->tenant)->create([
            'service_id' => $this->service->id,
            'customer_id' => $this->customer->id,
            'code' => 'FAT-202401010001',
        ]);
        
        $response = $this->post('/provider/invoices/store-from-service', [
            'service_code' => 'SVC-202401010001',
            'issue_date' => '2024-01-15',
            'due_date' => '2024-02-15',
            'notes' => 'Test invoice notes',
        ]);
        
        $response->assertRedirect();
        $response->assertSessionHas('error', 'Já existe uma fatura para este serviço');
    }

    public function test_store_from_service_with_non_existent_service(): void
    {
        $this->actingAs($this->user);
        
        $response = $this->post('/provider/invoices/store-from-service', [
            'service_code' => 'NONEXISTENT',
            'issue_date' => '2024-01-15',
            'due_date' => '2024-02-15',
            'notes' => 'Test invoice notes',
        ]);
        
        $response->assertRedirect();
        $response->assertSessionHas('error', 'Serviço não encontrado');
    }

    public function test_store_from_service_with_invalid_dates(): void
    {
        $this->actingAs($this->user);
        
        $response = $this->post('/provider/invoices/store-from-service', [
            'service_code' => 'SVC-202401010001',
            'issue_date' => 'invalid-date',
            'due_date' => '2024-02-15',
            'notes' => 'Test invoice notes',
        ]);
        
        $response->assertSessionHasErrors(['issue_date']);
    }

    public function test_store_from_service_validates_required_fields(): void
    {
        $this->actingAs($this->user);
        
        $response = $this->post('/provider/invoices/store-from-service', []);
        
        $response->assertSessionHasErrors(['service_code', 'issue_date', 'due_date']);
    }

    public function test_store_from_service_uses_tenant_scoping(): void
    {
        $this->actingAs($this->user);
        
        // Create another tenant and service
        $otherTenant = Tenant::factory()->create();
        $otherService = Service::factory()->forTenant($otherTenant)->create([
            'status' => 'completed',
            'code' => 'SVC-OTHER-001',
        ]);
        
        $response = $this->get('/provider/invoices/services/SVC-OTHER-001/create');
        
        $response->assertRedirect();
        $response->assertSessionHas('error', 'Serviço não encontrado');
    }
}