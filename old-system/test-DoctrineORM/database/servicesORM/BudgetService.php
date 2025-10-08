<?php

declare(strict_types=1);

namespace app\database\servicesORM;

use app\database\entitiesORM\BudgetEntity;
use app\database\entitiesORM\BudgetStatusesEntity;
use app\database\entitiesORM\CustomerEntity;
use app\database\entitiesORM\ServiceEntity;
use app\database\entitiesORM\ServiceItemEntity;
use app\database\entitiesORM\ServiceStatusesEntity;
use app\database\entitiesORM\UserConfirmationTokenEntity;
use app\database\repositories\BudgetRepository;
use app\database\repositories\BudgetStatusesRepository;
use app\database\repositories\CustomerRepository;
use app\database\repositories\ScheduleRepository;
use app\database\repositories\ServiceItemRepository;
use app\database\repositories\ServiceRepository;
use app\database\repositories\ServiceStatusesRepository;
use app\database\servicesORM\NotificationService;
use app\database\servicesORM\PdfService;
use app\database\servicesORM\SharedService;
use app\enums\OperationStatus;
use app\support\ServiceResult;
use core\dbal\EntityNotFound;
use core\library\Session;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use RuntimeException;

class BudgetService
{
    private ?array $authenticated = null;

    public function __construct(
        private BudgetRepository $budgetRepository,
        private ServiceRepository $serviceRepository,
        private ServiceStatusesRepository $serviceStatusesRepository,
        private ServiceItemRepository $serviceItemRepository,
        private BudgetStatusesRepository $budgetStatusesRepository,
        private SharedService $sharedService,
        private CustomerRepository $customerRepository,
        private ScheduleRepository $scheduleRepository,
        private PdfService $pdfService,
        private NotificationService $notificationService,
        private EntityManagerInterface $entityManager,
        private ActivityService $activityService, // Adicionar injeção para logActivity
    ) {
        if ( Session::has( 'auth' ) ) {
            $this->authenticated = Session::get( 'auth' );
        }
    }

    /**
     * Cria um novo orçamento.
     *
     * @param array $data Dados do orçamento
     * @return ServiceResult
     */
    public function createBudget( array $data ): ServiceResult
    {
        try {
            if ( $this->authenticated === null || !isset( $this->authenticated[ 'tenant_id' ] ) ) {
                return ServiceResult::error( OperationStatus::UNAUTHORIZED, 'Usuário não autenticado.' );
            }

            $tenantId = (int) $this->authenticated[ 'tenant_id' ];

            // Geração de código
            $lastCode = $this->budgetRepository->getLastCode( $tenantId );
            $lastCode = (float) substr( $lastCode, -4 ) + 1;
            $code     = 'ORC-' . date( 'Ymd' ) . str_pad( (string) $lastCode, 4, '0', STR_PAD_LEFT );

            $entity = new BudgetEntity();
            $entity->setTenantId( $tenantId );
            $entity->setCode( $code );
            $entity->setTotal( 0.00 );
            $entity->setBudgetStatusesId( 1 ); // Draft
            $entity->setCustomerId( $data[ 'customer_id' ] );
            $entity->setCreatedAt( new \DateTimeImmutable() );
            $entity->setUpdatedAt( new \DateTimeImmutable() );

            // Salvar
            $this->entityManager->persist( $entity );
            $this->entityManager->flush();

            // Log
            $this->logActivity( $tenantId, 'budget_created', $entity->getId(), "Orçamento {$code} criado para cliente {$data[ 'customer_name' ]}", [ $entity->jsonSerialize() ] );

            return ServiceResult::success( $entity, 'Orçamento criado com sucesso!' );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Falha ao criar orçamento: ' . $e->getMessage() );
        }
    }

