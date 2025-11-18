<?php

declare(strict_types=1);

namespace App\Services\Domain;

use App\Enums\InvoiceStatus;
use App\Enums\OperationStatus;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\Service;
use App\Models\Customer;
use App\Models\ServiceItem;
use App\Repositories\InvoiceRepository;
use App\Services\Core\Abstracts\AbstractBaseService;
use App\Services\NotificationService;
use App\Support\ServiceResult;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class InvoiceService extends AbstractBaseService
{
    private InvoiceRepository $invoiceRepository;
    private NotificationService $notificationService;

    public function __construct( InvoiceRepository $invoiceRepository, NotificationService $notificationService )
    {
        $this->invoiceRepository = $invoiceRepository;
        $this->notificationService = $notificationService;
    }

    public function findByCode( string $code, array $with = [] ): ServiceResult
    {
        try {
            $query = Invoice::where( 'code', $code );

            if ( !empty( $with ) ) {
                $query->with( $with );
            }

            $invoice = $query->first();

            if ( !$invoice ) {
                return $this->error(
                    OperationStatus::NOT_FOUND,
                    "Fatura com código {$code} não encontrada",
                );
            }

            return $this->success( $invoice, 'Fatura encontrada' );

        } catch ( Exception $e ) {
            return $this->error(
                OperationStatus::ERROR,
                'Erro ao buscar fatura',
                null,
                $e,
            );
        }
    }

    public function getFilteredInvoices( array $filters = [], array $with = [] ): ServiceResult
    {
        try {
            $invoices = $this->invoiceRepository->getFiltered( $filters, [ 'due_date' => 'desc' ], 15 );

            return $this->success( $invoices, 'Faturas filtradas' );

        } catch ( Exception $e ) {
            return $this->error(
                OperationStatus::ERROR,
                'Erro ao filtrar faturas',
                null,
                $e,
            );
        }
    }

    public function createInvoice( array $data ): ServiceResult
    {
        try {
            return DB::transaction( function () use ($data) {
                // Buscar serviço
                $service = Service::where( 'code', $data[ 'service_code' ] )->first();
                if ( !$service ) {
                    return $this->error(
                        OperationStatus::NOT_FOUND,
                        'Serviço não encontrado',
                    );
                }

                // Gerar código único
                $invoiceCode = $this->generateUniqueInvoiceCode( $service->code );

                // Calcular total da fatura
                $totalAmount = $this->calculateInvoiceTotal( $data[ 'items' ] );

                // Criar fatura
                $invoice = Invoice::create( [
                    'tenant_id'    => tenant()->id,
                    'service_id'   => $service->id,
                    'customer_id'  => $data[ 'customer_id' ],
                    'code'         => $invoiceCode,
                    'issue_date'   => $data[ 'issue_date' ],
                    'due_date'     => $data[ 'due_date' ],
                    'total_amount' => $totalAmount,
                    'status'       => $data[ 'status' ] ?? InvoiceStatus::PENDING->value,
                    'public_hash'  => bin2hex(random_bytes(32)),
                ] );

                $user = $this->authUser();
                if ($user) {
                    $tokenService = app(\App\Services\Application\UserConfirmationTokenService::class);
                    $tokenRes = $tokenService->createTokenWithGeneration($user, \App\Enums\TokenType::PAYMENT_VERIFICATION);
                    if ($tokenRes->isSuccess()) {
                        $tokenStr = (string)($tokenRes->getData()['token'] ?? '');
                        $tokenRecord = \App\Models\UserConfirmationToken::where('token', $tokenStr)->first();
                        if ($tokenRecord) {
                            $invoice->update(['user_confirmation_token_id' => $tokenRecord->id]);
                        }
                    }
                }

                // Criar itens da fatura
                if ( !empty( $data[ 'items' ] ) ) {
                    $this->createInvoiceItems( $invoice, $data[ 'items' ] );
                }

                return $this->success( $invoice->load( [
                    'customer',
                    'service',
                    'invoiceItems.product',
                    'invoiceStatus'
                ] ), 'Fatura criada com sucesso' );

            } );

        } catch ( Exception $e ) {
            return $this->error(
                OperationStatus::ERROR,
                'Erro ao criar fatura',
                null,
                $e,
            );
        }
    }

    private function generateUniqueInvoiceCode( string $serviceCode ): string
    {
        $lastInvoice = Invoice::whereHas( 'service', function ( $query ) use ( $serviceCode ) {
            $query->where( 'code', $serviceCode );
        } )
            ->orderBy( 'code', 'desc' )
            ->first();

        $sequential = 1;
        if ( $lastInvoice && preg_match( '/-INV(\d{3})$/', $lastInvoice->code, $matches ) ) {
            $sequential = (int) $matches[ 1 ] + 1;
        }

        return "{$serviceCode}-INV" . str_pad( $sequential, 3, '0', STR_PAD_LEFT );
    }

    private function calculateInvoiceTotal( array $items ): float
    {
        $total = 0;
        foreach ( $items as $itemData ) {
            $q = (float) ( $itemData[ 'quantity' ] ?? 0 );
            $uv = (float) ( $itemData[ 'unit_value' ] ?? $itemData[ 'unit_price' ] ?? 0 );
            $total += $q * $uv;
        }
        return $total;
    }

    private function createInvoiceItems( Invoice $invoice, array $items ): void
    {
        foreach ( $items as $itemData ) {
            // Validar produto
            $product = Product::where( 'id', $itemData[ 'product_id' ] )
                ->where( 'active', true )
                ->first();

            if ( !$product ) {
                throw new Exception( "Produto ID {$itemData[ 'product_id' ]} não encontrado ou inativo" );
            }

            // Calcular total do item
            $quantity  = (float) $itemData[ 'quantity' ];
            $unitValue = (float) ( $itemData[ 'unit_value' ] ?? $itemData[ 'unit_price' ] );
            $total     = $quantity * $unitValue;

            // Criar item
            InvoiceItem::create( [
                'tenant_id'  => $invoice->tenant_id,
                'invoice_id' => $invoice->id,
                'product_id' => $product->id,
                'unit_price' => $unitValue,
                'quantity'   => $quantity,
                'total'      => $total
            ] );
        }
    }

    public function createPartialInvoiceFromBudget( string $budgetCode, array $data ): ServiceResult
    {
        try {
            return DB::transaction( function () use ($budgetCode, $data) {
                $budget = \App\Models\Budget::where('code', $budgetCode)
                    ->with(['services.serviceItems', 'customer'])
                    ->first();

                if (!$budget) {
                    return $this->error(OperationStatus::NOT_FOUND, 'Orçamento não encontrado');
                }

                $service = \App\Models\Service::where('id', $data['service_id'] ?? null)
                    ->where('budget_id', $budget->id)
                    ->first();

                if (!$service) {
                    return $this->error(OperationStatus::VALIDATION_ERROR, 'Serviço inválido para o orçamento');
                }

                $selectedItems = $data['items'] ?? [];
                if (empty($selectedItems)) {
                    return $this->error(OperationStatus::VALIDATION_ERROR, 'Selecione ao menos um item');
                }

                $subtotal = 0.0;
                $preparedItems = [];
                foreach ($selectedItems as $item) {
                    $serviceItem = \App\Models\ServiceItem::where('id', $item['service_item_id'] ?? 0)
                        ->where('service_id', $service->id)
                        ->first();
                    if (!$serviceItem) {
                        return $this->error(OperationStatus::VALIDATION_ERROR, 'Item de serviço inválido');
                    }
                    $quantity = (float) ($item['quantity'] ?? $serviceItem->quantity);
                    $unit = (float) ($item['unit_value'] ?? $serviceItem->unit_value);
                    $subtotal += $quantity * $unit;
                    $preparedItems[] = [
                        'product_id' => $serviceItem->product_id,
                        'quantity' => $quantity,
                        'unit_value' => $unit,
                    ];
                }

                $alreadyBilled = $this->invoiceRepository->sumTotalByBudgetId($budget->id, ['pending','approved','in_process','authorized']);
                $budgetTotal = (float) ($budget->total ?? 0);
                $remaining = max(0.0, $budgetTotal - $alreadyBilled);

                if ($subtotal > $remaining) {
                    return $this->error(OperationStatus::VALIDATION_ERROR, 'Total selecionado excede o saldo disponível do orçamento');
                }

                $invoiceCode = $this->generateUniqueInvoiceCode($service->code);

                $invoice = Invoice::create([
                    'tenant_id' => tenant()->id,
                    'service_id' => $service->id,
                    'customer_id' => $budget->customer_id,
                    'code' => $invoiceCode,
                    'due_date' => $data['due_date'] ?? now()->addDays(7),
                    'subtotal' => $subtotal,
                    'discount' => (float) ($data['discount'] ?? 0),
                    'total' => $subtotal - (float) ($data['discount'] ?? 0),
                    'status' => $data['status'] ?? InvoiceStatus::PENDING->value,
                    'public_hash' => bin2hex(random_bytes(32)),
                ]);

                $user = $this->authUser();
                if ($user) {
                    $tokenService = app(\App\Services\Application\UserConfirmationTokenService::class);
                    $tokenRes = $tokenService->createTokenWithGeneration($user, \App\Enums\TokenType::PAYMENT_VERIFICATION);
                    if ($tokenRes->isSuccess()) {
                        $tokenStr = (string)($tokenRes->getData()['token'] ?? '');
                        $tokenRecord = \App\Models\UserConfirmationToken::where('token', $tokenStr)->first();
                        if ($tokenRecord) {
                            $invoice->update(['user_confirmation_token_id' => $tokenRecord->id]);
                        }
                    }
                }

                $this->createInvoiceItems($invoice, $preparedItems);

                return $this->success($invoice->load(['invoiceItems.product','service.budget','customer']), 'Fatura parcial criada');
            });
        } catch (Exception $e) {
            return $this->error(OperationStatus::ERROR, 'Erro ao criar fatura parcial', null, $e);
        }
    }

    public function updateInvoiceByCode( string $code, array $data ): ServiceResult
    {
        try {
            return DB::transaction( function () use ($code, $data) {
                $invoice = Invoice::where( 'code', $code )->first();

                if ( !$invoice ) {
                    return $this->error(
                        OperationStatus::NOT_FOUND,
                        "Fatura {$code} não encontrada",
                    );
                }

                // Atualizar fatura
                $invoice->update( [
                    'customer_id' => $data[ 'customer_id' ] ?? $invoice->customer_id,
                    'issue_date'  => $data[ 'issue_date' ] ?? $invoice->issue_date,
                    'due_date'    => $data[ 'due_date' ] ?? $invoice->due_date,
                    'status'      => $data[ 'status' ] ?? $invoice->status,
                ] );

                // Gerenciar itens se fornecidos
                if ( isset( $data[ 'items' ] ) ) {
                    $this->updateInvoiceItems( $invoice, $data[ 'items' ] );
                }

                // Recalcular total da fatura após gerenciar itens
                $invoice->total_amount = $this->calculateInvoiceTotal( $invoice->invoiceItems->toArray() );
                $invoice->save();

                return $this->success( $invoice->fresh( [
                    'invoiceItems.product',
                    'invoiceStatus',
                    'customer',
                    'service'
                ] ), 'Fatura atualizada' );

            } );

        } catch ( Exception $e ) {
            return $this->error(
                OperationStatus::ERROR,
                'Erro ao atualizar fatura',
                null,
                $e,
            );
        }
    }

    private function updateInvoiceItems( Invoice $invoice, array $itemsData ): void
    {
        $existingItemIds = $invoice->invoiceItems->pluck( 'id' )->toArray();
        $itemsToKeep     = [];

        foreach ( $itemsData as $itemData ) {
            if ( isset( $itemData[ 'id' ] ) && in_array( $itemData[ 'id' ], $existingItemIds ) ) {
                // Atualizar item existente
                $item = $invoice->invoiceItems->firstWhere( 'id', $itemData[ 'id' ] );
                if ( $item ) {
                    if ( ( $itemData[ 'action' ] ?? 'update' ) === 'delete' ) {
                        $item->delete();
                    } else {
                        $item->update( [
                            'product_id' => $itemData[ 'product_id' ],
                            'quantity'   => $itemData[ 'quantity' ],
                            'unit_value' => $itemData[ 'unit_value' ],
                            'total'      => (float) $itemData[ 'quantity' ] * (float) $itemData[ 'unit_value' ]
                        ] );
                        $itemsToKeep[] = $item->id;
                    }
                }
            } elseif ( ( $itemData[ 'action' ] ?? 'create' ) === 'create' ) {
                // Criar novo item
                $product = Product::where( 'id', $itemData[ 'product_id' ] )
                    ->where( 'active', true )
                    ->first();

                if ( !$product ) {
                    throw new Exception( "Produto ID {$itemData[ 'product_id' ]} não encontrado ou inativo" );
                }

                $newItem       = InvoiceItem::create( [
                    'tenant_id'  => $invoice->tenant_id,
                    'invoice_id' => $invoice->id,
                    'product_id' => $product->id,
                    'unit_value' => (float) $itemData[ 'unit_value' ],
                    'quantity'   => (float) $itemData[ 'quantity' ],
                    'total'      => (float) $itemData[ 'quantity' ] * (float) $itemData[ 'unit_value' ]
                ] );
                $itemsToKeep[] = $newItem->id;
            }
        }

        // Deletar itens que não foram mantidos
        $invoice->invoiceItems()->whereNotIn( 'id', $itemsToKeep )->delete();
    }

    public function changeStatus( string $code, string $newStatus ): ServiceResult
    {
        try {
            return DB::transaction( function () use ($code, $newStatus) {
                $invoice = Invoice::where( 'code', $code )->first();

                if ( !$invoice ) {
                    return $this->error(
                        OperationStatus::NOT_FOUND,
                        "Fatura {$code} não encontrada",
                    );
                }

                $oldStatus = $invoice->status;

                // Validar transição
                $allowedTransitions = $oldStatus->getNextStatus()( $oldStatus );
                if ( !in_array( $newStatus, $allowedTransitions ) ) {
                    return $this->error(
                        OperationStatus::VALIDATION_ERROR,
                        "Transição de {$oldStatus} para {$newStatus} não permitida",
                    );
                }

                // Atualizar fatura
                $invoice->update( [ 'status' => $newStatus ] );

                return $this->success( $invoice, 'Status alterado com sucesso' );

            } );

        } catch ( Exception $e ) {
            return $this->error(
                OperationStatus::ERROR,
                'Erro ao alterar status',
                null,
                $e,
            );
        }
    }

    public function deleteByCode( string $code ): ServiceResult
    {
        try {
            return DB::transaction( function () use ($code) {
                $invoice = Invoice::where( 'code', $code )->first();

                if ( !$invoice ) {
                    return $this->error(
                        OperationStatus::NOT_FOUND,
                        "Fatura {$code} não encontrada",
                    );
                }

                // Não pode deletar se tiver pagamentos
                if ( $invoice->payments()->count() > 0 ) {
                    return $this->error(
                        OperationStatus::VALIDATION_ERROR,
                        'Fatura possui pagamentos e não pode ser excluída',
                    );
                }

                // Deletar itens da fatura
                $invoice->invoiceItems()->delete();

                // Deletar a fatura
                $invoice->delete();

                return $this->success( null, 'Fatura excluída com sucesso' );

            } );

        } catch ( Exception $e ) {
            return $this->error(
                OperationStatus::ERROR,
                'Erro ao excluir fatura',
                null,
                $e,
            );
        }
    }

    public function generateInvoicePdf( string $code ): ServiceResult
    {
        try {
            $invoiceResult = $this->findByCode( $code, [ 'customer', 'service', 'invoiceItems.product' ] );

            if ( !$invoiceResult->isSuccess() ) {
                return $invoiceResult;
            }

            $invoice = $invoiceResult->getData();

            $publicUrl = null;
            if (!empty($invoice->public_hash)) {
                $publicUrl = route('invoices.public.show', ['hash' => $invoice->public_hash]);
            }

            $qrDataUri = null;
            if ($publicUrl) {
                $qrService = app(\App\Services\Infrastructure\QrCodeService::class);
                $qrDataUri = $qrService->generateDataUri($publicUrl, 180);
            }

            $html = view('invoices.pdf', [
                'invoice' => $invoice,
                'publicUrl' => $publicUrl,
                'qrDataUri' => $qrDataUri,
            ])->render();

            $mpdf = new \Mpdf\Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'margin_left' => 15,
                'margin_right' => 15,
                'margin_top' => 16,
                'margin_bottom' => 16,
            ]);
            $mpdf->WriteHTML($html);
            $content = $mpdf->Output('', 'S');

            $dir = 'invoices';
            $filename = 'invoice_' . $invoice->code . '.pdf';
            $path = $dir . '/' . $filename;
            \Illuminate\Support\Facades\Storage::put($path, $content);

            return $this->success($path, 'PDF da fatura gerado com sucesso');

        } catch ( Exception $e ) {
            return $this->error(
                OperationStatus::ERROR,
                'Erro ao gerar PDF da fatura',
                null,
                $e,
            );
        }
    }

    public function searchInvoices( array $filters = [], int $perPage = 15 ): ServiceResult
    {
        try {
            $query = Invoice::query();

            // Aplicar filtros avançados
            if ( !empty( $filters[ 'status' ] ) ) {
                $query->where( 'status', $filters[ 'status' ] );
            }

            if ( !empty( $filters[ 'customer_id' ] ) ) {
                $query->where( 'customer_id', $filters[ 'customer_id' ] );
            }

            if ( !empty( $filters[ 'service_id' ] ) ) {
                $query->where( 'service_id', $filters[ 'service_id' ] );
            }

            if ( !empty( $filters[ 'date_from' ] ) ) {
                $query->whereDate( 'issue_date', '>=', $filters[ 'date_from' ] );
            }

            if ( !empty( $filters[ 'date_to' ] ) ) {
                $query->whereDate( 'issue_date', '<=', $filters[ 'date_to' ] );
            }

            if ( !empty( $filters[ 'due_date_from' ] ) ) {
                $query->whereDate( 'due_date', '>=', $filters[ 'due_date_from' ] );
            }

            if ( !empty( $filters[ 'due_date_to' ] ) ) {
                $query->whereDate( 'due_date', '<=', $filters[ 'due_date_to' ] );
            }

            if ( !empty( $filters[ 'min_amount' ] ) ) {
                $query->where( 'total_amount', '>=', $filters[ 'min_amount' ] );
            }

            if ( !empty( $filters[ 'max_amount' ] ) ) {
                $query->where( 'total_amount', '<=', $filters[ 'max_amount' ] );
            }

            if ( !empty( $filters[ 'search' ] ) ) {
                $query->where( function ( $q ) use ( $filters ) {
                    $q->where( 'code', 'like', '%' . $filters[ 'search' ] . '%' )
                        ->orWhereHas( 'customer', function ( $sq ) use ( $filters ) {
                            $sq->where( 'name', 'like', '%' . $filters[ 'search' ] . '%' );
                        } )
                        ->orWhereHas( 'service', function ( $sq ) use ( $filters ) {
                            $sq->where( 'description', 'like', '%' . $filters[ 'search' ] . '%' );
                        } );
                } );
            }

            // Ordenação
            $sortBy        = $filters[ 'sort_by' ] ?? 'issue_date';
            $sortDirection = $filters[ 'sort_direction' ] ?? 'desc';
            $query->orderBy( $sortBy, $sortDirection );

            // Paginação
            $invoices = $query->paginate( $perPage );

            return $this->success( $invoices, 'Busca avançada realizada' );

        } catch ( Exception $e ) {
            return $this->error(
                OperationStatus::ERROR,
                'Erro na busca avançada',
                null,
                $e,
            );
        }
    }

    public function exportInvoices( array $filters = [], string $format = 'xlsx' ): ServiceResult
    {
        try {
            $searchResult = $this->searchInvoices( $filters, 1000 ); // Máximo 1000 registros para export

            if ( !$searchResult->isSuccess() ) {
                return $searchResult;
            }

            $invoices = $searchResult->getData();

            if ( $format === 'xlsx' ) {
                // Implementar export para Excel
                $filename = 'invoices_' . now()->format( 'Y-m-d_H-i-s' ) . '.xlsx';
                $path     = $this->exportToExcel( $invoices, $filename );
            } elseif ( $format === 'csv' ) {
                // Implementar export para CSV
                $filename = 'invoices_' . now()->format( 'Y-m-d_H-i-s' ) . '.csv';
                $path     = $this->exportToCsv( $invoices, $filename );
            } else {
                return $this->error(
                    OperationStatus::VALIDATION_ERROR,
                    'Formato de exportação não suportado',
                );
            }

            return $this->success( $path, 'Exportação realizada com sucesso' );

        } catch ( Exception $e ) {
            return $this->error(
                OperationStatus::ERROR,
                'Erro na exportação',
                null,
                $e,
            );
        }
    }

    private function exportToExcel( $invoices, string $filename ): string
    {
        // Placeholder - implementar com biblioteca real (ex: Maatwebsite\Excel)
        $path = 'storage/exports/' . tenant()->id . '/' . $filename;
        // Implementar lógica de export para Excel
        return $path;
    }

    private function exportToCsv( $invoices, string $filename ): string
    {
        // Placeholder - implementar export para CSV
        $path = 'storage/exports/' . tenant()->id . '/' . $filename;
        // Implementar lógica de export para CSV
        return $path;
    }

    /**
     * Gera dados da fatura a partir de um serviço concluído (Lógica do Sistema Antigo)
     */
    public function generateInvoiceDataFromService(string $serviceCode): ServiceResult
    {
        try {
            $service = Service::where('code', $serviceCode)
                ->where('tenant_id', tenant()->id)
                ->first();

            if (!$service) {
                return $this->error(
                    OperationStatus::NOT_FOUND,
                    'Serviço não encontrado.',
                );
            }

            $customer = Customer::where('id', $service->customer_id)
                ->where('tenant_id', tenant()->id)
                ->first();

            if (!$customer) {
                return $this->error(
                    OperationStatus::NOT_FOUND,
                    'Cliente não encontrado.',
                );
            }

            $serviceItems = ServiceItem::where('service_id', $service->id)
                ->where('tenant_id', tenant()->id)
                ->get();

            $invoiceData = [
                'customer_name'    => $customer->name,
                'customer_details' => $customer,
                'service_id'       => $service->id,
                'service_code'     => $service->code,
                'service_description' => $service->description,
                'due_date'         => $service->due_date,
                'items'            => $serviceItems,
                'subtotal'         => (float) $service->total,
                'discount'         => (float) $service->discount,
                'total'            => (float) $service->total - (float) $service->discount,
                'status'           => $service->status->value, // 'completed' ou 'partial'
            ];

            // Lógica para desconto em serviços parciais (do sistema antigo)
            if ($service->status->value === 'partial') {
                $partialDiscountPercentage = 0.90; // 10% de desconto
                $invoiceData['discount'] += $invoiceData['total'] * (1 - $partialDiscountPercentage);
                $invoiceData['total'] *= $partialDiscountPercentage;
                $invoiceData['notes'] = "Fatura gerada com base na conclusão parcial do serviço. Valor ajustado.";
            }

            return $this->success($invoiceData, 'Dados da fatura gerados com sucesso');

        } catch (Exception $e) {
            return $this->error(
                OperationStatus::ERROR,
                'Falha ao gerar dados da fatura: ' . $e->getMessage(),
                null,
                $e,
            );
        }
    }

    /**
     * Cria fatura a partir de um serviço concluído com validações do sistema antigo
     */
    public function createInvoiceFromService(string $serviceCode, array $additionalData = []): ServiceResult
    {
        return DB::transaction(function () use ($serviceCode, $additionalData) {
            try {
                // Verificar se o serviço existe
                $service = Service::where('code', $serviceCode)
                    ->where('tenant_id', tenant()->id)
                    ->first();

                if (!$service) {
                    return $this->error(
                        OperationStatus::NOT_FOUND,
                        'Serviço de referência não encontrado para criar a fatura.',
                    );
                }

                // Verificar se já existe fatura para este serviço
                $existingInvoice = Invoice::where('tenant_id', tenant()->id)
                    ->where('service_id', $service->id)
                    ->first();

                if ($existingInvoice) {
                    return $this->error(
                        OperationStatus::VALIDATION_ERROR,
                        'Já existe uma fatura para este serviço.',
                    );
                }

                // Gerar dados da fatura
                $invoiceDataResult = $this->generateInvoiceDataFromService($serviceCode);
                if (!$invoiceDataResult->isSuccess()) {
                    return $invoiceDataResult;
                }

                $invoiceData = $invoiceDataResult->getData();

                // Gerar código único seguindo padrão antigo
                $lastCode = Invoice::where('tenant_id', tenant()->id)
                    ->where('code', 'like', 'FAT-' . date('Ymd') . '%')
                    ->orderBy('code', 'desc')
                    ->first();

                $sequential = 1;
                if ($lastCode && preg_match('/FAT-(\d{8})(\d{4})/', $lastCode->code, $matches)) {
                    $sequential = (int) $matches[2] + 1;
                }

                $invoiceCode = 'FAT-' . date('Ymd') . str_pad((string) $sequential, 4, '0', STR_PAD_LEFT);

                // Criar fatura
                $invoice = Invoice::create([
                    'tenant_id'     => tenant()->id,
                    'service_id'    => $service->id,
                    'customer_id'   => $service->customer_id,
                    'code'          => $invoiceCode,
                    'issue_date'    => $additionalData['issue_date'] ?? now(),
                    'due_date'      => $invoiceData['due_date'] ?? now()->addDays(30),
                    'total_amount'  => $invoiceData['total'],
                    'status'        => InvoiceStatus::PENDING->value,
                    'public_hash'   => bin2hex(random_bytes(32)), // 64 caracteres hexadecimais
                    'notes'         => $invoiceData['notes'] ?? null,
                    'is_automatic'  => $additionalData['is_automatic'] ?? false,
                ]);

                // Criar itens da fatura a partir dos itens do serviço
                foreach ($invoiceData['items'] as $serviceItem) {
                    InvoiceItem::create([
                        'tenant_id'  => tenant()->id,
                        'invoice_id' => $invoice->id,
                        'product_id' => $serviceItem->product_id,
                        'unit_price' => (float) $serviceItem->unit_value,
                        'quantity'   => (float) $serviceItem->quantity,
                        'total'      => (float) $serviceItem->total,
                    ]);
                }

                // Criar token de confirmação
                $user = $this->authUser();
                if ($user) {
                    $tokenService = app(\App\Services\Application\UserConfirmationTokenService::class);
                    $tokenRes = $tokenService->createTokenWithGeneration($user, \App\Enums\TokenType::PAYMENT_VERIFICATION);
                    if ($tokenRes->isSuccess()) {
                        $tokenStr = (string)($tokenRes->getData()['token'] ?? '');
                        $tokenRecord = \App\Models\UserConfirmationToken::where('token', $tokenStr)->first();
                        if ($tokenRecord) {
                            $invoice->update(['user_confirmation_token_id' => $tokenRecord->id]);
                        }
                    }
                }

                // Enviar notificação por email (se implementado)
                try {
                    $customer = Customer::find($service->customer_id);
                    if ($customer && $customer->email) {
                        // Aqui você pode implementar o envio de email
                        // $this->notificationService->sendNewInvoiceNotification($invoice, $customer);
                    }
                } catch (Exception $e) {
                    Log::warning('Falha ao enviar notificação de fatura', [
                        'invoice_id' => $invoice->id,
                        'error' => $e->getMessage()
                    ]);
                }

                return $this->success(
                    $invoice->load(['customer', 'service', 'invoiceItems.product']),
                    'Fatura gerada com sucesso a partir do serviço'
                );

            } catch (Exception $e) {
                return $this->error(
                    OperationStatus::ERROR,
                    'Erro ao criar fatura a partir do serviço: ' . $e->getMessage(),
                    null,
                    $e,
                );
            }
        });
    }

    /**
     * Verifica se existe fatura para um serviço específico
     */
    public function checkExistingInvoiceForService(int $serviceId): bool
    {
        return Invoice::where('tenant_id', tenant()->id)
            ->where('service_id', $serviceId)
            ->exists();
    }

}
