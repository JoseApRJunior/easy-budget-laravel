<?php

declare(strict_types=1);

namespace App\Services\Domain;

use App\Enums\OperationStatus;
use App\Models\Support;
use App\Repositories\SupportRepository;
use App\Services\Core\Abstracts\AbstractBaseService;
use App\Support\ServiceResult;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Serviço para gerenciamento de tickets de suporte.
 *
 * Esta classe implementa toda a lógica de negócio relacionada a tickets de suporte,
 * incluindo criação, salvamento no banco de dados e envio de emails.
 */
class SupportService extends AbstractBaseService
{
    /**
     * Construtor do serviço de suporte.
     *
     * @param SupportRepository $supportRepository Repositório para operações de suporte
     */
    public function __construct( SupportRepository $supportRepository )
    {
        parent::__construct( $supportRepository );
    }

    /**
     * Retorna lista de filtros suportados pelo serviço.
     *
     * @return array<string> Lista de campos que podem ser filtrados.
     */
    protected function getSupportedFilters(): array
    {
        return [
            'id',
            'first_name',
            'last_name',
            'email',
            'subject',
            'status',
            'tenant_id',
            'created_at',
            'updated_at',
        ];
    }

    /**
     * Cria um novo ticket de suporte, salva no banco e dispara evento.
     *
     * @param array $data Dados do ticket de suporte
     * @return ServiceResult Resultado da operação
     */
    public function createSupportTicket( array $data ): ServiceResult
    {
        Log::info( 'SupportService::createSupportTicket - Iniciando criação de ticket', [
            'email'          => $data[ 'email' ] ?? 'N/A',
            'subject'        => $data[ 'subject' ] ?? 'N/A',
            'has_first_name' => !empty( $data[ 'first_name' ] ),
            'has_last_name'  => !empty( $data[ 'last_name' ] ),
            'message_length' => strlen( $data[ 'message' ] ?? '' ),
        ] );

        // Validação dos dados
        $validationResult = $this->validateSupportData( $data );
        if ( !$validationResult->isSuccess() ) {
            Log::warning( 'SupportService::createSupportTicket - Validação falhou', [
                'email'             => $data[ 'email' ] ?? 'N/A',
                'validation_errors' => $validationResult->getData(),
            ] );
            return $validationResult;
        }

        Log::info( 'SupportService::createSupportTicket - Validação passou, iniciando transação', [
            'email' => $data[ 'email' ] ?? 'N/A',
        ] );

        DB::beginTransaction();

        try {
            // Prepara os dados para salvamento
            $supportData = $this->prepareSupportData( $data );

            Log::info( 'SupportService::createSupportTicket - Dados preparados', [
                'email'     => $supportData[ 'email' ],
                'tenant_id' => $supportData[ 'tenant_id' ],
                'status'    => $supportData[ 'status' ],
            ] );

            // Salva o ticket no banco de dados
            $support = $this->repository->create( $supportData );

            if ( !$support ) {
                Log::error( 'SupportService::createSupportTicket - Falha ao salvar no banco', [
                    'email'        => $supportData[ 'email' ],
                    'support_data' => $supportData,
                ] );
                DB::rollBack();
                return $this->error(
                    OperationStatus::ERROR,
                    'Erro ao salvar ticket de suporte no banco de dados.',
                );
            }

            Log::info( 'SupportService::createSupportTicket - Ticket salvo com sucesso', [
                'support_id' => $support->id,
                'email'      => $support->email,
                'tenant_id'  => $support->tenant_id,
            ] );

            // Obtém tenant efetivo para o evento
            $effectiveTenant = $this->getEffectiveTenant();
            Log::info( 'SupportService::createSupportTicket - Tenant efetivo obtido', [
                'support_id'  => $support->id,
                'tenant_id'   => $effectiveTenant?->id,
                'tenant_name' => $effectiveTenant?->name,
            ] );

            // Dispara evento para processamento assíncrono
            \App\Events\SupportTicketCreated::dispatch( $support, $data, $effectiveTenant );

            Log::info( 'SupportService::createSupportTicket - Evento disparado', [
                'support_id'       => $support->id,
                'event_class'      => 'App\\Events\\SupportTicketCreated',
                'event_dispatched' => true,
            ] );

            DB::commit();

            Log::info( 'SupportService::createSupportTicket - Transação confirmada com sucesso', [
                'support_id'       => $support->id,
                'email'            => $support->email,
                'subject'          => $support->subject,
                'tenant_id'        => $support->tenant_id,
                'event_dispatched' => true,
            ] );

            return $this->success(
                $support,
                'Ticket de suporte criado com sucesso. Email será processado em breve.',
            );

        } catch ( Exception $e ) {
            DB::rollBack();

            Log::error( 'SupportService::createSupportTicket - Erro na transação', [
                'error'      => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'email'      => $data[ 'email' ] ?? 'N/A',
                'data'       => $data,
            ] );

            return $this->error(
                OperationStatus::ERROR,
                'Erro interno ao processar ticket de suporte.',
                null,
                $e,
            );
        }
    }

