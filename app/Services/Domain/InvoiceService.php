<?php

declare(strict_types=1);

namespace App\Services\Domain;

use App\DTOs\Invoice\InvoiceDTO;
use App\DTOs\Invoice\InvoiceFromBudgetDTO;
use App\DTOs\Invoice\InvoiceFromServiceDTO;
use App\DTOs\Invoice\InvoiceItemDTO;
use App\DTOs\Invoice\InvoiceUpdateDTO;
use App\Enums\InvoiceStatus;
use App\Repositories\BudgetRepository;
use App\Repositories\InvoiceItemRepository;
use App\Repositories\InvoiceRepository;
use App\Repositories\ProductRepository;
use App\Repositories\ServiceItemRepository;
use App\Repositories\ServiceRepository;
use App\Services\Core\Abstracts\AbstractBaseService;
use App\Services\NotificationService;
use App\Support\ServiceResult;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class InvoiceService extends AbstractBaseService
{
    public function __construct(
        InvoiceRepository $repository,
        private readonly InvoiceItemRepository $itemRepository,
        private readonly ServiceRepository $serviceRepository,
        private readonly BudgetRepository $budgetRepository,
        private readonly ProductRepository $productRepository,
        private readonly ServiceItemRepository $serviceItemRepository,
        private readonly InvoiceCodeGeneratorService $codeGenerator,
        private readonly NotificationService $notificationService,
        private readonly InvoiceShareService $invoiceShareService
    ) {
        parent::__construct($repository);
    }

    public function findByCode(string $code, array $with = []): ServiceResult
    {
        return $this->safeExecute(function () use ($code, $with) {
            $invoice = $this->repository->findByCode($code, $with);

            if (! $invoice) {
                return ServiceResult::error("Fatura com código {$code} não encontrada");
            }

            return ServiceResult::success($invoice);
        }, 'Erro ao buscar fatura');
    }

    public function getFilteredInvoices(array $filters = [], array $with = []): ServiceResult
    {
        return $this->safeExecute(function () use ($filters) {
            $invoices = $this->repository->getFiltered($filters, ['due_date' => 'desc'], 15);

            return ServiceResult::success($invoices);
        }, 'Erro ao filtrar faturas');
    }

    public function getDashboardStats(): ServiceResult
    {
        return $this->safeExecute(function () {
            $stats = $this->repository->getDashboardStats();

            return ServiceResult::success($stats);
        }, 'Erro ao obter estatísticas do dashboard');
    }

    public function createInvoice(InvoiceDTO $dto): ServiceResult
    {
        return $this->safeExecute(function () use ($dto) {
            return DB::transaction(function () use ($dto) {
                $service = $this->serviceRepository->find($dto->service_id);
                if (! $service) {
                    return ServiceResult::error('Serviço não encontrado');
                }

                $invoiceCode = $this->codeGenerator->generate($service->code);

                $invoiceData = $dto->toDatabaseArray();
                $invoiceData['code'] = $invoiceCode;

                $invoice = $this->repository->create($invoiceData);

                if (! empty($dto->items)) {
                    $this->createInvoiceItems($invoice->id, $dto->items);
                }

                // Cria o compartilhamento inicial da fatura
                $this->invoiceShareService->createShare([
                    'invoice_id' => $invoice->id,
                    'recipient_email' => $service->customer->email ?? null, // Usa do serviço pois invoice->customer pode não estar carregado
                    'recipient_name' => $service->customer->name ?? null,
                ], false);

                return ServiceResult::success($invoice->load(['customer', 'service', 'invoiceItems.product']));
            });
        }, 'Erro ao criar fatura');
    }

    public function deleteByCode(string $code): ServiceResult
    {
        return $this->safeExecute(function () use ($code) {
            $invoice = $this->repository->findByCode($code);

            if (! $invoice) {
                return ServiceResult::error('Fatura não encontrada');
            }

            // Não pode deletar se tiver pagamentos
            if ($this->repository->hasPayments($invoice->id)) {
                return ServiceResult::error('Fatura possui pagamentos e não pode ser excluída');
            }

            return DB::transaction(function () use ($invoice, $code) {
                $this->itemRepository->deleteByInvoiceId($invoice->id);

                return $this->repository->deleteByCode($code)
                    ? ServiceResult::success(null, 'Fatura excluída com sucesso')
                    : ServiceResult::error('Falha ao excluir fatura');
            });
        }, 'Erro ao excluir fatura');
    }

    public function updateStatusByCode(string $code, string $status): ServiceResult
    {
        return $this->safeExecute(function () use ($code, $status) {
            $success = $this->repository->updateStatusByCode($code, $status);

            if (! $success) {
                return ServiceResult::error('Fatura não encontrada ou falha ao atualizar status');
            }

            $invoice = $this->repository->findByCode($code);

            return ServiceResult::success($invoice, 'Status da fatura atualizado com sucesso');
        }, 'Erro ao atualizar status da fatura');
    }

    public function updateInvoiceByCode(string $code, InvoiceUpdateDTO $dto): ServiceResult
    {
        return $this->safeExecute(function () use ($code, $dto) {
            return DB::transaction(function () use ($code, $dto) {
                $invoice = $this->repository->findByCode($code);

                if (! $invoice) {
                    return ServiceResult::error('Fatura não encontrada');
                }

                $this->repository->updateFromDTO($invoice->id, $dto);

                if ($dto->items !== null) {
                    $this->itemRepository->deleteByInvoiceId($invoice->id);
                    $this->createInvoiceItems($invoice->id, $dto->items);
                }

                return ServiceResult::success($invoice->fresh(['customer', 'service', 'invoiceItems.product']));
            });
        }, 'Erro ao atualizar fatura');
    }

    public function createPartialInvoiceFromBudget(string $budgetCode, InvoiceFromBudgetDTO $dto): ServiceResult
    {
        return $this->safeExecute(function () use ($budgetCode, $dto) {
            return DB::transaction(function () use ($budgetCode, $dto) {
                $budget = $this->budgetRepository->findByCode($budgetCode, ['services.serviceItems', 'customer']);

                if (! $budget) {
                    return ServiceResult::error('Orçamento não encontrado');
                }

                $service = $this->serviceRepository->find($dto->service_id);

                if (! $service || $service->budget_id !== $budget->id) {
                    return ServiceResult::error('Serviço inválido para o orçamento');
                }

                $selectedItems = $dto->items;
                if (empty($selectedItems)) {
                    return ServiceResult::error('Selecione ao menos um item');
                }

                $subtotal = 0.0;
                $preparedItems = [];
                foreach ($selectedItems as $item) {
                    $serviceItem = $this->serviceItemRepository->findByIdAndServiceId($item['service_item_id'] ?? 0, $service->id);
                    if (! $serviceItem) {
                        return ServiceResult::error('Item de serviço inválido');
                    }
                    $quantity = (float) ($item['quantity'] ?? $serviceItem->quantity);
                    $unit = (float) ($item['unit_value'] ?? $serviceItem->unit_value);
                    $subtotal += $quantity * $unit;
                    $preparedItems[] = new InvoiceItemDTO(
                        product_id: (int) $serviceItem->product_id,
                        quantity: (int) $quantity,
                        unit_price: (float) $unit,
                        total: (float) ($quantity * $unit),
                    );
                }

                $alreadyBilled = $this->repository->sumTotalByBudgetId($budget->id, ['pending', 'approved', 'in_process', 'authorized']);
                $budgetTotal = (float) ($budget->total ?? 0);
                $remaining = max(0.0, $budgetTotal - $alreadyBilled);

                if ($subtotal > $remaining) {
                    return ServiceResult::error('Total selecionado excede o saldo disponível do orçamento');
                }

                $invoiceCode = $this->codeGenerator->generate($service->code);

                $invoiceDTO = new InvoiceDTO(
                    service_id: $service->id,
                    customer_id: $budget->customer_id,
                    status: $dto->status ?? InvoiceStatus::PENDING,
                    subtotal: $subtotal,
                    total: $subtotal - ($dto->discount ?? 0.0),
                    due_date: $dto->due_date ?? now()->addDays(7),
                    code: $invoiceCode,
                    discount: $dto->discount ?? 0.0,
                    items: $preparedItems
                );

                $invoice = $this->repository->createFromDTO($invoiceDTO);

                $this->createInvoiceItems($invoice->id, $preparedItems);

                // Cria o compartilhamento inicial da fatura
                $this->invoiceShareService->createShare([
                    'invoice_id' => $invoice->id,
                    'recipient_email' => $budget->customer->email ?? null,
                    'recipient_name' => $budget->customer->name ?? null,
                ], false);

                return ServiceResult::success($invoice->load(['customer', 'service', 'invoiceItems.product']));
            });
        }, 'Erro ao criar fatura parcial');
    }

    public function generateInvoicePdf(string $code): ServiceResult
    {
        return $this->safeExecute(function () use ($code) {
            $invoiceResult = $this->findByCode($code, ['customer', 'service', 'invoiceItems.product']);

            if ($invoiceResult->isError()) {
                return $invoiceResult;
            }

            $invoice = $invoiceResult->getData();

            // Obtém URL pública via sistema de compartilhamento
            $publicUrl = $invoice->getPublicUrl();

            // Se não tiver link público (share), cria um novo
            if (! $publicUrl) {
                $shareResult = $this->invoiceShareService->createShare([
                    'invoice_id' => $invoice->id,
                    'recipient_email' => $invoice->customer->email ?? null,
                    'recipient_name' => $invoice->customer->name ?? null,
                ], false);

                if ($shareResult->isSuccess()) {
                    $share = $shareResult->getData();
                    // Garante que usa a mesma rota definida no Model
                    $publicUrl = route('services.public.invoices.public.show', ['hash' => $share->share_token]);
                }
            }

            $qrDataUri = null;
            if ($publicUrl) {
                $qrService = app(\App\Services\Infrastructure\QrCodeService::class);
                $qrDataUri = $qrService->generateDataUri($publicUrl, 180);
            }

            $html = view('pages.invoice.pdf_professional', [
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
            Storage::put($path, $content);

            return ServiceResult::success($path, 'PDF da fatura gerado com sucesso');
        }, 'Erro ao gerar PDF da fatura');
    }

    public function generateInvoiceDataFromService(string $serviceCode): ServiceResult
    {
        return $this->safeExecute(function () use ($serviceCode) {
            $service = $this->serviceRepository->findByCode($serviceCode, ['serviceItems.product', 'customer']);

            if (! $service) {
                return ServiceResult::error('Serviço não encontrado');
            }

            $subtotal = $service->serviceItems->sum('total');

            return ServiceResult::success([
                'service_id' => $service->id,
                'customer_id' => $service->customer_id,
                'subtotal' => $subtotal,
                'total' => $subtotal, // Assume no discount initially
                'items' => $service->serviceItems->map(fn($item) => new InvoiceItemDTO(
                    product_id: $item->product_id,
                    quantity: $item->quantity,
                    unit_price: $item->unit_value,
                    total: $item->total,
                ))->toArray(),
            ]);
        }, 'Erro ao gerar dados da fatura a partir do serviço');
    }

    public function checkExistingInvoiceForService(int $serviceId): bool
    {
        return $this->repository->existsForService($serviceId);
    }

    public function createInvoiceFromService(InvoiceFromServiceDTO $dto): ServiceResult
    {
        return $this->safeExecute(function () use ($dto) {
            return DB::transaction(function () use ($dto) {
                $service = $this->serviceRepository->findByCode($dto->service_code, ['serviceItems.product']);

                if (! $service) {
                    return ServiceResult::error('Serviço não encontrado');
                }

                $invoiceCode = $this->codeGenerator->generate($service->code);

                $items = $dto->items ?? $service->serviceItems->map(fn($item) => new InvoiceItemDTO(
                    product_id: (int) $item->product_id,
                    quantity: (int) $item->quantity,
                    unit_price: (float) $item->unit_value,
                    total: (float) $item->total,
                ))->toArray();

                $subtotal = array_reduce($items, function ($carry, $item) {
                    if ($item instanceof InvoiceItemDTO) {
                        return $carry + $item->total;
                    }

                    return $carry + ($item['total'] ?? ($item['quantity'] * ($item['unit_price'] ?? $item['unit_value'])));
                }, 0.0);
                $discount = (float) ($dto->discount ?? 0.0);

                $invoiceDTO = new InvoiceDTO(
                    service_id: $service->id,
                    customer_id: $service->customer_id,
                    status: $dto->status ?? InvoiceStatus::PENDING,
                    subtotal: $subtotal,
                    total: $subtotal - $discount,
                    due_date: $dto->due_date ?? now()->addDays(7),
                    code: $invoiceCode,
                    discount: $discount,
                    notes: $dto->notes,
                    is_automatic: $dto->is_automatic,
                    items: $items
                );

                $invoice = $this->repository->createFromDTO($invoiceDTO);

                $this->createInvoiceItems($invoice->id, $items);

                return ServiceResult::success($invoice->load(['customer', 'service', 'invoiceItems.product']));
            });
        }, 'Erro ao criar fatura a partir do serviço');
    }

    public function searchInvoices(string $query, int $limit = 10): ServiceResult
    {
        return $this->safeExecute(function () use ($query, $limit) {
            $invoices = $this->repository->search($query, $limit)
                ->map(fn($i) => [
                    'id' => $i->id,
                    'text' => "{$i->code} - {$i->customer->name}",
                ]);

            return ServiceResult::success($invoices);
        }, 'Erro ao pesquisar faturas');
    }

    public function exportInvoices(array $filters, string $format = 'xlsx'): ServiceResult
    {
        return $this->safeExecute(function () {
            // Placeholder for export logic. In a real app, this would use Excel/CSV library.
            // For now, we return a mock result or implement if a library is available.
            return ServiceResult::error('Exportação não implementada nesta versão');
        }, 'Erro ao exportar faturas');
    }

    private function createInvoiceItems(int $invoiceId, array $items): void
    {
        foreach ($items as $item) {
            $itemDTO = $item instanceof InvoiceItemDTO ? $item : InvoiceItemDTO::fromRequest($item);

            $product = $this->productRepository->find($itemDTO->product_id);

            if (! $product || ! $product->active) {
                throw new Exception("Produto ID {$itemDTO->product_id} não encontrado ou inativo");
            }

            $this->itemRepository->createFromDTO($itemDTO, $invoiceId);
        }
    }
}
