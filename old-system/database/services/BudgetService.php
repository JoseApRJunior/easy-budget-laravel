<?php

namespace app\database\services;

use app\database\entitiesORM\BudgetEntity;
use app\database\entitiesORM\ServiceEntity;
use app\database\entitiesORM\ServiceItemEntity;
use app\database\entitiesORM\UserConfirmationTokenEntity;
use app\database\models\Budget;
use app\database\models\BudgetStatuses;
use app\database\models\Customer;
use app\database\models\Schedule;
use app\database\models\Service;
use app\database\models\ServiceItem;
use app\database\models\ServiceStatuses;
use core\dbal\EntityNotFound;
use core\library\Session;
use Doctrine\DBAL\Connection;
use Exception;
use RuntimeException;

class BudgetService
{
    /**
     * Summary of table
     * @var string
     */

    protected string $tableBudgets     = 'budgets';
    protected string $tableServices    = 'services';
    protected string $tableOrder_Items = 'order_items';
    private mixed    $authenticated;

    public function __construct(
        private readonly Connection $connection,
        private Service $service,
        private ServiceStatuses $serviceStatuses,
        private ServiceItem $serviceItem,
        private Budget $budget,
        private BudgetStatuses $budgetStatuses,
        private SharedService $sharedService,
        private Customer $customer,
        private Schedule $schedule,
        private PdfService $pdfService,
        private NotificationService $notificationService,
    ) {
        if ( Session::has( 'auth' ) ) {
            $this->authenticated = Session::get( 'auth' );
        }

    }

    /**
     * Manipula a mudança de status do orçamento.
     *
     * @param array<string, mixed> $data Dados da mudança de status
     * @param object $authenticated Usuário autenticado
     * @return array<string, mixed> Resultado da operação
     */
    public function handleStatusChange( array $data, object $authenticated ): array
    {
        $result              = [];
        $budget_id           = $data[ 'budget_id' ] ?? null;
        $current_status_name = $data[ 'current_status_name' ] ?? null;
        $current_status_slug = $data[ 'current_status_slug' ] ?? null;
        $new_status_slug     = $data[ 'action' ] ?? null;
        $email_notification  = $data[ 'email_notification' ] ?? null;

        // Buscar o orçamento pelo código (assegure-se de que o código é seguro)
        $budget = $this->budget->getBudgetFullById( $budget_id, $authenticated->tenant_id );
        // Verificar se o orçamento existe
        if ( $budget instanceof EntityNotFound ) {
            return [ 
                'status'  => 'error',
                'message' => 'Orçamento não encontrado.',
            ];
        }
        if (
            $current_status_slug == 'CANCELLED' or
            $current_status_slug == 'REJECTED' or
            $current_status_slug == 'EXPIRED'
        ) {
            $new_status_slug = 'DRAFT';
        }
        $newBudgetStatuses = $this->budgetStatuses->getStatusBySlug( $new_status_slug );
        if ( $newBudgetStatuses instanceof EntityNotFound ) {
            return [ 
                'status'  => 'error',
                'message' => 'Status do orçamento não encontrado.',
            ];
        }

        $services = $this->service->getAllServiceFullByIdBudget( $budget_id, $authenticated->tenant_id );

        $newBudgetStatusesToArray = $newBudgetStatuses->toArray();

        // Validação centralizada para evitar repetição
        $actionsRequiringServices = [ 'PENDING', 'APPROVED', 'REJECTED', 'DRAFT', 'EXPIRED' ];
        if ( in_array( $new_status_slug, $actionsRequiringServices ) && empty( $services ) ) {
            return [ 
                'status'  => 'error',
                'message' => 'Não é possível alterar o status do orçamento sem serviços associados.',
            ];
        }
        // Lógica para determinar o novo status com base na ação
        switch ( $new_status_slug ) {
            case 'PENDING':
                if ( $current_status_slug == 'DRAFT' ) {
                    $result = $this->changeStatus( $budget, $newBudgetStatusesToArray, $services, $authenticated );
                }

                break;
            case 'APPROVED':
                if ( $current_status_slug == 'PENDING' ) {
                    $result = $this->changeStatus( $budget, $newBudgetStatusesToArray, $services, $authenticated );
                }

                break;
            case 'REJECTED':
                if ( $current_status_slug == 'PENDING' ) {
                    $result = $this->changeStatus( $budget, $newBudgetStatusesToArray, $services, $authenticated );
                }

                break;
            case 'DRAFT':
                if (
                    $current_status_slug == 'CANCELLED' or
                    $current_status_slug == 'REJECTED' or
                    $current_status_slug == 'EXPIRED'
                ) {
                    $result = $this->changeStatus( $budget, $newBudgetStatusesToArray, $services, $authenticated );
                }

                break;

            case 'CANCELLED':
                if (
                    $current_status_slug !== 'CANCELLED' and $current_status_slug !== 'COMPLETED'
                ) {
                    $result = $this->changeStatus( $budget, $newBudgetStatusesToArray, $services, $authenticated );
                }

                break;
            case 'COMPLETED':
                if ( $current_status_slug == 'APPROVED' ) {
                    $result = $this->changeStatus( $budget, $newBudgetStatusesToArray, $services, $authenticated );
                }

                break;
            case 'EXPIRED':
                if ( $current_status_slug !== 'EXPIRED' and $current_status_slug !== 'COMPLETED' and $current_status_slug !== 'CANCELLED' ) {
                    $result = $this->changeStatus( $budget, $newBudgetStatusesToArray, $services, $authenticated );
                }

                break;
            default:
                return [ 
                    'status'  => 'error',
                    'message' => 'Ação inválida para mudança de status.',
                ];
        }

        return [ 
            'status'  => $result[ 'status' ] === 'success' ? 'success' : 'error',
            'message' => $result[ 'status' ] === 'success' ? 'Status do orçamento atualizado com sucesso.' : 'Nenhuma alteração realizada.',
            'data'    => [ 
                'old_status_name'         => $current_status_name ?? '',
                'new_status_name'         => $newBudgetStatusesToArray[ 'name' ] ?? '',
                'new_status_service_name' => $result[ 'data' ][ 'new_status_service_name' ] ?? '',
                'updated_budget'          => $result[ 'data' ][ 'updated_budget' ] ?? [],
                'updated_service'         => $result[ 'data' ][ 'updated_service' ] ?? [],
                'email_notification'      => $email_notification ?? false,
            ],
        ];

    }

