<?php

declare(strict_types=1);

namespace app\database\servicesORM;

use app\database\entitiesORM\CustomerEntity;
use app\database\entitiesORM\InvoiceEntity;
use app\database\entitiesORM\InvoiceStatusesEntity;
use app\database\entitiesORM\ServiceEntity;
use app\database\repositories\CustomerRepository;
use app\database\repositories\InvoiceRepository;
use app\database\repositories\InvoiceStatusesRepository;
use app\database\repositories\ServiceItemRepository;
use app\database\repositories\ServiceRepository;
use app\enums\InvoiceStatusEnum;
use app\enums\OperationStatus;
use app\interfaces\ServiceInterface;
use app\support\ServiceResult;
use core\dbal\EntityNotFound;
use core\library\Session;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use RuntimeException;

/**
 * Serviço para gerenciamento de faturas.
 *
 * Implementa o padrão ServiceInterface para operações CRUD de faturas, incluindo
 * geração de dados a partir de serviços, armazenamento, atualização com pagamentos,
 * mapeamento de status. Usa transações Doctrine e integrações com Repositories.
 */
class InvoiceService implements ServiceInterface
{
    private mixed $authenticated = null;

    public function __construct(
        private InvoiceRepository $invoiceRepository,
        private CustomerRepository $customerRepository,
        private ServiceRepository $serviceRepository,
        private ServiceItemRepository $serviceItemRepository,
        private InvoiceStatusesRepository $invoiceStatusesRepository,
        private EntityManagerInterface $entityManager,
    ) {
        if ( Session::has( 'auth' ) ) {
            $this->authenticated = Session::get( 'auth' );
        }
    }

    /**
     * Busca uma fatura pelo ID e tenant_id.
     *
     * @param int $id ID da fatura
     * @param int $tenant_id ID do tenant
     * @return ServiceResult Resultado da operação
     */
    public function getByIdAndTenantId( int $id, int $tenant_id ): ServiceResult
    {
        try {
            $invoice = $this->invoiceRepository->findByIdAndTenantId( $id, $tenant_id );

            if ( !$invoice ) {
                return ServiceResult::error( OperationStatus::NOT_FOUND, 'Fatura não encontrada.' );
            }

            return ServiceResult::success( $invoice, 'Fatura encontrada com sucesso.' );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Erro ao buscar fatura: ' . $e->getMessage() );
        }
    }

    /**
     * Lista faturas por tenant_id com filtros opcionais.
     *
     * @param int $tenant_id ID do tenant
     * @param array<string, mixed> $filters Filtros opcionais
     * @return ServiceResult Resultado da operação
     */
    public function listByTenantId( int $tenant_id, array $filters = [] ): ServiceResult
    {
        try {
            $criteria = [];
            $orderBy  = [ 'createdAt' => 'DESC' ];
            $limit    = $filters[ 'limit' ] ?? null;
            $offset   = $filters[ 'offset' ] ?? null;

            $invoices = $this->invoiceRepository->findAllByTenantId( $tenant_id, $criteria, $orderBy, $limit, $offset );

            return ServiceResult::success( $invoices, 'Faturas listadas com sucesso.' );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Erro ao listar faturas: ' . $e->getMessage() );
        }
    }

