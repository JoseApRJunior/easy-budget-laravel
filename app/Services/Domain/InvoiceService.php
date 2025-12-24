<?php

declare(strict_types=1);

namespace App\Services\Domain;

use App\DTOs\Invoice\InvoiceDTO;
use App\DTOs\Invoice\InvoiceFromBudgetDTO;
use App\DTOs\Invoice\InvoiceFromServiceDTO;
use App\DTOs\Invoice\InvoiceItemDTO;
use App\DTOs\Invoice\InvoiceUpdateDTO;
use App\Enums\InvoiceStatus;
use App\Models\Budget;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\Service;
use App\Models\ServiceItem;
use App\Repositories\InvoiceRepository;
use App\Services\Core\Abstracts\AbstractBaseService;
use App\Services\NotificationService;
use App\Support\ServiceResult;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class InvoiceService extends AbstractBaseService
{
    public function __construct(
        private readonly InvoiceRepository $repository,
        private readonly NotificationService $notificationService
    ) {}

    public function findByCode(string $code, array $with = []): ServiceResult
    {
        return $this->safeExecute(function () use ($code, $with) {
            $tenantId = $this->ensureTenantId();
            $query = $this->repository->newQuery()
                ->where('code', $code)
                ->where('tenant_id', $tenantId);

            if (!empty($with)) {
                $query->with($with);
            }

            $invoice = $query->first();

            if (!$invoice) {
                return ServiceResult::error("Fatura com código {$code} não encontrada");
            }

            return ServiceResult::success($invoice);
        }, 'Erro ao buscar fatura');
    }

    public function getFilteredInvoices(array $filters = [], array $with = []): ServiceResult
    {
        return $this->safeExecute(function () use ($filters, $with) {
            $tenantId = $this->ensureTenantId();
            $invoices = $this->repository->getFiltered($filters, ['due_date' => 'desc'], 15);
            return ServiceResult::success($invoices);
        }, 'Erro ao filtrar faturas');
    }

    public function getDashboardStats(): ServiceResult
    {
        return $this->safeExecute(function () {
            $tenantId = $this->ensureTenantId();

            $total     = Invoice::where('tenant_id', $tenantId)->count();
            $paid      = Invoice::where('tenant_id', $tenantId)->where('status', InvoiceStatus::PAID->value)->count();
            $pending   = Invoice::where('tenant_id', $tenantId)->where('status', InvoiceStatus::PENDING->value)->count();
            $overdue   = Invoice::where('tenant_id', $tenantId)->where('status', InvoiceStatus::OVERDUE->value)->count();
            $cancelled = Invoice::where('tenant_id', $tenantId)->where('status', InvoiceStatus::CANCELLED->value)->count();

            $totalBilled   = (float) Invoice::where('tenant_id', $tenantId)->sum('total');
            $totalReceived = (float) Invoice::where('tenant_id', $tenantId)->where('status', InvoiceStatus::PAID->value)->sum('transaction_amount');
            $totalPending  = (float) Invoice::where('tenant_id', $tenantId)->whereIn('status', [InvoiceStatus::PENDING->value, InvoiceStatus::OVERDUE->value])->sum('total');

            $statusBreakdown = [
                'PENDENTE'  => ['count' => $pending, 'color' => InvoiceStatus::PENDING->getColor()],
                'VENCIDA'   => ['count' => $overdue, 'color' => InvoiceStatus::OVERDUE->getColor()],
                'PAGA'      => ['count' => $paid, 'color' => InvoiceStatus::PAID->getColor()],
                'CANCELADA' => ['count' => $cancelled, 'color' => InvoiceStatus::CANCELLED->getColor()],
            ];

            $recent = Invoice::where('tenant_id', $tenantId)
                ->latest('created_at')
                ->limit(10)
                ->with(['customer.commonData', 'service'])
                ->get();

            return ServiceResult::success([
                'total_invoices'     => $total,
                'paid_invoices'      => $paid,
                'pending_invoices'   => $pending,
                'overdue_invoices'   => $overdue,
                'cancelled_invoices' => $cancelled,
                'total_billed'       => $totalBilled,
                'total_received'     => $totalReceived,
                'total_pending'      => $totalPending,
                'status_breakdown'   => $statusBreakdown,
                'recent_invoices'    => $recent,
            ]);
        }, 'Erro ao carregar estatísticas do dashboard');
    }

    public function createInvoice(InvoiceDTO $dto): ServiceResult
    {
        return $this->safeExecute(function () use ($dto) {
            $tenantId = $this->ensureTenantId($dto->tenant_id);

            return DB::transaction(function () use ($dto, $tenantId) {
                $service = Service::find($dto->service_id);
                if (!$service) {
                    return ServiceResult::error('Serviço não encontrado');
                }

                $invoiceCode = $this->generateUniqueInvoiceCode($service->code);
                $data = $dto->toArray();
                $data['tenant_id'] = $tenantId;
                $data['code'] = $invoiceCode;
                $data['public_hash'] = bin2hex(random_bytes(32));

                $invoice = $this->repository->create($data);

                if (!empty($dto->items)) {
                    $this->createInvoiceItems($invoice, $dto->items);
                }

                return ServiceResult::success($invoice->load(['customer', 'service', 'invoiceItems.product']));
            });
        }, 'Erro ao criar fatura');
    }

    public function deleteByCode(string $code): ServiceResult
    {
        return $this->safeExecute(function () use ($code) {
            $tenantId = $this->ensureTenantId();
            $invoice = $this->repository->newQuery()
                ->where('code', $code)
                ->where('tenant_id', $tenantId)
                ->first();

            if (!$invoice) {
                return ServiceResult::error('Fatura não encontrada');
            }

            // Não pode deletar se tiver pagamentos
            if ($invoice->payments()->count() > 0) {
                return ServiceResult::error('Fatura possui pagamentos e não pode ser excluída');
            }

            return DB::transaction(function () use ($invoice) {
                $invoice->invoiceItems()->delete();
                return $invoice->delete()
                    ? ServiceResult::success(null, 'Fatura excluída com sucesso')
                    : ServiceResult::error('Falha ao excluir fatura');
            });
        }, 'Erro ao excluir fatura');
    }

    public function updateStatusByCode(string $code, string $status): ServiceResult
    {
        return $this->safeExecute(function () use ($code, $status) {
            $tenantId = $this->ensureTenantId();
            $invoice = $this->repository->newQuery()
                ->where('code', $code)
                ->where('tenant_id', $tenantId)
                ->first();

            if (!$invoice) {
                return ServiceResult::error('Fatura não encontrada');
            }

            $invoice->update(['status' => $status]);
            return ServiceResult::success($invoice, 'Status da fatura atualizado com sucesso');
        }, 'Erro ao atualizar status da fatura');
    }

    public function updateInvoiceByCode(string $code, InvoiceUpdateDTO $dto): ServiceResult
    {
        return $this->safeExecute(function () use ($code, $dto) {
            $tenantId = $this->ensureTenantId($dto->tenant_id);

            return DB::transaction(function () use ($code, $dto, $tenantId) {
                $invoice = $this->repository->newQuery()
                    ->where('code', $code)
                    ->where('tenant_id', $tenantId)
                    ->first();

                if (!$invoice) {
                    return ServiceResult::error('Fatura não encontrada');
                }

                $invoice->update($dto->toArray());

                if ($dto->items !== null) {
                    $invoice->invoiceItems()->delete();
                    $this->createInvoiceItems($invoice, $dto->items);
                }

                return ServiceResult::success($invoice->fresh(['customer', 'service', 'invoiceItems.product']));
            });
        }, 'Erro ao atualizar fatura');
    }

    public function createPartialInvoiceFromBudget(string $budgetCode, InvoiceFromBudgetDTO $dto): ServiceResult
    {
        return $this->safeExecute(function () use ($budgetCode, $dto) {
            return DB::transaction(function () use ($budgetCode, $dto) {
                $budget = Budget::where('code', $budgetCode)
                    ->with(['services.serviceItems', 'customer'])
                    ->first();

                if (!$budget) {
                    return ServiceResult::error('Orçamento não encontrado');
                }

                $service = Service::where('id', $dto->service_id)
                    ->where('budget_id', $budget->id)
                    ->first();

                if (!$service) {
                    return ServiceResult::error('Serviço inválido para o orçamento');
                }

                $selectedItems = $dto->items;
                if (empty($selectedItems)) {
                    return ServiceResult::error('Selecione ao menos um item');
                }

                $subtotal      = 0.0;
                $preparedItems = [];
                foreach ($selectedItems as $item) {
                    $serviceItem = ServiceItem::where('id', $item['service_item_id'] ?? 0)
                        ->where('service_id', $service->id)
                        ->first();
                    if (!$serviceItem) {
                        return ServiceResult::error('Item de serviço inválido');
                    }
                    $quantity         = (float) ($item['quantity'] ?? $serviceItem->quantity);
                    $unit             = (float) ($item['unit_value'] ?? $serviceItem->unit_value);
                    $subtotal        += $quantity * $unit;
                    $preparedItems[]  = [
                        'product_id' => $serviceItem->product_id,
                        'quantity'   => $quantity,
                        'unit_price' => $unit,
                        'total'      => $quantity * $unit,
                    ];
                }

                $alreadyBilled = $this->repository->sumTotalByBudgetId($budget->id, ['pending', 'approved', 'in_process', 'authorized']);
                $budgetTotal   = (float) ($budget->total ?? 0);
                $remaining     = max(0.0, $budgetTotal - $alreadyBilled);

                if ($subtotal > $remaining) {
                    return ServiceResult::error('Total selecionado excede o saldo disponível do orçamento');
                }

                $invoiceCode = $this->generateUniqueInvoiceCode($service->code);

                $invoice = $this->repository->create([
                    'tenant_id'   => $this->ensureTenantId($dto->tenant_id),
                    'service_id'  => $service->id,
                    'customer_id' => $budget->customer_id,
                    'code'        => $invoiceCode,
                    'due_date'    => $dto->due_date ?? now()->addDays(7),
                    'subtotal'    => $subtotal,
                    'discount'    => $dto->discount,
                    'total'       => $subtotal - $dto->discount,
                    'status'      => $dto->status?->value ?? InvoiceStatus::PENDING->value,
                    'public_hash' => bin2hex(random_bytes(32)),
                ]);

                $this->createInvoiceItems($invoice, $preparedItems);

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

            $publicUrl = null;
            if (!empty($invoice->public_hash)) {
                $publicUrl = route('invoices.public.show', ['hash' => $invoice->public_hash]);
            }

            $qrDataUri = null;
            if ($publicUrl) {
                $qrService = app(\App\Services\Infrastructure\QrCodeService::class);
                $qrDataUri = $qrService->generateDataUri($publicUrl, 180);
            }

            $html = view('pages.invoice.pdf_professional', [
                'invoice'   => $invoice,
                'publicUrl' => $publicUrl,
                'qrDataUri' => $qrDataUri,
            ])->render();

            $mpdf = new \Mpdf\Mpdf([
                'mode'          => 'utf-8',
                'format'        => 'A4',
                'margin_left'   => 15,
                'margin_right'  => 15,
                'margin_top'    => 16,
                'margin_bottom' => 16,
            ]);
            $mpdf->WriteHTML($html);
            $content = $mpdf->Output('', 'S');

            $dir      = 'invoices';
            $filename = 'invoice_' . $invoice->code . '.pdf';
            $path     = $dir . '/' . $filename;
            Storage::put($path, $content);

            return ServiceResult::success($path, 'PDF da fatura gerado com sucesso');
        }, 'Erro ao gerar PDF da fatura');
    }

    public function generateInvoiceDataFromService(string $serviceCode): ServiceResult
    {
        return $this->safeExecute(function () use ($serviceCode) {
            $service = Service::where('code', $serviceCode)->with(['serviceItems.product', 'customer'])->first();

            if (!$service) {
                return ServiceResult::error('Serviço não encontrado');
            }

            $subtotal = $service->serviceItems->sum('total');

            return ServiceResult::success([
                'service_id'  => $service->id,
                'customer_id' => $service->customer_id,
                'subtotal'    => $subtotal,
                'total'       => $subtotal, // Assume no discount initially
                'items'       => $service->serviceItems->map(fn($item) => [
                    'product_id' => $item->product_id,
                    'quantity'   => $item->quantity,
                    'unit_value' => $item->unit_value,
                    'total'      => $item->total,
                ])->toArray(),
            ]);
        }, 'Erro ao gerar dados da fatura a partir do serviço');
    }

    public function checkExistingInvoiceForService(int $serviceId): bool
    {
        return Invoice::where('service_id', $serviceId)->exists();
    }

    public function createInvoiceFromService(InvoiceFromServiceDTO $dto): ServiceResult
    {
        return $this->safeExecute(function () use ($dto) {
            return DB::transaction(function () use ($dto) {
                $service = Service::where('code', $dto->service_code)->with(['serviceItems.product'])->first();

                if (!$service) {
                    return ServiceResult::error('Serviço não encontrado');
                }

                $tenantId = $this->ensureTenantId($dto->tenant_id);
                $invoiceCode = $this->generateUniqueInvoiceCode($service->code);

                $items = $dto->items ?? $service->serviceItems->map(fn($item) => [
                    'product_id' => $item->product_id,
                    'quantity'   => $item->quantity,
                    'unit_value' => $item->unit_value,
                    'total'      => $item->total,
                ])->toArray();

                $subtotal = array_reduce($items, fn($carry, $item) => $carry + ($item['total'] ?? ($item['quantity'] * $item['unit_value'])), 0.0);
                $discount = (float) ($dto->discount ?? 0.0);

                $invoice = $this->repository->create([
                    'tenant_id'    => $tenantId,
                    'service_id'   => $service->id,
                    'customer_id'  => $service->customer_id,
                    'code'         => $invoiceCode,
                    'status'       => $dto->status?->value ?? InvoiceStatus::PENDING->value,
                    'subtotal'     => $subtotal,
                    'discount'     => $discount,
                    'total'        => $subtotal - $discount,
                    'due_date'     => $dto->due_date ?? now()->addDays(7),
                    'notes'        => $dto->notes,
                    'is_automatic' => $dto->is_automatic,
                    'public_hash'  => bin2hex(random_bytes(32)),
                ]);

                $this->createInvoiceItems($invoice, $items);

                return ServiceResult::success($invoice->load(['customer', 'service', 'invoiceItems.product']));
            });
        }, 'Erro ao criar fatura a partir do serviço');
    }

    public function searchInvoices(string $query, int $limit = 10): ServiceResult
    {
        return $this->safeExecute(function () use ($query, $limit) {
            $tenantId = $this->ensureTenantId();
            $invoices = Invoice::where('tenant_id', $tenantId)
                ->where(function ($q) use ($query) {
                    $q->where('code', 'like', "%{$query}%")
                        ->orWhereHas('customer', function ($cq) use ($query) {
                            $cq->where('name', 'like', "%{$query}%");
                        });
                })
                ->limit($limit)
                ->get(['id', 'code', 'customer_id'])
                ->map(fn($i) => [
                    'id'   => $i->id,
                    'text' => "{$i->code} - {$i->customer->name}",
                ]);

            return ServiceResult::success($invoices);
        }, 'Erro ao pesquisar faturas');
    }

    public function exportInvoices(array $filters, string $format = 'xlsx'): ServiceResult
    {
        return $this->safeExecute(function () use ($filters, $format) {
            // Placeholder for export logic. In a real app, this would use Excel/CSV library.
            // For now, we return a mock result or implement if a library is available.
            return ServiceResult::error('Exportação não implementada nesta versão');
        }, 'Erro ao exportar faturas');
    }

    private function generateUniqueInvoiceCode(string $serviceCode): string
    {
        $lastInvoice = Invoice::whereHas('service', function ($query) use ($serviceCode) {
            $query->where('code', $serviceCode);
        })
            ->orderBy('code', 'desc')
            ->first();

        $sequential = 1;
        if ($lastInvoice && preg_match('/-INV(\d{3})$/', $lastInvoice->code, $matches)) {
            $sequential = (int) $matches[1] + 1;
        }

        return "{$serviceCode}-INV" . str_pad((string) $sequential, 3, '0', STR_PAD_LEFT);
    }

    private function createInvoiceItems(Invoice $invoice, array $items): void
    {
        foreach ($items as $item) {
            $itemData = $item instanceof InvoiceItemDTO ? $item->toArray() : $item;

            $product = Product::where('id', $itemData['product_id'])
                ->where('active', true)
                ->first();

            if (!$product) {
                throw new Exception("Produto ID {$itemData['product_id']} não encontrado ou inativo");
            }

            InvoiceItem::create([
                'tenant_id'  => $invoice->tenant_id,
                'invoice_id' => $invoice->id,
                'product_id' => $product->id,
                'unit_price' => $itemData['unit_price'] ?? $itemData['unit_value'],
                'quantity'   => $itemData['quantity'],
                'total'      => $itemData['total'],
                'description' => $itemData['description'] ?? null,
            ]);
        }
    }
}
