<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\OperationStatus;
use App\Models\User;
use App\Models\UserConfirmationToken;
use App\Repositories\UserConfirmationTokenRepository;
use App\Repositories\UserRepository;
use App\Services\Abstracts\BaseTenantService;
use App\Support\ServiceResult;
use App\Traits\SlugGenerator;
use Exception;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

/**
 * Serviço para operações de usuário com tenant.
 *
 * Migra lógica legacy: criação com hash de senha, tokens de confirmação,
 * ativação de conta, gerenciamento de usuários. Usa Eloquent via repositórios.
 * Mantém compatibilidade API com métodos *ByTenantId.
 */
class UserService extends BaseTenantService
{
    use SlugGenerator;

    /**
     * @var UserRepository
     */
    private UserRepository $userRepository;

    /**
     * @var UserConfirmationTokenRepository
     */
    private UserConfirmationTokenRepository $tokenRepository;

    /**
     * Construtor com injeção de dependências.
     */
    public function __construct( UserRepository $userRepository, UserConfirmationTokenRepository $tokenRepository )
    {
        $this->userRepository  = $userRepository;
        $this->tokenRepository = $tokenRepository;
    }

    // MÉTODOS ABSTRATOS OBRIGATÓRIOS DA BaseTenantService

    /**
     * Busca um usuário pelo ID e tenant_id.
     *
     * @param int $id ID do usuário
     * @param int $tenant_id ID do tenant (snake_case conforme padrão legacy)
     * @return ServiceResult
     */
    public function getByIdAndTenantId( int $id, int $tenant_id ): ServiceResult
    {
        try {
            $user = $this->findEntityByIdAndTenantId( $id, $tenant_id );
            if ( !$user ) {
                return $this->error( OperationStatus::NOT_FOUND, 'Usuário não encontrado.' );
            }

            return $this->success( $user, 'Usuário encontrado com sucesso.' );
        } catch ( Exception $e ) {
            return $this->error( OperationStatus::ERROR, 'Falha ao buscar usuário: ' . $e->getMessage(), null, $e );
        }
    }

    /**
     * Lista usuários por tenant_id com filtros.
     *
     * @param int $tenant_id ID do tenant (snake_case conforme padrão legacy)
     * @param array $filters Filtros opcionais
     * @param ?array $orderBy Ordem dos resultados
     * @param ?int $limit Limite de resultados
     * @param ?int $offset Offset dos resultados
     * @return ServiceResult
     */
    public function listByTenantId( int $tenant_id, array $filters = [], ?array $orderBy = null, ?int $limit = null, ?int $offset = null ): ServiceResult
    {
        try {
            $users = $this->listEntitiesByTenantId( $tenant_id, $filters );
            return $this->success( $users, 'Usuários listados com sucesso.' );
        } catch ( Exception $e ) {
            return $this->error( OperationStatus::ERROR, 'Falha ao listar usuários: ' . $e->getMessage(), null, $e );
        }
    }

    /**
     * Cria usuário para tenant_id.
     *
     * @param array $data Dados do usuário
     * @param int $tenant_id ID do tenant (snake_case conforme padrão legacy)
     * @return ServiceResult
     */
    public function createByTenantId( array $data, int $tenant_id ): ServiceResult
    {
        try {
            // Validação específica
            $validation = $this->validateForTenant( $data, $tenant_id, false );
            if ( !$validation->isSuccess() ) {
                return $validation;
            }

            DB::beginTransaction();

            $user  = $this->createEntity( $data, $tenant_id );
            $saved = $this->saveEntity( $user );

            if ( !$saved ) {
                DB::rollBack();
                return $this->error( OperationStatus::ERROR, 'Falha ao criar usuário.' );
            }

            // Gerar token de confirmação (migração do legacy)
            $tokenResult = $this->generateConfirmationToken( $user->id, $tenant_id );
            if ( !$tokenResult->isSuccess() ) {
                DB::rollBack();
                return $this->error( OperationStatus::ERROR, 'Falha ao gerar token de confirmação.' );
            }

            DB::commit();
            return $this->success( [ 
                'user'  => $user,
                'token' => $tokenResult->getData()[ 'token' ]
            ], 'Usuário criado com sucesso.' );
        } catch ( Exception $e ) {
            DB::rollBack();
            return $this->error( OperationStatus::ERROR, 'Falha ao criar usuário: ' . $e->getMessage(), null, $e );
        }
    }

