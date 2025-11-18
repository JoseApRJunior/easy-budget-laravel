<?php

namespace Tests\Unit\Observers;

use App\Models\Service;
use App\Models\Invoice;
use App\Models\Budget;
use App\Models\Customer;
use App\Models\Tenant;
use App\Models\User;
use App\Models\ServiceItem;
use App\Models\Product;
use App\Enums\ServiceStatus;
use App\Observers\ServiceObserver;
use App\Services\Domain\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class ServiceObserverTest extends TestCase
{
    use RefreshDatabase;

    protected ServiceObserver $observer;
    protected InvoiceService $invoiceService;
    protected Tenant $tenant;
    protected User $user;
    protected Customer $customer;
    protected Budget $budget;
    protected Service $service;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->invoiceService = $this->app->make(InvoiceService::class);
        $this->observer = new ServiceObserver($this->invoiceService);
        
        // Criar dados de teste
        $this->tenant = Tenant::factory()->create();
        $this->user = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->customer = Customer::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->budget = Budget::factory()->create([
            'tenant_id' => $this->tenant->id,
            'customer_id' => $this->customer->id,
            'total' => 1000.00
        ]);
        
        // Criar serviço em status diferente de completed
        $this->service = Service::factory()->create([
            'tenant_id' => $this->tenant->id,
            'budget_id' => $this->budget->id,
            'status' => ServiceStatus::IN_PROGRESS,
            'total' => 1000.00,
            'code' => 'TEST-SERVICE-001'
        ]);
    }

    /** @test */
    public function it_does_not_generate_invoice_when_service_is_not_completed()
    {
        // Mock do Log para verificar que não foi chamado
        Log::shouldReceive('info')->never();
        
        // Atualizar serviço para um status diferente de completed
        $this->service->update(['status' => ServiceStatus::IN_PROGRESS]);
        
        // Chamar o observer
        $this->observer->updated($this->service);
        
        // Verificar que nenhuma fatura foi criada
        $this->assertDatabaseMissing('invoices', [
            'service_id' => $this->service->id,
            'tenant_id' => $this->tenant->id
        ]);
    }

    /** @test */
    public function it_does_not_generate_invoice_when_status_did_not_change()
    {
        // Mock do Log para verificar que não foi chamado
        Log::shouldReceive('info')->never();
        
        // Atualizar outro campo sem mudar o status
        $this->service->update(['description' => 'Nova descrição']);
        
        // Chamar o observer
        $this->observer->updated($this->service);
        
        // Verificar que nenhuma fatura foi criada
        $this->assertDatabaseMissing('invoices', [
            'service_id' => $this->service->id,
            'tenant_id' => $this->tenant->id
        ]);
    }

    /** @test */
    public function it_generates_automatic_invoice_when_service_changes_to_completed()
    {
        // Criar itens do serviço
        $product = Product::factory()->create(['tenant_id' => $this->tenant->id]);
        ServiceItem::factory()->create([
            'tenant_id' => $this->tenant->id,
            'service_id' => $this->service->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_value' => 500.00,
            'total' => 1000.00
        ]);
        
        // Mudar status para completed
        $this->service->status = ServiceStatus::COMPLETED;
        $this->service->save();
        
        // Chamar o observer
        $this->observer->updated($this->service);
        
        // Verificar que a fatura foi criada
        $this->assertDatabaseHas('invoices', [
            'service_id' => $this->service->id,
            'customer_id' => $this->customer->id,
            'tenant_id' => $this->tenant->id,
            'is_automatic' => true,
            'status' => 'pending'
        ]);
        
        // Verificar que a fatura tem os valores corretos
        $invoice = Invoice::where('service_id', $this->service->id)->first();
        $this->assertEquals(1000.00, $invoice->total);
        $this->assertEquals(0, $invoice->discount);
        $this->assertStringContainsString('Fatura gerada automaticamente', $invoice->notes);
    }

    /** @test */
    public function it_does_not_generate_duplicate_invoices_for_same_service()
    {
        // Criar uma fatura existente para o serviço
        Invoice::factory()->create([
            'tenant_id' => $this->tenant->id,
            'service_id' => $this->service->id,
            'customer_id' => $this->customer->id,
            'code' => 'FAT-TEST-001',
            'total' => 1000.00
        ]);
        
        // Mudar status para completed
        $this->service->status = ServiceStatus::COMPLETED;
        $this->service->save();
        
        // Mock do Log para verificar que foi logado o aviso
        Log::shouldReceive('info')
            ->withArgs(function ($message, $context) {
                return $message === 'Fatura já existe para este serviço, ignorando geração automática';
            })
            ->once();
        
        // Chamar o observer
        $this->observer->updated($this->service);
        
        // Verificar que apenas uma fatura existe (a que já existia)
        $this->assertEquals(1, Invoice::where('service_id', $this->service->id)->count());
    }

    /** @test */
    public function it_logs_errors_when_invoice_generation_fails()
    {
        // Criar um serviço inválido (sem budget, por exemplo)
        $invalidService = Service::factory()->create([
            'tenant_id' => $this->tenant->id,
            'budget_id' => 99999, // Budget inexistente
            'status' => ServiceStatus::IN_PROGRESS,
            'total' => 1000.00,
            'code' => 'INVALID-SERVICE-001'
        ]);
        
        // Mudar status para completed
        $invalidService->status = ServiceStatus::COMPLETED;
        $invalidService->save();
        
        // Mock do Log para verificar que o erro foi logado
        Log::shouldReceive('error')
            ->withArgs(function ($message, $context) {
                return $message === 'Exceção ao gerar fatura automática' ||
                       $message === 'Erro ao gerar fatura automática';
            })
            ->atLeast()->once();
        
        // Chamar o observer - não deve lançar exceção
        $this->observer->updated($invalidService);
        
        // Verificar que nenhuma fatura foi criada
        $this->assertDatabaseMissing('invoices', [
            'service_id' => $invalidService->id
        ]);
    }

    /** @test */
    public function it_handles_services_without_items()
    {
        // Mudar status para completed (sem itens)
        $this->service->status = ServiceStatus::COMPLETED;
        $this->service->save();
        
        // Mock do Log para verificar que foi logado
        Log::shouldReceive('info')
            ->withArgs(function ($message, $context) {
                return $message === 'Iniciando geração automática de fatura para serviço';
            })
            ->once();
        
        // Chamar o observer
        $this->observer->updated($this->service);
        
        // Verificar que a fatura foi criada mesmo sem itens
        $this->assertDatabaseHas('invoices', [
            'service_id' => $this->service->id,
            'customer_id' => $this->customer->id,
            'tenant_id' => $this->tenant->id,
            'is_automatic' => true
        ]);
    }

    /** @test */
    public function it_generates_invoice_with_correct_due_date()
    {
        // Mudar status para completed
        $this->service->status = ServiceStatus::COMPLETED;
        $this->service->save();
        
        // Chamar o observer
        $this->observer->updated($this->service);
        
        // Verificar que a data de vencimento é 30 dias no futuro
        $invoice = Invoice::where('service_id', $this->service->id)->first();
        $expectedDueDate = now()->addDays(30)->format('Y-m-d');
        $this->assertEquals($expectedDueDate, $invoice->due_date->format('Y-m-d'));
    }
}