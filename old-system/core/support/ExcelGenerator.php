<?php

namespace core\support;

use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExcelGenerator
{
    private const FORMAT_CURRENCY_BRL = 'R$ #,##0.00';

    public function __construct(
        private Spreadsheet $spreadsheet,
    ) {}

    public function generateBudgetReport( $authenticated, $budgets, $filters, $totals )
    {
        $sheet = $this->spreadsheet->getActiveSheet();

        // Cabeçalho da empresa
        $sheet->mergeCells( 'A1:C3' );
        $sheet->setCellValue( 'A1', $authenticated->company_name );
        $sheet->getStyle( 'A1' )->getFont()->setSize( 24 )->setBold( true );

        // Informações da empresa
        $sheet->setCellValue( 'A4', '➤ ' . $authenticated->company_name );
        $sheet->setCellValue( 'A5', $authenticated->cnpj ? "⚑ CNPJ:{$authenticated->cnpj}" : "⚑ CPF:{$authenticated->cpf}" );
        $sheet->setCellValue( 'A6', '☎ ' . ( $authenticated->business_phone ? $authenticated->business_phone : $authenticated->phone ) );
        $sheet->setCellValue( 'A7', '✉ ' . ( $authenticated->email_business ? $authenticated->email_business : $authenticated->email ) );

        // Título do relatório
        $sheet->mergeCells( 'D1:F3' );
        $sheet->setCellValue( 'D1', 'Relatório de Orçamentos' );
        $sheet->getStyle( 'D1' )->getFont()->setSize( 28 )->setBold( true );

        // Informações do relatório
        $sheet->setCellValue( 'D4', 'Gerado em: ' . date( 'd/m/Y H:i:s' ) );
        $sheet->setCellValue( 'D5', 'Período: ' . ( $filters[ 'period' ] ?? 'Todos os períodos' ) );
        $sheet->setCellValue( 'D6', 'Total de Registros: ' . count( $budgets ) );

        // Linha divisória
        $sheet->mergeCells( 'A9:F9' );

        // Cabeçalho da tabela
        $headers = [ 
            'Nº Orçamento',
            'Cliente',
            'Data Criação',
            'Data Vencimento',
            'Status',
            'Valor Total'
        ];

        $row = 11;
        foreach ( $headers as $col => $header ) {
            $column = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex( $col + 1 );
            $sheet->setCellValue( $column . $row, $header );
            $sheet->getStyle( $column . $row )->applyFromArray( [ 
                'font' => [ 'bold' => true ],
                'fill' => [ 
                    'fillType'   => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => [ 'rgb' => 'F8F9FA' ]
                ]
            ] );
        }

        // Dados
        $row++;
        foreach ( $budgets as $budget ) {
            $sheet->setCellValueExplicit(
                'A' . $row,
                $budget[ 'code' ],
                DataType::TYPE_STRING,
            );
            $sheet->setCellValue( 'B' . $row, $budget[ 'customer_name' ] );
            $sheet->setCellValue( 'C' . $row, date( 'd/m/Y', strtotime( $budget[ 'created_at' ] ) ) );
            $sheet->setCellValue( 'D' . $row, date( 'd/m/Y', strtotime( $budget[ 'due_date' ] ) ) );
            $sheet->setCellValue( 'E' . $row, $budget[ 'name' ] ); // Mudado de status para name
            $sheet->setCellValue( 'F' . $row, floatval( $budget[ 'total' ] ) );

            // Formatar valor como moeda
            $sheet->getStyle( 'F' . $row )
                ->getNumberFormat()
                ->setFormatCode( self::FORMAT_CURRENCY_BRL );

            // Estilo do status
            $this->formatStatus( $sheet, 'E' . $row, $budget[ 'name' ], $budget[ 'color' ] ); // Adicionado color

            // Adicionar bordas
            $sheet->getStyle( "A{$row}:F{$row}" )->applyFromArray( [ 
                'borders' => [ 
                    'allBorders' => [ 
                        'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    ],
                ],
            ] );

            $row++;
        }

        // Totais
        $row++;
        $sheet->mergeCells( 'A' . $row . ':D' . $row );
        $sheet->setCellValue( 'A' . $row, 'Total:' );
        $sheet->setCellValue( 'F' . $row, $totals[ 'sum' ] );
        $sheet->getStyle( 'f' . $row )->getNumberFormat()->setFormatCode( self::FORMAT_CURRENCY_BRL );
        $sheet->getStyle( 'A' . $row . ':f' . $row )->getFont()->setBold( true );

        // Ajustar largura das colunas
        foreach ( range( 'A', 'F' ) as $col ) {
            $sheet->getColumnDimension( $col )->setAutoSize( true );
        }

        // Criar o arquivo
        $writer = new Xlsx( $this->spreadsheet );

        ob_start();
        $writer->save( 'php://output' );
        return ob_get_clean();
    }

    private function formatStatus( $sheet, $cell, $status, $color )
    {
        // Remover o # do início do código de cor se existir
        $color = ltrim( $color, '#' );

        $sheet->getStyle( $cell )->getFont()->setColor(
            new \PhpOffice\PhpSpreadsheet\Style\Color( $color ),
        );
    }

}
