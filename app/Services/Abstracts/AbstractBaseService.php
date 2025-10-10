<?php
declare(strict_types=1);

namespace App\Services\Abstracts;

use App\Enums\OperationStatus;
use App\Repositories\Contracts\BaseRepositoryInterface;
use App\Services\Contracts\CrudServiceInterface;
use App\Support\ServiceResult;
use Exception;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

/**
 * Classe base abstrata para todos os serviços.
 *
 * Esta classe IMPLEMENTA o CrudServiceInterface e INJETA o BaseRepositoryInterface.
 * Garante CRUD básico, helpers de comunicação e contexto prontos para uso.
 */
abstract class AbstractBaseService implements CrudServiceInterface
{
    protected BaseRepositoryInterface $repository;

    public function __construct( BaseRepositoryInterface $repository )
    {
        $this->repository = $repository;
    }

    // --------------------------------------------------------------------------
    // IMPLEMENTAÇÃO DO CRUDSERVICEINTERFACE (READ)
    // --------------------------------------------------------------------------

    public function findById( int $id, array $with = [] ): ServiceResult
    {
        try {
            $entity = $this->repository->find( $id );

            if ( !$entity ) {
                return $this->error( OperationStatus::NOT_FOUND, "Recurso com ID {$id} não encontrado." );
            }
            return $this->success( $entity, 'Busca realizada com sucesso.' );
        } catch ( Exception $e ) {
            return $this->error( OperationStatus::ERROR, "Erro ao buscar recurso.", null, $e );
        }
    }

    public function list( array $filters = [] ): ServiceResult
    {
        try {
            $entities = $this->repository->getAll();
            return $this->success( $entities, 'Listagem realizada com sucesso.' );
        } catch ( Exception $e ) {
            return $this->error( OperationStatus::ERROR, "Erro ao listar recursos.", null, $e );
        }
    }

    public function count( array $filters = [] ): ServiceResult
    {
        try {
            $count = $this->repository->getAll()->count();
            return $this->success( $count, 'Contagem realizada com sucesso.' );
        } catch ( Exception $e ) {
            return $this->error( OperationStatus::ERROR, "Erro ao contar recursos.", null, $e );
        }
    }

    // --------------------------------------------------------------------------
    // IMPLEMENTAÇÃO DO CRUDSERVICEINTERFACE (WRITE) - NOVOS MÉTODOS
    // --------------------------------------------------------------------------

    /**
     * Cria um novo recurso.
     */
    public function create( array $data ): ServiceResult
    {
        // Nota: A validação DEVE ocorrer no Service concreto antes desta chamada.
        try {
            // Delega a criação ao Repositório
            $entity = $this->repository->create( $data );

            return $this->success( $entity, 'Recurso criado com sucesso.' );
        } catch ( \Illuminate\Database\QueryException $e ) {
            // Captura erros comuns de banco (ex: violação de unique key)
            return $this->error( OperationStatus::CONFLICT, "Erro de dados: verifique a unicidade ou constraints.", null, $e );
        } catch ( Exception $e ) {
            return $this->error( OperationStatus::ERROR, "Erro ao criar recurso.", null, $e );
        }
    }

    /**
     * Atualiza um recurso existente pelo ID.
     */
    public function update( int $id, array $data ): ServiceResult
    {
        // Nota: A validação DEVE ocorrer no Service concreto antes desta chamada.
        try {
            // Busca a entidade primeiro (para garantir que existe e respeitar o tenant)
            $entity = $this->repository->find( $id );

            if ( !$entity ) {
                return $this->error( OperationStatus::NOT_FOUND, "Recurso com ID {$id} não encontrado para atualização." );
            }

            // Delega a atualização ao Repositório
            $updatedEntity = $this->repository->update( $id, $data );

            // O update do Repositório pode retornar o Model ou null em caso de falha silenciosa.
            if ( !$updatedEntity ) {
                // Se a busca inicial passou, mas o update falhou (ex: 0 linhas afetadas)
                return $this->error( OperationStatus::ERROR, "Falha ao aplicar as mudanças no recurso.", $entity );
            }

            return $this->success( $updatedEntity, 'Recurso atualizado com sucesso.' );
        } catch ( \Illuminate\Database\QueryException $e ) {
            return $this->error( OperationStatus::CONFLICT, "Erro de dados: verifique a unicidade ou constraints.", null, $e );
        } catch ( Exception $e ) {
            return $this->error( OperationStatus::ERROR, "Erro ao atualizar recurso.", null, $e );
        }
    }

    /**
     * Deleta um recurso pelo ID.
     */
    public function delete( int $id ): ServiceResult
    {
        try {
            // O Repositório já deve tratar a exclusão e retornar true/false
            $deleted = $this->repository->delete( $id );

            if ( !$deleted ) {
                return $this->error( OperationStatus::NOT_FOUND, "Recurso com ID {$id} não encontrado para exclusão." );
            }

            return $this->success( null, 'Recurso excluído com sucesso.' );
        } catch ( \Illuminate\Database\QueryException $e ) {
            // Captura erros de banco, como violação de chave estrangeira (restrição)
            return $this->error( OperationStatus::CONFLICT, "Não foi possível excluir. O recurso está em uso.", null, $e );
        } catch ( Exception $e ) {
            return $this->error( OperationStatus::ERROR, "Erro ao excluir recurso.", null, $e );
        }
    }

    // --------------------------------------------------------------------------
    // HELPERS DE COMUNICAÇÃO E CONTEXTO (OMITIDOS AQUI POR CLAREZA, MAS MANTIDOS)
    // --------------------------------------------------------------------------

    protected function success( mixed $data = null, string $message = '' ): ServiceResult
    {
        return ServiceResult::success( $data, $message );
    }

    protected function error( OperationStatus|string $status, string $message = '', mixed $data = null, ?Exception $exception = null ): ServiceResult
    {
        $finalStatus  = is_string( $status ) ? OperationStatus::ERROR : $status;
        $finalMessage = is_string( $status ) ? $status : $message;

        return ServiceResult::error( $finalStatus, $finalMessage, $data, $exception );
    }

    // --------------------------------------------------------------------------
    // HELPERS DE CONTEXTO (MANTIDOS)
    // --------------------------------------------------------------------------

    protected function authUser(): ?User
    {
        $user = Auth::user();
        return $user instanceof User ? $user : null;
    }

    protected function tenantId(): ?int
    {
        $user = $this->authUser();
        return $user?->tenant_id ?? null;
    }

}