    /**
     * Envia notificação do orçamento.
     *
     * @param object $budget Orçamento
     * @param string $token Token de confirmação
     * @return array<string, mixed> Resultado da operação
     */
    private function sendNotificationBudget( object $budget, string $token ): array
    {
        $customer = $this->customer->getCustomerFullbyId( $budget->customer_id, $this->authenticated->tenant_id );
        if ( $customer instanceof EntityNotFound ) {
            return [ 
                'status'  => 'error',
                'message' => 'Cliente não encontrado.',
            ];
        }

        $services = $this->service->getAllServiceFullByIdBudget( $budget->id, $this->authenticated->tenant_id );

        $service_items    = [];
        $latest_schedules = [];
        foreach ( $services as $service ) {
            $items = $this->serviceItem->getAllServiceItemsByIdService(
                $service[ 'id' ],
                $this->authenticated->tenant_id,
            );
            if ( is_array( $items ) ) {
                $service_items = array_merge( $service_items, $items );
            }

            $schedule = $this->schedule->getLatestByServiceId(
                $service[ 'id' ],
                $this->authenticated->tenant_id,
            );
            if ( !( $schedule instanceof EntityNotFound ) ) {
                $latest_schedules[] = $schedule->toArray();
            }
        }
        $pdf = $this->printPDF( $this->authenticated, $customer, $budget, $services, $service_items, $latest_schedules );
        if ( $pdf instanceof EntityNotFound ) {
            return [ 
                'status'  => 'error',
                'message' => 'Erro ao gerar o PDF do orçamento.',
            ];
        }
        $result = $this->notificationService->sendEmailApprovalBudgetNotification( $this->authenticated, $budget, $customer, $pdf, $token );
        if ( !$result ) {
            return [ 
                'status'  => 'error',
                'message' => 'Erro ao enviar notificação de aprovação do orçamento.',
            ];
        }

        return [ 
            'status'  => 'success',
            'message' => 'Notificação de aprovação do orçamento enviada com sucesso.',
        ];
    }