    /**
     * Valida os dados do ticket de suporte.
     *
     * @param array $data Dados a serem validados
     * @return ServiceResult Resultado da validação
     */
    private function validateSupportData( array $data ): ServiceResult
    {
        try {
            $validator = Validator::make( $data, [
                'first_name' => 'nullable|string|max:255',
                'last_name'  => 'nullable|string|max:255',
                'email'      => 'required|email|max:255',
                'subject'    => 'required|string|max:255',
                'message'    => 'required|string',
            ], [
                'email.required'   => 'O campo email é obrigatório.',
                'email.email'      => 'O email deve ter um formato válido.',
                'subject.required' => 'O campo assunto é obrigatório.',
                'message.required' => 'O campo mensagem é obrigatório.',
            ] );

            if ( $validator->fails() ) {
                return $this->error(
                    OperationStatus::VALIDATION_ERROR,
                    'Dados inválidos.',
                    $validator->errors()->toArray(),
                );
            }

            return $this->success( null, 'Dados válidos.' );

        } catch ( Exception $e ) {
            return $this->error(
                OperationStatus::ERROR,
                'Erro na validação dos dados.',
                null,
                $e,
            );
        }
    }

    /**
     * Prepara os dados para salvamento no banco.
     *
     * @param array $data Dados originais
     * @return array Dados preparados
     */
    private function prepareSupportData( array $data ): array
    {
        return [
            'first_name' => $data[ 'first_name' ] ?? null,
            'last_name'  => $data[ 'last_name' ] ?? null,
            'email'      => $data[ 'email' ],
            'subject'    => $data[ 'subject' ],
            'message'    => $data[ 'message' ],
            'status'     => Support::STATUS_ABERTO,
            'tenant_id'  => $this->getEffectiveTenantId(),
        ];
    }

    /**
     * Obtém o tenant_id efetivo para o ticket de suporte.
     *
     * Para usuários autenticados: usa o tenant do usuário
     * Para usuários não autenticados: usa o tenant público (ID 1)
     *
     * @return int ID do tenant
     */
    private function getEffectiveTenantId(): int
    {
        // Se usuário está autenticado, usa seu tenant
        $userTenantId = $this->tenantId();
        if ( $userTenantId ) {
            return $userTenantId;
        }

        // Para usuários não autenticados, usa o tenant público
        return 1; // ID do tenant público criado pelo PublicTenantSeeder
    }

    /**
     * Obtém o tenant efetivo para o ticket de suporte.
     *
     * Para usuários autenticados: retorna o tenant do usuário
     * Para usuários não autenticados: retorna o tenant público
     *
     * @return \App\Models\Tenant|null Tenant efetivo
     */
    private function getEffectiveTenant(): ?\App\Models\Tenant
    {
        try {
            // Se usuário está autenticado, usa seu tenant
            $userTenant = $this->authUser()?->tenant;
            if ( $userTenant ) {
                return $userTenant;
            }

            // Para usuários não autenticados, busca o tenant público
            return \App\Models\Tenant::find( 1 ); // ID do tenant público
        } catch ( Exception $e ) {
            Log::warning( 'Erro ao obter tenant do usuário: ' . $e->getMessage() );
            // Fallback para tenant público em caso de erro
            return \App\Models\Tenant::find( 1 );
        }
    }

    /**
     * Obtém tickets de suporte por status.
     *
     * @param string $status Status dos tickets
     * @return ServiceResult Resultado com os tickets
     */
    public function getTicketsByStatus( string $status ): ServiceResult
    {
        try {
            $tickets = $this->repository->findByStatus( $status );

            return $this->success(
                $tickets,
                "Tickets com status '{$status}' recuperados com sucesso.",
            );

        } catch ( Exception $e ) {
            return $this->error(
                OperationStatus::ERROR,
                'Erro ao buscar tickets por status.',
                null,
                $e,
            );
        }
    }

    /**
     * Obtém estatísticas de tickets de suporte.
     *
     * @return ServiceResult Resultado com as estatísticas
     */
    public function getTicketStats(): ServiceResult
    {
        try {
            $stats = $this->repository->getStatusStats();

            return $this->success(
                $stats,
                'Estatísticas de tickets recuperadas com sucesso.',
            );

        } catch ( Exception $e ) {
            return $this->error(
                OperationStatus::ERROR,
                'Erro ao obter estatísticas de tickets.',
                null,
                $e,
            );
        }
    }

    /**
     * Atualiza o status de um ticket de suporte.
     *
     * @param int $ticketId ID do ticket
     * @param string $status Novo status
     * @return ServiceResult Resultado da operação
     */
    public function updateTicketStatus( int $ticketId, string $status ): ServiceResult
    {
        try {
            // Valida se o status é válido
            $validStatuses = [
                Support::STATUS_ABERTO,
                Support::STATUS_RESPONDIDO,
                Support::STATUS_RESOLVIDO,
                Support::STATUS_FECHADO,
            ];

            if ( !in_array( $status, $validStatuses ) ) {
                return $this->error(
                    OperationStatus::VALIDATION_ERROR,
                    'Status inválido.',
                );
            }

            return $this->update( $ticketId, [ 'status' => $status ] );

        } catch ( Exception $e ) {
            return $this->error(
                OperationStatus::ERROR,
                'Erro ao atualizar status do ticket.',
                null,
                $e,
            );
        }
    }

}