    /**
     * Atualiza um orçamento.
     *
     * @param array $data Dados atualizados
     * @return ServiceResult
     */
    public function updateBudget( array $data ): ServiceResult
    {
        try {
            if ( $this->authenticated === null || !isset( $this->authenticated[ 'tenant_id' ] ) ) {
                return ServiceResult::error( OperationStatus::UNAUTHORIZED, 'Usuário não autenticado.' );
            }

            $tenantId = (int) $this->authenticated[ 'tenant_id' ];

            $budget = $this->budgetRepository->findByCode( $data[ 'code' ], $tenantId );
            if ( !$budget ) {
                return ServiceResult::error( OperationStatus::NOT_FOUND, 'Orçamento não encontrado.' );
            }

            // Validação de data
            if ( isset( $data[ 'due_date' ] ) ) {
                $dueDate = new DateTime( $data[ 'due_date' ] );
                $today   = new DateTime( 'today' );
                if ( $dueDate < $today ) {
                    return ServiceResult::error( OperationStatus::INVALID_DATA, 'Data de vencimento inválida.' );
                }
            }

            $budget->setTotal( $data[ 'total' ] );
            $budget->setDueDate( new DateTime( $data[ 'due_date' ] ) );
            // Outros campos...

            $this->entityManager->persist( $budget );
            $this->entityManager->flush();

            $this->logActivity( $tenantId, 'budget_updated', $budget->getId(), "Orçamento atualizado", [ $budget->jsonSerialize() ] );

            return ServiceResult::success( $budget, 'Orçamento atualizado com sucesso!' );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Falha ao atualizar orçamento: ' . $e->getMessage() );
        }
    }

    /**
     * Busca dados para exibição de orçamento (show).
     *
     * @param string $code Código do orçamento
     * @return ServiceResult
     */
    public function getBudgetShowData( string $code ): ServiceResult
    {
        try {
            if ( $this->authenticated === null || !isset( $this->authenticated[ 'tenant_id' ] ) ) {
                return ServiceResult::error( OperationStatus::UNAUTHORIZED, 'Usuário não autenticado.' );
            }

            $tenantId = (int) $this->authenticated[ 'tenant_id' ];

            $budget = $this->budgetRepository->findFullByCode( $code, $tenantId );
            if ( !$budget ) {
                return ServiceResult::error( OperationStatus::NOT_FOUND, 'Orçamento não encontrado.' );
            }

            $services             = $this->serviceRepository->getAllServiceFullByIdBudget( $budget->getId(), $tenantId );
            $serviceItems         = [];
            $latestSchedules      = [];
            $allServicesCompleted = true;

            foreach ( $services as $service ) {
                $serviceItems[ $service->getId()]    = $this->serviceItemRepository->getAllServiceItemsByIdService( $service->getId(), $tenantId );
                $latestSchedules[ $service->getId()] = $this->scheduleRepository->getLatestByServiceId( $service->getId(), $tenantId );
                if ( $allServicesCompleted ) {
                    $allServicesCompleted = in_array( $service->getStatusSlug(), [ 'COMPLETED', 'PARTIAL', 'CANCELLED', 'NOT_PERFORMED', 'EXPIRED' ] );
                }
            }

            return ServiceResult::success( [ 
                'budget'                 => $budget,
                'services'               => $services,
                'service_items'          => $serviceItems,
                'latest_schedules'       => $latestSchedules,
                'all_services_completed' => $allServicesCompleted
            ], 'Dados do orçamento carregados.' );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Falha ao carregar dados do orçamento: ' . $e->getMessage() );
        }
    }

    /**
     * Manipula mudança de status do orçamento (handleStatusChange já existe, expandir se necessário).
     */
    // ... (manter método existente)

    /**
     * Gera PDF do orçamento.
     *
     * @param object $authenticated Autenticado
     * @param CustomerEntity $customer Cliente
     * @param BudgetEntity $budget Orçamento
     * @param array $services Serviços
     * @param array $serviceItems Itens
     * @param array $latestSchedules Agendamentos
     * @return array
     */
    public function printPDF( object $authenticated, CustomerEntity $customer, BudgetEntity $budget, array $services, array $serviceItems, array $latestSchedules ): array
    {
        // Implementar geração de PDF usando PdfService
        // Retornar ['content' => $pdfContent, 'fileName' => $filename]
        // Assumir que PdfService aceita 6 args; ajustar se necessário
        $pdfContent = $this->pdfService->generateBudgetPdf( $authenticated, $customer, $budget, $services, $serviceItems, $latestSchedules );
        $fileName   = "orcamento-{$budget->getCode()}.pdf";

        return [ 
            'content'  => $pdfContent,
            'fileName' => $fileName
        ];
    }