    /**
     * Altera o status do orçamento.
     *
     * @param object $budget Orçamento
     * @param array<string, mixed> $newBudgetStatuses Novo status do orçamento
     * @param array<int, array<string, mixed>> $services Serviços do orçamento
     * @param object $authenticated Usuário autenticado
     * @return array<string, mixed> Resultado da operação
     */
    public function changeStatus( object $budget, array $newBudgetStatuses, array $services, object $authenticated ): array
    {
        try {
            return $this->connection->transactional( function () use ($budget, $newBudgetStatuses, $services, $authenticated): array {
                $result                  = [];
                $updatedService          = [];
                $new_status_service_name = [];
                $updatedBudget           = [];

                // Lógica para garantir que o orçamento tenha um token de confirmação válido.
                $needsNewToken = true;
                if ( $budget->user_confirmation_token_id ) {
                    $token = $this->sharedService->getUserConfirmationTokenById( $budget->user_confirmation_token_id, $authenticated->tenant_id );
                    if ( $token instanceof UserConfirmationTokenEntity && $token->expires_at > new \DateTime() ) {
                        $needsNewToken = false;
                    }
                }

                if ( $needsNewToken ) {
                    $result = $this->sharedService->generateNewUserConfirmationToken( $authenticated->user_id, $authenticated->tenant_id );
                    if ( $result[ 'status' ] === 'error' ) {
                        return [ 
                            'status'  => 'error',
                            'message' => 'Erro ao criar o token de confirmação.',
                        ];
                    }
                    $budget->user_confirmation_token_id = (int) $result[ 'data' ][ 'id' ];
                }

                $budget->budget_statuses_id = $newBudgetStatuses[ 'id' ]; // id do novo status

                // Sessão atualizaçao de status de orçamento
                $budgetEntity = BudgetEntity::create( removeUnnecessaryIndexes(
                    getConstructorProperties( BudgetEntity::class),
                    [ 'created_at', 'updated_at' ],
                    (array) $budget, // id do novo status
                ) );
                $result       = $this->budget->update( $budgetEntity );

                if ( $result[ 'status' ] === 'success' ) {
                    $budget = $this->budget->getBudgetFullById( $budget->id, $authenticated->tenant_id );

                    $updatedBudget = $budgetEntity;
                    // Fim sessão atualizaçao de status de orçamento
                    $serviceStatusesBySlug = $this->serviceStatuses->getAllStatusesBySlug();

                    switch ( $newBudgetStatuses[ 'slug' ] ) {
                        case 'PENDING':
                            // Inicio da sessão atualizaçao de status de servico
                            foreach ( $services as $service ) {
                                if ( $service[ 'status_slug' ] == 'DRAFT' ) { // id do status rascunho
                                    $result = $this->updateServiceStatus( $service, 'PENDING', $serviceStatusesBySlug );
                                    if ( $result[ 'status' ] === 'success' ) {
                                        $updatedService[] = $result[ 'data' ];
                                        if ( $serviceStatusesBySlug[ 'PENDING' ] instanceof \app\database\entities\ServiceStatusesEntity ) {
                                            $new_status_service_name = $serviceStatusesBySlug[ 'PENDING' ]->name;
                                        }
                                    }
                                }

                            }

                            // Criar UserConfirmationTokens e retorna o id do userConfirmationToken
                            $result = $this->sharedService->generateNewUserConfirmationToken( $this->authenticated->user_id, $this->authenticated->tenant_id );
                            // verifica se o userConfirmationToken foi criado com sucesso
                            if ( $result[ 'status' ] === 'error' ) {
                                return [ 
                                    'status'  => 'error',
                                    'message' => 'Erro ao criar o token de confirmação.',
                                ];
                            }
                            $createdUserConfirmationToken = $result[ 'data' ];
                            // Fim da sessão criar userConfirmationToken

                            $result = $this->sendNotificationBudget( $budget, $createdUserConfirmationToken[ 'token' ] );
                            if ( $result[ 'status' ] === 'error' ) {
                                return [ 
                                    'status'  => 'error',
                                    'message' => 'Erro ao enviar notificação de aprovação do orçamento.',
                                ];
                            }

                            // Fim da sessão atualizaçao de status de servico
                            break;

                        case 'APPROVED':
                            // Inicio da sessão atualizaçao de status de servico
                            foreach ( $services as $service ) {
                                if ( $service[ 'status_slug' ] == 'PENDING' ) { // id do status pendente
                                    $result = $this->updateServiceStatus( $service, 'SCHEDULING', $serviceStatusesBySlug );
                                    if ( $result[ 'status' ] === 'success' ) {
                                        $updatedService[] = $result[ 'data' ];
                                        if ( $serviceStatusesBySlug[ 'SCHEDULING' ] instanceof \app\database\entities\ServiceStatusesEntity ) {
                                            $new_status_service_name = $serviceStatusesBySlug[ 'SCHEDULING' ]->name;
                                        }
                                    }
                                }
                            }

                            // Fim da sessão atualizaçao de status de servico
                            break;

                        case 'REJECTED':
                            // Inicio da sessão atualizaçao de status de servico
                            foreach ( $services as $service ) {
                                if ( $service[ 'status_slug' ] == 'PENDING' ) { // id do status pendente
                                    $result = $this->updateServiceStatus( $service, 'DRAFT', $serviceStatusesBySlug );
                                    if ( $result[ 'status' ] === 'success' ) {
                                        $updatedService[] = $result[ 'data' ];
                                        if ( $serviceStatusesBySlug[ 'DRAFT' ] instanceof \app\database\entities\ServiceStatusesEntity ) {
                                            $new_status_service_name = $serviceStatusesBySlug[ 'DRAFT' ]->name;
                                        }
                                    }
                                }
                            }

                            // Fim da sessão atualizaçao de status de servico
                            break;

                        case 'CANCELLED':
                            foreach ( $services as $service ) {
                                // Pula serviços que já estão em um estado final
                                if ( in_array( $service[ 'status_slug' ], [ 'COMPLETED', 'PARTIAL', 'CANCELLED', 'NOT_PERFORMED', 'EXPIRED' ] ) ) {
                                    continue;
                                }

                                // Se o serviço estiver em andamento, torna-se parcial. Caso contrário, é cancelado.
                                $newServiceStatusSlug = ( $service[ 'status_slug' ] === 'IN_PROGRESS' ) ? 'PARTIAL' : 'CANCELLED';

                                $result = $this->updateServiceStatus( $service, $newServiceStatusSlug, $serviceStatusesBySlug );
                                if ( $result[ 'status' ] === 'success' ) {
                                    $updatedService[] = $result[ 'data' ];
                                    if ( $serviceStatusesBySlug[ $newServiceStatusSlug ] instanceof \app\database\entities\ServiceStatusesEntity ) {
                                        $new_status_service_name[] = $serviceStatusesBySlug[ $newServiceStatusSlug ]->name;
                                    }
                                }
                            }
                            // Como os serviços podem ter múltiplos novos status, definimos um nome genérico.

                            // Fim da sessão atualizaçao de status de servico
                            break;

                        case 'COMPLETED':
                            // Inicio da sessão atualizaçao de status de servico
                            $all_services_completed = true;
                            foreach ( $services as $service ) {
                                if ( $all_services_completed ) {
                                    $all_services_completed =
                                        in_array( $service[ 'status_slug' ], [ 
                                            'COMPLETED',
                                            'PARTIAL',
                                            'CANCELLED',
                                            'NOT_PERFORMED',
                                            'EXPIRED',
                                        ] );
                                }
                            }
                            if ( !$all_services_completed ) {
                                $this->connection->rollBack();

                                return [ 
                                    'status'  => 'error',
                                    'message' => 'Não é possível alterar o status do orçamento para concluído, pois existem serviços pendentes.',
                                ];
                            }

                            // Fim da sessão atualizaçao de status de servico
                            break;

                        case 'EXPIRED':
                            // Inicio da sessão atualizaçao de status de servico
                            foreach ( $services as $service ) {
                                if (
                                    $service[ 'status_slug' ] != 'CANCELLED' and // id do status cancelado
                                    $service[ 'status_slug' ] != 'COMPLETED' and // id do status concluído
                                    $service[ 'status_slug' ] != 'PARTIAL' and  // id do status parcial
                                    $service[ 'status_slug' ] != 'NOT_PERFORMED' and // id do status não realizado
                                    $service[ 'status_slug' ] != 'EXPIRED' // id do status expirado
                                ) {
                                    $result = $this->updateServiceStatus( $service, 'EXPIRED', $serviceStatusesBySlug );
                                    if ( $result[ 'status' ] === 'success' ) {
                                        $updatedService[] = $result[ 'data' ];
                                        if ( $serviceStatusesBySlug[ 'EXPIRED' ] instanceof \app\database\entities\ServiceStatusesEntity ) {
                                            $new_status_service_name = $serviceStatusesBySlug[ 'EXPIRED' ]->name;
                                        }
                                    }
                                }
                            }

                            // Fim da sessão atualizaçao de status de servico
                            break;

                        case 'DRAFT':
                            // Inicio da sessão atualizaçao de status de servico
                            foreach ( $services as $service ) {
                                if (
                                    $service[ 'status_slug' ] != 'DRAFT' and // id do status rascunho
                                    $service[ 'status_slug' ] != 'COMPLETED' and // id do status concluído
                                    $service[ 'status_slug' ] != 'PARTIAL' // id do status parcial
                                ) {
                                    $result = $this->updateServiceStatus( $service, 'DRAFT', $serviceStatusesBySlug );
                                    if ( $result[ 'status' ] === 'success' ) {
                                        $updatedService[] = $result[ 'data' ];
                                        if ( $serviceStatusesBySlug[ 'DRAFT' ] instanceof \app\database\entities\ServiceStatusesEntity ) {
                                            $new_status_service_name = $serviceStatusesBySlug[ 'DRAFT' ]->name;
                                        }
                                    }
                                }
                            }

                            // Fim da sessão atualizaçao de status de servico
                            break;

                        default:
                            return [ 
                                'status'  => 'error',
                                'message' => 'Ação inválida para mudança de status.',
                            ];
                    }
                }

                return [ 
                    'status'  => $result[ 'status' ] === 'success' ? 'success' : 'error',
                    'message' => $result[ 'status' ] === 'success' ? 'Status do orçamento atualizado com sucesso.' : 'Nenhuma alteração realizada.',
                    'data'    => [ 
                        'updated_service'         => $updatedService,
                        'new_status_service_name' => $new_status_service_name,
                        'updated_budget'          => $updatedBudget,
                    ],
                ];
            } );

        } catch ( Exception $e ) {
            throw new RuntimeException( "Falha ao atualizar o status do orçamento, tente novamente mais tarde ou entre em contato com suporte!", 0, $e );
        }
    }

