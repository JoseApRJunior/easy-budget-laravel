<?php

namespace core\support\report;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExcelGenerator
{
    private const FORMAT_CURRENCY_BRL = "R$ #,##0.00";
    private $sheet;
    private $currentRow;
    private $lastRow = 0;
    private $lastColumn;

    public function __construct(
        private Spreadsheet $spreadsheet,
    ) {
        $this->sheet = $this->spreadsheet->getActiveSheet();
    }

    public function generate(array $config): array
    {
        // Configurar cabeçalho da empresa
        $this->setCompanyHeader($config[ 'company' ]);

        // Configurar cabeçalho do relatório
        $this->setReportHeader($config[ 'report' ]);

        // Configurar tabela de dados
        $this->setDataTable($config[ 'table' ]);

        // Configurar rodapé
        $this->setFooter($config[ 'footer' ]);

        // Configurar página
        $this->setPageSetup($config[ 'page_setup' ] ?? []);

        return $this->generateFile();
    }

    private function setCompanyHeader(array $config)
    {
        // Título do relatório
        $this->sheet->mergeCells($config[ 'title_merge' ]);
        $this->sheet->setCellValue($config[ 'title_cell' ], $config[ 'name' ]);
        $this->sheet->getStyle($config[ 'title_cell' ])->getFont()->setSize(28)->setBold(true);

        // Informações do relatório
        foreach ($config[ 'info' ] as $position => $value) {
            $this->sheet->setCellValue($position, $value);

            $rowNumber = (int) preg_replace('/[^0-9]/', '', $position);
            $this->lastRow = max($this->lastRow, $rowNumber);
        }

        // Adiciona uma linha em branco após as informações
        $this->lastRow += 2;

    }

    private function setReportHeader(array $config)
    {
        // Título do relatório
        $this->sheet->mergeCells($config[ 'title_merge' ]);
        $this->sheet->setCellValue($config[ 'title_cell' ], $config[ 'title' ]);
        $this->sheet->getStyle($config[ 'title_cell' ])->getFont()->setSize(28)->setBold(true);

        // Informações do relatório
        foreach ($config[ 'info' ] as $position => $value) {
            $this->sheet->setCellValue($position, $value);
        }
    }

    private function setDataTable(array $config)
    {
        $this->currentRow = $config[ 'start_row' ] ?? $this->lastRow + 1;
        $this->lastColumn = $this->getLastColumn();

        // Cabeçalho da tabela
        foreach ($config[ 'headers' ] as $col => $header) {
            $column = Coordinate::stringFromColumnIndex($col + 1);
            $this->sheet->setCellValue("{$column}{$this->currentRow}", $header);
            $this->sheet->getStyle("{$column}{$this->currentRow}")->applyFromArray([
                'font' => [ 'bold' => true ],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => [ 'rgb' => 'F8F9FA' ],
                ],
            ]);
        }