    /**
     * Deleta orçamento, verificando relacionamentos.
     *
     * @param int $id ID do orçamento
     * @return ServiceResult
     */
    public function deleteBudget( int $id ): ServiceResult
    {
        try {
            if ( $this->authenticated === null || !isset( $this->authenticated[ 'tenant_id' ] ) ) {
                return ServiceResult::error( OperationStatus::UNAUTHORIZED, 'Usuário não autenticado.' );
            }

            $tenantId = (int) $this->authenticated[ 'tenant_id' ];

            $entity = $this->budgetRepository->findById( $id, $tenantId );
            if ( !$entity ) {
                return ServiceResult::error( OperationStatus::NOT_FOUND, 'Orçamento não encontrado.' );
            }

            $relationships = $this->budgetRepository->checkRelationships( $id, $tenantId );
            if ( $relationships[ 'status' ] === 'success' ) {
                return ServiceResult::error( OperationStatus::CONFLICT, 'Orçamento possui relacionamentos.' );
            }

            $this->entityManager->remove( $entity );
            $this->entityManager->flush();

            $this->logActivity( $tenantId, 'budget_deleted', $id, 'Orçamento deletado', [ [ 'id' => $id ] ] );

            return ServiceResult::success( null, 'Orçamento deletado com sucesso!' );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Falha ao deletar orçamento: ' . $e->getMessage() );
        }
    }

    /**
     * Log de atividade.
     *
     * @param int $tenantId Tenant ID
     * @param string $action Action
     * @param int $entityId Entity ID
     * @param string $description Description
     * @param array $metadata Metadata
     */
    private function logActivity( int $tenantId, string $action, int $entityId, string $description, array $metadata ): void
    {
        if ( $this->authenticated === null || !isset( $this->authenticated[ 'user_id' ] ) ) {
            return; // Não log se não autenticado
        }

        $userId = (int) $this->authenticated[ 'user_id' ];
        $this->activityService->logActivity( $tenantId, $userId, $action, 'budget', $entityId, $description, $metadata );
    }

    /**
     * Busca todos os clientes para criação de orçamento.
     *
     * @param int $tenantId ID do tenant
     * @return ServiceResult Lista de CustomerEntity
     */
    public function getAllCustomersForBudget( int $tenantId ): ServiceResult
    {
        try {
            $customers = $this->customerRepository->findAllByTenantId( $tenantId );
            return ServiceResult::success( $customers, 'Clientes carregados com sucesso.' );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Falha ao carregar clientes: ' . $e->getMessage() );
        }
    }

    /**
     * Busca dados para atualização de orçamento.
     *
     * @param string $code Código do orçamento
     * @return ServiceResult ['budget' => BudgetEntity, 'services' => array<ServiceEntity>]
     */
    public function getBudgetUpdateData( string $code ): ServiceResult
    {
        try {
            if ( $this->authenticated === null || !isset( $this->authenticated[ 'tenant_id' ] ) ) {
                return ServiceResult::error( OperationStatus::UNAUTHORIZED, 'Usuário não autenticado.' );
            }

            $tenantId = (int) $this->authenticated[ 'tenant_id' ];
            $budget   = $this->budgetRepository->findByCode( $code, $tenantId );
            if ( !$budget ) {
                return ServiceResult::error( OperationStatus::NOT_FOUND, 'Orçamento não encontrado.' );
            }
            $services = $this->serviceRepository->findByBudgetId( $budget->getId(), $tenantId );
            return ServiceResult::success( [ 
                'budget'   => $budget,
                'services' => $services
            ], 'Dados para atualização carregados.' );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Falha ao carregar dados de atualização: ' . $e->getMessage() );
        }
    }

    /**
     * Busca orçamento por código.
     *
     * @param string $code Código do orçamento
     * @return ServiceResult BudgetEntity
     */
    public function getByCode( string $code ): ServiceResult
    {
        try {
            if ( $this->authenticated === null || !isset( $this->authenticated[ 'tenant_id' ] ) ) {
                return ServiceResult::error( OperationStatus::UNAUTHORIZED, 'Usuário não autenticado.' );
            }

            $tenantId = (int) $this->authenticated[ 'tenant_id' ];
            $budget   = $this->budgetRepository->findByCode( $code, $tenantId );
            if ( !$budget ) {
                return ServiceResult::error( OperationStatus::NOT_FOUND, 'Orçamento não encontrado.' );
            }
            return ServiceResult::success( $budget, 'Orçamento encontrado.' );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Falha ao buscar orçamento: ' . $e->getMessage() );
        }
    }