    /**
     * Atualiza o status de um serviço com base no slug fornecido.
     *
     * @param array<string, mixed> $service Dados do serviço a ser atualizado.
     * @param string $newStatusSlug Slug do novo status.
     * @param array<string, mixed> $serviceStatusesBySlug Lista de status disponíveis indexados pelo slug.
     * @return array<string, mixed> Resultado da operação de atualização.
     */
    private function updateServiceStatus( array $service, string $newStatusSlug, array $serviceStatusesBySlug ): array
    {
        $properties = getConstructorProperties( ServiceEntity::class);
        if ( $serviceStatusesBySlug[ $newStatusSlug ] instanceof \app\database\entities\ServiceStatusesEntity ) {
            $service[ 'service_statuses_id' ] = $serviceStatusesBySlug[ $newStatusSlug ]->id;
        }

        $serviceEntity = ServiceEntity::create( removeUnnecessaryIndexes(
            $properties,
            [ 'created_at', 'updated_at' ],
            $service,
        ) );

        return $this->service->update( $serviceEntity );
    }

    /**
     * Altera o status do orçamento.
     *
     * @param int $budget_id ID do orçamento
     * @param string $new_status_slug Slug do novo status
     * @return array<string, mixed> Resultado da operação
     */
    public function changeStatusBudget( int $budget_id, string $new_status_slug ): array
    {
        try {
            return $this->connection->transactional( function () use ($budget_id, $new_status_slug) {
                $result        = [];
                $updatedBudget = [];

                $budget = $this->budget->getBudgetById( $budget_id, $this->authenticated->tenant_id );
                $budget = $budget->toArray();
                // Verificar se o orçamento existe
                if ( empty( $budget ) ) {
                    return [ 
                        'status'  => 'error',
                        'message' => 'Orçamento não encontrado.',
                    ];
                }

                $newBudgetStatuses = $this->budgetStatuses->getStatusBySlug( $new_status_slug );
                if ( $newBudgetStatuses instanceof EntityNotFound ) {
                    return [ 
                        'status'  => 'error',
                        'message' => 'Status do orçamento não encontrado.',
                    ];
                }
                $newBudgetStatusesToArray = $newBudgetStatuses->toArray();
                // Sessão atualizaçao de status de orçamento
                $budgetEntity = BudgetEntity::create( removeUnnecessaryIndexes(
                    $budget,
                    [ 'created_at', 'updated_at' ],
                    [ 'budget_statuses_id' => $newBudgetStatusesToArray[ 'id' ] ], // id do novo status
                ) );

                if ( $newBudgetStatusesToArray[ 'id' ] === $budget[ 'budget_statuses_id' ] ) {
                    return [ 
                        'status'  => 'success',
                        'message' => 'Já esta no status informado.',
                    ];
                }

                $result = $this->budget->update( $budgetEntity );

                if ( $result[ 'status' ] === 'success' ) {
                    $updatedBudget = $budgetEntity;
                    // Fim sessão atualizaçao de status de orçamento
                }

                return [ 
                    'status'  => $result[ 'status' ] === 'success' ? 'success' : 'error',
                    'message' => $result[ 'status' ] === 'success' ? 'Status do orçamento atualizado com sucesso.' : 'Nenhuma alteração realizada.',
                    'data'    => [ 
                        'updated_budget'  => $updatedBudget,
                        'new_status_name' => $newBudgetStatusesToArray[ 'name' ] ?? '',
                    ],
                ];
            } );

        } catch ( Exception $e ) {
            throw new RuntimeException( "Falha ao atualizar o status do orçamento, tente novamente mais tarde ou entre em contato com suporte!", 0, $e );
        }
    }

