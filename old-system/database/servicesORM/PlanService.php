<?php

namespace app\database\servicesORM;

use app\interfaces\ServiceNoTenantInterface;
use app\support\ServiceResult;
use app\enums\OperationStatus;
use core\dbal\EntityNotFound;
use core\library\Session;
use Doctrine\DBAL\Connection;
use Exception;
use app\database\entitiesORM\PlanEntity;
use app\database\entitiesORM\PlanSubscriptionEntity;
use app\database\repositories\PlanRepository;
use app\database\repositories\PlanSubscriptionRepository;
use app\database\repositories\PaymentMercadoPagoPlansRepository;

/**
 * Serviço para gerenciamento de planos
 * Implementa ServiceNoTenantInterface para operações sem tenant_id
 */
class PlanService extends BaseNoTenantService implements ServiceNoTenantInterface
{
    private mixed $authenticated;

    public function __construct(
        private readonly Connection $connection,
        private PlanSubscriptionRepository $planSubscriptionRepository,
        private PaymentMercadoPagoPlanService $paymentMercadoPagoPlanService,
        private PaymentMercadoPagoPlansRepository $paymentMercadoPagoPlansRepository,
        private PlanRepository $planRepository,
        EntityManager $entityManager,
    ) {
        parent::__construct( $entityManager );
        if ( Session::has( 'auth' ) ) {
            $this->authenticated = Session::get( 'auth' );
        }
    }

    public function getById( int $id ): ServiceResult
    {
        try {
            $entity = $this->planRepository->findById( $id );

            if ( $entity === null ) {
                return ServiceResult::error( OperationStatus::NOT_FOUND, 'Plano não encontrado.' );
            }

            return ServiceResult::success( $entity, 'Plano encontrado com sucesso.' );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Falha ao buscar plano: ' . $e->getMessage() );
        }
    }

    public function updateById( int $id, array $data ): ServiceResult
    {
        return $this->update( $id, $data );
    }

    public function deleteById( int $id ): ServiceResult
    {
        return $this->delete( $id );
    }

    /**
     * Busca um plano pelo ID.
     *
     * @param int $id ID da entidade
     * @return ServiceResult Resultado da operação
     */
    public function getById( int $id ): ServiceResult
    {
        try {
            $entity = $this->planRepository->findById( $id );

            if ( $entity === null ) {
                return ServiceResult::error( OperationStatus::NOT_FOUND, 'Plano não encontrado.' );
            }

            return ServiceResult::success( $entity, 'Plano encontrado com sucesso.' );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Falha ao buscar plano: ' . $e->getMessage() );
        }
    }

    /**
     * Lista planos
     *
     * @param array<string, mixed> $filters Filtros opcionais
     * @return ServiceResult Resultado da operação
     */
    public function list( array $filters = [] ): ServiceResult
    {
        try {
            $criteria = [];
            $orderBy  = [ 'id' => 'ASC' ];

            // Aplicar filtros conforme necessário
            if ( !empty( $filters[ 'name' ] ) ) {
                $criteria[ 'name' ] = $filters[ 'name' ];
            }

            if ( isset( $filters[ 'active' ] ) ) {
                $criteria[ 'status' ] = (bool) $filters[ 'active' ];
            }

            $entities = $this->planRepository->findAll( $criteria, $orderBy );

            return ServiceResult::success( $entities, 'Planos listados com sucesso.' );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Falha ao listar planos: ' . $e->getMessage() );
        }
    }

    /**
     * Cria uma nova assinatura de plano.
     *
     * @param array<string, mixed> $data Dados para criação da entidade
     * @return ServiceResult Resultado da operação
     */
    public function create( array $data ): ServiceResult
    {
        try {
            // Validar dados de entrada
            $validation = $this->validate( $data, false );
            if ( !$validation->isSuccess() ) {
                return $validation;
            }

            // Prepare data for base create
            $preparedData                  = $data;
            $preparedData[ 'description' ] = $data[ 'description' ] ?? '';
            $preparedData[ 'price' ]       = $data[ 'price' ] ?? 0.0;
            $preparedData[ 'status' ]      = isset( $data[ 'status' ] ) ? (bool) $data[ 'status' ] : true;
            $preparedData[ 'maxBudgets' ]  = $data[ 'maxBudgets' ] ?? 0;
            $preparedData[ 'maxClients' ]  = $data[ 'maxClients' ] ?? 0;
            $preparedData[ 'features' ]    = $data[ 'features' ] ?? [];

            // Handle slug
            if ( isset( $data[ 'slug' ] ) ) {
                $existingPlan = $this->planRepository->findBySlug( $data[ 'slug' ] );
                if ( $existingPlan !== null ) {
                    return ServiceResult::error( OperationStatus::INVALID_DATA, 'Slug já existe.' );
                }
                $preparedData[ 'slug' ] = $data[ 'slug' ];
            } else {
                $preparedData[ 'slug' ] = $this->generateUniqueSlug( $data[ 'name' ] );
            }

            return parent::create( $preparedData );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Falha ao criar plano: ' . $e->getMessage() );
        }
    }

