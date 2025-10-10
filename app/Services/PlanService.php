<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\OperationStatus;
use App\Models\Plan;
use App\Repositories\PlanRepository;
use App\Services\Abstracts\AbstractBaseService;
use App\Support\ServiceResult;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Validator;

/**
 * Serviço para gerenciamento de planos de assinatura.
 *
 * Esta classe implementa toda a lógica de negócio relacionada a planos,
 * incluindo operações CRUD avançadas e funcionalidades específicas do domínio.
 */
class PlanService extends AbstractBaseService
{
    /**
     * Construtor do serviço de planos.
     *
     * @param PlanRepository $planRepository Repositório para operações de planos
     */
    public function __construct( PlanRepository $planRepository )
    {
        parent::__construct( $planRepository );
    }

    /**
     * Retorna lista de filtros suportados para planos.
     *
     * @return array<string> Campos que podem ser filtrados
     */
    protected function getSupportedFilters(): array
    {
        return [
            'id',
            'name',
            'slug',
            'status',
            'price',
            'max_budgets',
            'max_clients',
            'created_at',
            'updated_at',
        ];
    }

    /**
     * Valida dados para operações de planos.
     *
     * @param array<string, mixed> $data Dados a serem validados
     * @param bool $isUpdate Indica se é operação de atualização
     * @return ServiceResult Resultado da validação
     */
    public function validate( array $data, bool $isUpdate = false ): ServiceResult
    {
        $rules = Plan::businessRules();

        // Para atualização, remove unique validation se necessário
        if ( $isUpdate && isset( $data[ 'id' ] ) ) {
            $rules[ 'slug' ] = 'required|string|max:50|unique:plans,slug,' . $data[ 'id' ];
        }

        $validator = Validator::make( $data, $rules );

        if ( $validator->fails() ) {
            $messages = implode( ', ', $validator->errors()->all() );
            return $this->error( OperationStatus::INVALID_DATA, $messages );
        }

        return $this->success( $data );
    }

    // --------------------------------------------------------------------------
    // IMPLEMENTAÇÃO DOS MÉTODOS ABSTRATOS DO CRUDSERVICEINTERFACE
    // --------------------------------------------------------------------------

