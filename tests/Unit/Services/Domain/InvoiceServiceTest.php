<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Domain;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Service;
use App\Models\Tenant;
use App\Services\Domain\InvoiceService;
use App\Services\Application\ServiceResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceServiceTest extends TestCase
{
    use RefreshDatabase;

    private InvoiceService $invoiceService;
    private Tenant $tenant;
    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->invoiceService = app(InvoiceService::class);
        
        // Create tenant and customer for testing
        $this->tenant = Tenant::factory()->create();
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
    }

    public function test_generate_invoice_data_from_service_with_partial_service_discount(): void
    {
        // Create a completed service with items
        $service = Service::factory()->forTenant($this->tenant)->create([
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

        // Generate invoice data from service
        $result = $this->invoiceService->generateInvoiceDataFromService('SVC-202401010001');

        $this->assertInstanceOf(ServiceResult::class, $result);
        $this->assertTrue($result->isSuccess());
        
        $data = $result->getData();
        $this->assertArrayHasKey('customer', $data);
        $this->assertArrayHasKey('service', $data);
        $this->assertArrayHasKey('items', $data);
        $this->assertArrayHasKey('subtotal', $data);
        $this->assertArrayHasKey('discount', $data);
        $this->assertArrayHasKey('total', $data);
        
        // Check customer data
        $this->assertEquals($this->customer->id, $data['customer']['id']);
        $this->assertEquals('Test Customer', $data['customer']['name']);
        $this->assertEquals('test@example.com', $data['customer']['email']);
        
        // Check service data
        $this->assertEquals($service->id, $data['service']['id']);
        $this->assertEquals('SVC-202401010001', $data['service']['code']);
        $this->assertEquals('Test Service', $data['service']['name']);
        
        // Check discount calculation (10% for partial service)
        $this->assertEquals(100.00, $data['discount']); // 10% of 1000.00
        $this->assertEquals(900.00, $data['total']); // 1000.00 - 100.00
    }

    public function test_generate_invoice_data_from_non_existent_service(): void
    {
        $result = $this->invoiceService->generateInvoiceDataFromService('SVC-NONEXISTENT');

        $this->assertInstanceOf(ServiceResult::class, $result);
        $this->assertFalse($result->isSuccess());
        $this->assertEquals('Serviço não encontrado', $result->getMessage());
    }

    public function test_generate_invoice_data_from_non_completed_service(): void
    {
        $service = Service::factory()->forTenant($this->tenant)->create([
            'customer_id' => $this->customer->id,
            'status' => 'in-progress', // Not completed
            'code' => 'SVC-202401010002',
        ]);

        $result = $this->invoiceService->generateInvoiceDataFromService('SVC-202401010002');

        $this->assertInstanceOf(ServiceResult::class, $result);
        $this->assertFalse($result->isSuccess());
        $this->assertEquals('Apenas serviços concluídos podem ser faturados', $result->getMessage());
    }

    public function test_check_existing_invoice_for_service(): void
    {
        $service = Service::factory()->forTenant($this->tenant)->create([
            'customer_id' => $this->customer->id,
            'status' => 'completed',
            'code' => 'SVC-202401010003',
        ]);

        // Initially should return false
        $this->assertFalse($this->invoiceService->checkExistingInvoiceForService($service->id));

        // Create an invoice for this service
        Invoice::factory()->forTenant($this->tenant)->create([
            'service_id' => $service->id,
            'customer_id' => $this->customer->id,
            'code' => 'FAT-202401010001',
        ]);

        // Now should return true
        $this->assertTrue($this->invoiceService->checkExistingInvoiceForService($service->id));
    }

    public function test_create_invoice_from_service_success(): void
    {
        $service = Service::factory()->forTenant($this->tenant)->create([
            'customer_id' => $this->customer->id,
            'status' => 'completed',
            'code' => 'SVC-202401010004',
            'name' => 'Test Service',
            'description' => 'Test Service Description',
            'total_value' => 1500.00,
            'discount_value' => 0.00,
            'discount_percentage' => 0.00,
            'final_value' => 1500.00,
        ]);

        $additionalData = [
            'issue_date' => '2024-01-15',
            'due_date' => '2024-02-15',
            'notes' => 'Test invoice notes',
        ];

        $result = $this->invoiceService->createInvoiceFromService('SVC-202401010004', $additionalData);

        $this->assertInstanceOf(ServiceResult::class, $result);
        $this->assertTrue($result->isSuccess());
        
        $invoice = $result->getData();
        $this->assertInstanceOf(Invoice::class, $invoice);
        
        // Check invoice data
        $this->assertEquals($this->customer->id, $invoice->customer_id);
        $this->assertEquals($service->id, $invoice->service_id);
        $this->assertEquals(1500.00, $invoice->subtotal);
        $this->assertEquals(150.00, $invoice->discount_value); // 10% discount
        $this->assertEquals(1350.00, $invoice->total_value);
        $this->assertEquals('2024-01-15', $invoice->issue_date->format('Y-m-d'));
        $this->assertEquals('2024-02-15', $invoice->due_date->format('Y-m-d'));
        $this->assertEquals('Test invoice notes', $invoice->notes);
        
        // Check if code was generated
        $this->assertNotNull($invoice->code);
        $this->assertStringStartsWith('FAT-', $invoice->code);
    }

    public function test_create_invoice_from_service_with_existing_invoice(): void
    {
        $service = Service::factory()->forTenant($this->tenant)->create([
            'customer_id' => $this->customer->id,
            'status' => 'completed',
            'code' => 'SVC-202401010005',
        ]);

        // Create an existing invoice for this service
        Invoice::factory()->forTenant($this->tenant)->create([
            'service_id' => $service->id,
            'customer_id' => $this->customer->id,
            'code' => 'FAT-202401010002',
        ]);

        $result = $this->invoiceService->createInvoiceFromService('SVC-202401010005', []);

        $this->assertInstanceOf(ServiceResult::class, $result);
        $this->assertFalse($result->isSuccess());
        $this->assertEquals('Já existe uma fatura para este serviço', $result->getMessage());
    }

    public function test_generate_unique_invoice_code(): void
    {
        // Create an existing invoice
        Invoice::factory()->forTenant($this->tenant)->create([
            'code' => 'FAT-202401150001',
        ]);

        // Use reflection to test the private method
        $reflection = new \ReflectionClass($this->invoiceService);
        $method = $reflection->getMethod('generateUniqueCode');
        $method->setAccessible(true);

        $newCode = $method->invoke($this->invoiceService);

        $this->assertStringStartsWith('FAT-', $newCode);
        $this->assertEquals('FAT-202401150002', $newCode);
    }

    public function test_invoice_items_generation_from_service(): void
    {
        $service = Service::factory()->forTenant($this->tenant)->create([
            'customer_id' => $this->customer->id,
            'status' => 'completed',
            'code' => 'SVC-202401010006',
            'name' => 'Test Service',
            'description' => 'Test Service Description',
            'total_value' => 2000.00,
            'discount_value' => 0.00,
            'discount_percentage' => 0.00,
            'final_value' => 2000.00,
        ]);

        $result = $this->invoiceService->generateInvoiceDataFromService('SVC-202401010006');

        $this->assertTrue($result->isSuccess());
        $data = $result->getData();
        
        // Check that items are generated
        $this->assertArrayHasKey('items', $data);
        $this->assertIsArray($data['items']);
        $this->assertNotEmpty($data['items']);
        
        // Check item structure
        $item = $data['items'][0];
        $this->assertArrayHasKey('description', $item);
        $this->assertArrayHasKey('quantity', $item);
        $this->assertArrayHasKey('unit_price', $item);
        $this->assertArrayHasKey('total', $item);
        
        // Check item values
        $this->assertEquals('Test Service - Test Service Description', $item['description']);
        $this->assertEquals(1, $item['quantity']);
        $this->assertEquals(2000.00, $item['unit_price']);
        $this->assertEquals(2000.00, $item['total']);
    }
}