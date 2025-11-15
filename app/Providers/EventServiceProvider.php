<?php

declare(strict_types=1);

namespace App\Providers;

use App\Events\EmailVerificationRequested;
use App\Events\InvoiceCreated;
use App\Events\PasswordResetRequested;
use App\Events\SocialAccountLinked;
use App\Events\SocialLoginWelcome;
use App\Events\StatusUpdated;
use App\Events\SupportTicketCreated;
use App\Events\SupportTicketResponded;
use App\Events\ReportGenerated;
use App\Events\UserRegistered;
use App\Listeners\SendEmailVerification;
use App\Listeners\SendInvoiceNotification;
use App\Listeners\SendPasswordResetNotification;
use App\Listeners\SendSocialAccountLinkedNotification;
use App\Listeners\SendSocialLoginWelcomeNotification;
use App\Listeners\SendStatusUpdateNotification;
use App\Listeners\SendSupportContactEmail;
use App\Listeners\SendSupportResponse;
use App\Listeners\LogReportGeneration;
use App\Listeners\SendWelcomeEmail;
use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Event Service Provider para registro de eventos e listeners customizados.
 *
 * Este provider registra todos os eventos relacionados a notificações por e-mail
 * e outros eventos customizados do sistema Easy Budget Laravel.
 */
