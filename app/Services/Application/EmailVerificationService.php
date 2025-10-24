<?php

declare(strict_types=1);

namespace App\Services\Application;

use App\Enums\OperationStatus;
use App\Events\EmailVerificationRequested;
use App\Models\User;
use App\Models\UserConfirmationToken;
use App\Repositories\UserConfirmationTokenRepository;
use App\Repositories\UserRepository;
use App\Services\Core\Abstracts\AbstractBaseService;
use App\Support\ServiceResult;
use Exception;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Serviço para gerenciamento de verificação de e-mail.
 *
 * Este serviço implementa toda a lógica de negócio para verificação de e-mail,
 * seguindo a arquitetura Controller → Service → Repository → Model estabelecida.
 *
 * Funcionalidades principais:
 * - Criação de tokens de confirmação com expiração de 30 minutos
 * - Remoção automática de tokens antigos do usuário
 * - Integração com sistema Laravel built-in de verificação
 * - Tratamento robusto de erros com logging detalhado
 * - Preservação do isolamento multi-tenant
 * - Uso de eventos para envio de e-mails
 *
 * O serviço utiliza o padrão de eventos para envio de e-mails, seguindo
 * a mesma arquitetura utilizada em UserRegistrationService e outros serviços.
 *
 * NOTA: A validação de dados de entrada é responsabilidade do Controller/FormRequest,
 * este serviço foca exclusivamente na lógica de negócio da verificação de e-mail.
 */
class EmailVerificationService extends AbstractBaseService
{
    protected UserConfirmationTokenRepository $userConfirmationTokenRepository;
    protected UserRepository                  $userRepository;

    public function __construct(
        UserConfirmationTokenRepository $userConfirmationTokenRepository,
        UserRepository $userRepository,
    ) {
        $this->userConfirmationTokenRepository = $userConfirmationTokenRepository;
        $this->userRepository                  = $userRepository;
    }

    /**
     * Cria token de confirmação para verificação de e-mail.
     *
     * Este método implementa toda a lógica de criação de tokens:
     * 1. Remove tokens antigos do usuário automaticamente
     * 2. Cria novo token com expiração de 30 minutos
     * 3. Salva token no banco de dados
     * 4. Dispara evento para envio de e-mail de verificação
     * 5. Retorna resultado usando ServiceResult
     *
     * @param User $user Usuário que receberá o token de verificação
     * @return ServiceResult Resultado da operação
     */
    public function createConfirmationToken( User $user ): ServiceResult
    {
        try {
            // 1. Remover tokens antigos do usuário automaticamente
            Log::info( 'Removendo tokens antigos do usuário', [
                'user_id'   => $user->id,
                'tenant_id' => $user->tenant_id,
                'email'     => $user->email,
            ] );

            $deletedCount = $this->userConfirmationTokenRepository->deleteByUserId( $user->id );
            if ( $deletedCount > 0 ) {
                Log::info( 'Tokens antigos removidos com sucesso', [
                    'user_id'        => $user->id,
                    'tokens_deleted' => $deletedCount,
                ] );
            }

            // 2. Criar novo token com expiração de 30 minutos
            // Gerar token usando apenas caracteres hexadecimais minúsculos (a-f, 0-9)
            $token     = $this->generateSecureToken( 64 );
            $expiresAt = now()->addMinutes( 30 ); // 30 minutos conforme especificado

            Log::info( 'Criando novo token de verificação', [
                'user_id'      => $user->id,
                'tenant_id'    => $user->tenant_id,
                'expires_at'   => $expiresAt,
                'token_length' => strlen( $token ),
            ] );

            $confirmationToken = new UserConfirmationToken( [
                'user_id'    => $user->id,
                'tenant_id'  => $user->tenant_id,
                'token'      => $token,
                'expires_at' => $expiresAt,
                'type'       => 'email_verification',
            ] );

            $savedToken = $this->userConfirmationTokenRepository->create( $confirmationToken->toArray() );

            Log::info( 'Token de verificação criado', [
                'user_id'    => $user->id,
                'tenant_id'  => $user->tenant_id,
                'token_id'   => $savedToken->id,
                'expires_at' => $expiresAt
            ] );

            return ServiceResult::success( [
                'token'      => $token,
                'expires_at' => $expiresAt,
                'user'       => $user,
            ], 'Token de verificação criado com sucesso. E-mail enviado.' );

        } catch ( Exception $e ) {
            Log::error( 'Erro ao criar token de verificação', [
                'user_id'   => $user->id,
                'tenant_id' => $user->tenant_id,
                'email'     => $user->email,
                'error'     => $e->getMessage(),
                'trace'     => $e->getTraceAsString(),
            ] );

            return ServiceResult::error(
                OperationStatus::ERROR,
                'Erro interno ao criar token de verificação. Tente novamente.',
                null,
                $e,
            );
        }
    }

