<?php

declare(strict_types=1);

namespace App\Services\Domain;

use App\Services\Domain\Abstracts\AbstractExportService;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InventoryExportService extends AbstractExportService
{
    private string $exportType = 'inventory';
    private array $customHeaders = [];

    public function setExportType(string $type, array $headers = []): void
    {
        $this->exportType = $type;
        $this->customHeaders = $headers;
    }

    protected function getHeaders(): array
    {
        if (!empty($this->customHeaders)) {
            return $this->customHeaders;
        }

        return match ($this->exportType) {
            'movements' => ['Data', 'SKU', 'Produto', 'Tipo', 'Quantidade', 'Usuário', 'Motivo'],
            'stock_turnover' => ['Produto', 'SKU', 'Categoria', 'Entradas', 'Saídas', 'Estoque Médio'],
            'most_used' => ['Produto', 'SKU', 'Categoria', 'Uso Total', 'Uso Médio Diário', 'Valor Total'],
            'report_summary' => ['SKU', 'Produto', 'Categoria', 'Qtd Atual', 'Mínimo', 'Máximo', 'Status'],
            'report_valuation' => ['SKU', 'Produto', 'Qtd Atual', 'Preço Unit.', 'Valor Total'],
            'report_low_stock' => ['SKU', 'Produto', 'Qtd Atual', 'Mínimo', 'Necessidade'],
            default => ['Produto', 'SKU', 'Categoria', 'Qtd Atual', 'Qtd Mínima', 'Qtd Máxima'],
        };
    }

    protected function mapData(object $item): array
    {
        // Se o item for um array, converte para objeto para manter compatibilidade com mapData
        if (is_array($item)) {
            $item = (object) $item;
        }

        return match ($this->exportType) {
            'movements' => [
                $item->data ?? '',
                $item->sku ?? '',
                $item->produto ?? '',
                $item->tipo ?? '',
                $item->quantidade ?? 0,
                $item->usuario ?? '',
                $item->motivo ?? '',
            ],
            'stock_turnover' => [
                $item->name ?? '',
                $item->sku ?? '',
                $item->category->name ?? 'N/A',
                $item->total_entries ?? 0,
                $item->total_exits ?? 0,
                number_format((float)($item->average_stock ?? 0), 2, ',', '.'),
            ],
            'most_used' => [
                $item->name ?? '',
                $item->sku ?? '',
                $item->category ?? 'N/A',
                $item->total_usage ?? 0,
                number_format((float)($item->average_usage ?? 0), 2, ',', '.'),
                'R$ ' . number_format((float)($item->total_value ?? 0), 2, ',', '.'),
            ],
            'report_summary' => [
                $item->sku ?? '',
                $item->produto ?? '',
                $item->categoria ?? 'N/A',
                $item->quantidade ?? 0,
                $item->estoque_min ?? 0,
                $item->estoque_max ?? '-',
                $item->status ?? '',
            ],
            'report_valuation' => [
                $item->sku ?? '',
                $item->produto ?? '',
                $item->quantidade ?? 0,
                $item->preço_unitário ?? 'R$ 0,00',
                $item->valor_total ?? 'R$ 0,00',
            ],
            'report_low_stock' => [
                $item->sku ?? '',
                $item->produto ?? '',
                $item->quantidade_atual ?? 0,
                $item->estoque_mínimo ?? 0,
                $item->necessidade ?? 0,
            ],
            default => [
                $item->product->name ?? '',
                $item->product->sku ?? '',
                $item->product->category->name ?? 'N/A',
                $item->quantity ?? 0,
                $item->min_quantity ?? 0,
                $item->max_quantity ?? '-',
            ],
        };
    }

    public function export(Collection $items, string $format = 'xlsx', string $fileName = 'inventory'): StreamedResponse
    {
        if ($format === 'pdf') {
            return $this->exportToPdf($items, $fileName);
        }
        return $this->exportToExcel($items, $format, $fileName);
    }

    protected function getPdfViewName(): ?string
    {
        return 'pages.inventory.pdf_export';
    }

    protected function getPdfData(Collection $items): array
    {
        return [
            'title' => $this->getExportTitle(),
            'headers' => $this->getHeaders(),
            'items' => $items->map(fn($item) => $this->mapData($item))->toArray(),
            'generated_at' => now()->format('d/m/Y H:i'),
        ];
    }

    protected function getExportTitle(): string
    {
        return match ($this->exportType) {
            'movements' => 'Relatório de Movimentações de Estoque',
            'stock_turnover' => 'Relatório de Giro de Estoque',
            'most_used' => 'Relatório de Produtos Mais Utilizados',
            'report_summary' => 'Relatório de Inventário - Resumo',
            'report_valuation' => 'Relatório de Inventário - Valoração',
            'report_low_stock' => 'Relatório de Inventário - Baixo Estoque',
            default => 'Relatório de Inventário Geral',
        };
    }
}
