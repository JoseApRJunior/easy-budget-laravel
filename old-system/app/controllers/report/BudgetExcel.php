<?php

namespace app\controllers\report;

class BudgetExcel
{
    /**
     * Summary of generateExcel
     * @param mixed $authenticated
     * @param mixed $budgets
     * @param mixed $data
     * @param mixed $date
     * @param mixed $totals
     * @param mixed $excel_name
     * @return array{company: array, footer: array, report: array, table: array}
     */
    public function generateExcel($authenticated, $budgets, $data, $date, $totals, $excel_name)
    {
        return [
            'company' => [
                'title_merge' => 'A1:C3',
                'title_cell' => 'A1',
                'name' => $authenticated->company_name,
                'info' => [
                    'A4' => "➤ {$authenticated->company_name}",
                    'A5' => $authenticated->cnpj ? "⚑ CNPJ:{$authenticated->cnpj}" : "⚑ CPF:{$authenticated->cpf}",
                    'A6' => "☎ " . ($authenticated->phone_business ?: $authenticated->phone),
                    'A7' => "✉ " . ($authenticated->email_business ?: $authenticated->email),
                ],
            ],
            'report' => [
                'title_merge' => 'D1:F3',
                'title_cell' => 'D1',
                'title' => 'Relatório de Orçamentos',
                'info' => [
                    'D4' => 'Gerado em: ' . $date->format('Ymd_H_i_s'),
                    'D5' => 'Período: ' . ($data[ 'start_date' ] && $data[ 'end_date' ]
                        ? date('d/m/Y', strtotime($data[ 'start_date' ])) . ' até ' . date('d/m/Y', strtotime($data[ 'end_date' ]))
                        : 'Todos os períodos'),
                    'D6' => 'Total de Registros: ' . count($budgets),
                ],
            ],
            'table' => [
                'headers' => [ 'Nº Orçamento', 'Cliente', 'Data Criação', 'Data Vencimento', 'Status', 'Valor Total' ],
                'columns' => [
                    [ 'field' => 'code', 'type' => 'numeric_string' ],
                    [ 'field' => 'customer_name' ],
                    [ 'field' => 'created_at', 'type' => 'date' ],
                    [ 'field' => 'due_date', 'type' => 'date' ],
                    [ 'field' => 'name', 'type' => 'colored_text', 'color' => '#color' ],
                    [ 'field' => 'total', 'type' => 'currency' ],
                ],
                'data' => $budgets,
                'totals' => [
                    'label' => 'Total:',
                    'value' => $totals[ 'sum' ],
                ],
            ],
            'footer' => [
                'sections' => [
                    [
                        'merge' => 'A{row}:C{row}',
                        'cell' => 'A{row}',
                        'value' => $excel_name,
                    ],
                ],
            ],
        ];
    }

}
