<?php

namespace app\database\services;

use app\database\entitiesORM\InvoiceEntity;
use app\database\entitiesORM\InvoiceStatusesEntity;
use app\database\models\Customer;
use app\database\models\Invoice;
use app\database\models\InvoiceStatuses;
use app\database\models\Service;
use app\database\models\ServiceItem;
use app\enums\InvoiceStatusEnum;
use core\dbal\EntityNotFound;
use core\library\Session;
use Doctrine\DBAL\Connection;
use Exception;
use RuntimeException;
use app\support\ServiceResult;
use app\enums\OperationStatus;

class InvoiceService
{
    private ?object $authenticated = null;

    public function __construct(
        private Service $service,
        private Customer $customer,
        private ServiceItem $serviceItem,
        private Invoice $invoiceModel,
        private InvoiceStatuses $invoiceStatuses,
        private readonly Connection $connection,
    ) {
        if ( Session::has( 'auth' ) ) {
            $this->authenticated = Session::get( 'auth' );
        }
    }

    /**
     * Gera dados da fatura a partir de um serviço.
     *
     * @param string $serviceCode Código do serviço
     * @return array<string, mixed> Dados da fatura gerada
     */
    public function generateInvoiceDataFromService( string $serviceCode ): array
    {
        try {
            $service = $this->service->getServiceFullByCode( $serviceCode, $this->authenticated->tenant_id );
            if ( $service instanceof EntityNotFound ) {
                return [ 
                    'status'  => 'error',
                    'message' => 'Serviço não encontrado.',
                ];
            }

            $customer = $this->customer->getCustomerFullById( $service->customer_id, $this->authenticated->tenant_id );
            if ( $customer instanceof EntityNotFound ) {
                return [ 
                    'status'  => 'error',
                    'message' => 'Cliente não encontrado.',
                ];
            }

            $serviceItems = $this->serviceItem->getAllServiceItemsByIdService( $service->id, $this->authenticated->tenant_id );

            $invoiceData = [ 
                'customer_name'    => "{$customer->first_name} {$customer->last_name}",
                'customer_details' => $customer,
                'service_id'       => $service->id,
                'service_code'     => $service->code,
                'service_desc'     => $service->description,
                'due_date'         => $service->due_date,
                'items'            => $serviceItems,
                'subtotal'         => $service->total,
                'discount'         => $service->discount,
                'total'            => $service->total - $service->discount,
                'status'           => $service->status_slug, // 'COMPLETED' ou 'PARTIAL'
            ];

            // Lógica para desconto em serviços parciais
            if ( $service->status_slug === 'PARTIAL' ) {
                // Aqui você pode definir uma regra de negócio.
                // Exemplo: aplicar um desconto de 0% sobre o valor líquido se for parcial.
                $partialDiscountPercentage = 0.90;
                $invoiceData[ 'discount' ] += $invoiceData[ 'total' ] * ( 1 - $partialDiscountPercentage );
                $invoiceData[ 'total' ] *= $partialDiscountPercentage;
                $invoiceData[ 'notes' ]    = "Fatura gerada com base na conclusão parcial do serviço. Valor ajustado.";
            }

            return [ 
                'status' => 'success',
                'data'   => $invoiceData,
            ];
        } catch ( Exception $e ) {
            throw new RuntimeException( "Falha ao gerar dados da fatura: " . $e->getMessage(), 0, $e );
        }
    }