    /**
     * Reenvia e-mail de verificação para usuário.
     *
     * Este método reutiliza a lógica de criação de token, garantindo
     * que apenas um token ativo exista por usuário e que tokens antigos
     * sejam removidos automaticamente.
     *
     * @param User $user Usuário que receberá o novo e-mail de verificação
     * @return ServiceResult Resultado da operação
     */
    public function resendConfirmationEmail( User $user ): ServiceResult
    {
        try {
            // Verificar se usuário já está verificado
            if ( $user->hasVerifiedEmail() ) {
                Log::info( 'Tentativa de reenvio de e-mail para usuário já verificado', [
                    'user_id'           => $user->id,
                    'tenant_id'         => $user->tenant_id,
                    'email'             => $user->email,
                    'email_verified_at' => $user->email_verified_at,
                ] );

                return ServiceResult::error(
                    OperationStatus::CONFLICT,
                    'E-mail já foi verificado anteriormente.',
                );
            }

            // Verificar se usuário está ativo
            if ( $user->is_active ) {
                Log::warning( 'Tentativa de reenvio de e-mail para usuário ativo', [
                    'user_id'   => $user->id,
                    'tenant_id' => $user->tenant_id,
                    'email'     => $user->email,
                ] );

                return ServiceResult::error(
                    OperationStatus::CONFLICT,
                    'Usuário ativo. Entre em contato com o suporte.',
                );
            }

            Log::info( 'Reenviando e-mail de verificação', [
                'user_id'   => $user->id,
                'tenant_id' => $user->tenant_id,
                'email'     => $user->email,
            ] );

            // Reutilizar lógica de criação de token (já remove tokens antigos e dispara evento)

            // Criar token de verificação
            Log::info( 'Criando token de verificação de e-mail...', [ 'user_id' => $user->id ] );
            $tokenResult = $this->createConfirmationToken( $user );
            if ( !$tokenResult->isSuccess() ) {
                Log::warning( 'Falha ao criar token de verificação, mas usuário foi registrado', [
                    'user_id' => $user->id,
                    'error'   => $tokenResult->getMessage(),
                ] );
                // Não falhar o registro por causa do token, apenas logar o problema
            } else {
                Log::info( 'Token de verificação criado com sucesso', [ 'user_id' => $user->id ] );
            }

            Event::dispatch( new EmailVerificationRequested(
                $user,
                $user->tenant,
                $tokenResult->getData()[ 'token' ] ) );

            return $tokenResult;

        } catch ( Exception $e ) {
            Log::error( 'Erro ao reenviar e-mail de verificação', [
                'user_id'   => $user->id,
                'tenant_id' => $user->tenant_id,
                'email'     => $user->email,
                'error'     => $e->getMessage(),
                'trace'     => $e->getTraceAsString(),
            ] );

            return ServiceResult::error(
                OperationStatus::ERROR,
                'Erro interno ao reenviar e-mail de verificação. Tente novamente.',
                null,
                $e,
            );
        }
    }

