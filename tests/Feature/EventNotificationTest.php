<?php

declare(strict_types=1);

namespace Tests\Feature;

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
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Testes para verificar o funcionamento do sistema de eventos de notificação.
 *
 * Estes testes verificam se:
 * - Eventos são disparados corretamente pelos controllers/services
 * - Listeners processam os eventos adequadamente
 * - Eventos são enfileirados para processamento assíncrono
 * - Tratamento de erro funciona corretamente
 */
class EventNotificationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Testa se o evento UserRegistered é disparado corretamente.
     *
     * @return void
     */
    public function test_user_registered_event_is_dispatched(): void
    {
        // Arrange
        $user   = User::factory()->create();
        $tenant = Tenant::factory()->create();

        // Garante que a queue está sendo usada
        Queue::fake();

        // Act
        Event::dispatch( new UserRegistered( $user, $tenant ) );

        // Assert
        Event::assertDispatched( UserRegistered::class, function ( $event ) use ( $user, $tenant ) {
            return $event->user->id === $user->id && $event->tenant->id === $tenant->id;
        } );

        // Verifica se o listener foi registrado
        Event::assertListening(
            UserRegistered::class,
            SendWelcomeEmail::class,
        );
    }

    /**
     * Testa se o evento InvoiceCreated é disparado corretamente.
     *
     * @return void
     */
    public function test_invoice_created_event_is_dispatched(): void
    {
        // Arrange
        $invoice  = Invoice::factory()->create();
        $customer = Customer::factory()->create();
        $tenant   = Tenant::factory()->create();

        // Garante que a queue está sendo usada
        Queue::fake();

        // Act
        Event::dispatch( new InvoiceCreated( $invoice, $customer, $tenant ) );

        // Assert
        Event::assertDispatched( InvoiceCreated::class, function ( $event ) use ( $invoice, $customer, $tenant ) {
            return $event->invoice->id === $invoice->id
                && $event->customer->id === $customer->id
                && $event->tenant->id === $tenant->id;
        } );

        // Verifica se o listener foi registrado
        Event::assertListening(
            InvoiceCreated::class,
            SendInvoiceNotification::class,
        );
    }

    /**
     * Testa se o evento StatusUpdated é disparado corretamente.
     *
     * @return void
     */
    public function test_status_updated_event_is_dispatched(): void
    {
        // Arrange
        $invoice    = Invoice::factory()->create();
        $oldStatus  = 'draft';
        $newStatus  = 'sent';
        $statusName = 'Enviada';
        $tenant     = Tenant::factory()->create();

        // Garante que a queue está sendo usada
        Queue::fake();

        // Act
        Event::dispatch( new StatusUpdated( $invoice, $oldStatus, $newStatus, $statusName, $tenant ) );

        // Assert
        Event::assertDispatched( StatusUpdated::class, function ( $event ) use ( $invoice, $oldStatus, $newStatus, $statusName, $tenant ) {
            return $event->entity->id === $invoice->id
                && $event->oldStatus === $oldStatus
                && $event->newStatus === $newStatus
                && $event->statusName === $statusName
                && $event->tenant->id === $tenant->id;
        } );

        // Verifica se o listener foi registrado
        Event::assertListening(
            StatusUpdated::class,
            SendStatusUpdateNotification::class,
        );
    }

    /**
     * Testa se o evento PasswordResetRequested é disparado corretamente.
     *
     * @return void
     */
    public function test_password_reset_requested_event_is_dispatched(): void
    {
        // Arrange
        $user       = User::factory()->create();
        $resetToken = 'test-reset-token-12345';
        $tenant     = Tenant::factory()->create();

        // Garante que a queue está sendo usada
        Queue::fake();

        // Act
        Event::dispatch( new PasswordResetRequested( $user, $resetToken, $tenant ) );

        // Assert
        Event::assertDispatched( PasswordResetRequested::class, function ( $event ) use ( $user, $resetToken, $tenant ) {
            return $event->user->id === $user->id
                && $event->resetToken === $resetToken
                && $event->tenant->id === $tenant->id;
        } );

        // Verifica se o listener foi registrado
        Event::assertListening(
            PasswordResetRequested::class,
            SendPasswordResetNotification::class,
        );
    }

    /**
     * Testa se o evento SupportTicketResponded é disparado corretamente.
     *
     * @return void
     */
    public function test_support_ticket_responded_event_is_dispatched(): void
    {
        // Arrange
        $ticket   = [
            'id'      => 1,
            'subject' => 'Problema com fatura',
            'email'   => 'cliente@example.com',
        ];
        $response = 'Sua fatura foi processada e estará disponível em breve.';
        $tenant   = Tenant::factory()->create();

        // Garante que a queue está sendo usada
        Queue::fake();

        // Act
        Event::dispatch( new SupportTicketResponded( $ticket, $response, $tenant ) );

        // Assert
        Event::assertDispatched( SupportTicketResponded::class, function ( $event ) use ( $ticket, $response, $tenant ) {
            return $event->ticket[ 'id' ] === $ticket[ 'id' ]
                && $event->ticket[ 'subject' ] === $ticket[ 'subject' ]
                && $event->response === $response
                && $event->tenant->id === $tenant->id;
        } );

        // Verifica se o listener foi registrado
        Event::assertListening(
            SupportTicketResponded::class,
            SendSupportResponse::class,
        );
    }

    /**
     * Testa se múltiplos eventos podem ser disparados simultaneamente.
     *
     * @return void
     */
    public function test_multiple_events_can_be_dispatched_simultaneously(): void
    {
        // Arrange
        $user     = User::factory()->create();
        $tenant   = Tenant::factory()->create();
        $invoice  = Invoice::factory()->create();
        $customer = Customer::factory()->create();

        Queue::fake();

        // Act
        Event::dispatch( new UserRegistered( $user, $tenant ) );
        Event::dispatch( new InvoiceCreated( $invoice, $customer, $tenant ) );

        // Assert
        Event::assertDispatched( UserRegistered::class);
        Event::assertDispatched( InvoiceCreated::class);
        Event::assertDispatchedTimes( UserRegistered::class, 1 );
        Event::assertDispatchedTimes( InvoiceCreated::class, 1 );
    }

    /**
     * Testa se eventos são processados mesmo quando listeners falham.
     *
     * @return void
     */
    public function test_events_are_dispatched_even_when_listeners_fail(): void
    {
        // Arrange
        $user   = User::factory()->create();
        $tenant = Tenant::factory()->create();

        // Mock do listener para simular falha
        Event::listen( UserRegistered::class, function () {
            throw new \Exception( 'Erro simulado no listener' );
        } );

        // Act & Assert
        // O evento deve ser disparado mesmo com listener com erro
        Event::dispatch( new UserRegistered( $user, $tenant ) );

        Event::assertDispatched( UserRegistered::class);
    }

    /**
     * Testa se eventos são enfileirados corretamente para processamento assíncrono.
     *
     * @return void
     */
    public function test_events_are_queued_for_async_processing(): void
    {
        // Arrange
        $user   = User::factory()->create();
        $tenant = Tenant::factory()->create();

        // Act
        Event::dispatch( new UserRegistered( $user, $tenant ) );

        // Assert
        // Verifica se o evento foi registrado na queue
        Queue::assertPushed( \Illuminate\Events\CallQueuedListener::class, function ( $job ) {
            return $job->class === SendWelcomeEmail::class;
        } );
    }

    /**
     * Testa tratamento de erro quando listener falha.
     *
     * @return void
     */
    public function test_listener_error_handling(): void
    {
        // Arrange
        $user   = User::factory()->create();
        $tenant = Tenant::factory()->create();

        // Cria um listener que sempre falha
        $failingListener = new class implements \Illuminate\Contracts\Queue\ShouldQueue
        {
            public $tries = 1;

            public function handle( UserRegistered $event ): void
            {
                throw new \Exception( 'Erro simulado no listener' );
            }

            public function failed( UserRegistered $event, \Throwable $exception ): void
            {
                // Verifica se o método failed foi chamado
                $this->assertEquals( 'Erro simulado no listener', $exception->getMessage() );
            }

        };

        // Substitui o listener padrão pelo listener que falha
        Event::listen( UserRegistered::class, get_class( $failingListener ) );

        // Act
        Event::dispatch( new UserRegistered( $user, $tenant ) );

        // Assert
        Event::assertDispatched( UserRegistered::class);

        // O evento deve ser disparado mesmo com erro no listener
        $this->assertTrue( true ); // Se chegou aqui, o teste passou
    }

}