    /**
     * Armazena uma nova fatura.
     *
     * @param array<string, mixed> $data Dados da fatura
     * @return array<string, mixed> Resultado da operação
     */
    public function storeInvoice( array $data ): array
    {
        try {
            $service = $this->service->getServiceFullByCode( $data[ 'service_code' ], $this->authenticated->tenant_id );
            if ( $service instanceof EntityNotFound ) {
                return [ 
                    'status'  => 'error',
                    'message' => 'Serviço de referência não encontrado para criar a fatura.',
                ];
            }

            $invoiceExists = $this->invoiceModel->findBy( [ 
                'tenant_id'  => $this->authenticated->tenant_id,
                'service_id' => $service->id,
            ] );
            if ( !$invoiceExists instanceof EntityNotFound ) {
                return [ 
                    'status'  => 'error',
                    'message' => 'Já existe uma fatura para este serviço.',
                ];
            }

            $invoiceStatuses = $this->invoiceStatuses->getStatusBySlug( 'pending' );
            if ( $invoiceStatuses instanceof EntityNotFound ) {
                return [ 
                    'status'  => 'error',
                    'message' => 'Status de fatura "Pendente" não configurado no sistema.',
                ];
            }

            $properties                  = getConstructorProperties( InvoiceEntity::class);
            $properties[ 'tenant_id' ]   = $this->authenticated->tenant_id;
            $properties[ 'service_id' ]  = $service->id;
            $properties[ 'customer_id' ] = $service->customer_id;
            $last_code                   = $this->invoiceModel->getLastCode( $this->authenticated->tenant_id );
            $last_code                   = (float) ( substr( $last_code, -4 ) ) + 1;
            $invoiceCode                 = 'FAT-' . date( 'Ymd' ) . str_pad( (string) $last_code, 4, '0', STR_PAD_LEFT );
            $properties[ 'code' ]        = $invoiceCode;
            $properties[ 'public_hash' ] = bin2hex( random_bytes( 32 ) ); // Gera um hash seguro de 64 caracteres

            // Prepare data for InvoiceEntity
            /** @var InvoiceStatusesEntity $invoiceStatuses */
            $invoice_data = [ 
                'invoice_statuses_id' => $invoiceStatuses->id,
                'subtotal'            => $data[ 'invoice' ][ 'subtotal' ],
                'discount'            => $data[ 'invoice' ][ 'discount' ],
                'total'               => $data[ 'invoice' ][ 'total' ],
                'due_date'            => new \DateTime( $data[ 'invoice' ][ 'due_date' ] ),
                'notes'               => $data[ 'invoice' ][ 'notes' ] ?? null,
            ];

            // popula model CommonDataEntity
            $entity = InvoiceEntity::create( removeUnnecessaryIndexes(
                $properties,
                [ 'id', 'created_at', 'updated_at' ],
                $invoice_data,
            ) );

            $result = $this->invoiceModel->create( $entity );

            return [ 
                'status'  => $result[ 'status' ],
                'message' => $result[ 'status' ] === 'success' ? 'Fatura gerada com sucesso.' : 'Falha ao gerar a fatura.',
                'data'    => ( $result[ 'status' ] === 'success' ) ? array_merge( $result[ 'data' ], [ 'code' => $invoiceCode, 'public_hash' => $properties[ 'public_hash' ] ] ) : [],
            ];
        } catch ( Exception $e ) {
            throw new RuntimeException( "Falha ao armazenar a fatura: " . $e->getMessage(), 0, $e );
        }
    }