    /**
     * Atualiza uma assinatura de plano existente.
     *
     * @param int $id ID da entidade
     * @param array<string, mixed> $data Dados para atualização
     * @return ServiceResult Resultado da operação
     */
    public function update( int $id, array $data ): ServiceResult
    {
        try {
            // Validar dados de entrada
            $validation = $this->validate( $data, true );
            if ( !$validation->isSuccess() ) {
                return $validation;
            }

            // Buscar entidade existente
            $entity = $this->planRepository->findById( $id );
            if ( $entity === null ) {
                return ServiceResult::error( OperationStatus::NOT_FOUND, 'Plano não encontrado.' );
            }

            // Prepare data for base update
            $preparedData                  = $data;
            $preparedData[ 'description' ] = isset( $data[ 'description' ] ) ? $data[ 'description' ] : $entity->getDescription();
            $preparedData[ 'price' ]       = isset( $data[ 'price' ] ) ? (float) $data[ 'price' ] : $entity->getPrice();
            $preparedData[ 'status' ]      = isset( $data[ 'status' ] ) ? (bool) $data[ 'status' ] : $entity->getStatus();
            $preparedData[ 'maxBudgets' ]  = isset( $data[ 'maxBudgets' ] ) ? (int) $data[ 'maxBudgets' ] : $entity->getMaxBudgets();
            $preparedData[ 'maxClients' ]  = isset( $data[ 'maxClients' ] ) ? (int) $data[ 'maxClients' ] : $entity->getMaxClients();
            $preparedData[ 'features' ]    = isset( $data[ 'features' ] ) ? (array) $data[ 'features' ] : $entity->getFeatures();
            $preparedData[ 'name' ]        = $data[ 'name' ];

            $oldName = $entity->getName();

            // Handle slug
            if ( isset( $data[ 'slug' ] ) ) {
                $existingPlan = $this->planRepository->findBySlug( $data[ 'slug' ] );
                if ( $existingPlan !== null && $existingPlan->getId() !== $id ) {
                    return ServiceResult::error( OperationStatus::INVALID_DATA, 'Slug já existe.' );
                }
                $preparedData[ 'slug' ] = $data[ 'slug' ];
            } elseif ( $oldName !== $data[ 'name' ] ) {
                $preparedData[ 'slug' ] = $this->generateUniqueSlug( $data[ 'name' ], $id );
            }

            return parent::update( $id, $preparedData );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Falha ao atualizar plano: ' . $e->getMessage() );
        }
    }

    /**
     * Remove uma assinatura de plano.
     *
     * @param int $id ID da entidade
     * @return ServiceResult Resultado da operação
     */
    public function delete( int $id ): ServiceResult
    {
        try {
            // Verificar se a entidade existe
            $entity = $this->planRepository->findById( $id );
            if ( $entity === null ) {
                return ServiceResult::error( OperationStatus::NOT_FOUND, 'Plano não encontrado.' );
            }

            // Executar exclusão via repository
            $result = $this->planRepository->delete( $id );

            if ( $result ) {
                return ServiceResult::success( null, 'Plano removido com sucesso.' );
            } else {
                return ServiceResult::error( OperationStatus::ERROR, 'Falha ao remover plano do banco de dados.' );
            }
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Falha ao excluir plano: ' . $e->getMessage() );
        }
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
        $errors = [];

        // Validar nome
        if ( empty( $data[ 'name' ] ) ) {
            $errors[] = "O nome do plano é obrigatório.";
        } elseif ( strlen( $data[ 'name' ] ) > 100 ) {
            $errors[] = "O nome do plano deve ter no máximo 100 caracteres.";
        }

        // Validar slug (se fornecido)
        if ( isset( $data[ 'slug' ] ) ) {
            if ( empty( $data[ 'slug' ] ) ) {
                $errors[] = "O slug não pode estar vazio quando fornecido.";
            } elseif ( strlen( $data[ 'slug' ] ) > 100 ) {
                $errors[] = "O slug deve ter no máximo 100 caracteres.";
            } elseif ( !preg_match( '/^[a-z0-9-]+$/', $data[ 'slug' ] ) ) {
                $errors[] = "O slug deve conter apenas letras minúsculas, números e hífens.";
            }
        }

        // Validar preço (se fornecido)
        if ( isset( $data[ 'price' ] ) ) {
            if ( !is_numeric( $data[ 'price' ] ) ) {
                $errors[] = "Preço deve ser um valor numérico.";
            } elseif ( $data[ 'price' ] < 0 ) {
                $errors[] = "Preço não pode ser negativo.";
            }
        }

        // Validar status (se fornecido)
        if ( isset( $data[ 'status' ] ) && !is_bool( $data[ 'status' ] ) && !in_array( $data[ 'status' ], [ 'active', 'inactive', 0, 1, '0', '1' ] ) ) {
            $errors[] = "Status deve ser um valor booleano válido.";
        }

        if ( count( $errors ) > 0 ) {
            return ServiceResult::error( OperationStatus::INVALID_DATA, "Dados inválidos: " . implode( ', ', $errors ) );
        }

        return ServiceResult::success( null, "Dados válidos." );
    }