class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
            // Eventos de autenticação padrão do Laravel
        Registered::class                 => [
            SendEmailVerificationNotification::class,
        ],

            // Eventos customizados de notificação por e-mail
        UserRegistered::class             => [
            SendWelcomeEmail::class,
        ],

        InvoiceCreated::class             => [
            SendInvoiceNotification::class,
        ],

        StatusUpdated::class              => [
            SendStatusUpdateNotification::class,
        ],

        PasswordResetRequested::class     => [
            SendPasswordResetNotification::class,
        ],

        EmailVerificationRequested::class => [
            SendEmailVerification::class,
        ],

        SocialLoginWelcome::class         => [
            SendSocialLoginWelcomeNotification::class,
        ],

        SocialAccountLinked::class        => [
            SendSocialAccountLinkedNotification::class,
        ],

        SupportTicketCreated::class       => [
            SendSupportContactEmail::class,
        ],

        SupportTicketResponded::class     => [
            SendSupportResponse::class,
        ],

        ReportGenerated::class            => [
            LogReportGeneration::class,
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function register(): void
    {
        parent::register();
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void
    {
        parent::boot();

        // Registra observers adicionais se necessário
        $this->registerAdditionalEventListeners();
    }

    /**
     * Registra listeners adicionais que podem precisar de lógica condicional.
     *
     * @return void
     */
    private function registerAdditionalEventListeners(): void
    {
        // Exemplo de registro condicional baseado em configurações
        if ( config( 'app.email_notifications_enabled', true ) ) {
            $this->registerEmailNotificationListeners();
        }

        // Exemplo de registro baseado em ambiente
        if ( app()->environment( [ 'local', 'testing' ] ) ) {
            $this->registerDevelopmentEventListeners();
        }
    }

    /**
     * Registra listeners específicos para notificações por e-mail.
     *
     * @return void
     */
    private function registerEmailNotificationListeners(): void
    {
        // Lista de eventos que disparam notificações por e-mail
        $emailEvents = [
            UserRegistered::class,
            InvoiceCreated::class,
            StatusUpdated::class,
            PasswordResetRequested::class,
            EmailVerificationRequested::class,
            SocialLoginWelcome::class,
            SocialAccountLinked::class,
            SupportTicketCreated::class,
            SupportTicketResponded::class,
        ];

        foreach ( $emailEvents as $event ) {
            Event::listen( $event, function ( $event ) {
                $this->logEventDispatched( $event );
            } );
        }
    }

    /**
     * Registra listeners específicos para ambiente de desenvolvimento.
     *
     * @return void
     */
    private function registerDevelopmentEventListeners(): void
    {
        // Eventos adicionais apenas para desenvolvimento
        Event::listen( '*', function ( $eventName, array $data ) {
            // Log detalhado de todos os eventos em desenvolvimento
            if ( app()->hasDebugModeEnabled() ) {
                Log::debug( 'Evento disparado em desenvolvimento', [
                    'event'        => $eventName,
                    'data'         => $data,
                    'memory_usage' => memory_get_usage( true ),
                    'timestamp'    => now()->toDateTimeString(),
                ] );
            }
        } );
    }

    /**
     * Log detalhado quando um evento é disparado.
     *
     * @param mixed $event
     * @return void
     */
    private function logEventDispatched( $event ): void
    {
        $eventName = get_class( $event );

        Log::info( 'Evento de notificação por e-mail disparado', [
            'event'           => $eventName,
            'event_data'      => $this->extractEventData( $event ),
            'listeners_count' => count( $this->getListenersForEvent( $eventName ) ),
            'timestamp'       => now()->toDateTimeString(),
        ] );
    }

    /**
     * Extrai dados relevantes do evento para logging.
     *
     * @param mixed $event
     * @return array
     */
    private function extractEventData( $event ): array
    {
        $data = [];

        // Extrai dados específicos baseado no tipo de evento
        switch ( get_class( $event ) ) {
            case UserRegistered::class:
                $data = [
                    'user_id'   => $event->user->id,
                    'email'     => $event->user->email,
                    'tenant_id' => $event->tenant?->id,
                ];
                break;

            case InvoiceCreated::class:
                $data = [
                    'invoice_id'   => $event->invoice->id,
                    'invoice_code' => $event->invoice->code,
                    'customer_id'  => $event->customer->id,
                    'tenant_id'    => $event->tenant?->id,
                ];
                break;

            case StatusUpdated::class:
                $data = [
                    'entity_type' => class_basename( $event->entity ),
                    'entity_id'   => $event->entity->id,
                    'old_status'  => $event->oldStatus,
                    'new_status'  => $event->newStatus,
                    'tenant_id'   => $event->tenant?->id,
                ];
                break;

            case PasswordResetRequested::class:
                $data = [
                    'user_id'   => $event->user->id,
                    'email'     => $event->user->email,
                    'tenant_id' => $event->tenant?->id,
                ];
                break;

            case EmailVerificationRequested::class:
                $data = [
                    'user_id'   => $event->user->id,
                    'email'     => $event->user->email,
                    'tenant_id' => $event->tenant?->id,
                ];
                break;

            case SocialLoginWelcome::class:
                $data = [
                    'user_id'   => $event->user->id,
                    'email'     => $event->user->email,
                    'tenant_id' => $event->tenant?->id,
                    'provider'  => $event->provider,
                ];
                break;

            case SocialAccountLinked::class:
                $data = [
                    'user_id'   => $event->user->id,
                    'email'     => $event->user->email,
                    'tenant_id' => $event->user->tenant_id,
                    'provider'  => $event->provider,
                ];
                break;

            case SupportTicketCreated::class:
                $data = [
                    'support_id' => $event->support->id,
                    'email'      => $event->support->email,
                    'subject'    => $event->support->subject,
                    'tenant_id'  => $event->tenant?->id,
                ];
                break;

            case SupportTicketResponded::class:
                $data = [
                    'ticket_id'      => $event->ticket[ 'id' ] ?? null,
                    'ticket_subject' => $event->ticket[ 'subject' ] ?? 'Sem assunto',
                    'tenant_id'      => $event->tenant?->id,
                ];
                break;
        }

        return $data;
    }

    /**
     * Obtém o número de listeners registrados para um evento específico.
     *
     * @param string $eventName
     * @return array
     */
    private function getListenersForEvent( string $eventName ): array
    {
        $listeners = [];

        foreach ( $this->listen as $event => $eventListeners ) {
            if ( $event === $eventName ) {
                $listeners = $eventListeners;
                break;
            }
        }

        return $listeners;
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     *
     * CORREÇÃO: Desabilitar descoberta automática para evitar conflitos
     * com o registro manual do evento EmailVerificationRequested.
     *
     * @return bool
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }

    /**
     * Get the listener directories that should be used to discover events.
     *
     * @return array<int, string>
     */
    protected function discoverEventsWithin(): array
    {
        return [
            app_path( 'Listeners' ),
        ];
    }

}