        // Dados
        $this->currentRow++;
        foreach ($config[ 'data' ] as $row) {
            foreach ($config[ 'columns' ] as $col => $column) {
                $this->setCellValue($col + 1, $row[ $column[ 'field' ] ], $column);
            }

            // Bordas
            $this->sheet->getStyle("A{$this->currentRow}:{$this->lastColumn}{$this->currentRow}")
                ->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                        ],
                    ],
                ]);

            $this->currentRow++;
        }

        // Totais
        if (isset($config[ 'totals' ])) {
            $this->setTotals($config[ 'totals' ]);
        }
    }

    private function setCellValue($colIndex, $value, array $options)
    {
        $column = Coordinate::stringFromColumnIndex($colIndex);
        $cell = "{$column}{$this->currentRow}";

        switch ($options[ 'type' ] ?? 'text') {
            case 'numeric_string':
                $this->sheet->setCellValueExplicit($cell, $value, DataType::TYPE_STRING);

                break;

            case 'currency':
                $this->sheet->setCellValue($cell, floatval($value));
                $this->sheet->getStyle($cell)
                    ->getNumberFormat()
                    ->setFormatCode(self::FORMAT_CURRENCY_BRL);

                break;

            case 'date':
                $this->sheet->setCellValue($cell, date('d/m/Y', strtotime($value)));

                break;

            case 'colored_text':
                $this->sheet->setCellValue($cell, $value);
                if (isset($options[ 'color' ])) {
                    $this->formatColor($cell, $options[ 'color' ]);
                }

                break;

            default:
                $this->sheet->setCellValue($cell, $value);
        }
    }

    private function setTotals(array $config)
    {
        $this->lastColumn = $this->getLastColumn();
        $this->currentRow++;
        $this->sheet->mergeCells("A{$this->currentRow}:D{$this->currentRow}");
        $this->sheet->setCellValue("A{$this->currentRow}", $config[ 'label' ]);
        $this->sheet->setCellValue("{$this->lastColumn}{$this->currentRow}", $config[ 'value' ]);
        $this->sheet->getStyle("{$this->lastColumn}{$this->currentRow}")
            ->getNumberFormat()
            ->setFormatCode(self::FORMAT_CURRENCY_BRL);
        $this->sheet->getStyle("A{$this->currentRow}:{$this->lastColumn}{$this->currentRow}")
            ->getFont()
            ->setBold(true);
    }

    private function setFooter(array $config)
    {
        $footerRow = $this->currentRow + ($config[ 'spacing' ] ?? 2);
        $this->lastColumn = $this->getLastColumn();

        // Linha divisória (opcional)

        if ($config[ 'show_divider' ] ?? true) {
            $this->sheet->mergeCells("A{$footerRow}:{$this->lastColumn}{$footerRow}");
            $this->sheet->getStyle("A{$footerRow}:{$this->lastColumn}{$footerRow}")
                ->applyFromArray([
                    'borders' => [
                        'top' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => [ 'rgb' => $config[ 'divider_color' ] ?? '000000' ],
                        ],
                    ],
                ]);
            $footerRow++;
        }

        // Informações do rodapé
        foreach ($config[ 'sections' ] as $section) {
            $merge = str_replace('{row}', $footerRow, $section[ 'merge' ]);
            $cell = str_replace('{row}', $footerRow, $section[ 'cell' ]);

            $this->sheet->mergeCells($merge);
            $this->sheet->setCellValue($cell, $section[ 'value' ]);

            // Aplicar estilos específicos da seção
            $style = [
                'font' => [
                    'size' => $section[ 'font_size' ] ?? 8,
                    'color' => [ 'rgb' => $section[ 'font_color' ] ?? '666666' ],
                    'bold' => $section[ 'bold' ] ?? false,
                ],
                'alignment' => [
                    'horizontal' => $section[ 'alignment' ] ?? Alignment::HORIZONTAL_LEFT,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ];

            $this->sheet->getStyle($cell)->applyFromArray($style);
        }

        $this->sheet->getRowDimension($footerRow)->setRowHeight($config[ 'row_height' ] ?? 20);
    }

    private function setPageSetup(array $config)
    {
        $this->lastColumn = $this->getLastColumn();
        $this->sheet->getPageSetup()
            ->setOrientation($config[ 'orientation' ] ?? PageSetup::ORIENTATION_PORTRAIT)
            ->setPaperSize($config[ 'paper_size' ] ?? PageSetup::PAPERSIZE_A4)
            ->setFitToWidth(1)
            ->setFitToHeight(0);

        $this->sheet->getPageMargins()
            ->setTop($config[ 'margins' ][ 'top' ] ?? 0.75)
            ->setRight($config[ 'margins' ][ 'right' ] ?? 0.25)
            ->setLeft($config[ 'margins' ][ 'left' ] ?? 0.25)
            ->setBottom($config[ 'margins' ][ 'bottom' ] ?? 0.75);

        foreach (range('A', $this->lastColumn) as $columnID) {
            $this->sheet->getColumnDimension($columnID)->setAutoSize(true);
        }
    }

    private function formatColor($cell, $color)
    {
        $color = ltrim($color, '#');
        $this->sheet->getStyle($cell)->getFont()->setColor(new Color($color));
    }

    private function generateFile()
    {
        $writer = new Xlsx($this->spreadsheet);
        ob_start();
        $writer->save('php://output');
        $fileContent = ob_get_clean();

        $sizeInBytes = strlen($fileContent);
        $size = [
            'bytes' => $sizeInBytes,
            'kb' => round($sizeInBytes / 1024, 2),
            'mb' => round($sizeInBytes / (1024 * 1024), 2),
        ];

        return [
            'content' => $fileContent,
            'size' => $size,
            'success' => true,
        ];
    }

    private function getLastColumn(): string
    {
        return $this->sheet->getHighestColumn();
    }

}