    /**
     * Gera um slug único.
     *
     * @param string $name Nome da entidade
     * @param int|null $excludeId ID da entidade a excluir da verificação (para updates)
     * @return string Slug único gerado
     */
    private function generateUniqueSlug( string $name, ?int $excludeId = null ): string
    {
        $baseSlug = $this->generateSlug( $name );
        $slug     = $baseSlug;
        $counter  = 1;

        // Verificar unicidade
        while ( true ) {
            $existingPlan = $this->planRepository->findBySlug( $slug );
            if ( $existingPlan === null || ( $excludeId !== null && $existingPlan->getId() === $excludeId ) ) {
                break;
            }
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Gera um slug a partir do nome.
     *
     * @param string $name Nome da entidade
     * @return string Slug gerado
     */
    private function generateSlug( string $name ): string
    {
        // Converter para minúsculas
        $slug = mb_strtolower( trim( $name ), 'UTF-8' );

        // Remover acentos
        $slug = iconv( 'UTF-8', 'ASCII//TRANSLIT', $slug );

        // Substituir espaços e caracteres especiais por hífens
        $slug = preg_replace( '/[^a-z0-9]+/', '-', $slug );

        // Remover hífens duplos e das extremidades
        $slug = trim( preg_replace( '/-+/', '-', $slug ), '-' );

        return $slug;
    }

    /**
     * Cria uma assinatura de plano.
     *
     * @param array $data Dados da assinatura
     * @param int $tenantId ID do tenant
     * @return ServiceResult
     */
    public function createSubscription( array $data, int $tenantId ): ServiceResult
    {
        try {
            $planId     = $data[ 'plan_id' ];
            $providerId = $data[ 'provider_id' ];

            $plan = $this->planRepository->findById( $planId );
            if ( !$plan ) {
                return ServiceResult::error( OperationStatus::NOT_FOUND, 'Plano não encontrado.' );
            }

            // Create subscription
            $subscription = new PlanSubscriptionEntity();
            $subscription->setPlanId( $planId );
            $subscription->setProviderId( $providerId );
            $subscription->setStatus( 'active' );
            $subscription->setCreatedAt( new DateTime() );
            $subscription->setExpiresAt( ( new DateTime() )->modify( '+1 month' ) );  // Example expiration

            // Save
            $this->entityManager->persist( $subscription );
            $this->entityManager->flush();

            // Create MercadoPago payment if needed
            $paymentResult = $this->paymentMercadoPagoPlanService->createPaymentForSubscription( $subscription->getId(), $plan->getPrice() );
            if ( !$paymentResult->isSuccess() ) {
                $this->entityManager->remove( $subscription );
                $this->entityManager->flush();
                return ServiceResult::error( OperationStatus::ERROR, 'Falha ao criar pagamento: ' . $paymentResult->message );
            }

            $this->logActivity( $tenantId, 'plan_subscription_created', $subscription->getId(), 'Assinatura criada', [ 
                'plan_id'     => $planId,
                'provider_id' => $providerId,
                'payment_id'  => $paymentResult->data[ 'payment_id' ]
            ] );

            return ServiceResult::success( $subscription, 'Assinatura criada com sucesso.' );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Falha ao criar assinatura: ' . $e->getMessage() );
        }
    }

    /**
     * Atualiza status de plano para cancelado.
     *
     * @param int $subscriptionId ID da assinatura
     * @return ServiceResult
     */
    public function updateStatusCancelled( int $subscriptionId ): ServiceResult
    {
        try {
            $subscription = $this->planSubscriptionRepository->findById( $subscriptionId );
            if ( !$subscription ) {
                return ServiceResult::error( OperationStatus::NOT_FOUND, 'Assinatura não encontrada.' );
            }

            $subscription->setStatus( 'cancelled' );
            $subscription->setCancelledAt( new DateTime() );

            // Update payment status
            $payment = $this->paymentMercadoPagoPlansRepository->findBySubscriptionId( $subscriptionId );
            if ( $payment ) {
                $this->paymentMercadoPagoPlanService->cancelPayment( $payment->getId() );
            }

            $this->entityManager->persist( $subscription );
            $this->entityManager->flush();

            $this->logActivity( $subscription->getTenantId(), 'plan_subscription_cancelled', $subscriptionId, 'Assinatura cancelada', [ 
                'subscription_id' => $subscriptionId,
                'plan_id'         => $subscription->getPlanId()
            ] );

            return ServiceResult::success( $subscription, 'Status cancelado atualizado.' );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Falha ao cancelar assinatura: ' . $e->getMessage() );
        }
    }

}
