<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\OperationStatus;
use App\Models\InvoiceStatus;
use App\Repositories\InvoiceStatusRepository;
use App\Services\Abstracts\BaseNoTenantService;
use App\Support\ServiceResult;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/**
 * Serviço para gerenciamento de status de faturas.
 *
 * Este serviço gerencia operações CRUD para status de faturas, que são entidades globais
 * (sem tenant isolation) utilizadas como tabela de lookup por todos os tenants do sistema.
 * O serviço migra funcionalidades do legacy InvoiceStatusesService implementando
 * operações CRUD completas através do padrão BaseNoTenantService.
 *
 * Funcionalidades principais:
 * - Operações CRUD básicas (criar, ler, atualizar, deletar)
 * - Validação robusta de dados de entrada
 * - Busca por slug, nome e outros critérios
 * - Filtragem por status ativo/inativo
 * - Ordenação personalizada
 * - Gerenciamento de índice de ordem
 *
 * @package App\Services
 */
class InvoiceStatusService extends BaseNoTenantService
{
    /**
     * Repositório para acesso aos dados de status de faturas.
     *
     * @var InvoiceStatusRepository
     */
    private InvoiceStatusRepository $invoiceStatusRepository;

    /**
     * Construtor do serviço.
     *
     * @param InvoiceStatusRepository $invoiceStatusRepository Repositório para status de faturas
     */
    public function __construct( InvoiceStatusRepository $invoiceStatusRepository )
    {
        $this->invoiceStatusRepository = $invoiceStatusRepository;
    }

    /**
     * {@inheritdoc}
     */
    protected function findEntityById( int $id ): ?Model
    {
        return $this->invoiceStatusRepository->findById( $id );
    }

    /**
     * {@inheritdoc}
     */
    protected function listEntities( ?array $orderBy = null, ?int $limit = null ): array
    {
        return $this->invoiceStatusRepository->findAll( $orderBy, $limit );
    }

    /**
     * {@inheritdoc}
     */
    protected function saveEntity( Model $entity ): bool
    {
        return $this->invoiceStatusRepository->save( $entity ) !== false;
    }

    /**
     * {@inheritdoc}
     */
    protected function createEntity( array $data ): Model
    {
        $invoiceStatus = new InvoiceStatus();
        $invoiceStatus->fill( $data );
        return $invoiceStatus;
    }

    /**
     * {@inheritdoc}
     */
    protected function updateEntity( int $id, array $data ): Model
    {
        $invoiceStatus = $this->findEntityById( $id );
        if ( !$invoiceStatus ) {
            throw new \Exception( 'Status de fatura não encontrado' );
        }
        $invoiceStatus->fill( $data );
        return $invoiceStatus;
    }

    /**
     * {@inheritdoc}
     */
    protected function deleteEntity( int $id ): bool
    {
        $invoiceStatus = $this->findEntityById( $id );
        if ( !$invoiceStatus ) {
            return false;
        }
        return $invoiceStatus->delete();
    }

    /**
     * {@inheritdoc}
     */
    protected function canDeleteEntity( Model $entity ): bool
    {
        // Status pode ser deletado se não possui faturas associadas
        return $entity->invoices()->count() === 0;
    }