    /**
     * Atualiza usuário por ID e tenant_id.
     *
     * @param int $id ID do usuário
     * @param int $tenant_id ID do tenant (snake_case conforme padrão legacy)
     * @param array $data Dados de atualização
     * @return ServiceResult
     */
    public function updateByIdAndTenantId( int $id, array $data, int $tenantId ): ServiceResult
    {
        try {
            $user = $this->findEntityByIdAndTenantId( $id, $tenant_id );
            if ( !$user ) {
                return $this->error( OperationStatus::NOT_FOUND, 'Usuário não encontrado.' );
            }

            // Validação específica
            $validation = $this->validateForTenant( $data, $tenant_id, true );
            if ( !$validation->isSuccess() ) {
                return $validation;
            }

            DB::beginTransaction();

            $this->updateEntity( $user, $data, $tenant_id );
            $saved = $this->saveEntity( $user );

            if ( !$saved ) {
                DB::rollBack();
                return $this->error( OperationStatus::ERROR, 'Falha ao atualizar usuário.' );
            }

            DB::commit();
            return $this->success( $user, 'Usuário atualizado com sucesso.' );
        } catch ( Exception $e ) {
            DB::rollBack();
            return $this->error( OperationStatus::ERROR, 'Falha ao atualizar usuário: ' . $e->getMessage(), null, $e );
        }
    }

    /**
     * Deleta usuário por ID e tenant_id.
     *
     * @param int $id ID do usuário
     * @param int $tenant_id ID do tenant (snake_case conforme padrão legacy)
     * @return ServiceResult
     */
    public function deleteByIdAndTenantId( int $id, int $tenant_id ): ServiceResult
    {
        try {
            $user = $this->findEntityByIdAndTenantId( $id, $tenant_id );
            if ( !$user ) {
                return $this->error( OperationStatus::NOT_FOUND, 'Usuário não encontrado.' );
            }

            // Verificar se pode deletar
            if ( !$this->canDeleteEntity( $user ) ) {
                return $this->error( OperationStatus::CONFLICT, 'Não é possível deletar este usuário.' );
            }

            DB::beginTransaction();

            $deleted = $this->deleteEntity( $user );

            if ( !$deleted ) {
                DB::rollBack();
                return $this->error( OperationStatus::ERROR, 'Falha ao deletar usuário.' );
            }

            DB::commit();
            return $this->success( null, 'Usuário deletado com sucesso.' );
        } catch ( Exception $e ) {
            DB::rollBack();
            return $this->error( OperationStatus::ERROR, 'Falha ao deletar usuário: ' . $e->getMessage(), null, $e );
        }
    }

    // MÉTODOS TEMPLATE SOBRESCRITOS PARA LÓGICA ESPECÍFICA

    /**
     * Encontra usuário por ID e tenant.
     */
    protected function findEntityByIdAndTenantId( int $id, int $tenantId ): ?EloquentModel
    {
        return $this->userRepository->findByIdAndTenantId( $id, $tenantId );
    }

    /**
     * Lista usuários por tenant com filtros.
     */
    protected function listEntitiesByTenantId( int $tenantId, array $filters = [], ?array $orderBy = null, ?int $limit = null, ?int $offset = null ): array
    {
        return $this->userRepository->listByTenantId( $tenantId, $filters, $orderBy, $limit, $offset );
    }

    /**
     * Cria entidade usuário.
     */
    protected function createEntity( array $data, int $tenantId ): EloquentModel
    {
        $user = new User();
        $user->fill( [ 
            'tenant_id' => $tenantId,
            'name'      => $data[ 'name' ],
            'email'     => $data[ 'email' ],
            'password'  => Hash::make( $data[ 'password' ] ),
            'status'    => 'pending', // Requer confirmação
            'slug'      => $this->generateSlug( $data[ 'name' ] ),
        ] );
        return $user;
    }

    /**
     * Atualiza entidade usuário.
     */
    protected function updateEntity( EloquentModel $entity, array $data, int $tenantId ): void
    {
        if ( isset( $data[ 'password' ] ) && !empty( $data[ 'password' ] ) ) {
            $data[ 'password' ] = Hash::make( $data[ 'password' ] );
        } else {
            unset( $data[ 'password' ] );
        }

        $entity->fill( $data );
    }

    /**
     * Salva entidade (sobrescreve se necessário).
     */
    protected function saveEntity( EloquentModel $entity ): bool
    {
        return $entity->save();
    }

    /**
     * Deleta entidade usuário.
     */
    protected function deleteEntity( EloquentModel $entity ): bool
    {
        // Deletar tokens associados
        $this->tokenRepository->deleteByUserId( $entity->id );
        return $entity->delete();
    }

    /**
     * Verifica se pertence ao tenant.
     */
    protected function belongsToTenant( EloquentModel $entity, int $tenantId ): bool
    {
        return $entity->tenant_id === $tenantId;
    }

    /**
     * Verifica se pode deletar (ex: não é o último admin).
     */
    protected function canDeleteEntity( EloquentModel $entity ): bool
    {
        // Lógica: não deletar se for o único admin, etc.
        $adminCount = $this->userRepository->countAdminsByTenantId( $entity->tenant_id );
        return !( $entity->role === 'admin' && $adminCount <= 1 );
    }

