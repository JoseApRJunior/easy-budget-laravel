<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\OperationStatus;
use App\Interfaces\ServiceNoTenantInterface;
use App\Models\Notification;
use App\Services\Abstracts\BaseNoTenantService;
use App\Support\ServiceResult;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class NotificationService extends BaseNoTenantService implements ServiceNoTenantInterface
{
    private ActivityService $activityService;

    public function __construct( ActivityService $activityService )
    {
        $this->activityService = $activityService;
    }

    protected function findEntityById( int $id ): ?Model
    {
        // Assume Notification model exists for stored notifications
        return \App\Models\Notification::find( $id );
    }

    protected function listEntities( array $filters = [] ): array
    {
        $query = \App\Models\Notification::query();

        if ( isset( $filters[ 'order' ] ) ) {
            $order = $filters[ 'order' ];
            $query->orderBy( $order[ 0 ] ?? 'id', $order[ 1 ] ?? 'asc' );
        }

        if ( isset( $filters[ 'limit' ] ) ) {
            $query->limit( (int) $filters[ 'limit' ] );
        }

        return $query->get()->all();
    }

    protected function createEntity( array $data ): Model
    {
        $notification = new \App\Models\Notification();
        $notification->fill( $data );
        $notification->save();
        return $notification;
    }

    protected function updateEntity( int $id, array $data ): Model
    {
        $entity = $this->findEntityById( $id );
        if ( !$entity ) {
            throw new \Exception( 'Notification not found' );
        }

        $entity->fill( $data );
        $this->saveEntity( $entity );

        return $entity;
    }

    protected function deleteEntity( int $id ): bool
    {
        $entity = $this->findEntityById( $id );
        if ( !$entity ) {
            return false;
        }
        return $entity->delete();
    }

    protected function canDeleteEntity( int $id ): bool
    {
        // Lógica para verificar se pode deletar (ex: não referenciada)
        return true;
    }

    /**
     * Envia notificação (email/SMS).
     */
    public function sendNotification( string $type, array $data ): ServiceResult
    {
        $result = match ( $type ) {
            'email' => $this->sendEmail( $data ),
            'sms'   => $this->sendSMS( $data ),
            default => $this->error( OperationStatus::INVALID_DATA, 'Tipo de notificação inválido.' ),
        };

        if ( $result->isSuccess() ) {
            $logData = [ 'type' => $type, 'data' => $data ];
            $this->activityService->logActivity( 'notification_sent', $logData );
        }

        return $result;
    }

    private function sendEmail( array $data ): ServiceResult
    {
        // Stub para envio de email
        try {
            \Illuminate\Support\Facades\Mail::raw( $data[ 'message' ] ?? 'Notificação padrão', function ($message) use ($data) {
                $message->to( $data[ 'email' ] ?? 'default@example.com' )
                    ->subject( $data[ 'subject' ] ?? 'Notificação' );
            } );
            return $this->success( [ 'sent' => true ], 'Email enviado.' );
        } catch ( \Exception $e ) {
            return $this->error( OperationStatus::ERROR, 'Falha ao enviar email: ' . $e->getMessage() );
        }
    }

    private function sendSMS( array $data ): ServiceResult
    {
        // Stub para SMS (implementar com Twilio ou similar futuramente)
        return $this->success( [ 'sent' => true ], 'SMS enviado (stub).' );
    }

    // Métodos da interface (delegados ou custom)
    public function create( array $data ): ServiceResult
    {
        return parent::create( $data );
    }

    public function getById( int $id ): ServiceResult
    {
        return parent::getById( $id );
    }

    // listAll removed - use list( ['order' => $orderBy, 'limit' => $limit] ) instead

    /**
     * Envia notificação específica para atualização de status do budget.
     *
     * @param \App\Models\Budget $budget Entidade do budget
     * @param string $oldStatus Status anterior
     * @param string $newStatus Novo status
     * @return ServiceResult
     */
    public function sendBudgetStatusUpdate( \App\Models\Budget $budget, string $oldStatus, string $newStatus ): ServiceResult
    {
        try {
            $subject = "Atualização de Status - Orçamento #{$budget->id}";
            $message = "O orçamento #{$budget->id} teve seu status alterado de '{$oldStatus}' para '{$newStatus}'.";

            // Enviar email para o cliente
            if ( $budget->customer && $budget->customer->email ) {
                $emailData = [ 
                    'email'   => $budget->customer->email,
                    'subject' => $subject,
                    'message' => $message
                ];

                $result = $this->sendEmail( $emailData );
                if ( !$result->isSuccess() ) {
                    return $result;
                }
            }

            // Log da notificação
            Log::info( "Budget status update notification sent: Budget {$budget->id} from {$oldStatus} to {$newStatus}" );

            return $this->success( [ 'sent' => true ], 'Notificação de atualização de status enviada.' );
        } catch ( \Exception $e ) {
            return $this->error( OperationStatus::ERROR, 'Falha ao enviar notificação: ' . $e->getMessage() );
        }
    }

    /**
     * Validação específica para notificações globais.
     *
     * @param array $data Dados a serem validados
     * @param bool $isUpdate Se é uma operação de atualização
     * @return ServiceResult Resultado da validação
     */
    protected function validateForGlobal( array $data, bool $isUpdate = false ): ServiceResult
    {
        $id    = $data[ 'id' ] ?? null;
        $rules = [ 
            'type'         => 'required|string|in:email,sms,push',
            'recipient'    => 'required|string|max:255',
            'message'      => 'required|string|max:1000',
            'subject'      => 'nullable|string|max:255',
            'priority'     => 'nullable|in:low,medium,high,urgent',
            'scheduled_at' => 'nullable|date|after:now',
            'metadata'     => 'nullable|array'
        ];

        $validator = Validator::make( $data, $rules );
        if ( $validator->fails() ) {
            $messages = $validator->errors()->all();
            return $this->error( OperationStatus::INVALID_DATA, implode( ', ', $messages ) );
        }

        return $this->success();
    }

}