    /**
     * Cria um novo serviço.
     *
     * @param array<string, mixed> $data Dados do serviço
     * @return array<string, mixed> Resultado da operação
     */
    public function createService( array $data ): array
    {
        try {
            return $this->connection->transactional( function () use ($data) {
                $result           = false;
                $createdService   = [];
                $createdServiceId = 0;
                $updatedBudget    = [];
                $createdItems     = [];
                $items            = $data[ 'items' ];

                // Sessão criar servico
                $budget = $this->budget->getBudgetByCode( $data[ 'code' ], $this->authenticated->tenant_id );
                if ( $budget instanceof EntityNotFound ) {
                    return [ 
                        'status'  => 'error',
                        'message' => 'Orçamento não encontrado.',
                    ];
                }
                /** @var BudgetEntity $budget */
                $last_code = $this->service->getLastCode( $budget->id, $this->authenticated->tenant_id );

                // Extrair apenas os últimos três dígitos do código
                $last_code = (float) ( substr( $last_code, -3 ) ) + 1;

                $data[ 'code' ] = $data[ 'code' ] . '-S' . str_pad( (string) $last_code, 3, '0', STR_PAD_LEFT );

                // Service
                $properties                = getConstructorProperties( ServiceEntity::class);
                $properties[ 'tenant_id' ] = $this->authenticated->tenant_id;
                /** @var BudgetEntity $budget  */
                $properties[ 'budget_id' ]           = $budget->id;
                $properties[ 'category_id' ]         = $data[ 'category' ];
                $properties[ 'service_statuses_id' ] = $data[ 'status' ];
                $properties[ 'total' ]               = array_sum( array_column( $items, 'total' ) );

                $serviceEntity = ServiceEntity::create( removeUnnecessaryIndexes(
                    $properties,
                    [ 'id', 'created_at', 'updated_at' ],
                    $data,
                ) );

                $result = $this->service->create( $serviceEntity );

                if ( $result[ 'status' ] === 'success' ) {
                    $createdServiceId = $result;
                    $createdService   = $serviceEntity;
                    // Fim da Sessão criar servico

                    // Sessão criar serviceItem
                    $service_id = $result;
                    foreach ( $items as $item ) {
                        // ServiceItem
                        $properties                 = getConstructorProperties( ServiceItemEntity::class);
                        $properties[ 'tenant_id' ]  = $this->authenticated->tenant_id;
                        $properties[ 'service_id' ] = $service_id;
                        $properties[ 'product_id' ] = $item[ 'id' ];

                        // popula model ServiceEntity
                        $serviceItemEntity = ServiceItemEntity::create( removeUnnecessaryIndexes(
                            $properties,
                            [ 'id', 'created_at', 'updated_at' ],
                            $item,
                        ) );

                        $result = $this->serviceItem->create( $serviceItemEntity );
                        if ( $result[ 'status' ] === 'success' ) {
                            $createdItems[] = $serviceItemEntity;
                        }
                    }
                    // Fim da Sessão criar serviceItem

                    // Sessão atualizar orçamento
                    $services = $this->service->getAllServiceFullByIdBudget( $budget->id, $this->authenticated->tenant_id );

                    // Budget
                    $budgetToArray            = $budget->toArray();
                    $properties               = getConstructorProperties( BudgetEntity::class);
                    $budgetToArray[ 'total' ] = array_sum( array_column( $services, 'total' ) );
                    $budgetEntity             = BudgetEntity::create( removeUnnecessaryIndexes(
                        $properties,
                        [ 'created_at', 'updated_at' ],
                        $budgetToArray,
                    ) );

                    if ( !compareObjects( $budget, $budgetEntity, [ 'created_at', 'updated_at' ] ) ) {
                        $result = $this->budget->update( $budgetEntity );
                        if ( $result[ 'status' ] === 'success' ) {
                            $updatedBudget = $budgetEntity;
                        }
                    }
                    // Fim da Sessão atualizar orçamento
                }

                return [ 
                    'status'  => $result[ 'status' ] === 'success' ? 'success' : 'error',
                    'message' => $result[ 'status' ] === 'success' ? 'Serviço cadastrado com sucesso.' : 'Nenhum serviço foi cadastrado.',
                    'data'    => [ 
                        'created_service_id' => $createdServiceId,
                        'created_service'    => $createdService,
                        'updated_budget'     => $updatedBudget,
                        'created_items'      => $createdItems,
                    ],
                ];
            } );

        } catch ( Exception $e ) {
            throw new RuntimeException( "Falha ao registrar o novo cliente, tente novamente mais tarde ou entre em contato com suporte!", 0, $e );
        }
    }

