<?php

declare(strict_types=1);

namespace App\Services\Application;

use App\Enums\TokenType;
use App\Models\User;
use App\Models\UserConfirmationToken;
use App\Repositories\UserConfirmationTokenRepository;
use App\Services\Core\Abstracts\AbstractBaseService;
use App\Support\ServiceResult;
use DateTime;
use Exception;
use Illuminate\Support\Facades\Log;

/**
 * Serviço para gerenciamento de tokens de confirmação de usuário.
 *
 * Este serviço centraliza toda a lógica de criação e atualização de tokens
 * de confirmação, seguindo a arquitetura estabelecida.
 *
 * Funcionalidades principais:
 * - Criação de tokens seguros com expiração configurável
 * - Remoção automática de tokens antigos do usuário
 * - Validação de tipos de token suportados
 * - Tratamento robusto de erros com logging detalhado
 * - Preservação do isolamento multi-tenant
 *
 * NOTA: Este serviço foca exclusivamente na gestão de tokens, sem
 * responsabilidade sobre envio de e-mails ou eventos relacionados.
 */
class UserConfirmationTokenService extends AbstractBaseService
{
    protected UserConfirmationTokenRepository $userConfirmationTokenRepository;

    public function __construct( UserConfirmationTokenRepository $userConfirmationTokenRepository )
    {
        $this->userConfirmationTokenRepository = $userConfirmationTokenRepository;
    }

    /**
     * Cria um novo token de confirmação para o usuário.
     *
     * Este método implementa a lógica de persistência de tokens:
     * 1. Remove tokens antigos do usuário automaticamente
     * 2. Valida tipo de token suportado usando TokenType enum
     * 3. Salva token no banco de dados
     * 4. Retorna resultado usando ServiceResult
     *
     * @param User $user Usuário que receberá o token
     * @param string $token Token de confirmação em formato base64url
     * @param TokenType $type Tipo de token usando enum TokenType
     * @param DateTime $expiresAt Data de expiração do token
     * @return ServiceResult Resultado da operação
     */
    public function createToken( User $user, string $token, TokenType $type, DateTime $expiresAt ): ServiceResult
    {
        try {
            // Validar tipo de token suportado usando enum
            if ( !TokenType::isValid( $type->value ) ) {
                return ServiceResult::error(
                    \App\Enums\OperationStatus::INVALID_DATA,
                    'Tipo de token não suportado. Tipos válidos: ' . implode( ', ', TokenType::getAllTypes() ),
                );
            }

            // 1. Remover tokens antigos do usuário automaticamente
            Log::info( 'Removendo tokens antigos do usuário', [
                'user_id'   => $user->id,
                'tenant_id' => $user->tenant_id,
                'email'     => $user->email,
                'type'      => $type,
            ] );

            $deletedCount = $this->userConfirmationTokenRepository->deleteByUserId( $user->id );
            if ( $deletedCount > 0 ) {
                Log::info( 'Tokens antigos removidos com sucesso', [
                    'user_id'        => $user->id,
                    'tokens_deleted' => $deletedCount,
                    'type'           => $type,
                ] );
            }

            Log::info( 'Criando novo token de confirmação', [
                'user_id'      => $user->id,
                'tenant_id'    => $user->tenant_id,
                'email'        => $user->email,
                'type'         => $type,
                'expires_at'   => $expiresAt,
                'token_length' => strlen( $token ),
            ] );

            // 2. Criar e salvar token
            $confirmationToken = new UserConfirmationToken( [
                'user_id'    => $user->id,
                'tenant_id'  => $user->tenant_id,
                'token'      => $token,
                'expires_at' => $expiresAt,
                'type'       => $type,
            ] );

            $savedToken = $this->userConfirmationTokenRepository->create( $confirmationToken->toArray() );

            Log::info( 'Token de confirmação criado', [
                'user_id'    => $user->id,
                'tenant_id'  => $user->tenant_id,
                'token_id'   => $savedToken->id,
                'type'       => $type,
                'expires_at' => $expiresAt,
            ] );

            return ServiceResult::success( [
                'token'      => $token,
                'expires_at' => $expiresAt,
                'type'       => $type,
                'user'       => $user,
            ], 'Token de confirmação criado com sucesso.' );

        } catch ( Exception $e ) {
            Log::error( 'Erro ao criar token de confirmação', [
                'user_id'   => $user->id,
                'tenant_id' => $user->tenant_id,
                'email'     => $user->email,
                'type'      => $type,
                'error'     => $e->getMessage(),
                'trace'     => $e->getTraceAsString(),
            ] );

            return ServiceResult::error(
                \App\Enums\OperationStatus::ERROR,
                'Erro interno ao criar token de confirmação. Tente novamente.',
                null,
                $e,
            );
        }
    }

    /**
     * Atualiza um token existente para o usuário.
     *
     * Remove tokens antigos e cria um novo com os parâmetros fornecidos.
     *
     * @param User $user Usuário proprietário do token
     * @param string $token Token de confirmação em formato base64url
     * @param TokenType $type Tipo de token usando enum TokenType
     * @param DateTime $expiresAt Data de expiração do token
     * @return ServiceResult Resultado da operação
     */
    public function updateToken( User $user, string $token, TokenType $type, DateTime $expiresAt ): ServiceResult
    {
        return $this->createToken( $user, $token, $type, $expiresAt );
    }

    /**
     * Define filtros suportados pelo serviço.
     */
    protected function getSupportedFilters(): array
    {
        return [
            'id',
            'user_id',
            'tenant_id',
            'token',
            'expires_at',
            'type',
            'created_at',
            'updated_at',
        ];
    }

}