    /**
     * {@inheritdoc}
     */
    protected function validateForGlobal( array $data, bool $isUpdate = false ): ServiceResult
    {
        $id = $isUpdate ? ( $data[ 'id' ] ?? null ) : null;

        $rules = [ 
            'name'        => 'required|string|max:255',
            'slug'        => [ 
                'required',
                'string',
                'max:255',
                Rule::unique( 'invoice_statuses', 'slug' )->ignore( $id ),
            ],
            'description' => 'nullable|string|max:1000',
            'color'       => 'nullable|string|max:7|regex:/^#[0-9A-Fa-f]{6}$/',
            'icon'        => 'nullable|string|max:100',
            'is_active'   => 'boolean',
            'order_index' => 'integer|min:0',
        ];

        $validator = Validator::make( $data, $rules );

        if ( $validator->fails() ) {
            return $this->error(
                OperationStatus::INVALID_DATA,
                'Dados de validação inválidos para status de fatura.',
                $validator->errors()->toArray(),
            );
        }

        return $this->success( $data );
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

    /**
     * Lista status de faturas ativas ordenadas por order_index.
     *
     * @param array $orderBy Ordenação personalizada (opcional)
     * @return ServiceResult Resultado da operação
     */
    public function listActive( array $orderBy = [ 'order_index' => 'asc' ] ): ServiceResult
    {
        try {
            $data = $this->invoiceStatusRepository->findActive( $orderBy );
            return $this->success( $data, 'Status de faturas ativos listados com sucesso.' );
        } catch ( \Exception $e ) {
            return $this->error(
                OperationStatus::ERROR,
                'Erro ao listar status de faturas ativos: ' . $e->getMessage()
            );
        }
    }

    /**
     * Busca status de fatura por slug.
     *
     * @param string $slug Slug único do status
     * @return ServiceResult Resultado da operação
     */
    public function getBySlug( string $slug ): ServiceResult
    {
        try {
            $data = $this->invoiceStatusRepository->findBySlug( $slug );
            if ( !$data ) {
                return $this->error( OperationStatus::NOT_FOUND, 'Status de fatura não encontrado.' );
            }
            return $this->success( $data, 'Status de fatura encontrado com sucesso.' );
        } catch ( \Exception $e ) {
            return $this->error(
                OperationStatus::ERROR,
                'Erro ao buscar status de fatura por slug: ' . $e->getMessage()
            );
        }
    }

    /**
     * Busca status de fatura por nome.
     *
     * @param string $name Nome do status
     * @return ServiceResult Resultado da operação
     */
    public function getByName( string $name ): ServiceResult
    {
        try {
            $data = $this->invoiceStatusRepository->findByName( $name );
            if ( !$data ) {
                return $this->error( OperationStatus::NOT_FOUND, 'Status de fatura não encontrado.' );
            }
            return $this->success( $data, 'Status de fatura encontrado com sucesso.' );
        } catch ( \Exception $e ) {
            return $this->error(
                OperationStatus::ERROR,
                'Erro ao buscar status de fatura por nome: ' . $e->getMessage()
            );
        }
    }

    /**
     * Lista status de faturas ordenados por um campo específico.
     *
     * @param string $field Campo para ordenação
     * @param string $direction Direção da ordenação (asc/desc)
     * @return ServiceResult Resultado da operação
     */
    public function listOrderedBy( string $field, string $direction = 'asc' ): ServiceResult
    {
        try {
            $data = $this->invoiceStatusRepository->findOrderedBy( $field, $direction );
            return $this->success( $data, 'Status de faturas ordenados listados com sucesso.' );
        } catch ( \Exception $e ) {
            return $this->error(
                OperationStatus::ERROR,
                'Erro ao listar status de faturas ordenados: ' . $e->getMessage()
            );
        }
    }

    /**
     * Lista status de faturas por cor específica.
     *
     * @param string $color Cor do status
     * @return ServiceResult Resultado da operação
     */
    public function listByColor( string $color ): ServiceResult
    {
        try {
            $data = $this->invoiceStatusRepository->findByColor( $color );
            return $this->success( $data, 'Status de faturas por cor listados com sucesso.' );
        } catch ( \Exception $e ) {
            return $this->error(
                OperationStatus::ERROR,
                'Erro ao listar status de faturas por cor: ' . $e->getMessage()
            );
        }
    }

    /**
     * Lista status de faturas dentro de um range de order_index.
     *
     * @param int $minOrderIndex Mínimo order_index
     * @param int $maxOrderIndex Máximo order_index
     * @return ServiceResult Resultado da operação
     */
    public function listByOrderIndexRange( int $minOrderIndex, int $maxOrderIndex ): ServiceResult
    {
        try {
            $data = $this->invoiceStatusRepository->findByOrderIndexRange( $minOrderIndex, $maxOrderIndex );
            return $this->success( $data, 'Status de faturas por range de índice listados com sucesso.' );
        } catch ( \Exception $e ) {
            return $this->error(
                OperationStatus::ERROR,
                'Erro ao listar status de faturas por range de índice: ' . $e->getMessage()
            );
        }
    }

    /**
     * Conta total de status de faturas.
     *
     * @return ServiceResult Resultado da operação
     */
    public function count(): ServiceResult
    {
        try {
            $data = $this->invoiceStatusRepository->count();
            return $this->success( $data, 'Total de status de faturas contado com sucesso.' );
        } catch ( \Exception $e ) {
            return $this->error(
                OperationStatus::ERROR,
                'Erro ao contar status de faturas: ' . $e->getMessage()
            );
        }
    }

    /**
     * Conta total de status de faturas ativas.
     *
     * @return ServiceResult Resultado da operação
     */
    public function countActive(): ServiceResult
    {
        try {
            $data = $this->invoiceStatusRepository->countActive();
            return $this->success( $data, 'Total de status de faturas ativos contado com sucesso.' );
        } catch ( \Exception $e ) {
            return $this->error(
                OperationStatus::ERROR,
                'Erro ao contar status de faturas ativos: ' . $e->getMessage()
            );
        }
    }

    /**
     * Verifica se existe status com slug específico.
     *
     * @param string $slug Slug para verificação
     * @return ServiceResult Resultado da operação
     */
    public function existsBySlug( string $slug ): ServiceResult
    {
        try {
            $data = $this->invoiceStatusRepository->existsBySlug( $slug );
            return $this->success( $data, 'Verificação de existência por slug realizada com sucesso.' );
        } catch ( \Exception $e ) {
            return $this->error(
                OperationStatus::ERROR,
                'Erro ao verificar existência por slug: ' . $e->getMessage()
            );
        }
    }

    /**
     * Atualiza o índice de ordem de um status.
     *
     * @param int $id ID do status
     * @param int $orderIndex Novo índice de ordem
     * @return ServiceResult Resultado da operação
     */
    public function updateOrderIndex( int $id, int $orderIndex ): ServiceResult
    {
        try {
            $entity = $this->findEntityById( $id );
            if ( !$entity ) {
                return $this->error( OperationStatus::NOT_FOUND, 'Status de fatura não encontrado.' );
            }

            $result = $this->update( $id, [ 'order_index' => $orderIndex ] );
            if ( $result->isSuccess() ) {
                return $this->success( $result->getData(), 'Índice de ordem atualizado com sucesso.' );
            }

            return $result;
        } catch ( \Exception $e ) {
            return $this->error(
                OperationStatus::ERROR,
                'Erro ao atualizar índice de ordem: ' . $e->getMessage()
            );
        }
    }

    /**
     * Ativa um status de fatura.
     *
     * @param int $id ID do status
     * @return ServiceResult Resultado da operação
     */
    public function activate( int $id ): ServiceResult
    {
        try {
            $result = $this->update( $id, [ 'is_active' => true ] );
            if ( $result->isSuccess() ) {
                return $this->success( $result->getData(), 'Status de fatura ativado com sucesso.' );
            }
            return $result;
        } catch ( \Exception $e ) {
            return $this->error(
                OperationStatus::ERROR,
                'Erro ao ativar status de fatura: ' . $e->getMessage()
            );
        }
    }

    /**
     * Desativa um status de fatura.
     *
     * @param int $id ID do status
     * @return ServiceResult Resultado da operação
     */
    public function deactivate( int $id ): ServiceResult
    {
        try {
            $result = $this->update( $id, [ 'is_active' => false ] );
            if ( $result->isSuccess() ) {
                return $this->success( $result->getData(), 'Status de fatura desativado com sucesso.' );
            }
            return $result;
        } catch ( \Exception $e ) {
            return $this->error(
                OperationStatus::ERROR,
                'Erro ao desativar status de fatura: ' . $e->getMessage()
            );
        }
    }

    /**
     * Busca o primeiro status ativo.
     *
     * @return ServiceResult Resultado da operação
     */
    public function getFirstActive(): ServiceResult
    {
        try {
            $data = $this->invoiceStatusRepository->findFirstBy( [ 'is_active' => true ] );
            if ( !$data ) {
                return $this->error( OperationStatus::NOT_FOUND, 'Nenhum status de fatura ativo encontrado.' );
            }
            return $this->success( $data, 'Primeiro status de fatura ativo encontrado com sucesso.' );
        } catch ( \Exception $e ) {
            return $this->error(
                OperationStatus::ERROR,
                'Erro ao buscar primeiro status de fatura ativo: ' . $e->getMessage()
            );
        }
    }

    /**
     * Busca o último status ativo.
     *
     * @return ServiceResult Resultado da operação
     */
    public function getLastActive(): ServiceResult
    {
        try {
            $data = $this->invoiceStatusRepository->findLastBy( [ 'is_active' => true ] );
            if ( !$data ) {
                return $this->error( OperationStatus::NOT_FOUND, 'Nenhum status de fatura ativo encontrado.' );
            }
            return $this->success( $data, 'Último status de fatura ativo encontrado com sucesso.' );
        } catch ( \Exception $e ) {
            return $this->error(
                OperationStatus::ERROR,
                'Erro ao buscar último status de fatura ativo: ' . $e->getMessage()
            );
        }
    }

}