    /**
     * Atualiza um serviço existente.
     *
     * @param array<string, mixed> $data Dados do serviço
     * @return array<string, mixed> Resultado da operação
     */
    public function updateService( array $data ): array
    {
        try {
            return $this->connection->transactional( function () use ($data) {

                $result         = false;
                $itemsOld       = [];
                $updatedService = [];
                $updatedBudget  = [];
                $deletedItems   = [];
                $updatedItems   = [];
                $createdItems   = [];

                // Sessão atualizaçao de servico
                $service = $this->service->getServiceById( $data[ 'id' ], $this->authenticated->tenant_id );

                // Verificar se o servico existe
                if ( $service instanceof EntityNotFound ) {
                    return [ 
                        'status'  => 'error',
                        'message' => 'Serviço não encontrado.',
                    ];
                }

                // Converter o objeto para array
                $originalData = $service->toArray();

                // Verificar se a data de vencimento é válida
                if ( isset( $data[ 'due_date' ] ) ) {
                    $dueDate = \convertToDateTime( $data[ 'due_date' ] );
                    $today   = \convertToDateTime( date( 'Y-m-d' ) );

                    if ( $dueDate < $today ) {
                        return [ 
                            'status'  => 'error',
                            'message' => 'A data de vencimento não pode ser anterior à data atual.',
                        ];
                    }
                }

                $data[ 'total' ]               = array_sum( array_column( $data[ 'items' ], 'total' ) );
                $data[ 'service_statuses_id' ] = $data[ 'status' ];
                // Popula ServiceEntity com os dados do formulário
                $serviceEntity = ServiceEntity::create( removeUnnecessaryIndexes(
                    $originalData,
                    [],
                    $data,
                ) );

                // Verificar se os dados do formulário foram alterados
                if ( !compareObjects( $service, $serviceEntity, [ 'created_at', 'updated_at' ] ) ) {
                    // Atualizar ServiceEntity com os dados do formulário
                    $result        = $this->service->update( $serviceEntity );
                    $serviceEntity = $serviceEntity->toArray();

                    // Verificar se o serviço foi atualizado com sucesso
                    if ( $result[ 'status' ] === 'success' ) {
                        $updatedService = $serviceEntity;
                    }
                }
                // Fim sessão atualizaçao de servico

                // Busca os servicos do orçamento
                $services = $this->service->getServiceByBudgetId( $originalData[ 'budget_id' ], $this->authenticated->tenant_id );

                // Verificar se o servico existe
                if ( $services instanceof EntityNotFound ) {
                    return [ 
                        'status'  => 'error',
                        'message' => 'Serviço não encontrado.',
                    ];
                }

                // Sessão atualizaçao de orçamento
                $budget = $this->budget->getBudgetById( $originalData[ 'budget_id' ], $this->authenticated->tenant_id );

                // Verificar se o orçamento existe
                if ( $budget instanceof EntityNotFound ) {
                    return [ 
                        'status'  => 'error',
                        'message' => 'Orçamento não encontrado.',
                    ];
                }
                $originalData = $budget->toArray();
                $services     = is_array( $services ) ? $services : $services->toArray();

                $originalData[ 'total' ] = array_sum( array_column( $services, 'total' ) );
                // Popula budgetEntity
                $budgetEntity = BudgetEntity::create(
                    $originalData,
                );

                if ( !compareObjects( $budget, $budgetEntity, [ 'created_at', 'updated_at' ] ) ) {

                    $result = $this->budget->update( $budgetEntity );

                    $budgetEntity = $budgetEntity->toArray();

                    // Verificar se o serviço foi atualizado com sucesso
                    if ( $result[ 'status' ] === 'success' ) {
                        $updatedBudget = $budgetEntity;
                    }
                }
                // Fim sessão atualizaçao de orçamento

                // Sessão deletar, atualizar e criar item de serviço
                $serviceItem = $this->serviceItem->getAllServiceItemsByIdService( $data[ 'id' ], $this->authenticated->tenant_id );
                if ( empty( $serviceItem ) ) {
                    return [ 
                        'status'  => 'error',
                        'message' => 'Item de serviço não encontrado.',
                    ];
                }

                $items = $data[ 'items' ];
                foreach ( $serviceItem as $item ) {

                    // Verifica se o código do item está presente nos itens enviados e se houve alteração
                    if ( in_array( $item[ 'product_id' ], array_column( $data[ 'items' ], 'product_id' ) ) ) {
                        $itemsOld[] = $item;
                    } else {
                        // Se o item não estiver mais presente, exclui-o
                        $result = $this->serviceItem->delete( $item[ 'id' ], $this->authenticated->tenant_id );
                        if ( $result[ 'status' ] === 'success' ) {
                            $deletedItems[] = $item;
                        }
                    }
                }

                foreach ( $items as $item ) {
                    if ( isset( $item[ 'id' ] ) ) {
                        // ServiceItem
                        $properties                 = getConstructorProperties( ServiceItemEntity::class);
                        $properties[ 'tenant_id' ]  = $this->authenticated->tenant_id;
                        $properties[ 'service_id' ] = $data[ 'id' ];
                        $properties[ 'product_id' ] = $item[ 'id' ];

                        // popula model ServiceItemEntity
                        $serviceItemEntity = ServiceItemEntity::create( removeUnnecessaryIndexes(
                            $properties,
                            [ 'created_at', 'updated_at' ],
                            $item,
                        ) );

                        if ( in_array( $item[ 'product_id' ], array_column( $itemsOld, 'product_id' ) ) ) {
                            $index = array_search( $item[ 'product_id' ], array_column( $itemsOld, 'product_id' ) );
                            if ( $item[ 'quantity' ] != $itemsOld[ $index ][ 'quantity' ] ) {
                                $result = $this->serviceItem->update( $serviceItemEntity );
                                if ( $result[ 'status' ] === 'success' ) {
                                    $updatedItems[] = $serviceItemEntity->toArray();
                                }
                            }
                        } else {
                            $result = $this->serviceItem->create( $serviceItemEntity );
                            if ( $result[ 'status' ] === 'success' ) {
                                $createdItems[] = $serviceItemEntity->toArray();
                            }
                        }
                    }
                }
                // Fim sessão deletar, atualizar e criar item de serviço

                return [ 
                    'status'  => $result[ 'status' ] === 'success' ? 'success' : 'error',
                    'message' => $result[ 'status' ] === 'success' ? 'Serviço atualizado com sucesso.' : 'Nenhuma alteração realizada.',
                    'data'    => [ 
                        'updated_service' => $updatedService,
                        'updated_budget'  => $updatedBudget,
                        'deleted_items'   => $deletedItems,
                        'updated_items'   => $updatedItems,
                        'created_items'   => $createdItems,
                    ],
                ];
            } );

        } catch ( Exception $e ) {
            throw new RuntimeException( "Falha ao registrar ao atualizar o servico, tente novamente mais tarde ou entre em contato com suporte!", 0, $e );
        }
    }