    /**
     * Manipula mudança de status do orçamento.
     *
     * @param array $data Dados da mudança
     * @param object $user Usuário (authenticated or token)
     * @return ServiceResult
     */
    public function handleStatusChange( array $data, object $user ): ServiceResult
    {
        try {
            if ( !isset( $user->tenant_id ) ) {
                return ServiceResult::error( OperationStatus::UNAUTHORIZED, 'Usuário não autenticado.' );
            }

            $tenantId  = (int) $user->tenant_id;
            $budgetId  = $data[ 'budget_id' ];
            $newStatus = $data[ 'new_status' ];

            $budget = $this->budgetRepository->findById( $budgetId, $tenantId );
            if ( !$budget ) {
                return ServiceResult::error( OperationStatus::NOT_FOUND, 'Orçamento não encontrado.' );
            }

            // Atualizar status budget
            $statusEntity = $this->budgetStatusesRepository->findBySlug( $newStatus );
            if ( !$statusEntity ) {
                return ServiceResult::error( OperationStatus::INVALID_DATA, 'Status inválido.' );
            }
            $budget->setBudgetStatusesId( $statusEntity->getId() );

            // Handle services statuses if needed
            $newServiceStatusNames = [];
            if ( isset( $data[ 'services' ] ) ) {
                foreach ( $data[ 'services' ] as $serviceId => $serviceStatus ) {
                    $service = $this->serviceRepository->findById( $serviceId, $tenantId );
                    if ( $service ) {
                        $serviceStatusEntity = $this->serviceStatusesRepository->findBySlug( $serviceStatus );
                        if ( $serviceStatusEntity ) {
                            $service->setServiceStatusesId( $serviceStatusEntity->getId() );
                            $this->entityManager->persist( $service );
                            $newServiceStatusNames[] = $serviceStatusEntity->getName();
                        }
                    }
                }
            }

            // Generate invoice if status requires
            if ( $newStatus === 'approved' || $newStatus === 'completed' ) {
                // TODO: Integrate with InvoiceService
            }

            $this->entityManager->persist( $budget );
            $this->entityManager->flush();

            $newStatusName = $statusEntity->getName();

            $this->logActivity( $tenantId, 'budget_status_changed', $budgetId, "Status alterado para {$newStatusName}", [ 
                'new_status'              => $newStatusName,
                'new_status_service_name' => $newServiceStatusNames
            ] );

            return ServiceResult::success( [ 
                'new_status_name'         => $newStatusName,
                'new_status_service_name' => $newServiceStatusNames
            ], 'Status atualizado com sucesso.' );

        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Falha ao alterar status: ' . $e->getMessage() );
        }
    }

    /**
     * Manipula token expirado para update budget.
     *
     * @param string $code Código do orçamento
     * @param object $token Token expirado
     * @param object $user Usuário
     * @return ServiceResult
     */
    public function handleTokenUpdateBudget( string $code, object $token, object $user ): ServiceResult
    {
        try {
            if ( !isset( $user->tenant_id ) || !isset( $user->user_id ) ) {
                return ServiceResult::error( OperationStatus::UNAUTHORIZED, 'Usuário não autenticado.' );
            }

            $tenantId = (int) $user->tenant_id;
            $userId   = (int) $user->user_id;

            // Generate new token
            $newToken = new UserConfirmationTokenEntity();
            $newToken->setUserId( $userId );
            $newToken->setTenantId( $tenantId );
            $newToken->setToken( bin2hex( random_bytes( 32 ) ) );
            $newToken->setExpiresAt( ( new DateTime() )->modify( '+24 hours' ) );
            $newToken->setUsed( false );
            $newToken->setType( 'budget_update' );
            $newToken->setRelatedId( $code ); // Related to budget code

            $this->entityManager->persist( $newToken );
            $this->entityManager->flush();

            // Send email with new token (use NotificationService)
            $this->notificationService->sendBudgetUpdateToken( $user, $newToken->getToken(), $code );

            $this->logActivity( $tenantId, 'budget_token_updated', 0, 'Novo token enviado para update de orçamento', [ 
                'code'         => $code,
                'new_token_id' => $newToken->getId()
            ] );

            return ServiceResult::success( null, 'Novo token enviado.' );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Falha ao gerar novo token: ' . $e->getMessage() );
        }
    }