    /**
     * Gera dados da fatura a partir de um serviço.
     *
     * @param string $serviceCode Código do serviço
     * @param int $tenant_id ID do tenant
     * @return ServiceResult Dados da fatura ou erro
     */
    public function generateInvoiceDataFromService( string $serviceCode, int $tenant_id ): ServiceResult
    {
        try {
            $service = $this->serviceRepository->getServiceFullByCode( $serviceCode, $tenant_id );

            if ( $service instanceof EntityNotFound ) {
                return ServiceResult::error( OperationStatus::NOT_FOUND, 'Serviço não encontrado.' );
            }

            $customer = $this->customerRepository->getCustomerFullbyId( $service->getCustomerId(), $tenant_id );

            if ( $customer instanceof EntityNotFound ) {
                return ServiceResult::error( OperationStatus::NOT_FOUND, 'Cliente não encontrado.' );
            }

            $serviceItems = $this->serviceItemRepository->getAllServiceItemsByIdService( $service->getId(), $tenant_id );

            $invoiceData = [ 
                'customer_name'    => $customer->getFirstName() . ' ' . $customer->getLastName(),
                'customer_details' => $customer,
                'service_id'       => $service->getId(),
                'service_code'     => $service->getCode(),
                'service_desc'     => $service->getDescription(),
                'due_date'         => $service->getDueDate(),
                'items'            => $serviceItems,
                'subtotal'         => $service->getTotal(),
                'discount'         => $service->getDiscount(),
                'total'            => $service->getTotal() - $service->getDiscount(),
                'status'           => $service->getStatusSlug(), // 'COMPLETED' ou 'PARTIAL'
            ];

            // Lógica para desconto em serviços parciais
            if ( $service->getStatusSlug() === 'PARTIAL' ) {
                $partialDiscountPercentage = 0.90;
                $invoiceData[ 'discount' ] += $invoiceData[ 'total' ] * ( 1 - $partialDiscountPercentage );
                $invoiceData[ 'total' ] *= $partialDiscountPercentage;
                $invoiceData[ 'notes' ]      = "Fatura gerada com base na conclusão parcial do serviço. Valor ajustado.";
            }

            return ServiceResult::success( $invoiceData, 'Dados da fatura gerados com sucesso.' );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Falha ao gerar dados da fatura: ' . $e->getMessage() );
        }
    }

    /**
     * Armazena uma nova fatura.
     *
     * @param array<string, mixed> $data Dados da fatura
     * @param int $tenant_id ID do tenant
     * @return ServiceResult Resultado da operação
     */
    public function createByTenantId( array $data, int $tenant_id ): ServiceResult
    {
        $validation = $this->validate( $data );
        if ( !$validation->isSuccess() ) {
            return $validation;
        }

        try {
            return $this->entityManager->transactional( function () use ($data, $tenant_id) {
                $service = $this->serviceRepository->getServiceFullByCode( $data[ 'service_code' ], $tenant_id );

                if ( $service instanceof EntityNotFound ) {
                    return ServiceResult::error( OperationStatus::NOT_FOUND, 'Serviço de referência não encontrado para criar a fatura.' );
                }

                // Verificar se já existe fatura para este serviço
                $existing = $this->invoiceRepository->findOneBy( [ 
                    'serviceId' => $service->getId(),
                    'tenantId'  => $tenant_id
                ] );

                if ( $existing ) {
                    return ServiceResult::error( OperationStatus::CONFLICT, 'Já existe uma fatura para este serviço.' );
                }

                $invoiceStatuses = $this->invoiceStatusesRepository->getStatusBySlug( 'pending' );

                if ( $invoiceStatuses instanceof EntityNotFound ) {
                    return ServiceResult::error( OperationStatus::NOT_FOUND, 'Status de fatura "Pendente" não configurado no sistema.' );
                }

                $lastCode    = $this->invoiceRepository->getLastCode( $tenant_id );
                $lastCodeNum = (float) substr( $lastCode, -4 ) + 1;
                $invoiceCode = 'FAT-' . date( 'Ymd' ) . str_pad( (string) $lastCodeNum, 4, '0', STR_PAD_LEFT );
                $publicHash  = bin2hex( random_bytes( 32 ) );

                $invoiceEntity = new InvoiceEntity();
                $invoiceEntity->setTenantId( $tenant_id );
                $invoiceEntity->setServiceId( $service->getId() );
                $invoiceEntity->setCustomerId( $service->getCustomerId() );
                $invoiceEntity->setCode( $invoiceCode );
                $invoiceEntity->setPublicHash( $publicHash );
                $invoiceEntity->setInvoiceStatusesId( $invoiceStatuses->getId() );
                $invoiceEntity->setSubtotal( $data[ 'invoice' ][ 'subtotal' ] ?? 0 );
                $invoiceEntity->setDiscount( $data[ 'invoice' ][ 'discount' ] ?? 0 );
                $invoiceEntity->setTotal( $data[ 'invoice' ][ 'total' ] ?? 0 );
                $invoiceEntity->setDueDate( new \DateTime( $data[ 'invoice' ][ 'due_date' ] ) );
                $invoiceEntity->setNotes( $data[ 'invoice' ][ 'notes' ] ?? null );
                $invoiceEntity->setCreatedAt( new \DateTimeImmutable() );
                $invoiceEntity->setUpdatedAt( new \DateTimeImmutable() );

                $result = $this->invoiceRepository->save( $invoiceEntity, $tenant_id );

                if ( $result->isError() ) {
                    return $result;
                }

                // Salvar items se houver (assumindo InvoiceItemEntity separada)
                // Para simplicidade, assumindo que items são salvos separadamente ou como JSON
                // Exemplo: foreach ($data['items'] as $item) { $itemEntity = new InvoiceItemEntity(); ... $this->invoiceItemRepository->save($itemEntity); }

                $data = [ 
                    'created_invoice_id' => $invoiceEntity->getId(),
                    'created_invoice'    => $invoiceEntity,
                    'code'               => $invoiceCode,
                    'public_hash'        => $publicHash
                ];

                return ServiceResult::success( $data, 'Fatura gerada com sucesso.' );
            } );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Falha ao armazenar a fatura: ' . $e->getMessage() );
        }
    }