    /**
     * Gera PDF do orçamento.
     *
     * @param object $authenticated Usuário autenticado
     * @param object $customer Cliente
     * @param object $budget Orçamento
     * @param array<int, array<string, mixed>> $services Serviços
     * @param array<int, array<string, mixed>> $service_items Itens dos serviços
     * @param array<int, array<string, mixed>> $latest_schedules Agendamentos mais recentes
     * @return mixed Resultado da geração do PDF
     */
    public function printPDF( object $authenticated, object $customer, object $budget, array $services, array $service_items, array $latest_schedules ): mixed
    {
        // Lógica de verificação do Hash
        $verificationHash = $budget->pdf_verification_hash;
        if ( empty( $verificationHash ) ) {
            $verificationHash              = bin2hex( random_bytes( 20 ) );
            $budget->pdf_verification_hash = $verificationHash;
            // Atualiza o serviço no banco com o novo hash

            $properties   = getConstructorProperties( BudgetEntity::class);
            $budgetEntity = BudgetEntity::create( removeUnnecessaryIndexes(
                $properties,
                [ 'created_at', 'updated_at' ],
                (array) $budget,
            ) );

            $result = $this->budget->update( $budgetEntity );
            if ( $result[ 'status' ] !== 'success' ) {
                return [ 
                    'status'  => 'error',
                    'message' => 'Erro ao atualizar o orçamento com o novo hash de verificação.',
                ];
            }
        }

        return $this->pdfService->generateBudgetPdf( $authenticated, $customer, $budget, $services, $service_items, $latest_schedules, $verificationHash );
    }

