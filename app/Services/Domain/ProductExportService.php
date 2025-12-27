<?php

declare(strict_types=1);

namespace App\Services\Domain;

use App\Services\Domain\Abstracts\AbstractExportService;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductExportService extends AbstractExportService
{
    protected function getHeaders(): array
    {
        return ['Produto', 'SKU', 'Categoria', 'Preço', 'Estoque', 'Situação', 'Data Criação'];
    }

    protected function getExportTitle(): string
    {
        return 'Relatório de Produtos';
    }

    protected function mapData(object $product): array
    {
        $createdAt = $product->created_at ? $product->created_at->format('d/m/Y H:i:s') : '';
        $price = 'R$ '.number_format((float) $product->price, 2, ',', '.');
        
        // Garante que o estoque seja exibido como número, inclusive se for 0
        $stock = (string) ($product->total_stock ?? 0);
        
        $category = $product->category ? $product->category->name : '-';

        // Determina a situação: Deletado > Inativo > Ativo
        $situacao = ! is_null($product->deleted_at) ? 'Deletado' : ($product->active ? 'Ativo' : 'Inativo');

        return [
            $product->name,
            $product->sku ?? '-',
            $category,
            $price,
            $stock,
            $situacao,
            $createdAt,
        ];
    }

    public function exportToExcel(Collection $products, string $format = 'xlsx', string $fileName = 'products'): StreamedResponse
    {
        return parent::exportToExcel($products, $format, $fileName);
    }

    public function exportToPdf(Collection $products, string $fileName = 'products', string $orientation = 'A4-L'): StreamedResponse
    {
        return parent::exportToPdf($products, $fileName, $orientation);
    }

    /**
     * Sobrescreve para aplicar estilos específicos.
     */
    protected function applyExcelStyles($sheet, int $rowCount): void
    {
        parent::applyExcelStyles($sheet, $rowCount);

        // Centralizar colunas "Situação" (F)
        $sheet->getStyle('F1:F'.($rowCount - 1))
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Alinhar Preço (D) e Estoque (E) à direita
        $sheet->getStyle('D1:E'.($rowCount - 1))
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    }
}