    /**
     * Atualiza uma fatura com dados de pagamento.
     *
     * @param array<string, mixed> $payment Dados do pagamento
     * @param int $tenant_id ID do tenant
     * @return ServiceResult Resultado da operação
     */
    public function updateByIdAndTenantId( int $id, int $tenant_id, array $payment ): ServiceResult
    {
        $validation = $this->validate( $payment, true );
        if ( !$validation->isSuccess() ) {
            return $validation;
        }

        try {
            return $this->entityManager->transactional( function () use ($id, $tenant_id, $payment) {
                $currentInvoice = $this->invoiceRepository->findByIdAndTenantId( $id, $tenant_id );

                if ( $currentInvoice instanceof EntityNotFound ) {
                    return ServiceResult::error( OperationStatus::NOT_FOUND, 'Fatura não encontrada.' );
                }

                $currentInvoiceArray = $currentInvoice->toArray();

                $newStatusEnum = mapPaymentStatusToInvoiceStatus( $payment[ 'status' ] );

                $invoiceStatuses = $this->invoiceStatusesRepository->getStatusBySlug( $newStatusEnum->value );

                if ( $invoiceStatuses instanceof EntityNotFound ) {
                    return ServiceResult::error( OperationStatus::NOT_FOUND, 'Status de fatura não encontrado.' );
                }

                // Verificar se já existe com mesmo status
                if (
                    $currentInvoiceArray[ 'invoice_statuses_id' ] == $invoiceStatuses->getId() &&
                    $currentInvoiceArray[ 'payment_id' ] == $payment[ 'payment_id' ] &&
                    $currentInvoiceArray[ 'payment_method' ] == $payment[ 'payment_method' ]
                ) {
                    return ServiceResult::success( $currentInvoice, 'Pagamento já existe com o mesmo status.' );
                }

                $invoiceEntity = new InvoiceEntity();
                $invoiceEntity->setId( $currentInvoice->getId() );
                $invoiceEntity->setTenantId( $tenant_id );
                $invoiceEntity->setServiceId( $currentInvoice->getServiceId() );
                $invoiceEntity->setCustomerId( $currentInvoice->getCustomerId() );
                $invoiceEntity->setCode( $currentInvoice->getCode() );
                $invoiceEntity->setPublicHash( $currentInvoice->getPublicHash() );
                $invoiceEntity->setInvoiceStatusesId( $invoiceStatuses->getId() );
                $invoiceEntity->setSubtotal( $currentInvoice->getSubtotal() );
                $invoiceEntity->setDiscount( $currentInvoice->getDiscount() );
                $invoiceEntity->setTotal( $currentInvoice->getTotal() );
                $invoiceEntity->setDueDate( $currentInvoice->getDueDate() );
                $invoiceEntity->setNotes( $currentInvoice->getNotes() ?? null );

                // Atualizar campos de pagamento
                $invoiceEntity->setPaymentId( $payment[ 'payment_id' ] ?? null );
                $invoiceEntity->setPaymentMethod( $payment[ 'payment_method' ] ?? null );
                $invoiceEntity->setUpdatedAt( new \DateTimeImmutable() );

                $result = $this->invoiceRepository->save( $invoiceEntity, $tenant_id );

                if ( $result->isError() ) {
                    return $result;
                }

                return ServiceResult::success( $invoiceEntity, 'Fatura atualizada com sucesso.' );
            } );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Falha ao atualizar a fatura: ' . $e->getMessage() );
        }
    }