    /**
     * Manipula a atualização de token do orçamento.
     *
     * @param string $code Código do orçamento
     * @param UserConfirmationTokenEntity $userConfirmationTokenEntity Token de confirmação
     * @param object $authenticated Usuário autenticado
     * @return array<string, mixed> Resultado da operação
     */
    public function handleTokenUpdateBudget( string $code, UserConfirmationTokenEntity $userConfirmationTokenEntity, object $authenticated ): array
    {
        if ( $this->authenticated === null ) {
            $this->authenticated = $authenticated;
        }

        try {
            return $this->connection->transactional( function () use ($code, $userConfirmationTokenEntity) {
                $result                       = [];
                $createdUserConfirmationToken = [];
                $updated_budget               = [];
                $updated_budget_id            = null;
                $sendEmailNotification        = [];

                $budget = $this->budget->getBudgetByCode(
                    $code,
                    $this->authenticated->tenant_id,
                );
                if ( $budget instanceof EntityNotFound ) {
                    return
                        [ 
                            'status'  => 'error',
                            'message' => 'Orçamento não encontrado.',
                        ];
                }

                /** @var BudgetEntity $budget                  */
                if ( $budget->user_confirmation_token_id !== $userConfirmationTokenEntity->id ) {
                    return [ 
                        'status'  => 'error',
                        'message' => 'Um novo token, ja foi enviado no seu e-mail.',
                    ];
                }

                // Sessão criar userConfirmationToken

                // Criar UserConfirmationTokens e retorna o id do userConfirmationToken
                $result = $this->sharedService->generateNewUserConfirmationToken( $this->authenticated->user_id, $this->authenticated->tenant_id );
                // verifica se o userConfirmationToken foi criado com sucesso
                if ( $result[ 'status' ] === 'error' ) {
                    return [ 
                        'status'  => 'error',
                        'message' => 'Erro ao criar o token de confirmação.',
                    ];
                }
                $createdUserConfirmationToken = $result[ 'data' ];
                // Fim da sessão criar userConfirmationToken

                // Inicio da lógica de atualizaçao de token agendamento
                // Converter o objeto para array
                $originalData                            = $budget->toArray();
                $newData                                 = $originalData;
                $newData[ 'user_confirmation_token_id' ] = (int) $createdUserConfirmationToken[ 'id' ];

                // Popula BudgetEntity com os dados do formulário
                $budgetEntity = BudgetEntity::create( removeUnnecessaryIndexes(
                    $originalData,
                    [],
                    $newData,
                ) );

                // Verificar se os dados foram alterados
                if ( !compareObjects( $originalData, $newData, [ 'created_at', 'updated_at' ] ) ) {
                    // Atualizar BudgetEntity
                    $result = $this->budget->update( $budgetEntity );

                    // Verificar se o orçamento foi atualizado com sucesso
                    if ( $result[ 'status' ] === 'success' ) {
                        $updated_budget    = $result[ 'data' ];
                        $updated_budget_id = $result[ 'data' ][ 'id' ];
                        // Fim sessão atualizaçao de orçamento

                        $customer = $this->customer->getCustomerFullbyId( $budget->customer_id, $this->authenticated->tenant_id );
                        if ( $customer instanceof EntityNotFound ) {
                            return [ 
                                'status'  => 'error',
                                'message' => 'Cliente não encontrado.',
                            ];
                        }

                        // Sessão envio de e-mail
                        $result                = $this->notificationService->sendNewTokenForBudgetNotification( $this->authenticated, $budget, $customer, $createdUserConfirmationToken[ 'token' ] );
                        $sendEmailNotification = $result;

                        if ( !$result ) {
                            return [ 
                                'status'  => 'error',
                                'message' => 'Erro ao enviar o e-mail com o novo token.',
                            ];
                        }
                        // Fim sessão envio de e-mail
                    }
                }

                return [ 
                    'status'  => $result[ 'status' ] === 'success' ? 'success' : 'error',
                    'message' => $result[ 'status' ] === 'success' ? 'Orçamento atualizado com sucesso.' : 'Nenhum orçamento foi atualizado.',
                    'data'    => [ 
                        'updated_budget_id'               => $updated_budget_id,
                        'updated_budget'                  => $updated_budget,
                        'created_user_confirmation_token' => $createdUserConfirmationToken,
                        'token'                           => $createdUserConfirmationToken[ 'token' ],
                        'send_email_notification'         => $sendEmailNotification,
                    ],
                ];
            } );

        } catch ( Exception $e ) {
            throw new RuntimeException( "Falha ao atualizar o status do orçamento, tente novamente mais tarde ou entre em contato com suporte!", 0, $e );
        }

    }

}
