<?php

declare(strict_types=1);

namespace App\Services\Domain;

use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductInventory;
use App\Repositories\InventoryMovementRepository;
use App\Repositories\ProductInventoryRepository;
use App\Services\Core\Abstracts\AbstractBaseService;
use App\Support\ServiceResult;
use Exception;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InventoryService extends AbstractBaseService
{
    /**
     * @var ProductInventoryRepository
     */
    protected ProductInventoryRepository $productInventoryRepository;

    /**
     * @var InventoryMovementRepository
     */
    protected InventoryMovementRepository $inventoryMovementRepository;

    /**
     * InventoryService constructor.
     */
    public function __construct(
        ProductInventoryRepository $productInventoryRepository,
        InventoryMovementRepository $inventoryMovementRepository
    ) {
        $this->productInventoryRepository = $productInventoryRepository;
        $this->inventoryMovementRepository = $inventoryMovementRepository;
    }

    /**
     * Ajusta inventário de um produto
     */
    public function adjustInventory(
        Product $product,
        string $type,
        int $quantity,
        string $reason = '',
        ?int $referenceId = null,
        ?string $referenceType = null
    ): InventoryMovement {
        return DB::transaction(function () use ($product, $type, $quantity, $reason, $referenceId, $referenceType) {
            // Valida quantidade
            if ($quantity <= 0) {
                throw new Exception('Quantidade deve ser maior que zero');
            }

            // Busca ou cria inventário
            $inventory = $this->productInventoryRepository->findByProduct($product->id);
            if (!$inventory) {
                $inventory = $this->productInventoryRepository->updateOrCreate($product->id, [
                    'quantity' => 0,
                    'min_quantity' => 0,
                    'max_quantity' => null,
                ]);
            }

            // Calcula nova quantidade
            $currentQuantity = $inventory->quantity;
            if ($type === 'in') {
                $newQuantity = $currentQuantity + $quantity;
            } elseif ($type === 'out') {
                if ($currentQuantity < $quantity) {
                    throw new Exception('Estoque insuficiente para esta operação');
                }
                $newQuantity = $currentQuantity - $quantity;
            } else {
                throw new Exception('Tipo de movimento inválido. Use "in" ou "out"');
            }

            // Atualiza inventário
            $inventory->update(['quantity' => $newQuantity]);

            // Cria movimentação
            $movementData = [
                'tenant_id' => tenant()->id,
                'product_id' => $product->id,
                'type' => $type,
                'quantity' => $quantity,
                'previous_quantity' => $currentQuantity,
                'new_quantity' => $newQuantity,
                'reason' => $reason ?: 'Ajuste manual de inventário',
                'reference_id' => $referenceId,
                'reference_type' => $referenceType,
            ];

            return $this->inventoryMovementRepository->create($movementData);
        });
    }

    /**
     * Calcula valor total do inventário
     */
    public function calculateTotalInventoryValue(): float
    {
        try {
            $inventories = $this->productInventoryRepository->getPaginated(1000);
            $totalValue = 0;

            foreach ($inventories as $inventory) {
                if ($inventory->product && $inventory->product->price) {
                    $totalValue += $inventory->quantity * $inventory->product->price;
                }
            }

            return $totalValue;
        } catch (Exception $e) {
            Log::error('Erro ao calcular valor total do inventário', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return 0;
        }
    }

    /**
     * Gera relatório de resumo
     */
    public function generateSummaryReport(): array
    {
        try {
            $statistics = $this->productInventoryRepository->getStatistics();
            $totalValue = $this->calculateTotalInventoryValue();
            $recentMovements = $this->inventoryMovementRepository->getRecentMovements(10);

            return [
                'statistics' => array_merge($statistics, ['total_value' => $totalValue]),
                'recent_movements' => $recentMovements,
                'low_stock_items' => $this->productInventoryRepository->getLowStockItems(),
                'report_date' => now(),
            ];
        } catch (Exception $e) {
            Log::error('Erro ao gerar relatório de resumo', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'statistics' => [],
                'recent_movements' => collect(),
                'low_stock_items' => collect(),
                'report_date' => now(),
                'error' => 'Erro ao gerar relatório',
            ];
        }
    }

    /**
     * Gera relatório de movimentações
     */
    public function generateMovementReport(?string $startDate = null, ?string $endDate = null): array
    {
        try {
            $statistics = $this->inventoryMovementRepository->getStatisticsByPeriod($startDate, $endDate);
            $summaryByType = $this->inventoryMovementRepository->getSummaryByType($startDate, $endDate);
            $mostMovedProducts = $this->inventoryMovementRepository->getMostMovedProducts(10, $startDate, $endDate);

            return [
                'statistics' => $statistics,
                'summary_by_type' => $summaryByType,
                'most_moved_products' => $mostMovedProducts,
                'report_period' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                ],
                'report_date' => now(),
            ];
        } catch (Exception $e) {
            Log::error('Erro ao gerar relatório de movimentações', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'statistics' => [],
                'summary_by_type' => [],
                'most_moved_products' => collect(),
                'report_period' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                ],
                'report_date' => now(),
                'error' => 'Erro ao gerar relatório',
            ];
        }
    }

    /**
     * Gera relatório de avaliação
     */
    public function generateValuationReport(): array
    {
        try {
            $inventories = $this->productInventoryRepository->getPaginated(1000);
            $valuationData = [];
            $totalValue = 0;

            foreach ($inventories as $inventory) {
                if ($inventory->product && $inventory->product->price) {
                    $value = $inventory->quantity * $inventory->product->price;
                    $totalValue += $value;

                    $valuationData[] = [
                        'product' => $inventory->product,
                        'quantity' => $inventory->quantity,
                        'unit_price' => $inventory->product->price,
                        'total_value' => $value,
                        'stock_percentage' => $inventory->max_quantity > 0 
                            ? round(($inventory->quantity / $inventory->max_quantity) * 100, 2)
                            : null,
                    ];
                }
            }

            // Ordena por valor total
            usort($valuationData, function ($a, $b) {
                return $b['total_value'] <=> $a['total_value'];
            });

            return [
                'valuation_data' => $valuationData,
                'total_value' => $totalValue,
                'total_items' => count($valuationData),
                'report_date' => now(),
            ];
        } catch (Exception $e) {
            Log::error('Erro ao gerar relatório de avaliação', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'valuation_data' => [],
                'total_value' => 0,
                'total_items' => 0,
                'report_date' => now(),
                'error' => 'Erro ao gerar relatório',
            ];
        }
    }

    /**
     * Gera relatório de estoque baixo
     */
    public function generateLowStockReport(): array
    {
        try {
            $lowStockItems = $this->productInventoryRepository->getLowStockItems();
            $urgentItems = [];
            $warningItems = [];

            foreach ($lowStockItems as $item) {
                if ($item->quantity == 0) {
                    $urgentItems[] = $item;
                } elseif ($item->min_quantity > 0) {
                    $percentage = ($item->quantity / $item->min_quantity) * 100;
                    if ($percentage <= 50) {
                        $urgentItems[] = $item;
                    } else {
                        $warningItems[] = $item;
                    }
                } else {
                    $warningItems[] = $item;
                }
            }

            return [
                'urgent_items' => collect($urgentItems),
                'warning_items' => collect($warningItems),
                'total_urgent' => count($urgentItems),
                'total_warning' => count($warningItems),
                'report_date' => now(),
            ];
        } catch (Exception $e) {
            Log::error('Erro ao gerar relatório de estoque baixo', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'urgent_items' => collect(),
                'warning_items' => collect(),
                'total_urgent' => 0,
                'total_warning' => 0,
                'report_date' => now(),
                'error' => 'Erro ao gerar relatório',
            ];
        }
    }

    /**
     * Exporta relatório
     */
    public function exportReport(string $reportType, string $format)
    {
        try {
            $reportData = match ($reportType) {
                'movements' => $this->generateMovementReport(),
                'valuation' => $this->generateValuationReport(),
                'low-stock' => $this->generateLowStockReport(),
                default => $this->generateSummaryReport(),
            };

            if ($format === 'pdf') {
                return $this->exportToPdf($reportData, $reportType);
            } elseif ($format === 'csv') {
                return $this->exportToCsv($reportData, $reportType);
            } elseif ($format === 'xlsx') {
                return $this->exportToExcel($reportData, $reportType);
            }

            throw new Exception('Formato de exportação não suportado');
        } catch (Exception $e) {
            Log::error('Erro ao exportar relatório', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'report_type' => $reportType,
                'format' => $format,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erro ao exportar relatório: ' . $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Exporta para PDF
     */
    private function exportToPdf(array $reportData, string $reportType)
    {
        // Implementação básica - pode ser estendida com bibliotecas como DomPDF
        $view = view('reports.inventory.pdf.' . $reportType, compact('reportData'));
        
        return response($view->render())
            ->header('Content-Type', 'text/html')
            ->header('Content-Disposition', 'attachment; filename="relatorio-inventario-' . $reportType . '-' . date('Y-m-d') . '.pdf"');
    }

    /**
     * Exporta para CSV
     */
    private function exportToCsv(array $reportData, string $reportType)
    {
        $filename = 'relatorio-inventario-' . $reportType . '-' . date('Y-m-d') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        return response()->stream(function () use ($reportData, $reportType) {
            $handle = fopen('php://output', 'w');
            
            // Cabeçalhos baseados no tipo de relatório
            $this->writeCsvHeaders($handle, $reportType);
            
            // Dados baseados no tipo de relatório
            $this->writeCsvData($handle, $reportData, $reportType);
            
            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Exporta para Excel
     */
    private function exportToExcel(array $reportData, string $reportType)
    {
        // Implementação básica - pode ser estendida com bibliotecas como PhpSpreadsheet
        // Por enquanto, retorna CSV com extensão xlsx
        return $this->exportToCsv($reportData, $reportType);
    }

    /**
     * Escreve cabeçalhos CSV
     */
    private function writeCsvHeaders($handle, string $reportType): void
    {
        $headers = match ($reportType) {
            'summary' => ['Período', 'Total de Itens', 'Valor Total', 'Estoque Baixo', 'Data'],
            'movements' => ['Data', 'Produto', 'Tipo', 'Quantidade', 'Quantidade Anterior', 'Quantidade Nova', 'Motivo'],
            'valuation' => ['Produto', 'SKU', 'Quantidade', 'Preço Unitário', 'Valor Total', '% Estoque'],
            'low-stock' => ['Produto', 'SKU', 'Quantidade Atual', 'Quantidade Mínima', 'Situação'],
            default => ['Dados'],
        };
        
        fputcsv($handle, $headers);
    }

    /**
     * Escreve dados CSV
     */
    private function writeCsvData($handle, array $reportData, string $reportType): void
    {
        switch ($reportType) {
            case 'summary':
                fputcsv($handle, [
                    'Resumo',
                    $reportData['statistics']['total_items'] ?? 0,
                    'R$ ' . number_format($reportData['statistics']['total_value'] ?? 0, 2, ',', '.'),
                    $reportData['statistics']['low_stock_items'] ?? 0,
                    $reportData['report_date']->format('d/m/Y H:i'),
                ]);
                break;
                
            case 'movements':
                if (isset($reportData['most_moved_products'])) {
                    foreach ($reportData['most_moved_products'] as $item) {
                        fputcsv($handle, [
                            $item->created_at->format('d/m/Y H:i'),
                            $item->product->name ?? '',
                            strtoupper($item->type ?? ''),
                            $item->total_quantity ?? 0,
                            '', // quantidade anterior não disponível neste relatório
                            '', // quantidade nova não disponível neste relatório
                            'Produto mais movimentado',
                        ]);
                    }
                }
                break;
                
            case 'valuation':
                if (isset($reportData['valuation_data'])) {
                    foreach ($reportData['valuation_data'] as $item) {
                        fputcsv($handle, [
                            $item['product']->name ?? '',
                            $item['product']->sku ?? '',
                            $item['quantity'] ?? 0,
                            'R$ ' . number_format($item['unit_price'] ?? 0, 2, ',', '.'),
                            'R$ ' . number_format($item['total_value'] ?? 0, 2, ',', '.'),
                            ($item['stock_percentage'] ?? 0) . '%',
                        ]);
                    }
                }
                break;
                
            case 'low-stock':
                if (isset($reportData['urgent_items'])) {
                    foreach ($reportData['urgent_items'] as $item) {
                        fputcsv($handle, [
                            $item->product->name ?? '',
                            $item->product->sku ?? '',
                            $item->quantity ?? 0,
                            $item->min_quantity ?? 0,
                            'URGENTE',
                        ]);
                    }
                }
                if (isset($reportData['warning_items'])) {
                    foreach ($reportData['warning_items'] as $item) {
                        fputcsv($handle, [
                            $item->product->name ?? '',
                            $item->product->sku ?? '',
                            $item->quantity ?? 0,
                            $item->min_quantity ?? 0,
                            'ALERTA',
                        ]);
                    }
                }
                break;
        }
    }
}