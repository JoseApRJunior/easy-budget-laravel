<?php

namespace app\database\services;

use app\database\entitiesORM\BudgetEntity;
use app\database\entitiesORM\ScheduleEntity;
use app\database\entitiesORM\ServiceEntity;
use app\database\entitiesORM\ServiceItemEntity;
use app\database\entitiesORM\UserConfirmationTokenEntity;
use app\database\models\Budget;
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

class ServiceService
{
    /**
     * Summary of table
     * @var string
     */

    protected string $tableServices = 'services';
    protected string $tableOrder_Items = 'order_items';
    private mixed $authenticated;

    public function __construct(
        private readonly Connection $connection,
        private Service $service,
        private ServiceStatuses $serviceStatuses,
        private ServiceItem $serviceItem,
        private Budget $budget,
        private BudgetService $budgetService,
        private Schedule $schedule,
        private NotificationService $notificationService,
        private SharedService $sharedService,
        private PdfService $pdfService,
        private Customer $customer,
    ) {
        if ( Session::has( 'auth' ) ) {
            $this->authenticated = Session::get( 'auth' );
        }

    }

    /**
     * Cria um novo serviço.
     *
     * @param array<string, mixed> $data Dados do serviço.
     * @return array<string, mixed> Resultado da operação.
     */
    public function createService( array $data ): array
    {
        try {
            return $this->connection->transactional( function () use ($data) {
                $result           = [];
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
                $properties[ 'service_statuses_id' ] = 1; // DRAFT (Rascunho)
                $properties[ 'total' ]               = array_sum( array_column( $items, 'total' ) );

                $serviceEntity = ServiceEntity::create( removeUnnecessaryIndexes(
                    $properties,
                    [ 'id', 'created_at', 'updated_at' ],
                    $data,
                ) );

                $result = $this->service->create( $serviceEntity );

                if ( $result[ 'status' ] === 'success' ) {
                    $createdServiceId = $result[ 'data' ][ 'id' ];
                    $createdService   = $serviceEntity;
                    // Fim da Sessão criar servico

                    // Sessão criar serviceItem
                    $service_id = $result[ 'data' ][ 'id' ];
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
     * @param array<string, mixed> $data Dados do serviço.
     * @return array<string, mixed> Resultado da operação.
     */
    public function updateService( array $data ): array
    {
        try {
            return $this->connection->transactional( function () use ($data) {

                $result         = [ 
                    'status'  => 'error',
                    'message' => 'Nenhuma alteração realizada.',
                ];
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

                $data[ 'total' ]       = array_sum( array_column( $data[ 'items' ], 'total' ) );
                $data[ 'category_id' ] = $data[ 'category' ];
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

                // Sessão atualizaçao de servico
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

                $originalData[ 'total' ] = $services[ 'total' ] ?? array_sum( array_column( $services, 'total' ) );
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
     * Manipula a mudança de status de um serviço.
     *
     * @param array<string, mixed> $data Dados da mudança de status.
     * @param object $authenticated Usuário autenticado.
     * @return array<string, mixed> Resultado da operação.
     */
    public function handleStatusChange( array $data, object $authenticated ): array
    {
        if ( $this->authenticated === null ) {
            $this->authenticated = $authenticated;
        }
        $result                 = [];
        $updated_budget         = [];
        $new_status_budget_name = '';
        $service_id             = (int) $data[ 'service_id' ];
        $current_status_name    = $data[ 'current_status_name' ] ?? null;
        $current_status_slug    = $data[ 'current_status_slug' ] ?? null;
        $new_status_slug        = $data[ 'action' ] ?? null;

        // Buscar o serviço pelo código (assegure-se de que o código é seguro)
        $service = $this->service->getServiceById( $service_id, $this->authenticated->tenant_id );
        // Verificar se o serviço existe
        if ( $service instanceof EntityNotFound ) {
            return [ 
                'status'  => 'error',
                'message' => 'Serviço não encontrado.',
            ];
        }
        $service = $service->toArray();

        if (
            $current_status_slug == 'CANCELLED' or
            $current_status_slug == 'NOT_PERFORMED' or
            $current_status_slug == 'EXPIRED'
        ) {
            $new_status_slug = 'DRAFT';
        }
        $newServiceStatuses = $this->serviceStatuses->getStatusBySlug( $new_status_slug );
        if ( $newServiceStatuses instanceof EntityNotFound ) {
            return [ 
                'status'  => 'error',
                'message' => 'Status do serviço não encontrado.',
            ];
        }

        $serviceItems = $this->serviceItem->getAllServiceItemsByIdService( $service_id, $this->authenticated->tenant_id );

        $newServiceStatusesToArray = $newServiceStatuses->toArray();
        // Lógica para determinar o novo status com base na ação
        switch ( $new_status_slug ) {
            case 'SCHEDULING':
                if ( $current_status_slug == 'PENDING' ) {
                    if ( empty( $serviceItems ) ) {
                        return [ 
                            'status'  => 'error',
                            'message' => 'Não é possível alterar o status do serviço sem items adicionados.',
                        ];
                    }
                    $result = $this->changeStatus( $service, $newServiceStatusesToArray );
                }

                break;
            case 'PREPARING':
                if ( $current_status_slug == 'SCHEDULED' ) {
                    if ( empty( $serviceItems ) ) {
                        return [ 
                            'status'  => 'error',
                            'message' => 'Não é possível alterar o status do serviço sem items adicionados.',
                        ];
                    }
                    $result = $this->changeStatus( $service, $newServiceStatusesToArray );
                }

                break;
            case 'IN_PROGRESS':
                if ( $current_status_slug == 'PREPARING' ) {
                    if ( empty( $serviceItems ) ) {
                        return [ 
                            'status'  => 'error',
                            'message' => 'Não é possível alterar o status do serviço serviço sem items adicionados.',
                        ];
                    }
                    $result = $this->changeStatus( $service, $newServiceStatusesToArray );
                    if ( $result[ 'status' ] === 'success' ) {
                        $service = (object) $result[ 'data' ][ 'updated_service' ];

                        $customer = $this->customer->getCustomerFullByServiceCode( $service->code, $this->authenticated->tenant_id );

                        if ( $customer instanceof EntityNotFound ) {
                            return [ 
                                'status'  => 'error',
                                'message' => 'Cliente não encontrado para notificação.',
                            ];
                        }

                        $emailSent = $this->notificationService->sendServiceStatusUpdate( $this->authenticated, $service, $newServiceStatuses, $customer, $result[ 'data' ][ 'token' ] );

                        if ( !$emailSent ) {
                            return [ 
                                'status'  => 'error',
                                'message' => 'Status alterado, mas falha ao enviar notificação por e-mail.',
                            ];
                        }
                    } else {
                        return [ 
                            'status'  => 'error',
                            'message' => 'Não foi possível iniciar o serviço.',
                        ];
                    }
                }

                break;
            case 'PARTIAL':
                if ( $current_status_slug == 'IN_PROGRESS' ) {
                    if ( empty( $serviceItems ) ) {
                        return [ 
                            'status'  => 'error',
                            'message' => 'Não é possível alterar o status do serviço sem items adicionados.',
                        ];
                    }
                    $result = $this->changeStatus( $service, $newServiceStatusesToArray );
                    if ( $result[ 'status' ] === 'success' ) {
                        $service = (object) $result[ 'data' ][ 'updated_service' ];

                        $customer = $this->customer->getCustomerFullByServiceCode( $service->code, $this->authenticated->tenant_id );

                        if ( $customer instanceof EntityNotFound ) {
                            return [ 
                                'status'  => 'error',
                                'message' => 'Cliente não encontrado para notificação.',
                            ];
                        }

                        $emailSent = $this->notificationService->sendServiceStatusUpdate( $this->authenticated, $service, $newServiceStatuses, $customer, $result[ 'data' ][ 'token' ] );

                        if ( !$emailSent ) {
                            return [ 
                                'status'  => 'error',
                                'message' => 'Status alterado, mas falha ao enviar notificação por e-mail.',
                            ];
                        }
                    } else {
                        return [ 
                            'status'  => 'error',
                            'message' => 'Não foi possível concluir o serviço parcial.',
                        ];
                    }
                }

                break;
            case 'COMPLETED':
                if ( $current_status_slug == 'IN_PROGRESS' ) {
                    if ( empty( $serviceItems ) ) {
                        return [ 
                            'status'  => 'error',
                            'message' => 'Não é possível alterar o status do serviço sem items adicionados.',
                        ];
                    }
                    $result = $this->changeStatus( $service, $newServiceStatusesToArray );
                    if ( $result[ 'status' ] === 'success' ) {
                        $service = (object) $result[ 'data' ][ 'updated_service' ];

                        $customer = $this->customer->getCustomerFullByServiceCode( $service->code, $this->authenticated->tenant_id );

                        if ( $customer instanceof EntityNotFound ) {
                            return [ 
                                'status'  => 'error',
                                'message' => 'Cliente não encontrado para notificação.',
                            ];
                        }

                        $emailSent = $this->notificationService->sendServiceStatusUpdate( $this->authenticated, $service, $newServiceStatuses, $customer, $result[ 'data' ][ 'token' ] );

                        if ( !$emailSent ) {
                            return [ 
                                'status'  => 'error',
                                'message' => 'Status alterado, mas falha ao enviar notificação por e-mail.',
                            ];
                        }
                    } else {
                        return [ 
                            'status'  => 'error',
                            'message' => 'Não foi possível concluir o serviço.',
                        ];
                    }
                }

                break;
            case 'ON_HOLD':
                if (
                    $current_status_slug == 'PENDING' or
                    $current_status_slug == 'SCHEDULING' or
                    $current_status_slug == 'SCHEDULED' or
                    $current_status_slug == 'PREPARING' or
                    $current_status_slug == 'IN_PROGRESS'

                ) {
                    if ( empty( $serviceItems ) ) {
                        return [ 
                            'status'  => 'error',
                            'message' => 'Não é possível alterar o status do serviço sem items adicionados.',
                        ];
                    }
                    $result = $this->changeStatus( $service, $newServiceStatusesToArray );
                    if ( $result[ 'status' ] === 'success' ) {
                        $service = (object) $result[ 'data' ][ 'updated_service' ];

                        $customer = $this->customer->getCustomerFullByServiceCode( $service->code, $this->authenticated->tenant_id );

                        if ( $customer instanceof EntityNotFound ) {
                            return [ 
                                'status'  => 'error',
                                'message' => 'Cliente não encontrado para notificação.',
                            ];
                        }

                        $emailSent = $this->notificationService->sendServiceStatusUpdate( $this->authenticated, $service, $newServiceStatuses, $customer, $result[ 'data' ][ 'token' ] );

                        if ( !$emailSent ) {
                            return [ 
                                'status'  => 'error',
                                'message' => 'Status alterado, mas falha ao enviar notificação por e-mail.',
                            ];
                        }
                    } else {
                        return [ 
                            'status'  => 'error',
                            'message' => 'Não foi possível colocar o serviço em espera.',
                        ];
                    }
                }

                break;
            case 'SCHEDULED':
                if (
                    $current_status_slug == 'SCHEDULING' or $current_status_slug == 'ON_HOLD'
                ) {
                    if ( empty( $serviceItems ) ) {
                        return [ 
                            'status'  => 'error',
                            'message' => 'Não é possível alterar o status do serviço sem items adicionados.',
                        ];
                    }

                    $result = $this->changeStatus( $service, $newServiceStatusesToArray, $data );
                    if ( $result[ 'status' ] === 'success' ) {
                        $service = (array) $this->service->getServiceFullById( $service[ 'id' ], $this->authenticated->tenant_id );
                        $service = (object) $result[ 'data' ][ 'updated_service' ];

                        $customer = $this->customer->getCustomerFullByServiceCode( $service->code, $this->authenticated->tenant_id );

                        if ( $customer instanceof EntityNotFound ) {
                            return [ 
                                'status'  => 'error',
                                'message' => 'Cliente não encontrado para notificação.',
                            ];
                        }

                        $emailSent = $this->notificationService->sendServiceStatusUpdate( $this->authenticated, $service, $newServiceStatuses, $customer, $result[ 'data' ][ 'token' ] );

                        if ( !$emailSent ) {
                            return [ 
                                'status'  => 'error',
                                'message' => 'Status alterado, mas falha ao enviar notificação por e-mail.',
                            ];
                        }
                    } else {
                        return [ 
                            'status'  => 'error',
                            'message' => 'Não foi possível agendar o serviço.',
                        ];
                    }
                }

                break;
            case 'NOT_PERFORMED':
                if (
                    $current_status_slug == 'ON_HOLD' or
                    $current_status_slug == 'SCHEDULING' or
                    $current_status_slug == 'SCHEDULED' or
                    $current_status_slug == 'PREPARING'
                ) {
                    if ( empty( $serviceItems ) ) {
                        return [ 
                            'status'  => 'error',
                            'message' => 'Não é possível alterar o status do serviço sem items adicionados.',
                        ];
                    }
                    $result = $this->changeStatus( $service, $newServiceStatusesToArray );
                    if ( $result[ 'status' ] === 'success' ) {
                        $service = (object) $result[ 'data' ][ 'updated_service' ];

                        $customer = $this->customer->getCustomerFullByServiceCode( $service->code, $this->authenticated->tenant_id );

                        if ( $customer instanceof EntityNotFound ) {
                            return [ 
                                'status'  => 'error',
                                'message' => 'Cliente não encontrado para notificação.',
                            ];
                        }

                        $emailSent = $this->notificationService->sendServiceStatusUpdate( $this->authenticated, $service, $newServiceStatuses, $customer, $result[ 'data' ][ 'token' ] );

                        if ( !$emailSent ) {
                            return [ 
                                'status'  => 'error',
                                'message' => 'Status alterado, mas falha ao enviar notificação por e-mail.',
                            ];
                        }
                    } else {
                        return [ 
                            'status'  => 'error',
                            'message' => 'Não foi possível alterar o status do serviço.',
                        ];
                    }
                }

                break;
            case 'DRAFT':
                if (
                    $current_status_slug == 'CANCELLED' or
                    $current_status_slug == 'NOT_PERFORMED' or
                    $current_status_slug == 'EXPIRED'
                ) {
                    if ( empty( $serviceItems ) ) {
                        return [ 
                            'status'  => 'error',
                            'message' => 'Não é possível alterar o status do serviço sem items adicionados.',
                        ];
                    }
                    $result = $this->budgetService->changeStatusBudget( $service[ 'budget_id' ], 'DRAFT' );
                    if ( $result[ 'status' ] === 'success' ) {
                        $updated_budget         = $result[ 'data' ][ 'updated_budget' ] ?? [];
                        $new_status_budget_name = $result[ 'data' ][ 'new_status_name' ] ?? '';
                        $result                 = $this->changeStatus( $service, $newServiceStatusesToArray );
                    } else {
                        return [ 
                            'status'  => 'error',
                            'message' => 'Não foi possível alterar para rascunho o serviço.',
                        ];
                    }
                }

                break;

            case 'CANCELLED':
                // Se o serviço já estava em andamento, o cancelamento deve ser tratado como uma conclusão parcial para faturamento.
                if ( $current_status_slug === 'IN_PROGRESS' ) {
                    $newServiceStatuses = $this->serviceStatuses->getStatusBySlug( 'PARTIAL' );
                    if ( $newServiceStatuses instanceof EntityNotFound ) {
                        return [ 'status' => 'error', 'message' => 'Status "Parcial" não encontrado no sistema.' ];
                    }
                    $newServiceStatusesToArray = $newServiceStatuses->toArray();
                    $result                    = $this->changeStatus( $service, $newServiceStatusesToArray );

                    if ( $result[ 'status' ] === 'success' ) {
                        $service = (object) $result[ 'data' ][ 'updated_service' ];

                        $customer = $this->customer->getCustomerFullByServiceCode( $service->code, $this->authenticated->tenant_id );

                        if ( $customer instanceof EntityNotFound ) {
                            return [ 
                                'status'  => 'error',
                                'message' => 'Cliente não encontrado para notificação.',
                            ];
                        }

                        $emailSent = $this->notificationService->sendServiceStatusUpdate( $this->authenticated, $service, $newServiceStatuses, $customer, $result[ 'data' ][ 'token' ] );

                        if ( !$emailSent ) {
                            return [ 
                                'status'  => 'error',
                                'message' => 'Status alterado, mas falha ao enviar notificação por e-mail.',
                            ];
                        }
                    } else {
                        return [ 
                            'status'  => 'error',
                            'message' => 'Não foi possível cancelar o serviço.',
                        ];
                    }
                } else {
                    // Lógica de cancelamento padrão para outros status (PENDING, SCHEDULED, etc.)
                    if (
                        !in_array( $current_status_slug, [ 'CANCELLED', 'COMPLETED', 'PARTIAL', 'NOT_PERFORMED', 'EXPIRED' ] )
                    ) {
                        $result = $this->changeStatus( $service, $newServiceStatusesToArray );
                        if ( $result[ 'status' ] === 'success' ) {
                            $service = (object) $result[ 'data' ][ 'updated_service' ];

                            $customer = $this->customer->getCustomerFullByServiceCode( $service->code, $this->authenticated->tenant_id );

                            if ( $customer instanceof EntityNotFound ) {
                                return [ 
                                    'status'  => 'error',
                                    'message' => 'Cliente não encontrado para notificação.',
                                ];
                            }

                            $emailSent = $this->notificationService->sendServiceStatusUpdate( $this->authenticated, $service, $newServiceStatuses, $customer, $result[ 'data' ][ 'token' ] );

                            if ( !$emailSent ) {
                                return [ 
                                    'status'  => 'error',
                                    'message' => 'Status alterado, mas falha ao enviar notificação por e-mail.',
                                ];
                            }
                        }
                    }
                }

                break;

            case 'EXPIRED':
                if ( $current_status_slug !== 'EXPIRED' and $current_status_slug !== 'COMPLETED' and $current_status_slug !== 'CANCELLED' ) {
                    if ( empty( $serviceItems ) ) {
                        return [ 
                            'status'  => 'error',
                            'message' => 'Não é possível alterar o status do serviço sem items adicionados.',
                        ];
                    }
                    $result = $this->changeStatus( $service, $newServiceStatusesToArray );
                    if ( $result[ 'status' ] === 'success' ) {
                        $service = (object) $result[ 'data' ][ 'updated_service' ];

                        $customer = $this->customer->getCustomerFullByServiceCode( $service->code, $this->authenticated->tenant_id );

                        if ( $customer instanceof EntityNotFound ) {
                            return [ 
                                'status'  => 'error',
                                'message' => 'Cliente não encontrado para notificação.',
                            ];
                        }

                        $emailSent = $this->notificationService->sendServiceStatusUpdate( $this->authenticated, $service, $newServiceStatuses, $customer, $result[ 'data' ][ 'token' ] );

                        if ( !$emailSent ) {
                            return [ 
                                'status'  => 'error',
                                'message' => 'Status alterado, mas falha ao enviar notificação por e-mail.',
                            ];
                        }
                    } else {
                        return [ 
                            'status'  => 'error',
                            'message' => 'Não foi possível alterar o status do serviço.',
                        ];
                    }
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
            'message' => $result[ 'status' ] === 'success' ? 'Status do serviço atualizado com sucesso.' : 'Nenhuma alteração realizada.',
            'data'    => [ 
                'old_status_name'        => $current_status_name ?? '',
                'new_status_name'        => $newServiceStatusesToArray[ 'name' ] ?? '',
                'new_status_budget_name' => $new_status_budget_name,
                'updated_budget'         => $updated_budget,
                'updated_service'        => $result[ 'data' ][ 'updated_service' ] ?? [],
            ],
        ];

    }

    /**
     * Altera o status de um serviço.
     *
     * @param array<string, mixed> $service Dados do serviço.
     * @param array<string, mixed> $newServiceStatuses Novo status do serviço.
     * @param array<string, mixed> $data Dados adicionais.
     * @return array<string, mixed> Resultado da operação.
     */
    public function changeStatus( array $service, array $newServiceStatuses, array $data = [] ): array
    {
        try {
            return $this->connection->transactional( function () use ($service, $newServiceStatuses, $data) {
                $result         = [];
                $updatedService = [];

                // Sessão atualizaçao de status de serviço
                $service[ 'due_date' ] = isset( $data[ 'new_due_date' ] ) ? convertToDateTime( $data[ 'new_due_date' ] ) : $service[ 'due_date' ];
                $serviceEntity         = ServiceEntity::create( removeUnnecessaryIndexes(
                    $service,
                    [ 'created_at', 'updated_at' ],
                    [ 'service_statuses_id' => $newServiceStatuses[ 'id' ] ], // id do novo status
                ) );
                $result                = $this->service->update( $serviceEntity );

                if ( $result[ 'status' ] === 'success' ) {
                    $updatedService = $serviceEntity;
                    switch ( $newServiceStatuses[ 'slug' ] ) {
                        case 'SCHEDULED':
                            $resultScheduled = $this->handleStatusScheduled( $data );
                            if ( $resultScheduled[ 'status' ] === 'success' ) {
                                $result[ 'data' ][ 'scheduled' ] = $resultScheduled[ 'data' ];
                            } else {
                                return [ 
                                    'status'  => 'error',
                                    'message' => $resultScheduled[ 'message' ],
                                ];
                            }

                            break;
                        case 'IN_PROGRESS':
                        case 'PARTIAL':
                        case 'ON_HOLD':
                        case 'COMPLETED':
                        case 'CANCELLED':

                            $schedule = $this->schedule->getLastSchedulingTokenByServiceId(
                                $service[ 'id' ],
                                $this->authenticated->tenant_id,
                            );
                            if ( $schedule instanceof EntityNotFound ) {
                                return [ 
                                    'status'  => 'error',
                                    'message' => 'Agendamento não encontrado.',
                                ];
                            }
                            $result[ 'data' ][ 'scheduled' ][ 'token' ] = $schedule->token;

                            break;
                    }
                    // Fim sessão atualizaçao de status de serviço
                }

                return [ 
                    'status'  => $result[ 'status' ] === 'success' ? 'success' : 'error',
                    'message' => $result[ 'status' ] === 'success' ? 'Status do serviço atualizado com sucesso.' : 'Nenhuma alteração realizada.',
                    'data'    => [ 
                        'updated_service' => $updatedService,
                        'scheduled'       => $result[ 'data' ][ 'scheduled' ] ?? [],
                        'token'           => $result[ 'data' ][ 'scheduled' ][ 'token' ] ?? [],
                    ],
                ];
            } );

        } catch ( Exception $e ) {
            throw new RuntimeException( "Falha ao atualizar o status do serviço, tente novamente mais tarde ou entre em contato com suporte!", 0, $e );
        }
    }

    /**
     * Manipula o status de agendamento de um serviço.
     *
     * @param array<string, mixed> $data Dados do agendamento.
     * @return array<string, mixed> Resultado da operação.
     */
    public function handleStatusScheduled( array $data ): array
    {
        try {
            return $this->connection->transactional( function () use ($data) {
                $result                       = [];
                $createdUserConfirmationToken = [];
                $createdSchedule              = [];
                $createdServiceId             = null;
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

                // Inicio da lógica de agendamento

                $properties                                 = getConstructorProperties( ScheduleEntity::class);
                $properties[ 'tenant_id' ]                  = $this->authenticated->tenant_id;
                $properties[ 'service_id' ]                 = (int) $data[ 'service_id' ];
                $properties[ 'user_confirmation_token_id' ] = $result[ 'data' ][ 'id' ];
                $data[ 'start_date_time' ]                  = convertToDateTime( $data[ 'start_date_time' ] );
                $data[ 'end_date_time' ]                    = convertToDateTime( $data[ 'new_due_date' ] );

                $scheduleEntity = ScheduleEntity::create( removeUnnecessaryIndexes(
                    $properties,
                    [ 'id', 'created_at', 'updated_at' ],
                    $data,
                ) );

                $result = $this->schedule->create( $scheduleEntity );
                if ( $result[ 'status' ] === 'success' ) {
                    $createdServiceId = $result[ 'data' ][ 'id' ];
                    $createdSchedule  = $result[ 'data' ];
                }

                // Fim da lógica de agendamento
                return [ 
                    'status'  => $result[ 'status' ] === 'success' ? 'success' : 'error',
                    'message' => $result[ 'status' ] === 'success' ? 'Serviço de agendamento atualizado com sucesso.' : 'Nenhum serviço foi agendado.',
                    'data'    => [ 
                        'created_schedule_id'             => $createdServiceId,
                        'created_schedule'                => $createdSchedule,
                        'created_user_confirmation_token' => $createdUserConfirmationToken,
                        'token'                           => $createdUserConfirmationToken[ 'token' ],
                    ],
                ];
            } );

        } catch ( Exception $e ) {
            throw new RuntimeException( "Falha ao atualizar o status do serviço, tente novamente mais tarde ou entre em contato com suporte!", 0, $e );
        }
    }

    /**
     * Manipula a atualização de token para status agendado.
     *
     * @param string $code Código do serviço.
     * @param UserConfirmationTokenEntity $userConfirmationTokenEntity Token de confirmação.
     * @param object $authenticated Usuário autenticado.
     * @return array<string, mixed> Resultado da operação.
     */
    public function handleTokenUpdateScheduledStatus( string $code, UserConfirmationTokenEntity $userConfirmationTokenEntity, object $authenticated ): array
    {
        if ( $this->authenticated === null ) {
            $this->authenticated = $authenticated;
        }

        try {
            return $this->connection->transactional( function () use ($code, $userConfirmationTokenEntity) {

                $result                       = [];
                $createdUserConfirmationToken = [];
                $updatedSchedule              = [];
                $updated_schedule_id          = null;

                $service = $this->service->getServiceFullByCode(
                    $code,
                    $this->authenticated->tenant_id,
                );
                if ( $service instanceof EntityNotFound ) {
                    return
                        [ 
                            'status'  => 'error',
                            'message' => 'Serviço não encontrado.',
                        ];
                }

                $latest_schedule = $this->schedule->getLatestByServiceId(
                    $service->id,
                    $this->authenticated->tenant_id,
                );

                if ( $latest_schedule instanceof EntityNotFound ) {
                    return [ 
                        'status'  => 'error',
                        'message' => 'Agendamento não encontrado.',
                    ];
                }

                $newServiceStatuses = $this->serviceStatuses->getStatusBySlug( $service->status_slug );
                if ( $newServiceStatuses instanceof EntityNotFound ) {
                    return [ 
                        'status'  => 'error',
                        'message' => 'Status do serviço não encontrado.',
                    ];
                }
                /** @var ScheduleEntity $latest_schedule                  */
                if ( $latest_schedule->user_confirmation_token_id !== $userConfirmationTokenEntity->id ) {
                    $userConfirmationToken = $this->sharedService->getUserConfirmationTokenById( $latest_schedule->user_confirmation_token_id, $this->authenticated->tenant_id );
                    if ( $userConfirmationToken instanceof EntityNotFound ) {
                        return [ 
                            'status'  => 'error',
                            'message' => 'Token de confirmação não encontrado.',
                        ];
                    }
                    /** @var UserConfirmationTokenEntity $userConfirmationToken */
                    if ( !hasTokenExpirate( $userConfirmationToken->expires_at ) ) {
                        return [ 
                            'status'  => 'error',
                            'message' => 'Um novo token, ja foi enviado no seu e-mail.',
                        ];
                    }
                }

                // Sessão criar userConfirmationToken

                // Criar UserConfirmationTokens e retorna o id do userConfirmationToken
                $result = $this->sharedService->generateNewUserConfirmationToken( $userConfirmationTokenEntity->user_id ?? $this->authenticated->user_id, $this->authenticated->tenant_id );
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
                $originalData                            = $latest_schedule->toArray();
                $newData                                 = $latest_schedule->toArray();
                $newData[ 'user_confirmation_token_id' ] = (int) $createdUserConfirmationToken[ 'id' ];

                // Popula ServiceEntity com os dados do formulário
                $scheduleEntity = ScheduleEntity::create( removeUnnecessaryIndexes(
                    $originalData,
                    [ 'created_at', 'updated_at' ],
                    $newData,
                ) );

                // Verificar se os dados foram alterados
                if ( !compareObjects( $originalData, $newData, [ 'created_at', 'updated_at' ] ) ) {
                    // Atualizar ScheduleEntity
                    $result = $this->schedule->update( $scheduleEntity );

                    // Verificar se o agendamento de servico foi atualizado com sucesso
                    if ( $result[ 'status' ] === 'success' ) {
                        $updatedSchedule = $result[ 'data' ];

                        $customer = $this->customer->getCustomerFullByServiceCode( $service->code, $this->authenticated->tenant_id );

                        if ( $customer instanceof EntityNotFound ) {
                            return [ 
                                'status'  => 'error',
                                'message' => 'Cliente não encontrado para notificação.',
                            ];
                        }

                        $emailSent = $this->notificationService->sendServiceStatusUpdate( $this->authenticated, $service, $newServiceStatuses, $customer, $createdUserConfirmationToken[ 'token' ] );

                        if ( !$emailSent ) {
                            return [ 
                                'status'  => 'error',
                                'message' => 'Status alterado, mas falha ao enviar notificação por e-mail.',
                            ];
                        }
                    } else {
                        return [ 
                            'status'  => 'error',
                            'message' => 'Não foi possível alterar o status do serviço.',
                        ];
                    }

                }
                // Fim sessão atualizaçao de agendamento de servico

                return [ 
                    'status'  => $result[ 'status' ] === 'success' ? 'success' : 'error',
                    'message' => $result[ 'status' ] === 'success' ? 'Serviço de agendamento atualizado com sucesso.' : 'Nenhum serviço foi agendado.',
                    'data'    => [ 
                        'updated_schedule_id'             => $updated_schedule_id,
                        'updated_schedule'                => $updatedSchedule,
                        'created_user_confirmation_token' => $createdUserConfirmationToken,
                        'token'                           => $createdUserConfirmationToken[ 'token' ],
                    ],
                ];
            } );

        } catch ( Exception $e ) {
            throw new RuntimeException( "Falha ao atualizar o status do serviço, tente novamente mais tarde ou entre em contato com suporte!", 0, $e );
        }

    }

    /**
     * Gera PDF do serviço.
     *
     * @param object $authenticated Usuário autenticado.
     * @param object $customer Cliente.
     * @param object $budget Orçamento.
     * @param object $service Serviço.
     * @param array<int, array<string, mixed>> $service_items Itens do serviço.
     * @param object $latest_schedule Último agendamento.
     * @return array<string, mixed> Resultado da geração do PDF.
     */
    public function printPDF( object $authenticated, object $customer, object $budget, object $service, array $service_items, object $latest_schedule ): array
    {

        // Lógica de verificação do Hash
        $verificationHash = $service->pdf_verification_hash;
        if ( empty( $verificationHash ) ) {
            $verificationHash               = bin2hex( random_bytes( 20 ) );
            $service->pdf_verification_hash = $verificationHash;
            // Atualiza o serviço no banco com o novo hash

            $properties    = getConstructorProperties( ServiceEntity::class);
            $serviceEntity = ServiceEntity::create( removeUnnecessaryIndexes(
                $properties,
                [ 'created_at', 'updated_at' ],
                (array) $service,
            ) );

            $result = $this->service->update( $serviceEntity );
            if ( $result[ 'status' ] !== 'success' ) {
                return [ 
                    'status'  => 'error',
                    'message' => 'Erro ao atualizar o serviço com o novo hash de verificação.',
                ];
            }
        }

        return $this->pdfService->generateServicePdf( $authenticated, $customer, $budget, $service, $service_items, $latest_schedule, $verificationHash );
    }

}
