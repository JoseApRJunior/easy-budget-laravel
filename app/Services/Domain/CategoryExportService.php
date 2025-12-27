<?php

declare(strict_types=1);

namespace App\Services\Domain;

use App\Services\Domain\Abstracts\AbstractExportService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CategoryExportService extends AbstractExportService
{
    protected function getHeaders(): array
    {
        return ['Categoria', 'Subcategoria', 'Situação', 'Subcategorias Ativas', 'Data Criação', 'Data Atualização'];
    }

    protected function getExportTitle(): string
    {
        return 'Relatório de Categorias';
    }

    protected function mapData(object $category): array
    {
        $createdAt = $category->created_at ? $category->created_at->format('d/m/Y H:i:s') : '';
        $updatedAt = $category->updated_at ? $category->updated_at->format('d/m/Y H:i:s') : '';
        $categoryName = $category->parent_id && $category->parent ? $category->parent->name : $category->name;
        $subcategoryName = $category->parent_id ? $category->name : '—';
        $childrenCount = $category->children()->where('is_active', true)->count();

        // DEBUG: Log para verificar o valor de deleted_at
        Log::info('Category Export Debug', [
            'id' => $category->id,
            'name' => $category->name,
            'deleted_at' => $category->deleted_at,
            'is_active' => $category->is_active,
        ]);

        $situacao = ! is_null($category->deleted_at) ? 'Deletado' : ($category->is_active ? 'Ativo' : 'Inativo');

        return [
            $categoryName,
            $subcategoryName,
            $situacao,
            $childrenCount,
            $createdAt,
            $updatedAt,
        ];
    }

    public function exportToExcel(Collection $categories, string $format = 'xlsx', string $fileName = 'categories'): StreamedResponse
    {
        return parent::exportToExcel($categories, $format, $fileName);
    }

    public function exportToPdf(Collection $categories, string $fileName = 'categories', string $orientation = 'A4'): StreamedResponse
    {
        return parent::exportToPdf($categories, $fileName, $orientation);
    }

    /**
     * Sobrescreve para aplicar centralização específica.
     */
    protected function applyExcelStyles($sheet, int $rowCount): void
    {
        parent::applyExcelStyles($sheet, $rowCount);

        // Centralizar colunas "Situação" (C) e "Subcategorias Ativas" (D)
        $sheet->getStyle('C1:D'.($rowCount - 1))
            ->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);
    }
}