    /**
     * Atualiza uma fatura com dados de pagamento.
     *
     * @param array<string, mixed> $payment Dados do pagamento
     * @return array<string, mixed> Resultado da operação
     */
    public function updateInvoice( array $payment ): array
    {
        try {
            return $this->connection->transactional( function () use ($payment) {
                $result         = [ 'status' => 'error', 'message' => '' ];
                $invoiceUpdated = [];

                $currentInvoice = $this->invoiceModel->findBy( [ 
                    'id'        => $payment[ 'invoice_id' ],
                    'tenant_id' => $payment[ 'tenant_id' ],
                ] );

                if ( $currentInvoice instanceof EntityNotFound ) {
                    return [ 
                        'status'  => 'error',
                        'message' => 'Fatura não encontrada.',
                    ];
                }

                $currentInvoice = $currentInvoice->toArray();

                if ( mapPaymentStatusToInvoiceStatus( $payment[ 'status' ] ) == InvoiceStatusEnum::paid ) {
                    $invoiceStatuses = $this->invoiceStatuses->getStatusBySlug( InvoiceStatusEnum::paid->value );
                    if ( $invoiceStatuses instanceof EntityNotFound ) {
                        return [ 
                            'status'  => 'error',
                            'message' => 'Status de fatura "Pendente" não configurado no sistema.',
                        ];
                    }

                    /** @var InvoiceStatusesEntity $invoiceStatuses  */
                    if (
                        $currentInvoice[ 'invoice_statuses_id' ] == $invoiceStatuses->id
                        && $currentInvoice[ 'payment_id' ] == $payment[ 'payment_id' ]
                        && $currentInvoice[ 'id' ] == $payment[ 'invoice_id' ]
                        && $currentInvoice[ 'payment_method' ] == $payment[ 'payment_method' ]
                    ) {
                        return [ 
                            'status'               => 'success',
                            'message'              => 'Pagamento já existe com o mesmo status.',
                            'data'                 => $currentInvoice,
                            'invoiceAlreadyExists' => true,
                        ];
                    }

                    $currentInvoice[ 'invoice_statuses_id' ] = $invoiceStatuses->id;
                    $data                                    = $payment;
                    unset( $data[ 'invoice_statuses_id' ] );
                    $entity = InvoiceEntity::create( removeUnnecessaryIndexes(
                        $currentInvoice,
                        [ 'created_at', 'updated_at' ],
                        $data,
                    ) );

                    $result = $this->invoiceModel->update( $entity );
                    if ( $result[ 'status' ] === 'error' ) {
                        return [ 
                            'status'  => 'error',
                            'message' => 'Falha ao atualizar a fatura, tente novamente mais tarde ou entre em contato com suporte!',
                        ];
                    }
                    $invoiceUpdated = $result[ 'data' ];
                }

                if ( mapPaymentStatusToInvoiceStatus( $payment[ 'status' ] ) == InvoiceStatusEnum::pending ) {
                    $invoiceStatuses = $this->invoiceStatuses->getStatusBySlug( InvoiceStatusEnum::pending->value );
                    if ( $invoiceStatuses instanceof EntityNotFound ) {
                        return [ 
                            'status'  => 'error',
                            'message' => 'Status de fatura "Pendente" não configurado no sistema.',
                        ];
                    }

                    /** @var InvoiceStatusesEntity $invoiceStatuses */
                    if (
                        $currentInvoice[ 'invoice_statuses_id' ] == $invoiceStatuses->id
                        && $currentInvoice[ 'payment_id' ] == $payment[ 'payment_id' ]
                        && $currentInvoice[ 'id' ] == $payment[ 'invoice_id' ]
                        && $currentInvoice[ 'payment_method' ] == $payment[ 'payment_method' ]
                    ) {
                        return [ 
                            'status'               => 'success',
                            'message'              => 'Pagamento já existe com o mesmo status.',
                            'data'                 => $currentInvoice,
                            'invoiceAlreadyExists' => true,
                        ];
                    }

                    $currentInvoice[ 'invoice_statuses_id' ] = $invoiceStatuses->id;
                    $data                                    = $payment;
                    unset( $data[ 'invoice_statuses_id' ] );
                    $invoiceEntity = InvoiceEntity::create( removeUnnecessaryIndexes(
                        $currentInvoice,
                        [ 'created_at', 'updated_at' ],
                        $data,
                    ) );

                    $result = $this->invoiceModel->update( $invoiceEntity );
                    if ( $result[ 'status' ] === 'error' ) {
                        return [ 
                            'status'  => 'error',
                            'message' => 'Falha ao atualizar a fatura, tente novamente mais tarde ou entre em contato com suporte!',
                        ];
                    }
                    $invoiceUpdated = $result[ 'data' ];

                }

                return [ 
                    'status'  => $result[ 'status' ] === 'success' ? 'success' : 'error',
                    'message' => $result[ 'status' ] === 'success' ? 'Fatura atualizada com sucesso.' : $result[ 'message' ],
                    'data'    => $invoiceUpdated,
                ];

            } );
        } catch ( Exception $e ) {
            throw new RuntimeException( "Falha ao atualizar a fatura, tente novamente mais tarde ou entre em contato com suporte!", 0, $e );
        }
    }

