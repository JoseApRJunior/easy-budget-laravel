<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Events\InvoiceCreated;
use App\Events\PasswordResetRequested;
use App\Events\StatusUpdated;
use App\Events\SupportTicketResponded;
use App\Events\UserRegistered;
use App\Listeners\SendInvoiceNotification;
use App\Listeners\SendPasswordResetNotification;
use App\Listeners\SendStatusUpdateNotification;
use App\Listeners\SendSupportResponse;
use App\Listeners\SendWelcomeEmail;
use App\Providers\EventServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Testes unitários para verificar se o sistema de eventos está configurado corretamente.
 *
 * Estes testes verificam se:
 * - Eventos estão definidos corretamente
 * - Listeners estão implementados corretamente
 * - EventServiceProvider registra eventos adequadamente
 * - Eventos podem ser instanciados sem erro
 */
class EventSystemTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Testa se todos os eventos podem ser instanciados corretamente.
     *
     * @return void
     */
    public function test_events_can_be_instantiated(): void
    {
        // Testa UserRegistered event
        $user   = new \App\Models\User( [ 'id' => 1, 'email' => 'test@example.com' ] );
        $tenant = new \App\Models\Tenant( [ 'id' => 1, 'name' => 'Test Tenant' ] );

        $userRegisteredEvent = new UserRegistered( $user, $tenant );
        $this->assertInstanceOf( UserRegistered::class, $userRegisteredEvent );
        $this->assertEquals( $user->id, $userRegisteredEvent->user->id );
        $this->assertEquals( $tenant->id, $userRegisteredEvent->tenant->id );

        // Testa InvoiceCreated event
        $invoice  = new \App\Models\Invoice( [ 'id' => 1, 'code' => 'INV-001' ] );
        $customer = new \App\Models\Customer( [ 'id' => 1, 'name' => 'Test Customer' ] );

        $invoiceCreatedEvent = new InvoiceCreated( $invoice, $customer, $tenant );
        $this->assertInstanceOf( InvoiceCreated::class, $invoiceCreatedEvent );
        $this->assertEquals( $invoice->id, $invoiceCreatedEvent->invoice->id );
        $this->assertEquals( $customer->id, $invoiceCreatedEvent->customer->id );

        // Testa StatusUpdated event
        $oldStatus  = 'draft';
        $newStatus  = 'sent';
        $statusName = 'Enviada';

        $statusUpdatedEvent = new StatusUpdated( $invoice, $oldStatus, $newStatus, $statusName, $tenant );
        $this->assertInstanceOf( StatusUpdated::class, $statusUpdatedEvent );
        $this->assertEquals( $oldStatus, $statusUpdatedEvent->oldStatus );
        $this->assertEquals( $newStatus, $statusUpdatedEvent->newStatus );
        $this->assertEquals( $statusName, $statusUpdatedEvent->statusName );

        // Testa PasswordResetRequested event
        $resetToken = 'test-reset-token-12345';

        $passwordResetEvent = new PasswordResetRequested( $user, $resetToken, $tenant );
        $this->assertInstanceOf( PasswordResetRequested::class, $passwordResetEvent );
        $this->assertEquals( $resetToken, $passwordResetEvent->resetToken );

        // Testa SupportTicketResponded event
        $ticket   = [
            'id'      => 1,
            'subject' => 'Problema com fatura',
            'email'   => 'cliente@example.com',
        ];
        $response = 'Sua fatura foi processada.';

        $supportResponseEvent = new SupportTicketResponded( $ticket, $response, $tenant );
        $this->assertInstanceOf( SupportTicketResponded::class, $supportResponseEvent );
        $this->assertEquals( $ticket[ 'id' ], $supportResponseEvent->ticket[ 'id' ] );
        $this->assertEquals( $response, $supportResponseEvent->response );
    }

    /**
     * Testa se todos os listeners podem ser instanciados corretamente.
     *
     * @return void
     */
    public function test_listeners_can_be_instantiated(): void
    {
        // Testa SendWelcomeEmail listener
        $welcomeEmailListener = new SendWelcomeEmail();
        $this->assertInstanceOf( SendWelcomeEmail::class, $welcomeEmailListener );
        $this->assertEquals( 3, $welcomeEmailListener->tries );
        $this->assertEquals( 30, $welcomeEmailListener->backoff );

        // Testa SendInvoiceNotification listener
        $invoiceNotificationListener = new SendInvoiceNotification();
        $this->assertInstanceOf( SendInvoiceNotification::class, $invoiceNotificationListener );
        $this->assertEquals( 3, $invoiceNotificationListener->tries );
        $this->assertEquals( 30, $invoiceNotificationListener->backoff );

        // Testa SendStatusUpdateNotification listener
        $statusUpdateListener = new SendStatusUpdateNotification();
        $this->assertInstanceOf( SendStatusUpdateNotification::class, $statusUpdateListener );
        $this->assertEquals( 3, $statusUpdateListener->tries );
        $this->assertEquals( 30, $statusUpdateListener->backoff );

        // Testa SendPasswordResetNotification listener
        $passwordResetListener = new SendPasswordResetNotification();
        $this->assertInstanceOf( SendPasswordResetNotification::class, $passwordResetListener );
        $this->assertEquals( 3, $passwordResetListener->tries );
        $this->assertEquals( 30, $passwordResetListener->backoff );

        // Testa SendSupportResponse listener
        $supportResponseListener = new SendSupportResponse();
        $this->assertInstanceOf( SendSupportResponse::class, $supportResponseListener );
        $this->assertEquals( 3, $supportResponseListener->tries );
        $this->assertEquals( 30, $supportResponseListener->backoff );
    }

    /**
     * Testa se o EventServiceProvider registra os eventos corretamente.
     *
     * @return void
     */
    public function test_event_service_provider_registers_events(): void
    {
        $provider = new EventServiceProvider( app() );

        // Verifica se o provider tem os eventos registrados
        $this->assertIsArray( $provider->listen );

        // Verifica se todos os nossos eventos estão registrados
        $this->assertArrayHasKey( UserRegistered::class, $provider->listen );
        $this->assertArrayHasKey( InvoiceCreated::class, $provider->listen );
        $this->assertArrayHasKey( StatusUpdated::class, $provider->listen );
        $this->assertArrayHasKey( PasswordResetRequested::class, $provider->listen );
        $this->assertArrayHasKey( SupportTicketResponded::class, $provider->listen );

        // Verifica se os listeners corretos estão associados aos eventos
        $this->assertContains( SendWelcomeEmail::class, $provider->listen[ UserRegistered::class] );
        $this->assertContains( SendInvoiceNotification::class, $provider->listen[ InvoiceCreated::class] );
        $this->assertContains( SendStatusUpdateNotification::class, $provider->listen[ StatusUpdated::class] );
        $this->assertContains( SendPasswordResetNotification::class, $provider->listen[ PasswordResetRequested::class] );
        $this->assertContains( SendSupportResponse::class, $provider->listen[ SupportTicketResponded::class] );
    }

    /**
     * Testa se o EventServiceProvider pode ser inicializado sem erro.
     *
     * @return void
     */
    public function test_event_service_provider_boots_without_error(): void
    {
        $provider = new EventServiceProvider( app() );

        // Deve conseguir fazer boot sem lançar exceções
        $provider->boot();

        $this->assertTrue( true ); // Se chegou aqui, passou no teste
    }

    /**
     * Testa se eventos usam as traits corretas do Laravel.
     *
     * @return void
     */
    public function test_events_use_correct_traits(): void
    {
        $user   = new \App\Models\User( [ 'id' => 1, 'email' => 'test@example.com' ] );
        $tenant = new \App\Models\Tenant( [ 'id' => 1, 'name' => 'Test Tenant' ] );

        $event = new UserRegistered( $user, $tenant );

        // Verifica se o evento tem os métodos das traits
        $this->assertTrue( method_exists( $event, 'broadcast' ) );
        $this->assertTrue( method_exists( $event, 'broadcastAs' ) );
        $this->assertTrue( method_exists( $event, 'broadcastOn' ) );
        $this->assertTrue( method_exists( $event, 'broadcastWhen' ) );
    }

    /**
     * Testa se listeners implementam ShouldQueue corretamente.
     *
     * @return void
     */
    public function test_listeners_implement_should_queue(): void
    {
        $listeners = [
            new SendWelcomeEmail(),
            new SendInvoiceNotification(),
            new SendStatusUpdateNotification(),
            new SendPasswordResetNotification(),
            new SendSupportResponse(),
        ];

        foreach ( $listeners as $listener ) {
            $this->assertInstanceOf( \Illuminate\Contracts\Queue\ShouldQueue::class, $listener );
        }
    }

    /**
     * Testa se eventos podem ser serializados (necessário para queue).
     *
     * @return void
     */
    public function test_events_are_serializable(): void
    {
        $user   = new \App\Models\User( [ 'id' => 1, 'email' => 'test@example.com' ] );
        $tenant = new \App\Models\Tenant( [ 'id' => 1, 'name' => 'Test Tenant' ] );

        $event = new UserRegistered( $user, $tenant );

        // Testa serialização
        $serialized = serialize( $event );
        $this->assertIsString( $serialized );

        // Testa deserialização
        $unserialized = unserialize( $serialized );
        $this->assertInstanceOf( UserRegistered::class, $unserialized );
        $this->assertEquals( $event->user->id, $unserialized->user->id );
        $this->assertEquals( $event->tenant->id, $unserialized->tenant->id );
    }

    /**
     * Testa se eventos têm propriedades públicas necessárias.
     *
     * @return void
     */
    public function test_events_have_public_properties(): void
    {
        $user   = new \App\Models\User( [ 'id' => 1, 'email' => 'test@example.com' ] );
        $tenant = new \App\Models\Tenant( [ 'id' => 1, 'name' => 'Test Tenant' ] );

        $event = new UserRegistered( $user, $tenant );

        // Verifica se as propriedades são públicas e acessíveis
        $this->assertTrue( isset( $event->user ) );
        $this->assertTrue( isset( $event->tenant ) );
        $this->assertEquals( $user->id, $event->user->id );
        $this->assertEquals( $tenant->id, $event->tenant->id );
    }

}