    /**
     * Busca dados para impressão/PDF.
     *
     * @param string $code Código do orçamento
     * @param ?string $token Token opcional
     * @return ServiceResult ['pdf' => ['content' => string, 'fileName' => string]]
     */
    public function getBudgetPrintData( string $code, ?string $token = null ): ServiceResult
    {
        try {
            if ( $this->authenticated === null || !isset( $this->authenticated[ 'tenant_id' ] ) ) {
                return ServiceResult::error( OperationStatus::UNAUTHORIZED, 'Usuário não autenticado.' );
            }

            $tenantId = (int) $this->authenticated[ 'tenant_id' ];
            $budget   = $this->budgetRepository->findFullByCode( $code, $tenantId );
            if ( !$budget ) {
                return ServiceResult::error( OperationStatus::NOT_FOUND, 'Orçamento não encontrado.' );
            }

            $customer = $this->customerRepository->findById( $budget->getCustomerId(), $tenantId );
            if ( !$customer ) {
                return ServiceResult::error( OperationStatus::NOT_FOUND, 'Cliente não encontrado.' );
            }

            $services     = $this->serviceRepository->getAllServiceFullByIdBudget( $budget->getId(), $tenantId );
            $serviceItems = [];
            foreach ( $services as $service ) {
                $serviceItems[ $service->getId()] = $this->serviceItemRepository->getAllServiceItemsByIdService( $service->getId(), $tenantId );
            }
            $latestSchedules = [];
            foreach ( $services as $service ) {
                $latestSchedules[ $service->getId()] = $this->scheduleRepository->getLatestByServiceId( $service->getId(), $tenantId );
            }

            $pdfData = $this->printPDF( $this->authenticated, $customer, $budget, $services, $serviceItems, $latestSchedules );

            return ServiceResult::success( [ 
                'pdf' => $pdfData
            ], 'Dados para PDF gerados.' );

        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Falha ao preparar PDF: ' . $e->getMessage() );
        }
    }

    /**
     * Busca orçamento por código com dados do cliente.
     *
     * @param string $code Código do orçamento
     * @param int $tenantId ID do tenant
     * @return ServiceResult BudgetEntity com customer
     */
    public function getBudgetByCodeWithCustomerData( string $code, int $tenantId ): ServiceResult
    {
        try {
            $budget = $this->budgetRepository->findByCode( $code, $tenantId );
            if ( !$budget ) {
                return ServiceResult::error( OperationStatus::NOT_FOUND, 'Orçamento não encontrado.' );
            }

            $customer = $this->customerRepository->findById( $budget->getCustomerId(), $tenantId );
            if ( $customer ) {
                $budget->customer = $customer; // Adicionar relação para view
            }

            return ServiceResult::success( $budget, 'Orçamento com cliente carregado.' );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Falha ao carregar orçamento com cliente: ' . $e->getMessage() );
        }
    }

    /**
     * Busca todos os serviços completos por ID do orçamento.
     *
     * @param int $budgetId ID do orçamento
     * @param int $tenantId ID do tenant
     * @return ServiceResult array<ServiceEntity>
     */
    public function getAllServicesFullByBudgetId( int $budgetId, int $tenantId ): ServiceResult
    {
        try {
            $services = $this->serviceRepository->getAllServiceFullByIdBudget( $budgetId, $tenantId );
            return ServiceResult::success( $services, 'Serviços carregados.' );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Falha ao carregar serviços: ' . $e->getMessage() );
        }
    }

    /**
     * Busca todos os itens de serviço por ID do serviço.
     *
     * @param int $serviceId ID do serviço
     * @param int $tenantId ID do tenant
     * @return ServiceResult array<ServiceItemEntity>
     */
    public function getAllServiceItemsByServiceId( int $serviceId, int $tenantId ): ServiceResult
    {
        try {
            $items = $this->serviceItemRepository->getAllServiceItemsByIdService( $serviceId, $tenantId );
            return ServiceResult::success( $items, 'Itens de serviço carregados.' );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Falha ao carregar itens: ' . $e->getMessage() );
        }
    }

    /**
     * Busca o último agendamento por ID do serviço.
     *
     * @param int $serviceId ID do serviço
     * @param int $tenantId ID do tenant
     * @return ServiceResult ScheduleEntity or null
     */
    public function getLatestScheduleByServiceId( int $serviceId, int $tenantId ): ServiceResult
    {
        try {
            $schedule = $this->scheduleRepository->getLatestByServiceId( $serviceId, $tenantId );
            return ServiceResult::success( $schedule, 'Agendamento carregado.' );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Falha ao carregar agendamento: ' . $e->getMessage() );
        }
    }

    /**
     * Busca orçamentos recentes para um tenant.
     *
     * @param int $tenantId ID do tenant
     * @param int $limit Número máximo de orçamentos a retornar
     * @return ServiceResult Array de BudgetEntity ordenados por data de criação DESC
     */
    public function getRecentBudgets( int $tenantId, int $limit ): ServiceResult
    {
        try {
            $criteria = [ 'tenant_id' => $tenantId ];
            $orderBy  = [ 'created_at' => 'DESC' ];
            $budgets  = $this->budgetRepository->findBy( $criteria, $orderBy, $limit );

            return ServiceResult::success( $budgets, 'Orçamentos recentes carregados com sucesso.' );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Falha ao carregar orçamentos recentes: ' . $e->getMessage() );
        }
    }

}