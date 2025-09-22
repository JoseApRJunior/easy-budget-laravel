<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\OperationStatus;
use App\Repositories\RoleRepository;
use App\Services\Abstracts\BaseNoTenantService;
use App\Support\ServiceResult;
use App\Traits\SlugGenerator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class RoleService extends BaseNoTenantService
{
    use SlugGenerator;

    private RoleRepository $roleRepository;

    public function __construct( RoleRepository $roleRepository )
    {
        parent::__construct();
        $this->roleRepository = $roleRepository;
    }

    /**
     * Retorna a classe do modelo Role.
     *
     * @return \Illuminate\Database\Eloquent\Model
     */
    protected function getModelClass(): Model
    {
        return new \App\Models\Role();
    }

    /**
     * Gera um slug básico a partir de um texto com traduções específicas para roles.
     *
     * @param string $text Texto para gerar o slug
     * @return string Slug gerado
     */
    protected function generateSlug( string $text ): string
    {
        $dict = $this->loadRoleTranslations();
        if ( $translated = $this->translateWithDictionary( $text, $dict ) ) {
            return $translated;
        }
        return $this->generateDefaultSlug( $text );
    }

    protected function findEntityById( int $id ): ?Model
    {
        return $this->roleRepository->findById( $id );
    }

    protected function listEntities( ?array $orderBy = null, ?int $limit = null ): array
    {
        $filters = [];
        if ( $orderBy !== null ) {
            $filters[ 'order' ] = $orderBy;
        }
        if ( $limit !== null ) {
            $filters[ 'limit' ] = $limit;
        }
        return $this->roleRepository->findAll( $filters );
    }

    protected function createEntity( array $data ): Model
    {
        if ( isset( $data[ 'name' ] ) && !isset( $data[ 'slug' ] ) ) {
            $data[ 'slug' ] = $this->generateUniqueSlug( $data[ 'name' ], $this->roleRepository );
        }
        $role = new \App\Models\Role();
        $role->fill( $data );
        return $role;
    }

    protected function updateEntity( int $id, array $data ): Model
    {
        $entity = $this->findEntityById( $id );
        if ( !$entity ) {
            throw new \Exception( 'Entidade não encontrada para atualização.' );
        }
        if ( isset( $data[ 'name' ] ) && ( $data[ 'name' ] !== $entity->name ) ) {
            $data[ 'slug' ] = $this->generateUniqueSlug( $data[ 'name' ], $this->roleRepository, null, $entity->id );
        }
        $entity->fill( $data );
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

    protected function canDeleteEntity( Model $entity ): bool
    {
        $userCount = \App\Models\User::where( 'role_id', $entity->id )->count();
        return $userCount === 0;
    }

    protected function saveEntity( Model $entity ): bool
    {
        return $entity->save();
    }

    protected function validateForGlobal( array $data, bool $isUpdate = false ): ServiceResult
    {
        $id        = $data[ 'id' ] ?? null;
        $rules     = [ 
            'name'   => [ 
                'required',
                'string',
                'max:255',
                $isUpdate ? 'unique:roles,name,' . $id : 'unique:roles,name'
            ],
            'slug'   => [ 
                'nullable',
                'string',
                'max:255',
                $isUpdate ? 'unique:roles,slug,' . $id : 'unique:roles,slug'
            ],
            'status' => 'required|in:pending,active,inactive',
        ];
        $validator = Validator::make( $data, $rules );
        if ( $validator->fails() ) {
            $messages = $validator->errors()->all();
            return $this->error( OperationStatus::INVALID_DATA, implode( ', ', $messages ) );
        }
        return $this->success();
    }

    /**
     * Validação para tenant (não aplicável para serviços NoTenant).
     *
     * Este método é obrigatório por herança mas não realiza validação específica
     * de tenant, pois esta é uma classe NoTenant.
     *
     * @param array $data Dados a validar
     * @param int $tenant_id ID do tenant
     * @param bool $is_update Se é uma operação de atualização
     * @return ServiceResult Resultado da validação
     */
    protected function validateForTenant( array $data, int $tenant_id, bool $is_update = false ): ServiceResult
    {
        // Para serviços NoTenant, não há validação específica de tenant
        // Retorna sucesso pois a validação é feita pelo método validateForGlobal
        return $this->success();
    }

}
