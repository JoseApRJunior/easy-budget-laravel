<?php

declare(strict_types=1);

namespace App\Services\Domain;

use App\Entities\UserEntity;
use App\Models\User;
use App\Repositories\UserRepository;
use App\Services\Core\Abstracts\AbstractBaseService;
use App\Support\ServiceResult;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

/**
 * Serviço para operações de usuário com tenant.
 *
 * Migra lógica legacy: criação com hash de senha, tokens de confirmação,
 * ativação de conta, gerenciamento de usuários. Usa Eloquent via repositórios.
 * Mantém compatibilidade API com métodos *ByTenantId.
 */
class UserService extends AbstractBaseService
{
    public function __construct( UserRepository $repository )
    {
        parent::__construct( $repository );
    }

    /**
     * Define o Model a ser utilizado pelo Service.
     */
    protected function makeModel(): Model
    {
        return new User();
    }

    /**
     * Encontra usuário por ID e tenant ID.
     */
    public function findByIdAndTenantId( int $id, int $tenantId ): ServiceResult
    {
        try {
            $user = $this->repository->find( $id );

            if ( !$user ) {
                return $this->error( 'Usuário não encontrado' );
            }

            return $this->success( $user, 'Usuário encontrado com sucesso' );
        } catch ( \Exception $e ) {
            return $this->error( 'Erro ao buscar usuário: ' . $e->getMessage() );
        }
    }

    /**
     * Encontra usuário por email.
     */
    public function findByEmail( string $email ): ServiceResult
    {
        try {
            $user = $this->repository->getAll()->where( 'email', $email )->first();

            if ( !$user ) {
                return $this->error( 'Usuário não encontrado' );
            }

            return $this->success( $user, 'Usuário encontrado com sucesso' );
        } catch ( \Exception $e ) {
            return $this->error( 'Erro ao buscar usuário: ' . $e->getMessage() );
        }
    }

    /**
     * Atualiza dados pessoais do usuário (dados básicos + redes sociais).
     */
    public function updatePersonalData( array $data, int $tenantId ): ServiceResult
    {
        try {
            $user = Auth::user();

            // Preparar dados para atualização
            $updateData = [];

            // Dados básicos do usuário
            if ( isset( $data[ 'name' ] ) ) {
                $updateData[ 'name' ] = $data[ 'name' ];
            }

            if ( isset( $data[ 'email' ] ) ) {
                $updateData[ 'email' ] = $data[ 'email' ];
            }

            if ( isset( $data[ 'avatar' ] ) ) {
                $updateData[ 'avatar' ] = $data[ 'avatar' ];
            }

            // Atualizar usuário se houver dados
            if ( !empty( $updateData ) ) {
                $this->repository->update( $user->id, $updateData );
            }

            // Atualizar configurações de redes sociais se existirem
            if (
                isset( $data[ 'social_facebook' ] ) || isset( $data[ 'social_twitter' ] ) ||
                isset( $data[ 'social_linkedin' ] ) || isset( $data[ 'social_instagram' ] )
            ) {

                $settingsService = app( \App\Services\Domain\SettingsService::class);
                $settingsService->updateUserSettings( [
                    'social_facebook'  => $data[ 'social_facebook' ] ?? null,
                    'social_twitter'   => $data[ 'social_twitter' ] ?? null,
                    'social_linkedin'  => $data[ 'social_linkedin' ] ?? null,
                    'social_instagram' => $data[ 'social_instagram' ] ?? null,
                ] );
            }

            return $this->success( $this->repository->find( $user->id ), 'Dados pessoais atualizados com sucesso' );
        } catch ( \Exception $e ) {
            return $this->error( 'Erro ao atualizar dados pessoais: ' . $e->getMessage() );
        }
    }

    /**
     * Obtém dados do perfil do usuário para edição.
     */
    public function getProfileData( int $tenantId ): ServiceResult
    {
        try {
            $user = Auth::user();

            // Carregar relacionamentos necessários usando repository
            $userWithRelations = $this->repository->find( $user->id );
            if ( $userWithRelations ) {
                $userWithRelations->load( [ 'provider.commonData', 'provider.contact', 'settings' ] );
            }

            $settingsService  = app( \App\Services\Domain\SettingsService::class);
            $completeSettings = $settingsService->getCompleteUserSettings( $user );

            return $this->success( [
                'user'     => $userWithRelations ?? $user,
                'settings' => $completeSettings,
            ], 'Dados do perfil obtidos com sucesso' );
        } catch ( \Exception $e ) {
            return $this->error( 'Erro ao obter dados do perfil: ' . $e->getMessage() );
        }
    }

}