    /**
     * Remove uma fatura.
     *
     * @param int $id ID da fatura
     * @param int $tenant_id ID do tenant
     * @return ServiceResult Resultado da operação
     */
    public function deleteByIdAndTenantId( int $id, int $tenant_id ): ServiceResult
    {
        try {
            $invoice = $this->invoiceRepository->findByIdAndTenantId( $id, $tenant_id );

            if ( $invoice instanceof EntityNotFound ) {
                return ServiceResult::error( OperationStatus::NOT_FOUND, 'Fatura não encontrada.' );
            }

            $this->invoiceRepository->deleteByIdAndTenantId( $id, $tenant_id );

            return ServiceResult::success( null, 'Fatura removida com sucesso.' );
        } catch ( Exception $e ) {
            return ServiceResult::error( OperationStatus::ERROR, 'Falha ao remover fatura: ' . $e->getMessage() );
        }
    }

    /**
     * Valida dados para criação ou atualização de fatura.
     *
     * @param array<string, mixed> $data Dados a validar
     * @param bool $isUpdate Se é atualização
     * @return ServiceResult Resultado da validação
     */
    public function validate( array $data, bool $isUpdate = false ): ServiceResult
    {
        $errors = [];

        if ( empty( $data[ 'customer_id' ] ) ) $errors[] = 'ID do cliente é obrigatório.';
        if ( empty( $data[ 'service_id' ] ) ) $errors[] = 'ID do serviço é obrigatório.';
        if ( empty( $data[ 'invoice_statuses_id' ] ) ) $errors[] = 'Status da fatura é obrigatório.';

        // Validar numéricos
        if ( isset( $data[ 'customer_id' ] ) && !is_numeric( $data[ 'customer_id' ] ) ) $errors[] = 'ID do cliente deve ser numérico.';
        if ( isset( $data[ 'service_id' ] ) && !is_numeric( $data[ 'service_id' ] ) ) $errors[] = 'ID do serviço deve ser numérico.';
        if ( isset( $data[ 'invoice_statuses_id' ] ) && !is_numeric( $data[ 'invoice_statuses_id' ] ) ) $errors[] = 'ID do status deve ser numérico.';
        if ( isset( $data[ 'total' ] ) && !is_numeric( $data[ 'total' ] ) ) $errors[] = 'Total deve ser numérico.';
        if ( isset( $data[ 'total' ] ) && $data[ 'total' ] < 0 ) $errors[] = 'Total deve ser positivo.';

        if ( !empty( $errors ) ) {
            return ServiceResult::error( OperationStatus::INVALID_DATA, implode( ', ', $errors ) );
        }

        return ServiceResult::success( null, 'Dados válidos.' );
    }

}