    /**
     * Validação específica para usuário.
     */
    public function validateForTenant( array $data, int $tenantId, bool $isUpdate = false ): ServiceResult
    {
        $rules = [ 
            'name' => 'required|string|max:255',
        ];

        $emailRule = 'required|email';
        if ( $isUpdate ) {
            // Para update, precisamos do ID do usuário atual para exclusão da validação única
            // Como não temos o ID na assinatura, vamos usar uma abordagem diferente
            $emailRule .= '|unique:users,email,tenant_id,' . $tenantId;
        } else {
            $emailRule .= '|unique:users,email,NULL,id,tenant_id,' . $tenantId;
        }
        $rules[ 'email' ] = $emailRule;

        if ( isset( $data[ 'password' ] ) ) {
            $rules[ 'password' ] = 'required|string|min:8|confirmed';
        }

        $validator = Validator::make( $data, $rules );
        if ( $validator->fails() ) {
            $messages = $validator->errors()->all();
            return $this->error( OperationStatus::INVALID_DATA, implode( ', ', $messages ) );
        }

        return $this->success();
    }

    /**
     * Valida os dados de entrada para operações de criação ou atualização.
     *
     * @param array<string, mixed> $data Dados a serem validados
     * @param bool $isUpdate Indica se é uma operação de atualização
     * @return ServiceResult Resultado da validação
     */
    public function validate( array $data, bool $isUpdate = false ): ServiceResult
    {
        // Extract tenant_id from data if available, otherwise use 0 as default
        $tenantId = $data[ 'tenant_id' ] ?? 0;

        // Delegate to validateForTenant method
        return $this->validateForTenant( $data, $tenantId, $isUpdate );
    }

    // MÉTODOS ESPECÍFICOS DE USUÁRIO (MIGRADOS DE LEGACY)

    /**
     * Confirma conta do usuário via token.
     *
     * @param string $token Token de confirmação
     * @param int $tenantId
     * @return ServiceResult
     */
    public function confirmAccount( string $token, int $tenantId ): ServiceResult
    {
        DB::beginTransaction();
        try {
            $hashedToken = hash( 'sha256', $token );
            $tokenEntity = $this->tokenRepository->findByTokenAndTenantId( $hashedToken, $tenantId );
            if ( !$tokenEntity ) {
                DB::rollBack();
                return $this->error( OperationStatus::NOT_FOUND, 'Token inválido ou expirado.' );
            }

            // Check expiração
            if ( $tokenEntity->expires_at && $tokenEntity->expires_at->isPast() ) {
                DB::rollBack();
                return $this->error( OperationStatus::NOT_FOUND, 'Token expirado.' );
            }

            $user = $this->findEntityByIdAndTenantId( $tokenEntity->user_id, $tenantId );
            if ( !$user ) {
                DB::rollBack();
                return $this->error( OperationStatus::NOT_FOUND, 'Usuário não encontrado.' );
            }

            if ( $user->status === 'active' ) {
                DB::rollBack();
                return $this->error( OperationStatus::CONFLICT, 'Conta já confirmada.' );
            }

            $user->status = 'active';
            $this->saveEntity( $user );

            $tokenEntity->delete();

            DB::commit();
            return $this->success( $user, 'Conta confirmada com sucesso.' );
        } catch ( Exception $e ) {
            DB::rollBack();
            return $this->error( OperationStatus::ERROR, 'Falha ao confirmar conta: ' . $e->getMessage(), null, $e );
        }
    }

    /**
     * Ativa conta do usuário (admin).
     *
     * @param int $id ID do usuário
     * @param int $tenantId ID do tenant
     * @return ServiceResult
     */
    public function activateAccount( int $id, int $tenantId ): ServiceResult
    {
        $user = $this->findEntityByIdAndTenantId( $id, $tenantId );
        if ( !$user ) {
            return $this->error( OperationStatus::NOT_FOUND, 'Usuário não encontrado.' );
        }

        if ( $user->status === 'active' ) {
            return $this->error( OperationStatus::CONFLICT, 'Conta já ativa.' );
        }

        $user->status = 'active';
        $this->saveEntity( $user );

        // Deletar tokens pendentes
        $this->tokenRepository->deleteByUserId( $id );

        return $this->success( $user, 'Conta ativada com sucesso.' );
    }

    /**
     * Gera token de confirmação para usuário.
     *
     * @param int $userId
     * @param int $tenantId
     * @return ServiceResult
     */
    public function generateConfirmationToken( int $userId, int $tenantId ): ServiceResult
    {
        $user = $this->findEntityByIdAndTenantId( $userId, $tenantId );
        if ( !$user ) {
            return $this->error( OperationStatus::NOT_FOUND, 'Usuário não encontrado.' );
        }

        if ( $user->status === 'active' ) {
            return $this->error( OperationStatus::CONFLICT, 'Conta já ativa.' );
        }

        $token                   = Str::random( 60 );
        $hashedToken             = hash( 'sha256', $token );
        $tokenEntity             = new UserConfirmationToken();
        $tokenEntity->token      = $hashedToken;
        $tokenEntity->user_id    = $userId;
        $tokenEntity->tenant_id  = $tenantId;
        $tokenEntity->expires_at = Carbon::now()->toImmutable()->addHours( 24 );

        if ( !$tokenEntity->save() ) {
            return $this->error( OperationStatus::ERROR, 'Falha ao gerar token.' );
        }

        return $this->success( [ 'token' => $token ], 'Token gerado com sucesso.' );
    }

}