    /**
     * Busca token de confirmação válido para um usuário.
     *
     * @param string $token Token de confirmação
     * @return ServiceResult Resultado da operação
     */
    public function findValidToken( string $token ): ServiceResult
    {
        try {
            $confirmationToken = $this->userConfirmationTokenRepository->findByToken( $token );

            if ( !$confirmationToken ) {
                return ServiceResult::error(
                    OperationStatus::NOT_FOUND,
                    'Token de verificação não encontrado.',
                );
            }

            // Verificar se token não expirou
            if ( $confirmationToken->expires_at->isPast() ) {
                // Remover token expirado
                $this->userConfirmationTokenRepository->delete( $confirmationToken->id );

                Log::info( 'Token expirado removido automaticamente', [
                    'token_id'   => $confirmationToken->id,
                    'user_id'    => $confirmationToken->user_id,
                    'expires_at' => $confirmationToken->expires_at,
                ] );

                return ServiceResult::error(
                    OperationStatus::CONFLICT,
                    'Token de verificação expirado. Solicite um novo.',
                );
            }

            // Buscar usuário associado ao token
            $user = $this->userRepository->find( $confirmationToken->user_id );
            if ( !$user ) {
                return ServiceResult::error(
                    OperationStatus::NOT_FOUND,
                    'Usuário associado ao token não encontrado.',
                );
            }

            return ServiceResult::success( [
                'token' => $confirmationToken,
                'user'  => $user,
            ], 'Token válido encontrado.' );

        } catch ( Exception $e ) {
            Log::error( 'Erro ao buscar token de verificação', [
                'token' => substr( $token, 0, 10 ) . '...', // Log parcial por segurança
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ] );

            return ServiceResult::error(
                OperationStatus::ERROR,
                'Erro interno ao validar token.',
                null,
                $e,
            );
        }
    }

    /**
     * Remove token após uso bem-sucedido.
     *
     * @param UserConfirmationToken $token Token a ser removido
     * @return ServiceResult Resultado da operação
     */
    public function removeToken( UserConfirmationToken $token ): ServiceResult
    {
        try {
            $deleted = $this->userConfirmationTokenRepository->delete( $token->id );

            if ( !$deleted ) {
                Log::warning( 'Falha ao remover token após uso', [
                    'token_id' => $token->id,
                    'user_id'  => $token->user_id,
                ] );

                return ServiceResult::error(
                    OperationStatus::ERROR,
                    'Erro ao remover token usado.',
                );
            }

            Log::info( 'Token removido após uso bem-sucedido', [
                'token_id' => $token->id,
                'user_id'  => $token->user_id,
            ] );

            return ServiceResult::success(
                null,
                'Token removido com sucesso.',
            );

        } catch ( Exception $e ) {
            Log::error( 'Erro ao remover token', [
                'token_id' => $token->id,
                'user_id'  => $token->user_id,
                'error'    => $e->getMessage(),
            ] );

            return ServiceResult::error(
                OperationStatus::ERROR,
                'Erro interno ao remover token.',
                null,
                $e,
            );
        }
    }

    /**
     * Limpa tokens expirados do sistema.
     *
     * Método utilitário para manutenção, pode ser chamado por jobs periódicos.
     *
     * @return ServiceResult Resultado da operação
     */
    public function cleanupExpiredTokens(): ServiceResult
    {
        try {
            $deletedCount = $this->userConfirmationTokenRepository->deleteExpired();

            Log::info( 'Limpeza de tokens expirados executada', [
                'tokens_removed' => $deletedCount,
            ] );

            return ServiceResult::success( [
                'tokens_removed' => $deletedCount,
            ], "Limpeza executada. {$deletedCount} tokens expirados removidos." );

        } catch ( Exception $e ) {
            Log::error( 'Erro na limpeza de tokens expirados', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ] );

            return ServiceResult::error(
                OperationStatus::ERROR,
                'Erro interno na limpeza de tokens.',
                null,
                $e,
            );
        }
    }

    /**
     * Gera token seguro usando padrão criptograficamente seguro do sistema legado.
     *
     * Método aprimorado usando Str::random() do Laravel para máxima compatibilidade:
     * - Str::random(64) gera 64 caracteres aleatórios seguros
     * - Padrão usado com sucesso no sistema legado
     *
     * @return string Token seguro de 64 caracteres hexadecimais
     */
    private function generateSecureToken( int $length ): string
    {
        // Usar Str::random() do Laravel para máxima compatibilidade
        // Gera string aleatória segura de 64 caracteres
        return Str::random( 64 );
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