    /**
     * Busca uma fatura por ID considerando o tenant_id
     * 
     * @param int $id ID da fatura
     * @param int $tenant_id ID do tenant
     * @return array Resultado da operação
     */
    public function getById( int $id, int $tenant_id ): array
    {
        try {
            $invoice = $this->invoiceModel->getById( $id, $tenant_id );
            
            if ( $invoice instanceof EntityNotFound ) {
                return [ 
                    'status'  => 'error',
                    'message' => 'Fatura não encontrada.',
                    'data'    => [ 
                        'entity' => null,
                    ],
                ];
            }
            
            return [ 
                'status'  => 'success',
                'message' => 'Fatura encontrada com sucesso.',
                'data'    => [ 
                    'entity' => $invoice,
                ],
            ];
        } catch ( Exception $e ) {
            return [ 
                'status'  => 'error',
                'message' => 'Falha ao buscar fatura.',
                'data'    => [ 
                    'entity' => null,
                    'error'  => $e->getMessage(),
                ],
            ];
        }
    }

    /**
     * Lista faturas considerando o tenant_id
     * 
     * @param int $tenant_id ID do tenant
     * @param array $filters Filtros opcionais
     * @return array Resultado da operação
     */
    public function list( int $tenant_id, array $filters = [] ): array
    {
        try {
            $invoices = $this->invoiceModel->list( $tenant_id, $filters );
            
            return [ 
                'status'  => 'success',
                'message' => 'Faturas listadas com sucesso.',
                'data'    => [ 
                    'entities' => $invoices,
                ],
            ];
        } catch ( Exception $e ) {
            return [ 
                'status'  => 'error',
                'message' => 'Falha ao listar faturas.',
                'data'    => [ 
                    'entities' => [],
                    'error'    => $e->getMessage(),
                ],
            ];
        }
    }

    /**
     * Cria uma nova fatura considerando o tenant_id
     * 
     * @param array $data Dados da fatura
     * @param int $tenant_id ID do tenant
     * @return array Resultado da operação
     */
    public function create( array $data, int $tenant_id ): array
    {
        try {
            // Validar dados
            $validation = $this->validate( $data, $tenant_id );
            if ( $validation[ 'status' ] === 'error' ) {
                return $validation;
            }
            
            // Adicionar tenant_id aos dados
            $data[ 'tenant_id' ] = $tenant_id;
            
            // Criar entidade
            $entity = InvoiceEntity::create( $data );
            
            // Salvar no banco
            $result = $this->invoiceModel->create( $entity );
            
            if ( $result[ 'status' ] === 'error' ) {
                return [ 
                    'status'  => 'error',
                    'message' => 'Falha ao criar fatura.',
                    'data'    => [ 
                        'entity' => null,
                        'error'  => $result[ 'message' ] ?? 'Erro desconhecido',
                    ],
                ];
            }
            
            return [ 
                'status'  => 'success',
                'message' => 'Fatura criada com sucesso.',
                'data'    => [ 
                    'entity' => $result[ 'data' ],
                ],
            ];
        } catch ( Exception $e ) {
            return [ 
                'status'  => 'error',
                'message' => 'Falha ao criar fatura.',
                'data'    => [ 
                    'entity' => null,
                    'error'  => $e->getMessage(),
                ],
            ];
        }
    }