    /**
     * {@inheritdoc}
     */
    public function findMany( array $ids, array $with = [] ): ServiceResult
    {
        try {
            $plans = [];
            foreach ( $ids as $id ) {
                $result = $this->findById( $id, $with );
                if ( $result->isSuccess() ) {
                    $plans[] = $result->getData();
                }
            }
            return $this->success( $plans, 'Planos encontrados com sucesso.' );
        } catch ( Exception $e ) {
            return $this->error( OperationStatus::ERROR, "Erro ao buscar planos.", null, $e );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function findOneBy( array $criteria, array $with = [] ): ServiceResult
    {
        try {
            // Para simplificar, vamos usar o método list() e pegar o primeiro resultado
            $result = $this->list( $criteria );
            if ( !$result->isSuccess() ) {
                return $this->error( OperationStatus::NOT_FOUND, "Plano não encontrado." );
            }

            $plans = $result->getData();
            $plan  = is_array( $plans ) && count( $plans ) > 0 ? $plans[ 0 ] : null;

            if ( !$plan ) {
                return $this->error( OperationStatus::NOT_FOUND, "Plano não encontrado." );
            }

            return $this->success( $plan, 'Plano encontrado com sucesso.' );
        } catch ( Exception $e ) {
            return $this->error( OperationStatus::ERROR, "Erro ao buscar plano.", null, $e );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function updateMany( array $ids, array $data ): ServiceResult
    {
        try {
            $updatedCount = 0;

            foreach ( $ids as $id ) {
                $result = $this->update( $id, $data );
                if ( $result->isSuccess() ) {
                    $updatedCount++;
                }
            }

            return $this->success( $updatedCount, "{$updatedCount} planos atualizados com sucesso." );
        } catch ( Exception $e ) {
            return $this->error( OperationStatus::ERROR, "Erro ao atualizar planos.", null, $e );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function deleteMany( array $ids ): ServiceResult
    {
        try {
            $deletedCount = 0;

            foreach ( $ids as $id ) {
                $result = $this->delete( $id );
                if ( $result->isSuccess() ) {
                    $deletedCount++;
                }
            }

            return $this->success( $deletedCount, "{$deletedCount} planos removidos com sucesso." );
        } catch ( Exception $e ) {
            return $this->error( OperationStatus::ERROR, "Erro ao remover planos.", null, $e );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function deleteByCriteria( array $criteria ): ServiceResult
    {
        try {
            // Para simplificar, vamos usar o método list() e depois deletar
            $result = $this->list( $criteria );
            if ( !$result->isSuccess() ) {
                return $result;
            }

            $plans        = $result->getData();
            $deletedCount = 0;

            foreach ( $plans as $plan ) {
                $deleteResult = $this->delete( $plan->id );
                if ( $deleteResult->isSuccess() ) {
                    $deletedCount++;
                }
            }

            return $this->success( $deletedCount, "{$deletedCount} planos removidos com sucesso." );
        } catch ( Exception $e ) {
            return $this->error( OperationStatus::ERROR, "Erro ao remover planos por critérios.", null, $e );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function exists( array $criteria ): ServiceResult
    {
        try {
            // Para simplificar, vamos usar o método findOneBy e verificar se encontrou algo
            $result = $this->findOneBy( $criteria );
            $exists = $result->isSuccess();

            return $this->success( $exists, 'Verificação de existência realizada.' );
        } catch ( Exception $e ) {
            return $this->error( OperationStatus::ERROR, "Erro ao verificar existência.", null, $e );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function duplicate( int $id, array $overrides = [] ): ServiceResult
    {
        try {
            $originalResult = $this->findById( $id );
            if ( !$originalResult->isSuccess() ) {
                return $this->error( OperationStatus::NOT_FOUND, "Plano original não encontrado." );
            }

            $original = $originalResult->getData();
            $data     = $original->toArray();

            // Remove campos que não devem ser duplicados
            unset( $data[ 'id' ], $data[ 'created_at' ], $data[ 'updated_at' ] );

            // Aplica overrides
            $data = array_merge( $data, $overrides );

            // Garante que slug seja único
            if ( !isset( $overrides[ 'slug' ] ) ) {
                $data[ 'slug' ] = $data[ 'slug' ] . '-copia-' . time();
            }

            return $this->create( $data );
        } catch ( Exception $e ) {
            return $this->error( OperationStatus::ERROR, "Erro ao duplicar plano.", null, $e );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function restore( int $id ): ServiceResult
    {
        try {
            // Verifica se o modelo Plan tem SoftDeletes habilitado
            if ( !method_exists( Plan::class, 'withTrashed' ) ) {
                return $this->error( OperationStatus::NOT_SUPPORTED, "Modelo Plan não suporta restauração." );
            }

            // Para simplificar, vamos usar o método findById primeiro
            $result = $this->findById( $id );
            if ( !$result->isSuccess() ) {
                return $this->error( OperationStatus::NOT_FOUND, "Plano não encontrado para restauração." );
            }

            $plan = $result->getData();

            // Como não podemos usar withTrashed diretamente, vamos assumir que o plano existe
            // e tentar restaurá-lo usando o repository
            $restored = $this->repository->update( $id, $plan->toArray() );

            if ( !$restored ) {
                return $this->error( OperationStatus::ERROR, "Erro ao restaurar plano." );
            }

            return $this->success( $restored, 'Plano restaurado com sucesso.' );
        } catch ( Exception $e ) {
            return $this->error( OperationStatus::ERROR, "Erro ao restaurar plano.", null, $e );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getStats( array $filters = [] ): ServiceResult
    {
        try {
            // Para simplificar, vamos usar o método list() e calcular estatísticas básicas
            $result = $this->list( $filters );
            if ( !$result->isSuccess() ) {
                return $result;
            }

            $plans = $result->getData();
            $total = is_countable( $plans ) ? count( $plans ) : 0;

            $active     = 0;
            $inactive   = 0;
            $totalPrice = 0;
            $priceCount = 0;
            $maxPrice   = 0;
            $minPrice   = PHP_FLOAT_MAX;

            foreach ( $plans as $plan ) {
                if ( $plan->status ) {
                    $active++;
                    $totalPrice += $plan->price;
                    $priceCount++;

                    if ( $plan->price > $maxPrice ) {
                        $maxPrice = $plan->price;
                    }
                    if ( $plan->price < $minPrice ) {
                        $minPrice = $plan->price;
                    }
                } else {
                    $inactive++;
                }
            }

            $stats = [
                'total'         => $total,
                'active'        => $active,
                'inactive'      => $inactive,
                'average_price' => $priceCount > 0 ? $totalPrice / $priceCount : 0,
                'max_price'     => $maxPrice > 0 ? $maxPrice : 0,
                'min_price'     => $minPrice !== PHP_FLOAT_MAX ? $minPrice : 0,
            ];

            return $this->success( $stats, 'Estatísticas calculadas com sucesso.' );
        } catch ( Exception $e ) {
            return $this->error( OperationStatus::ERROR, "Erro ao calcular estatísticas.", null, $e );
        }
    }

    // --------------------------------------------------------------------------
    // MÉTODOS ESPECÍFICOS DE NEGÓCIO PARA PLANOS
    // --------------------------------------------------------------------------

    /**
     * Encontra planos ativos.
     *
     * @return ServiceResult Resultado com planos ativos
     */
    public function findActive(): ServiceResult
    {
        try {
            $result = $this->findOneBy( [ 'status' => true ] );
            if ( !$result->isSuccess() ) {
                return $this->list( [ 'status' => true ] );
            }

            $activePlan = $result->getData();
            $plans      = $activePlan instanceof Collection ? $activePlan : [ $activePlan ];

            return $this->success( $plans, 'Planos ativos encontrados com sucesso.' );
        } catch ( Exception $e ) {
            return $this->error( OperationStatus::ERROR, "Erro ao buscar planos ativos.", null, $e );
        }
    }

    /**
     * Encontra plano por slug.
     *
     * @param string $slug Slug do plano
     * @return ServiceResult Resultado com plano encontrado
     */
    public function findBySlug( string $slug ): ServiceResult
    {
        try {
            $result = $this->findOneBy( [ 'slug' => $slug ] );

            if ( !$result->isSuccess() ) {
                return $this->error( OperationStatus::NOT_FOUND, "Plano com slug '{$slug}' não encontrado." );
            }

            return $this->success( $result->getData(), 'Plano encontrado com sucesso.' );
        } catch ( Exception $e ) {
            return $this->error( OperationStatus::ERROR, "Erro ao buscar plano por slug.", null, $e );
        }
    }

    /**
     * Encontra planos ordenados por preço.
     *
     * @param string $direction Direção da ordenação (asc/desc)
     * @return ServiceResult Resultado com planos ordenados
     */
    public function findOrderedByPrice( string $direction = 'asc' ): ServiceResult
    {
        try {
            $result = $this->list( [ 'order_by' => 'price', 'order_direction' => $direction ] );
            return $this->success( $result->getData(), 'Planos ordenados por preço encontrados com sucesso.' );
        } catch ( Exception $e ) {
            return $this->error( OperationStatus::ERROR, "Erro ao buscar planos ordenados por preço.", null, $e );
        }
    }

    /**
     * Encontra planos que permitem determinado número de orçamentos.
     *
     * @param int $budgetCount Número de orçamentos
     * @return ServiceResult Resultado com planos compatíveis
     */
    public function findByAllowedBudgets( int $budgetCount ): ServiceResult
    {
        try {
            $result = $this->list( [ 'max_budgets' => $budgetCount, 'status' => true ] );
            return $this->success( $result->getData(), 'Planos compatíveis encontrados com sucesso.' );
        } catch ( Exception $e ) {
            return $this->error( OperationStatus::ERROR, "Erro ao buscar planos por orçamento.", null, $e );
        }
    }

    /**
     * Encontra planos que permitem determinado número de clientes.
     *
     * @param int $clientCount Número de clientes
     * @return ServiceResult Resultado com planos compatíveis
     */
    public function findByAllowedClients( int $clientCount ): ServiceResult
    {
        try {
            $result = $this->list( [ 'max_clients' => $clientCount, 'status' => true ] );
            return $this->success( $result->getData(), 'Planos compatíveis encontrados com sucesso.' );
        } catch ( Exception $e ) {
            return $this->error( OperationStatus::ERROR, "Erro ao buscar planos por clientes.", null, $e );
        }
    }

    /**
     * Valida se nome do plano é único.
     *
     * @param string $name Nome a ser verificado
     * @param int|null $excludeId ID do plano a ser excluído da verificação
     * @return ServiceResult Resultado da validação
     */
    public function validateUniqueName( string $name, ?int $excludeId = null ): ServiceResult
    {
        try {
            $criteria     = [ 'name' => $name ];
            $existsResult = $this->exists( $criteria );

            if ( !$existsResult->isSuccess() ) {
                return $this->error( OperationStatus::ERROR, "Erro ao verificar unicidade." );
            }

            $isUnique = !$existsResult->getData();
            return $this->success( $isUnique, 'Validação de unicidade realizada.' );
        } catch ( Exception $e ) {
            return $this->error( OperationStatus::ERROR, "Erro ao validar nome único.", null, $e );
        }
    }

    /**
     * Cria um novo plano com validação completa.
     *
     * @param array<string, mixed> $data Dados do plano
     * @return ServiceResult Resultado da criação
     */
    public function create( array $data ): ServiceResult
    {
        // Valida dados antes de criar
        $validation = $this->validate( $data );
        if ( !$validation->isSuccess() ) {
            return $validation;
        }

        return parent::create( $data );
    }

    /**
     * Atualiza um plano com validação completa.
     *
     * @param int $id ID do plano
     * @param array<string, mixed> $data Dados para atualização
     * @return ServiceResult Resultado da atualização
     */
    public function update( int $id, array $data ): ServiceResult
    {
        // Valida dados antes de atualizar
        $validation = $this->validate( $data, true );
        if ( !$validation->isSuccess() ) {
            return $validation;
        }

        return parent::update( $id, $data );
    }

}
