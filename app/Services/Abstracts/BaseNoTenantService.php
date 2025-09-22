<?php

declare(strict_types=1);

namespace App\Services\Abstracts;

use App\Enums\OperationStatus;
use App\Interfaces\ServiceNoTenantInterface;
use App\Support\ServiceResult;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

abstract class BaseNoTenantService extends BaseService implements ServiceNoTenantInterface
{
    /**
     * @var Model
     */
    protected Model $model;

    /**
     * BaseNoTenantService constructor.
     * Inicializa a propriedade $model através do método abstrato getModelClass().
     */
    public function __construct()
    {
        $this->model = $this->getModelClass();
    }

    /**
     * Método abstrato que deve ser implementado pelas classes filhas
     * para fornecer a classe do modelo correspondente.
     *
     * @return Model
     */
    abstract protected function getModelClass(): Model;

    /**
     * Obtém entidade por ID global.
     *
     * @param int $id ID da entidade
     * @return ServiceResult
     */
    public function getById( int $id ): ServiceResult
    {
        $entity = $this->findEntityById( $id );

        if ( !$entity ) {
            return $this->error( OperationStatus::NOT_FOUND, 'Entidade não encontrada.' );
        }

        return $this->success( $entity, 'Entidade obtida com sucesso.' );
    }

    /**
     * Lista entidades com filtros opcionais.
     *
     * @param array $filters Filtros para consulta
     * @return ServiceResult
     */
    public function list( array $filters = [] ): ServiceResult
    {
        $entities = $this->listEntities( $filters );

        if ( empty( $entities ) ) {
            return $this->success( [], 'Nenhuma entidade encontrada.' );
        }

        return $this->success( $entities, 'Entidades listadas com sucesso.' );
    }

    /**
     * Cria nova entidade global.
     *
     * @param array $data Dados para criação
     * @return ServiceResult
     */
    public function create( array $data ): ServiceResult
    {
        DB::beginTransaction();
        try {
            $validation = $this->validate( $data, false );
            if ( !$validation->isSuccess() ) {
                DB::rollBack();
                return $validation;
            }

            $entity = $this->createEntity( $data );
            $saved  = $entity->save();

            if ( !$saved ) {
                DB::rollBack();
                return $this->error( OperationStatus::ERROR, 'Falha ao criar entidade.' );
            }

            DB::commit();
            return ServiceResult::success( $entity, 'Entidade criada com sucesso.' );
        } catch ( ValidationException $e ) {
            DB::rollBack();
            return $this->error( OperationStatus::INVALID_DATA, $e->getMessage() );
        } catch ( Exception $e ) {
            DB::rollBack();
            return $this->error( OperationStatus::ERROR, 'Falha ao criar entidade: ' . $e->getMessage(), null, $e );
        }
    }

    /**
     * Atualiza entidade por ID global.
     *
     * @param int $id ID da entidade
     * @param array $data Dados para atualização
     * @return ServiceResult
     */
    public function update( int $id, array $data ): ServiceResult
    {
        DB::beginTransaction();
        try {
            $validation = $this->validate( $data, true );
            if ( !$validation->isSuccess() ) {
                DB::rollBack();
                return $validation;
            }

            $entity = $this->findEntityById( $id );
            if ( !$entity ) {
                DB::rollBack();
                return $this->error( OperationStatus::NOT_FOUND, 'Entidade não encontrada.' );
            }

            $updated = $this->updateEntity( $id, $data );

            $saved = $updated->save();
            if ( !$saved ) {
                DB::rollBack();
                return $this->error( OperationStatus::ERROR, 'Falha ao salvar entidade atualizada.' );
            }

            DB::commit();
            return ServiceResult::success( $updated, 'Entidade atualizada com sucesso.' );
        } catch ( ValidationException $e ) {
            DB::rollBack();
            return $this->error( OperationStatus::INVALID_DATA, $e->getMessage() );
        } catch ( Exception $e ) {
            DB::rollBack();
            return $this->error( OperationStatus::ERROR, 'Falha ao atualizar entidade: ' . $e->getMessage(), null, $e );
        }
    }

    /**
     * Deleta entidade por ID global.
     *
     * @param int $id ID da entidade
     * @return ServiceResult
     */
    public function delete( int $id ): ServiceResult
    {
        DB::beginTransaction();
        try {
            $entity = $this->findEntityById( $id );
            if ( !$entity ) {
                DB::rollBack();
                return $this->error( OperationStatus::NOT_FOUND, 'Entidade não encontrada.' );
            }

            $deleted = $this->deleteEntity( $id );
            if ( !$deleted ) {
                DB::rollBack();
                return $this->error( OperationStatus::ERROR, 'Falha ao deletar entidade.' );
            }

            DB::commit();
            return $this->success( true, 'Entidade deletada com sucesso.' );
        } catch ( Exception $e ) {
            DB::rollBack();
            return $this->error( OperationStatus::ERROR, 'Falha ao deletar entidade: ' . $e->getMessage(), null, $e );
        }
    }

    // MÉTODOS ABSTRATOS - CONCRETO DEVE IMPLEMENTAR

    /**
     * Encontra entidade por ID (sem tenant).
     *
     * @param int $id
     * @return Model|null
     */
    abstract protected function findEntityById( int $id ): ?Model;

    /**
     * Lista entidades com filtros (sem tenant).
     *
     * @param ?array $orderBy Ordenação opcional
     * @param ?int $limit Limite opcional
     * @return array
     */
    abstract protected function listEntities( ?array $orderBy = null, ?int $limit = null ): array;

    /**
     * Cria nova entidade.
     *
     * @param array $data
     * @return Model
     */
    abstract protected function createEntity( array $data ): Model;

    /**
     * Atualiza entidade existente.
     *
     * @param int $id ID da entidade
     * @param array $data Dados para atualização
     * @return Model
     */
    abstract protected function updateEntity( int $id, array $data ): Model;

    /**
     * Deleta entidade.
     *
     * @param int $id
     * @return bool
     */
    abstract protected function deleteEntity( int $id ): bool;

    /**
     * Valida dados para criação/atualização.
     *
     * @param array $data Dados a validar
     * @param bool $isUpdate Define se é atualização (para validações específicas)
     * @return ServiceResult
     * @throws ValidationException
     */
    public function validate( array $data, bool $isUpdate = false ): ServiceResult
    {
        return $this->validateForGlobal( $data, $isUpdate );
    }

    /**
     * Validação específica para entidades globais (sem tenant).
     *
     * Concrete services must override this method for entity-specific validation.
     *
     * @param array $data
     * @param bool $isUpdate
     * @return ServiceResult
     */
    abstract protected function validateForGlobal( array $data, bool $isUpdate = false ): ServiceResult;

}