    /**
     * Atualiza uma fatura considerando o tenant_id
     * 
     * @param int $id ID da fatura
     * @param array $data Dados para atualização
     * @param int $tenant_id ID do tenant
     * @return array Resultado da operação
     */
    public function update( int $id, array $data, int $tenant_id ): array
    {
        try {
            // Verificar se a fatura existe
            $existingInvoice = $this->getById( $id, $tenant_id );
            if ( $existingInvoice[ 'status' ] === 'error' ) {
                return $existingInvoice;
            }
            
            // Validar dados
            $validation = $this->validate( $data, $tenant_id );
            if ( $validation[ 'status' ] === 'error' ) {
                return $validation;
            }
            
            // Adicionar ID e tenant_id aos dados
            $data[ 'id' ] = $id;
            $data[ 'tenant_id' ] = $tenant_id;
            
            // Criar entidade
            $entity = InvoiceEntity::create( $data );
            
            // Atualizar no banco
            $result = $this->invoiceModel->update( $entity );
            
            if ( $result[ 'status' ] === 'error' ) {
                return [ 
                    'status'  => 'error',
                    'message' => 'Falha ao atualizar fatura.',
                    'data'    => [ 
                        'entity' => null,
                        'error'  => $result[ 'message' ] ?? 'Erro desconhecido',
                    ],
                ];
            }
            
            return [ 
                'status'  => 'success',
                'message' => 'Fatura atualizada com sucesso.',
                'data'    => [ 
                    'entity' => $result[ 'data' ],
                ],
            ];
        } catch ( Exception $e ) {
            return [ 
                'status'  => 'error',
                'message' => 'Falha ao atualizar fatura.',
                'data'    => [ 
                    'entity' => null,
                    'error'  => $e->getMessage(),
                ],
            ];
        }
    }

    /**
     * Valida os dados de entrada para operações de criação ou atualização.
     * 
     * @param array<string, mixed> $data Dados para validação
     * @param bool $isUpdate Indica se é uma operação de atualização
     * @return ServiceResult Resultado da validação
     */
    public function validate( array $data, bool $isUpdate = false ): ServiceResult
    {
        $errors = [];
        
        // Validar campos obrigatórios
        if ( empty( $data[ 'customer_id' ] ) ) {
            $errors[] = 'ID do cliente é obrigatório.';
        }
        
        if ( empty( $data[ 'service_id' ] ) ) {
            $errors[] = 'ID do serviço é obrigatório.';
        }
        
        if ( empty( $data[ 'invoice_statuses_id' ] ) ) {
            $errors[] = 'Status da fatura é obrigatório.';
        }
        
        // Validar campos numéricos
        if ( isset( $data[ 'customer_id' ] ) && !is_numeric( $data[ 'customer_id' ] ) ) {
            $errors[] = 'ID do cliente deve ser um número.';
        }
        
        if ( isset( $data[ 'service_id' ] ) && !is_numeric( $data[ 'service_id' ] ) ) {
            $errors[] = 'ID do serviço deve ser um número.';
        }
        
        if ( isset( $data[ 'invoice_statuses_id' ] ) && !is_numeric( $data[ 'invoice_statuses_id' ] ) ) {
            $errors[] = 'ID do status da fatura deve ser um número.';
        }
        
        if ( isset( $data[ 'total_amount' ] ) && !is_numeric( $data[ 'total_amount' ] ) ) {
            $errors[] = 'Valor total deve ser um número.';
        }
        
        // Validar valores positivos
        if ( isset( $data[ 'total_amount' ] ) && $data[ 'total_amount' ] < 0 ) {
            $errors[] = 'Valor total deve ser positivo.';
        }
        
        if ( !empty( $errors ) ) {
            return ServiceResult::error(
                OperationStatus::INVALID_DATA,
                'Dados inválidos: ' . implode( ', ', $errors )
            );
        }
        
        return ServiceResult::success( [], 'Dados válidos.' );
    }

}
