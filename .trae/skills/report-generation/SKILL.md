# 📊 Skill: Report Generation (Geração de Relatórios)

**Descrição:** Sistema completo de geração de relatórios com suporte a múltiplos formatos, filtros avançados, agendamento automático, estratégias de performance e integração com todos os módulos do sistema.

**Categoria:** Relatórios e Analytics
**Complexidade:** Média
**Status:** ✅ Implementado e Documentado

## 🎯 Objetivo

Fornecer uma solução completa para geração, exportação e distribuição de relatórios empresariais, permitindo análise de dados em tempo real, exportação em múltiplos formatos e automação de processos de reporting.

## 📋 Requisitos Técnicos

### **✅ Tipos de Relatórios: Financeiros, Operacionais, Analíticos e Personalizados**

```php
class ReportService extends AbstractBaseService
{
    public function generateReport(string $reportType, array $filters, int $tenantId): ServiceResult
    {
        return $this->safeExecute(function() use ($reportType, $filters, $tenantId) {
            // 1. Validar tipo de relatório
            $reportConfig = $this->getReportConfiguration($reportType);
            if (!$reportConfig) {
                return $this->error('Tipo de relatório inválido', OperationStatus::INVALID_DATA);
            }

            // 2. Validar filtros
            $validation = $this->validateReportFilters($reportType, $filters);
            if (!$validation->isSuccess()) {
                return $validation;
            }

            // 3. Gerar dados do relatório
            $reportData = $this->generateReportData($reportType, $filters, $tenantId);

            // 4. Formatar relatório
            $formattedReport = $this->formatReport($reportType, $reportData, $filters);

            // 5. Salvar histórico
            $this->saveReportHistory($reportType, $filters, $tenantId, $formattedReport);

            return $this->success($formattedReport, 'Relatório gerado com sucesso');
        });
    }

    private function getReportConfiguration(string $reportType): ?array
    {
        $configurations = [
            'financial_summary' => [
                'name' => 'Resumo Financeiro',
                'description' => 'Visão geral das finanças do período',
                'required_filters' => ['start_date', 'end_date'],
                'optional_filters' => ['customer_id', 'category_id'],
                'data_source' => 'FinancialReportService',
            ],
            'inventory_movements' => [
                'name' => 'Movimentação de Estoque',
                'description' => 'Entradas e saídas de produtos',
                'required_filters' => ['start_date', 'end_date'],
                'optional_filters' => ['product_id', 'movement_type'],
                'data_source' => 'InventoryReportService',
            ],
            'customer_analytics' => [
                'name' => 'Análise de Clientes',
                'description' => 'Comportamento e performance de clientes',
                'required_filters' => ['start_date', 'end_date'],
                'optional_filters' => ['customer_type', 'status'],
                'data_source' => 'CustomerReportService',
            ],
            'sales_performance' => [
                'name' => 'Performance de Vendas',
                'description' => 'Vendas por período, produtos e serviços',
                'required_filters' => ['start_date', 'end_date'],
                'optional_filters' => ['product_id', 'service_id', 'salesperson'],
                'data_source' => 'SalesReportService',
            ],
        ];

        return $configurations[$reportType] ?? null;
    }

    private function generateReportData(string $reportType, array $filters, int $tenantId): array
    {
        $config = $this->getReportConfiguration($reportType);
        $dataSource = $config['data_source'];

        // Instanciar serviço de dados específico
        $service = app($dataSource);

        // Gerar dados baseados no tipo de relatório
        return match ($reportType) {
            'financial_summary' => $service->getFinancialSummary($filters, $tenantId),
            'inventory_movements' => $service->getInventoryMovements($filters, $tenantId),
            'customer_analytics' => $service->getCustomerAnalytics($filters, $tenantId),
            'sales_performance' => $service->getSalesPerformance($filters, $tenantId),
            default => [],
        };
    }

    private function formatReport(string $reportType, array $data, array $filters): array
    {
        return [
            'type' => $reportType,
            'generated_at' => now(),
            'filters' => $filters,
            'data' => $data,
            'summary' => $this->calculateReportSummary($reportType, $data),
            'charts' => $this->generateReportCharts($reportType, $data),
        ];
    }

    private function calculateReportSummary(string $reportType, array $data): array
    {
        return match ($reportType) {
            'financial_summary' => $this->calculateFinancialSummary($data),
            'inventory_movements' => $this->calculateInventorySummary($data),
            'customer_analytics' => $this->calculateCustomerSummary($data),
            'sales_performance' => $this->calculateSalesSummary($data),
            default => [],
        };
    }
}
```

### **✅ Formatos de Exportação: PDF, Excel, CSV com Formatação Adequada**

```php
class ReportExportService extends AbstractBaseService
{
    public function exportReport(array $reportData, string $format, int $tenantId): ServiceResult
    {
        return $this->safeExecute(function() use ($reportData, $format, $tenantId) {
            // 1. Validar formato
            $validation = $this->validateExportFormat($format);
            if (!$validation->isSuccess()) {
                return $validation;
            }

            // 2. Gerar conteúdo do relatório
            $content = $this->generateExportContent($reportData, $format);

            // 3. Salvar arquivo
            $filePath = $this->saveExportFile($content, $format, $reportData['type'], $tenantId);

            // 4. Gerar URL de download
            $downloadUrl = $this->generateDownloadUrl($filePath);

            return $this->success([
                'file_path' => $filePath,
                'download_url' => $downloadUrl,
                'format' => $format,
                'size' => strlen($content),
            ], 'Relatório exportado com sucesso');
        });
    }

    private function generateExportContent(array $reportData, string $format): string
    {
        return match ($format) {
            'pdf' => $this->generatePdfContent($reportData),
            'excel' => $this->generateExcelContent($reportData),
            'csv' => $this->generateCsvContent($reportData),
            default => throw new Exception('Formato de exportação não suportado'),
        };
    }

    private function generatePdfContent(array $reportData): string
    {
        // Configurar PDF
        $pdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 10,
            'margin_right' => 10,
            'margin_top' => 30,
            'margin_bottom' => 10,
            'margin_header' => 5,
            'margin_footer' => 5,
        ]);

        // Definir tema
        $pdf->SetDefaultBodyCSS('font-family', 'Arial, sans-serif');
        $pdf->SetDefaultBodyCSS('font-size', '12pt');
        $pdf->SetDefaultBodyCSS('color', '#333');

        // Cabeçalho
        $header = view('reports.pdf.header', [
            'reportType' => $reportData['type'],
            'generatedAt' => $reportData['generated_at'],
            'filters' => $reportData['filters'],
        ])->render();
        $pdf->SetHTMLHeader($header);

        // Conteúdo
        $content = view('reports.pdf.content', [
            'reportData' => $reportData,
        ])->render();
        $pdf->WriteHTML($content);

        // Rodapé
        $footer = view('reports.pdf.footer', [
            'pageCount' => $pdf->page,
        ])->render();
        $pdf->SetHTMLFooter($footer);

        return $pdf->Output('', 'S');
    }

    private function generateExcelContent(array $reportData): string
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Configurar propriedades
        $spreadsheet->getProperties()
            ->setTitle("Relatório {$reportData['type']}")
            ->setSubject("Relatório gerado em {$reportData['generated_at']}")
            ->setCreator("Easy Budget Laravel")
            ->setLastModifiedBy("Easy Budget Laravel")
            ->setDescription("Relatório {$reportData['type']}");

        // Cabeçalho do relatório
        $this->addExcelHeader($sheet, $reportData);

        // Dados do relatório
        $this->addExcelData($sheet, $reportData);

        // Formatação
        $this->formatExcelSheet($sheet, $reportData);

        // Salvar como string
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        ob_start();
        $writer->save('php://output');
        return ob_get_clean();
    }

    private function generateCsvContent(array $reportData): string
    {
        $output = fopen('php://temp', 'r+');

        // Cabeçalho CSV
        $headers = $this->getCsvHeaders($reportData['type']);
        fputcsv($output, $headers, ';', '"');

        // Dados
        foreach ($reportData['data'] as $row) {
            $rowData = $this->formatCsvRow($row, $reportData['type']);
            fputcsv($output, $rowData, ';', '"');
        }

        rewind($output);
        $csvContent = stream_get_contents($output);
        fclose($output);

        return $csvContent;
    }

    private function addExcelHeader($sheet, array $reportData): void
    {
        // Título
        $sheet->setCellValue('A1', "Relatório: {$reportData['type']}");
        $sheet->mergeCells('A1:Z1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);

        // Informações do relatório
        $sheet->setCellValue('A2', "Gerado em: {$reportData['generated_at']}");
        $sheet->setCellValue('A3', "Filtros aplicados: " . json_encode($reportData['filters']));

        // Espaçamento
        $sheet->getRowDimension(4)->setRowHeight(10);
    }

    private function addExcelData($sheet, array $reportData): void
    {
        $row = 5;

        // Cabeçalhos das colunas
        $headers = $this->getExcelHeaders($reportData['type']);
        foreach ($headers as $col => $header) {
            $sheet->setCellValue("{$col}{$row}", $header);
            $sheet->getStyle("{$col}{$row}")->getFont()->setBold(true);
        }

        // Dados
        $row++;
        foreach ($reportData['data'] as $dataRow) {
            $col = 'A';
            foreach ($headers as $headerKey => $headerLabel) {
                $value = $this->formatExcelValue($dataRow[$headerKey] ?? '', $headerKey);
                $sheet->setCellValue("{$col}{$row}", $value);
                $col++;
            }
            $row++;
        }
    }

    private function formatExcelSheet($sheet, array $reportData): void
    {
        // Auto-size columns
        foreach (range('A', 'Z') as $columnID) {
            $sheet->getColumnDimension($columnID)->setAutoSize(true);
        }

        // Borda para cabeçalhos
        $highestColumn = $sheet->getHighestColumn();
        $highestRow = $sheet->getHighestRow();

        $styleArray = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => '000000'],
                ],
            ],
        ];

        $sheet->getStyle("A5:{$highestColumn}5")->applyFromArray($styleArray);
    }

    private function getCsvHeaders(string $reportType): array
    {
        return match ($reportType) {
            'financial_summary' => ['Data', 'Descrição', 'Entrada', 'Saída', 'Saldo'],
            'inventory_movements' => ['Data', 'Produto', 'Tipo', 'Quantidade', 'Motivo'],
            'customer_analytics' => ['Cliente', 'Total Comprado', 'Última Compra', 'Status'],
            'sales_performance' => ['Data', 'Produto/Serviço', 'Quantidade', 'Valor Total'],
            default => ['Coluna 1', 'Coluna 2', 'Coluna 3'],
        };
    }

    private function formatCsvRow(array $row, string $reportType): array
    {
        return match ($reportType) {
            'financial_summary' => [
                $row['date'] ?? '',
                $row['description'] ?? '',
                $row['income'] ?? '',
                $row['expense'] ?? '',
                $row['balance'] ?? '',
            ],
            'inventory_movements' => [
                $row['date'] ?? '',
                $row['product_name'] ?? '',
                $row['type'] ?? '',
                $row['quantity'] ?? '',
                $row['reason'] ?? '',
            ],
            'customer_analytics' => [
                $row['customer_name'] ?? '',
                $row['total_spent'] ?? '',
                $row['last_purchase'] ?? '',
                $row['status'] ?? '',
            ],
            'sales_performance' => [
                $row['date'] ?? '',
                $row['product_service'] ?? '',
                $row['quantity'] ?? '',
                $row['total_value'] ?? '',
            ],
            default => array_values($row),
        };
    }

    private function formatExcelValue($value, string $fieldType): string
    {
        if (is_numeric($value)) {
            return number_format($value, 2, ',', '.');
        }

        if (is_string($value) && strtotime($value)) {
            return date('d/m/Y', strtotime($value));
        }

        return $value;
    }
}
```

### **✅ Filtros e Parametrização: Como Implementar Filtros Avançados e Parâmetros de Relatório**

```php
class ReportFilterService extends AbstractBaseService
{
    public function validateReportFilters(string $reportType, array $filters): ServiceResult
    {
        return $this->safeExecute(function() use ($reportType, $filters) {
            $config = $this->getReportConfiguration($reportType);

            // 1. Validar filtros obrigatórios
            foreach ($config['required_filters'] as $requiredFilter) {
                if (!isset($filters[$requiredFilter]) || empty($filters[$requiredFilter])) {
                    return $this->error("Filtro obrigatório ausente: {$requiredFilter}", OperationStatus::INVALID_DATA);
                }
            }

            // 2. Validar tipos de filtros
            $validation = $this->validateFilterTypes($reportType, $filters);
            if (!$validation->isSuccess()) {
                return $validation;
            }

            // 3. Validar valores dos filtros
            $validation = $this->validateFilterValues($reportType, $filters);
            if (!$validation->isSuccess()) {
                return $validation;
            }

            return $this->success($filters, 'Filtros válidos');
        });
    }

    private function validateFilterTypes(string $reportType, array $filters): ServiceResult
    {
        $allowedFilters = $this->getAllowedFilters($reportType);
        $issues = [];

        foreach ($filters as $filterName => $filterValue) {
            if (!isset($allowedFilters[$filterName])) {
                $issues[] = "Filtro desconhecido: {$filterName}";
                continue;
            }

            $filterConfig = $allowedFilters[$filterName];
            $validation = $this->validateFilterType($filterValue, $filterConfig['type']);

            if (!$validation->isSuccess()) {
                $issues[] = "Tipo inválido para filtro {$filterName}: {$validation->getMessage()}";
            }
        }

        if (!empty($issues)) {
            return $this->error(implode('; ', $issues), OperationStatus::INVALID_DATA);
        }

        return $this->success(null, 'Tipos de filtros válidos');
    }

    private function validateFilterValues(string $reportType, array $filters): ServiceResult
    {
        $allowedFilters = $this->getAllowedFilters($reportType);
        $issues = [];

        foreach ($filters as $filterName => $filterValue) {
            if (!isset($allowedFilters[$filterName])) {
                continue;
            }

            $filterConfig = $allowedFilters[$filterName];

            // Validar valores permitidos
            if (isset($filterConfig['allowed_values']) && !in_array($filterValue, $filterConfig['allowed_values'])) {
                $issues[] = "Valor inválido para filtro {$filterName}: {$filterValue}";
            }

            // Validar range de valores
            if (isset($filterConfig['min']) && $filterValue < $filterConfig['min']) {
                $issues[] = "Valor mínimo para {$filterName}: {$filterConfig['min']}";
            }

            if (isset($filterConfig['max']) && $filterValue > $filterConfig['max']) {
                $issues[] = "Valor máximo para {$filterName}: {$filterConfig['max']}";
            }
        }

        if (!empty($issues)) {
            return $this->error(implode('; ', $issues), OperationStatus::INVALID_DATA);
        }

        return $this->success(null, 'Valores de filtros válidos');
    }

    private function getAllowedFilters(string $reportType): array
    {
        return match ($reportType) {
            'financial_summary' => [
                'start_date' => ['type' => 'date', 'required' => true],
                'end_date' => ['type' => 'date', 'required' => true],
                'customer_id' => ['type' => 'integer', 'required' => false],
                'category_id' => ['type' => 'integer', 'required' => false],
                'status' => ['type' => 'string', 'required' => false, 'allowed_values' => ['paid', 'pending', 'cancelled']],
            ],
            'inventory_movements' => [
                'start_date' => ['type' => 'date', 'required' => true],
                'end_date' => ['type' => 'date', 'required' => true],
                'product_id' => ['type' => 'integer', 'required' => false],
                'movement_type' => ['type' => 'string', 'required' => false, 'allowed_values' => ['in', 'out', 'adjustment']],
            ],
            'customer_analytics' => [
                'start_date' => ['type' => 'date', 'required' => true],
                'end_date' => ['type' => 'date', 'required' => true],
                'customer_type' => ['type' => 'string', 'required' => false, 'allowed_values' => ['individual', 'company']],
                'status' => ['type' => 'string', 'required' => false, 'allowed_values' => ['active', 'inactive', 'pending']],
            ],
            'sales_performance' => [
                'start_date' => ['type' => 'date', 'required' => true],
                'end_date' => ['type' => 'date', 'required' => true],
                'product_id' => ['type' => 'integer', 'required' => false],
                'service_id' => ['type' => 'integer', 'required' => false],
                'salesperson' => ['type' => 'string', 'required' => false],
            ],
            default => [],
        };
    }

    private function validateFilterType($value, string $expectedType): ServiceResult
    {
        return match ($expectedType) {
            'date' => $this->validateDateFilter($value),
            'integer' => $this->validateIntegerFilter($value),
            'string' => $this->validateStringFilter($value),
            'array' => $this->validateArrayFilter($value),
            default => $this->success(null, 'Tipo de filtro válido'),
        };
    }

    private function validateDateFilter($value): ServiceResult
    {
        if (!strtotime($value)) {
            return $this->error('Data inválida', OperationStatus::INVALID_DATA);
        }

        $date = new \DateTime($value);
        $minDate = new \DateTime('2020-01-01');
        $maxDate = new \DateTime('2030-12-31');

        if ($date < $minDate || $date > $maxDate) {
            return $this->error('Data fora do intervalo permitido (2020-2030)', OperationStatus::INVALID_DATA);
        }

        return $this->success(null, 'Data válida');
    }

    private function validateIntegerFilter($value): ServiceResult
    {
        if (!is_numeric($value) || intval($value) != $value) {
            return $this->error('Valor deve ser um número inteiro', OperationStatus::INVALID_DATA);
        }

        if ($value < 0) {
            return $this->error('Valor não pode ser negativo', OperationStatus::INVALID_DATA);
        }

        return $this->success(null, 'Número inteiro válido');
    }

    private function validateStringFilter($value): ServiceResult
    {
        if (!is_string($value)) {
            return $this->error('Valor deve ser uma string', OperationStatus::INVALID_DATA);
        }

        if (strlen($value) > 255) {
            return $this->error('String muito longa (máximo 255 caracteres)', OperationStatus::INVALID_DATA);
        }

        return $this->success(null, 'String válida');
    }

    private function validateArrayFilter($value): ServiceResult
    {
        if (!is_array($value)) {
            return $this->error('Valor deve ser um array', OperationStatus::INVALID_DATA);
        }

        if (empty($value)) {
            return $this->error('Array não pode ser vazio', OperationStatus::INVALID_DATA);
        }

        return $this->success(null, 'Array válido');
    }
}
```

### **✅ Agendamento de Relatórios: Como Criar Relatórios Programados e Automáticos**

```php
class ReportScheduleService extends AbstractBaseService
{
    public function scheduleReport(array $scheduleData, int $tenantId): ServiceResult
    {
        return $this->safeExecute(function() use ($scheduleData, $tenantId) {
            // 1. Validar dados do agendamento
            $validation = $this->validateScheduleData($scheduleData);
            if (!$validation->isSuccess()) {
                return $validation;
            }

            // 2. Criar registro de agendamento
            $schedule = ReportSchedule::create([
                'tenant_id' => $tenantId,
                'user_id' => auth()->id(),
                'report_type' => $scheduleData['report_type'],
                'filters' => $scheduleData['filters'],
                'schedule_type' => $scheduleData['schedule_type'],
                'schedule_config' => $scheduleData['schedule_config'],
                'recipients' => $scheduleData['recipients'],
                'formats' => $scheduleData['formats'],
                'status' => 'active',
                'next_run_at' => $this->calculateNextRun($scheduleData),
            ]);

            // 3. Agendar job no Laravel Scheduler
            $this->scheduleJob($schedule);

            return $this->success($schedule, 'Relatório agendado com sucesso');
        });
    }

    public function runScheduledReports(): ServiceResult
    {
        return $this->safeExecute(function() {
            $now = now();
            $dueSchedules = ReportSchedule::where('status', 'active')
                ->where('next_run_at', '<=', $now)
                ->get();

            $results = [];

            foreach ($dueSchedules as $schedule) {
                try {
                    $result = $this->executeScheduledReport($schedule);
                    $results[] = $result;

                    // Atualizar próximo run
                    $schedule->update([
                        'last_run_at' => $now,
                        'next_run_at' => $this->calculateNextRun($schedule->toArray()),
                    ]);

                } catch (\Exception $e) {
                    Log::error("Erro ao executar relatório agendado {$schedule->id}: {$e->getMessage()}");
                    $results[] = ['schedule_id' => $schedule->id, 'status' => 'failed', 'error' => $e->getMessage()];
                }
            }

            return $this->success($results, 'Execução de relatórios agendados concluída');
        });
    }

    private function executeScheduledReport(ReportSchedule $schedule): array
    {
        // 1. Gerar relatório
        $reportResult = $this->reportService->generateReport(
            $schedule->report_type,
            $schedule->filters,
            $schedule->tenant_id
        );

        if (!$reportResult->isSuccess()) {
            throw new \Exception("Falha ao gerar relatório: {$reportResult->getMessage()}");
        }

        $reportData = $reportResult->getData();

        // 2. Exportar em formatos solicitados
        $exportResults = [];
        foreach ($schedule->formats as $format) {
            $exportResult = $this->exportService->exportReport($reportData, $format, $schedule->tenant_id);

            if ($exportResult->isSuccess()) {
                $exportResults[] = $exportResult->getData();
            }
        }

        // 3. Enviar por e-mail
        if (!empty($schedule->recipients)) {
            $this->sendReportEmail($schedule, $reportData, $exportResults);
        }

        return [
            'schedule_id' => $schedule->id,
            'status' => 'success',
            'report_type' => $schedule->report_type,
            'exports' => $exportResults,
        ];
    }

    private function sendReportEmail(ReportSchedule $schedule, array $reportData, array $exportResults): void
    {
        foreach ($schedule->recipients as $recipient) {
            Mail::to($recipient)->send(new ScheduledReportMail($schedule, $reportData, $exportResults));
        }
    }

    private function calculateNextRun(array $scheduleData): \DateTime
    {
        $now = new \DateTime();
        $scheduleType = $scheduleData['schedule_type'];
        $config = $scheduleData['schedule_config'];

        return match ($scheduleType) {
            'daily' => $this->calculateDailySchedule($now, $config),
            'weekly' => $this->calculateWeeklySchedule($now, $config),
            'monthly' => $this->calculateMonthlySchedule($now, $config),
            'custom' => $this->calculateCustomSchedule($now, $config),
            default => $now->modify('+1 day'),
        };
    }

    private function calculateDailySchedule(\DateTime $now, array $config): \DateTime
    {
        $nextRun = clone $now;
        $nextRun->modify('+1 day');
        $nextRun->setTime($config['hour'] ?? 9, $config['minute'] ?? 0);
        return $nextRun;
    }

    private function calculateWeeklySchedule(\DateTime $now, array $config): \DateTime
    {
        $nextRun = clone $now;
        $nextRun->modify('+1 week');
        $nextRun->modify('next ' . $config['day_of_week']);
        $nextRun->setTime($config['hour'] ?? 9, $config['minute'] ?? 0);
        return $nextRun;
    }

    private function calculateMonthlySchedule(\DateTime $now, array $config): \DateTime
    {
        $nextRun = clone $now;
        $nextRun->modify('+1 month');
        $nextRun->setDate($nextRun->format('Y'), $nextRun->format('m'), $config['day_of_month'] ?? 1);
        $nextRun->setTime($config['hour'] ?? 9, $config['minute'] ?? 0);
        return $nextRun;
    }

    private function calculateCustomSchedule(\DateTime $now, array $config): \DateTime
    {
        $interval = $config['interval'] ?? '1 day';
        $nextRun = clone $now;
        $nextRun->modify("+{$interval}");
        $nextRun->setTime($config['hour'] ?? 9, $config['minute'] ?? 0);
        return $nextRun;
    }

    private function validateScheduleData(array $scheduleData): ServiceResult
    {
        $issues = [];

        if (empty($scheduleData['report_type'])) {
            $issues[] = 'Tipo de relatório é obrigatório';
        }

        if (empty($scheduleData['schedule_type'])) {
            $issues[] = 'Tipo de agendamento é obrigatório';
        }

        if (empty($scheduleData['recipients'])) {
            $issues[] = 'Destinatários são obrigatórios';
        }

        if (empty($scheduleData['formats'])) {
            $issues[] = 'Formatos de exportação são obrigatórios';
        }

        if (!empty($issues)) {
            return $this->error(implode('; ', $issues), OperationStatus::INVALID_DATA);
        }

        return $this->success(null, 'Dados de agendamento válidos');
    }
}
```

### **✅ Performance e Cache: Estratégias para Relatórios com Grandes Volumes de Dados**

```php
class ReportCacheService extends AbstractBaseService
{
    public function getCachedReport(string $reportType, array $filters, int $tenantId): ServiceResult
    {
        return $this->safeExecute(function() use ($reportType, $filters, $tenantId) {
            $cacheKey = $this->generateCacheKey($reportType, $filters, $tenantId);
            $cacheTTL = $this->getCacheTTL($reportType);

            // 1. Verificar cache
            if (Cache::has($cacheKey)) {
                $cachedData = Cache::get($cacheKey);
                return $this->success($cachedData, 'Relatório obtido do cache');
            }

            // 2. Gerar relatório
            $reportData = $this->generateReportData($reportType, $filters, $tenantId);

            // 3. Armazenar em cache
            Cache::put($cacheKey, $reportData, $cacheTTL);

            return $this->success($reportData, 'Relatório gerado e armazenado em cache');
        });
    }

    public function invalidateReportCache(string $reportType, int $tenantId): ServiceResult
    {
        return $this->safeExecute(function() use ($reportType, $tenantId) {
            // 1. Identificar chaves de cache relacionadas
            $cacheKeys = $this->getRelatedCacheKeys($reportType, $tenantId);

            // 2. Invalidar cache
            foreach ($cacheKeys as $key) {
                Cache::forget($key);
            }

            return $this->success(['invalidated_keys' => count($cacheKeys)], 'Cache invalidado');
        });
    }

    private function generateCacheKey(string $reportType, array $filters, int $tenantId): string
    {
        $filterHash = md5(json_encode($filters));
        return "report:{$reportType}:tenant:{$tenantId}:filters:{$filterHash}";
    }

    private function getCacheTTL(string $reportType): int
    {
        return match ($reportType) {
            'financial_summary' => 300, // 5 minutos
            'inventory_movements' => 600, // 10 minutos
            'customer_analytics' => 1800, // 30 minutos
            'sales_performance' => 900, // 15 minutos
            default => 300, // 5 minutos padrão
        };
    }

    private function getRelatedCacheKeys(string $reportType, int $tenantId): array
    {
        $pattern = "report:{$reportType}:tenant:{$tenantId}:*";
        return Cache::getRedis()->keys($pattern);
    }

    public function optimizeReportQuery(string $reportType, array $filters, int $tenantId): ServiceResult
    {
        return $this->safeExecute(function() use ($reportType, $filters, $tenantId) {
            // 1. Identificar índices necessários
            $requiredIndexes = $this->getRequiredIndexes($reportType, $filters);

            // 2. Verificar se índices existem
            $missingIndexes = $this->checkMissingIndexes($requiredIndexes);

            // 3. Otimizar query
            $optimizedQuery = $this->buildOptimizedQuery($reportType, $filters, $tenantId);

            // 4. Executar com profiling
            $executionTime = $this->executeWithProfiling($optimizedQuery);

            return $this->success([
                'execution_time' => $executionTime,
                'missing_indexes' => $missingIndexes,
                'query' => $optimizedQuery,
            ], 'Query otimizada');
        });
    }

    private function buildOptimizedQuery(string $reportType, array $filters, int $tenantId): \Illuminate\Database\Query\Builder
    {
        return match ($reportType) {
            'financial_summary' => $this->buildFinancialQuery($filters, $tenantId),
            'inventory_movements' => $this->buildInventoryQuery($filters, $tenantId),
            'customer_analytics' => $this->buildCustomerQuery($filters, $tenantId),
            'sales_performance' => $this->buildSalesQuery($filters, $tenantId),
            default => throw new \Exception('Tipo de relatório não suportado'),
        };
    }

    private function buildFinancialQuery(array $filters, int $tenantId): \Illuminate\Database\Query\Builder
    {
        return DB::table('invoices')
            ->select([
                'invoices.created_at as date',
                'invoices.description',
                'invoices.total as income',
                DB::raw('0 as expense'),
                'invoices.total as balance'
            ])
            ->where('invoices.tenant_id', $tenantId)
            ->whereBetween('invoices.created_at', [$filters['start_date'], $filters['end_date']])
            ->union(
                DB::table('payments')
                    ->select([
                        'payments.created_at as date',
                        'payments.description',
                        DB::raw('0 as income'),
                        'payments.amount as expense',
                        DB::raw('-payments.amount as balance')
                    ])
                    ->where('payments.tenant_id', $tenantId)
                    ->whereBetween('payments.created_at', [$filters['start_date'], $filters['end_date']])
            )
            ->orderBy('date');
    }

    private function buildInventoryQuery(array $filters, int $tenantId): \Illuminate\Database\Query\Builder
    {
        return DB::table('inventory_movements')
            ->join('products', 'inventory_movements.product_id', '=', 'products.id')
            ->select([
                'inventory_movements.created_at as date',
                'products.name as product_name',
                'inventory_movements.type',
                'inventory_movements.quantity',
                'inventory_movements.reason'
            ])
            ->where('inventory_movements.tenant_id', $tenantId)
            ->whereBetween('inventory_movements.created_at', [$filters['start_date'], $filters['end_date']])
            ->when($filters['product_id'] ?? null, function ($query, $productId) {
                return $query->where('inventory_movements.product_id', $productId);
            })
            ->when($filters['movement_type'] ?? null, function ($query, $movementType) {
                return $query->where('inventory_movements.type', $movementType);
            })
            ->orderBy('inventory_movements.created_at', 'desc');
    }

    private function buildCustomerQuery(array $filters, int $tenantId): \Illuminate\Database\Query\Builder
    {
        return DB::table('customers')
            ->join('invoices', 'customers.id', '=', 'invoices.customer_id')
            ->select([
                'customers.name as customer_name',
                DB::raw('SUM(invoices.total) as total_spent'),
                DB::raw('MAX(invoices.created_at) as last_purchase'),
                'customers.status'
            ])
            ->where('customers.tenant_id', $tenantId)
            ->where('invoices.tenant_id', $tenantId)
            ->whereBetween('invoices.created_at', [$filters['start_date'], $filters['end_date']])
            ->groupBy('customers.id', 'customers.name', 'customers.status')
            ->orderBy('total_spent', 'desc');
    }

    private function buildSalesQuery(array $filters, int $tenantId): \Illuminate\Database\Query\Builder
    {
        return DB::table('budget_items')
            ->join('products', 'budget_items.product_id', '=', 'products.id')
            ->join('budgets', 'budget_items.budget_id', '=', 'budgets.id')
            ->select([
                'budgets.created_at as date',
                'products.name as product_service',
                'budget_items.quantity',
                'budget_items.total as total_value'
            ])
            ->where('budgets.tenant_id', $tenantId)
            ->where('products.tenant_id', $tenantId)
            ->whereBetween('budgets.created_at', [$filters['start_date'], $filters['end_date']])
            ->union(
                DB::table('service_items')
                    ->join('services', 'service_items.service_id', '=', 'services.id')
                    ->join('products', 'service_items.product_id', '=', 'products.id')
                    ->select([
                        'services.created_at as date',
                        'products.name as product_service',
                        'service_items.quantity',
                        'service_items.total as total_value'
                    ])
                    ->where('services.tenant_id', $tenantId)
                    ->where('products.tenant_id', $tenantId)
                    ->whereBetween('services.created_at', [$filters['start_date'], $filters['end_date']])
            )
            ->orderBy('date');
    }

    private function executeWithProfiling(\Illuminate\Database\Query\Builder $query): float
    {
        $startTime = microtime(true);

        // Habilitar profiling
        DB::enableQueryLog();

        $result = $query->get();

        $executionTime = microtime(true) - $startTime;

        // Log de performance
        Log::info('Report query execution', [
            'execution_time' => $executionTime,
            'query' => DB::getQueryLog(),
            'result_count' => count($result),
        ]);

        return $executionTime;
    }
}
```

### **✅ Integrações: Como Relatórios se Relacionam com Orçamentos, Faturas, Clientes e Produtos**

```php
class ReportIntegrationService extends AbstractBaseService
{
    public function getReportDataFromBudgets(array $filters, int $tenantId): array
    {
        return $this->safeExecute(function() use ($filters, $tenantId) {
            $query = Budget::with(['customer', 'items.product'])
                ->where('tenant_id', $tenantId)
                ->whereBetween('created_at', [$filters['start_date'], $filters['end_date']]);

            // Filtros adicionais
            if (isset($filters['customer_id'])) {
                $query->where('customer_id', $filters['customer_id']);
            }

            if (isset($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            $budgets = $query->get();

            return [
                'total_budgets' => $budgets->count(),
                'total_value' => $budgets->sum('total'),
                'by_status' => $this->calculateBudgetsByStatus($budgets),
                'by_customer' => $this->calculateBudgetsByCustomer($budgets),
                'by_product' => $this->calculateBudgetsByProduct($budgets),
            ];
        });
    }

    public function getReportDataFromInvoices(array $filters, int $tenantId): array
    {
        return $this->safeExecute(function() use ($filters, $tenantId) {
            $query = Invoice::with(['customer', 'items.product'])
                ->where('tenant_id', $tenantId)
                ->whereBetween('created_at', [$filters['start_date'], $filters['end_date']]);

            // Filtros adicionais
            if (isset($filters['customer_id'])) {
                $query->where('customer_id', $filters['customer_id']);
            }

            if (isset($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            $invoices = $query->get();

            return [
                'total_invoices' => $invoices->count(),
                'total_value' => $invoices->sum('total'),
                'paid_value' => $invoices->where('status', 'paid')->sum('total'),
                'pending_value' => $invoices->where('status', 'pending')->sum('total'),
                'by_status' => $this->calculateInvoicesByStatus($invoices),
                'by_customer' => $this->calculateInvoicesByCustomer($invoices),
                'by_month' => $this->calculateInvoicesByMonth($invoices),
            ];
        });
    }

    public function getReportDataFromCustomers(array $filters, int $tenantId): array
    {
        return $this->safeExecute(function() use ($filters, $tenantId) {
            $query = Customer::with(['invoices', 'budgets'])
                ->where('tenant_id', $tenantId);

            // Filtros adicionais
            if (isset($filters['status'])) {
                $query->where('status', $filters['status']);
            }

            if (isset($filters['customer_type'])) {
                $query->whereHas('commonData', function($q) use ($filters) {
                    $q->where('type', $filters['customer_type']);
                });
            }

            $customers = $query->get();

            return [
                'total_customers' => $customers->count(),
                'active_customers' => $customers->where('status', 'active')->count(),
                'by_status' => $this->calculateCustomersByStatus($customers),
                'by_type' => $this->calculateCustomersByType($customers),
                'avg_customer_value' => $this->calculateAverageCustomerValue($customers),
                'top_customers' => $this->getTopCustomers($customers),
            ];
        });
    }

    public function getReportDataFromProducts(array $filters, int $tenantId): array
    {
        return $this->safeExecute(function() use ($filters, $tenantId) {
            $query = Product::with(['inventory', 'categories'])
                ->where('tenant_id', $tenantId);

            // Filtros adicionais
            if (isset($filters['category_id'])) {
                $query->whereHas('categories', function($q) use ($filters) {
                    $q->where('categories.id', $filters['category_id']);
                });
            }

            if (isset($filters['type'])) {
                $query->where('type', $filters['type']);
            }

            $products = $query->get();

            return [
                'total_products' => $products->count(),
                'physical_products' => $products->where('type', 'physical')->count(),
                'service_products' => $products->where('type', 'service')->count(),
                'total_stock_value' => $this->calculateTotalStockValue($products),
                'low_stock_products' => $this->getLowStockProducts($products),
                'by_category' => $this->calculateProductsByCategory($products),
                'by_type' => $this->calculateProductsByType($products),
            ];
        });
    }

    private function calculateBudgetsByStatus($budgets): array
    {
        return $budgets->groupBy('status')->map(function($group) {
            return [
                'count' => $group->count(),
                'total_value' => $group->sum('total'),
                'avg_value' => $group->avg('total'),
            ];
        })->toArray();
    }

    private function calculateBudgetsByCustomer($budgets): array
    {
        return $budgets->groupBy('customer_id')->map(function($group) {
            return [
                'customer_name' => $group->first()->customer->name,
                'total_budgets' => $group->count(),
                'total_value' => $group->sum('total'),
                'avg_value' => $group->avg('total'),
            ];
        })->toArray();
    }

    private function calculateBudgetsByProduct($budgets): array
    {
        $productStats = [];

        foreach ($budgets as $budget) {
            foreach ($budget->items as $item) {
                $productName = $item->product->name;

                if (!isset($productStats[$productName])) {
                    $productStats[$productName] = [
                        'product_name' => $productName,
                        'total_quantity' => 0,
                        'total_value' => 0,
                        'avg_price' => 0,
                        'occurrences' => 0,
                    ];
                }

                $productStats[$productName]['total_quantity'] += $item->quantity;
                $productStats[$productName]['total_value'] += $item->total;
                $productStats[$productName]['occurrences']++;
            }
        }

        // Calcular médias
        foreach ($productStats as &$stats) {
            $stats['avg_price'] = $stats['total_value'] / $stats['total_quantity'];
        }

        return array_values($productStats);
    }

    private function calculateInvoicesByStatus($invoices): array
    {
        return $invoices->groupBy('status')->map(function($group) {
            return [
                'count' => $group->count(),
                'total_value' => $group->sum('total'),
                'percentage' => round(($group->sum('total') / $invoices->sum('total')) * 100, 2),
            ];
        })->toArray();
    }

    private function calculateInvoicesByCustomer($invoices): array
    {
        return $invoices->groupBy('customer_id')->map(function($group) {
            return [
                'customer_name' => $group->first()->customer->name,
                'total_invoices' => $group->count(),
                'total_value' => $group->sum('total'),
                'paid_value' => $group->where('status', 'paid')->sum('total'),
                'pending_value' => $group->where('status', 'pending')->sum('total'),
            ];
        })->toArray();
    }

    private function calculateInvoicesByMonth($invoices): array
    {
        return $invoices->groupBy(function($invoice) {
            return $invoice->created_at->format('Y-m');
        })->map(function($group, $month) {
            return [
                'month' => $month,
                'total_invoices' => $group->count(),
                'total_value' => $group->sum('total'),
                'paid_value' => $group->where('status', 'paid')->sum('total'),
            ];
        })->toArray();
    }

    private function calculateCustomersByStatus($customers): array
    {
        return $customers->groupBy('status')->map(function($group) {
            return [
                'count' => $group->count(),
                'percentage' => round(($group->count() / $customers->count()) * 100, 2),
            ];
        })->toArray();
    }

    private function calculateCustomersByType($customers): array
    {
        return $customers->groupBy(function($customer) {
            return $customer->commonData->type;
        })->map(function($group, $type) {
            return [
                'type' => $type,
                'count' => $group->count(),
                'percentage' => round(($group->count() / $customers->count()) * 100, 2),
            ];
        })->toArray();
    }

    private function calculateAverageCustomerValue($customers): float
    {
        $totalValue = 0;
        $activeCustomers = 0;

        foreach ($customers as $customer) {
            $customerValue = $customer->invoices->sum('total');
            if ($customerValue > 0) {
                $totalValue += $customerValue;
                $activeCustomers++;
            }
        }

        return $activeCustomers > 0 ? $totalValue / $activeCustomers : 0;
    }

    private function getTopCustomers($customers, int $limit = 10): array
    {
        return $customers->map(function($customer) {
            return [
                'customer_name' => $customer->commonData->first_name . ' ' . $customer->commonData->last_name,
                'total_spent' => $customer->invoices->sum('total'),
                'total_budgets' => $customer->budgets->count(),
                'last_purchase' => $customer->invoices->max('created_at'),
            ];
        })->sortByDesc('total_spent')->take($limit)->toArray();
    }

    private function calculateTotalStockValue($products): float
    {
        $totalValue = 0;

        foreach ($products as $product) {
            if ($product->type === 'physical' && $product->inventory) {
                $totalValue += $product->price * $product->inventory->quantity;
            }
        }

        return $totalValue;
    }

    private function getLowStockProducts($products): array
    {
        return $products->filter(function($product) {
            return $product->type === 'physical' &&
                   $product->inventory &&
                   $product->inventory->quantity <= $product->inventory->min_quantity;
        })->map(function($product) {
            return [
                'product_name' => $product->name,
                'current_stock' => $product->inventory->quantity,
                'min_quantity' => $product->inventory->min_quantity,
                'shortage' => $product->inventory->min_quantity - $product->inventory->quantity,
            ];
        })->toArray();
    }

    private function calculateProductsByCategory($products): array
    {
        return $products->groupBy(function($product) {
            return $product->categories->first()->name ?? 'Sem Categoria';
        })->map(function($group, $categoryName) {
            return [
                'category_name' => $categoryName,
                'product_count' => $group->count(),
                'total_stock' => $group->sum(function($product) {
                    return $product->type === 'physical' && $product->inventory ? $product->inventory->quantity : 0;
                }),
                'total_value' => $group->sum(function($product) {
                    return $product->type === 'physical' && $product->inventory ?
                           $product->price * $product->inventory->quantity : 0;
                }),
            ];
        })->toArray();
    }

    private function calculateProductsByType($products): array
    {
        return $products->groupBy('type')->map(function($group, $type) {
            return [
                'type' => $type,
                'count' => $group->count(),
                'total_stock' => $group->sum(function($product) {
                    return $product->type === 'physical' && $product->inventory ? $product->inventory->quantity : 0;
                }),
                'total_value' => $group->sum(function($product) {
                    return $product->type === 'physical' && $product->inventory ?
                           $product->price * $product->inventory->quantity : 0;
                }),
            ];
        })->toArray();
    }
}
```

### **✅ Dashboards: Como Criar Dashboards Executivos com Métricas em Tempo Real**

```php
class DashboardService extends AbstractBaseService
{
    public function getExecutiveDashboard(int $tenantId, array $filters = []): ServiceResult
    {
        return $this->safeExecute(function() use ($tenantId, $filters) {
            $dashboardData = [
                'summary' => $this->getDashboardSummary($tenantId, $filters),
                'charts' => $this->getDashboardCharts($tenantId, $filters),
                'metrics' => $this->getDashboardMetrics($tenantId, $filters),
                'alerts' => $this->getDashboardAlerts($tenantId, $filters),
                'recent_activity' => $this->getRecentActivity($tenantId, $filters),
            ];

            return $this->success($dashboardData, 'Dashboard executivo gerado');
        });
    }

    private function getDashboardSummary(int $tenantId, array $filters): array
    {
        $startDate = $filters['start_date'] ?? now()->startOfMonth();
        $endDate = $filters['end_date'] ?? now()->endOfMonth();

        return [
            'total_revenue' => $this->calculateTotalRevenue($tenantId, $startDate, $endDate),
            'total_expenses' => $this->calculateTotalExpenses($tenantId, $startDate, $endDate),
            'net_profit' => $this->calculateNetProfit($tenantId, $startDate, $endDate),
            'active_customers' => $this->getActiveCustomersCount($tenantId, $startDate, $endDate),
            'pending_invoices' => $this->getPendingInvoicesCount($tenantId),
            'overdue_invoices' => $this->getOverdueInvoicesCount($tenantId),
            'total_products' => $this->getTotalProductsCount($tenantId),
            'low_stock_products' => $this->getLowStockProductsCount($tenantId),
        ];
    }

    private function getDashboardCharts(int $tenantId, array $filters): array
    {
        return [
            'revenue_by_month' => $this->getRevenueByMonthChart($tenantId, $filters),
            'sales_by_category' => $this->getSalesByCategoryChart($tenantId, $filters),
            'customer_growth' => $this->getCustomerGrowthChart($tenantId, $filters),
            'product_performance' => $this->getProductPerformanceChart($tenantId, $filters),
            'invoice_status' => $this->getInvoiceStatusChart($tenantId, $filters),
        ];
    }

    private function getDashboardMetrics(int $tenantId, array $filters): array
    {
        return [
            'revenue_growth' => $this->calculateRevenueGrowth($tenantId, $filters),
            'customer_retention' => $this->calculateCustomerRetention($tenantId, $filters),
            'average_order_value' => $this->calculateAverageOrderValue($tenantId, $filters),
            'conversion_rate' => $this->calculateConversionRate($tenantId, $filters),
            'inventory_turnover' => $this->calculateInventoryTurnover($tenantId, $filters),
            'days_sales_outstanding' => $this->calculateDaysSalesOutstanding($tenantId, $filters),
        ];
    }

    private function getDashboardAlerts(int $tenantId, array $filters): array
    {
        $alerts = [];

        // Alertas de financeiro
        $pendingInvoices = $this->getPendingInvoicesCount($tenantId);
        if ($pendingInvoices > 10) {
            $alerts[] = [
                'type' => 'warning',
                'category' => 'financeiro',
                'message' => "Existem {$pendingInvoices} faturas pendentes",
                'action_url' => route('invoices.index', ['status' => 'pending']),
            ];
        }

        // Alertas de estoque
        $lowStockCount = $this->getLowStockProductsCount($tenantId);
        if ($lowStockCount > 0) {
            $alerts[] = [
                'type' => 'danger',
                'category' => 'estoque',
                'message' => "Existem {$lowStockCount} produtos com estoque baixo",
                'action_url' => route('products.index'),
            ];
        }

        // Alertas de clientes
        $inactiveCustomers = $this->getInactiveCustomersCount($tenantId);
        if ($inactiveCustomers > 20) {
            $alerts[] = [
                'type' => 'info',
                'category' => 'clientes',
                'message' => "Existem {$inactiveCustomers} clientes inativos nos últimos 90 dias",
                'action_url' => route('customers.index', ['status' => 'inactive']),
            ];
        }

        return $alerts;
    }

    private function getRecentActivity(int $tenantId, array $filters): array
    {
        return [
            'latest_invoices' => $this->getLatestInvoices($tenantId, 5),
            'latest_budgets' => $this->getLatestBudgets($tenantId, 5),
            'latest_customers' => $this->getLatestCustomers($tenantId, 5),
            'latest_movements' => $this->getLatestMovements($tenantId, 5),
        ];
    }

    private function calculateTotalRevenue(int $tenantId, $startDate, $endDate): float
    {
        return Invoice::where('tenant_id', $tenantId)
            ->where('status', 'paid')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('total') ?? 0;
    }

    private function calculateTotalExpenses(int $tenantId, $startDate, $endDate): float
    {
        return Payment::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->sum('amount') ?? 0;
    }

    private function calculateNetProfit(int $tenantId, $startDate, $endDate): float
    {
        $revenue = $this->calculateTotalRevenue($tenantId, $startDate, $endDate);
        $expenses = $this->calculateTotalExpenses($tenantId, $startDate, $endDate);
        return $revenue - $expenses;
    }

    private function getActiveCustomersCount(int $tenantId, $startDate, $endDate): int
    {
        return Customer::where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->whereHas('invoices', function($query) use ($startDate, $endDate) {
                $query->whereBetween('created_at', [$startDate, $endDate]);
            })
            ->count();
    }

    private function getPendingInvoicesCount(int $tenantId): int
    {
        return Invoice::where('tenant_id', $tenantId)
            ->where('status', 'pending')
            ->count();
    }

    private function getOverdueInvoicesCount(int $tenantId): int
    {
        return Invoice::where('tenant_id', $tenantId)
            ->where('status', 'pending')
            ->where('due_date', '<', now())
            ->count();
    }

    private function getTotalProductsCount(int $tenantId): int
    {
        return Product::where('tenant_id', $tenantId)->count();
    }

    private function getLowStockProductsCount(int $tenantId): int
    {
        return Product::where('tenant_id', $tenantId)
            ->where('type', 'physical')
            ->whereHas('inventory', function($query) {
                $query->whereColumn('quantity', '<=', 'min_quantity');
            })
            ->count();
    }

    private function getRevenueByMonthChart(int $tenantId, array $filters): array
    {
        $months = [];
        $revenues = [];

        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $startOfMonth = $date->startOfMonth();
            $endOfMonth = $date->endOfMonth();

            $months[] = $date->format('M/Y');
            $revenues[] = $this->calculateTotalRevenue($tenantId, $startOfMonth, $endOfMonth);
        }

        return [
            'labels' => $months,
            'data' => $revenues,
            'title' => 'Receita por Mês (Últimos 12 Meses)',
        ];
    }

    private function getSalesByCategoryChart(int $tenantId, array $filters): array
    {
        $categories = Category::where('tenant_id', $tenantId)->get();
        $categorySales = [];

        foreach ($categories as $category) {
            $sales = Invoice::where('tenant_id', $tenantId)
                ->whereHas('items.product.categories', function($query) use ($category) {
                    $query->where('categories.id', $category->id);
                })
                ->sum('total') ?? 0;

            if ($sales > 0) {
                $categorySales[] = [
                    'name' => $category->name,
                    'value' => $sales,
                ];
            }
        }

        return [
            'data' => $categorySales,
            'title' => 'Vendas por Categoria',
        ];
    }

    private function getCustomerGrowthChart(int $tenantId, array $filters): array
    {
        $months = [];
        $customerCounts = [];

        for ($i = 11; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $startOfMonth = $date->startOfMonth();
            $endOfMonth = $date->endOfMonth();

            $months[] = $date->format('M/Y');
            $customerCounts[] = Customer::where('tenant_id', $tenantId)
                ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                ->count();
        }

        return [
            'labels' => $months,
            'data' => $customerCounts,
            'title' => 'Crescimento de Clientes (Últimos 12 Meses)',
        ];
    }

    private function getProductPerformanceChart(int $tenantId, array $filters): array
    {
        $products = Product::where('tenant_id', $tenantId)
            ->withSum('budgetItems', 'quantity')
            ->orderBy('budget_items_sum_quantity', 'desc')
            ->limit(10)
            ->get();

        return [
            'labels' => $products->pluck('name'),
            'data' => $products->pluck('budget_items_sum_quantity'),
            'title' => 'Produtos Mais Vendidos',
        ];
    }

    private function getInvoiceStatusChart(int $tenantId, array $filters): array
    {
        $statusCounts = Invoice::where('tenant_id', $tenantId)
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $chartData = [];
        foreach (['paid', 'pending', 'cancelled'] as $status) {
            $chartData[] = [
                'status' => $status,
                'count' => $statusCounts[$status] ?? 0,
            ];
        }

        return [
            'data' => $chartData,
            'title' => 'Status das Faturas',
        ];
    }

    private function calculateRevenueGrowth(int $tenantId, array $filters): float
    {
        $currentMonth = now()->startOfMonth();
        $previousMonth = now()->subMonth()->startOfMonth();

        $currentRevenue = $this->calculateTotalRevenue($tenantId, $currentMonth, $currentMonth->endOfMonth());
        $previousRevenue = $this->calculateTotalRevenue($tenantId, $previousMonth, $previousMonth->endOfMonth());

        if ($previousRevenue == 0) {
            return $currentRevenue > 0 ? 100 : 0;
        }

        return round((($currentRevenue - $previousRevenue) / $previousRevenue) * 100, 2);
    }

    private function calculateCustomerRetention(int $tenantId, array $filters): float
    {
        $currentMonth = now()->startOfMonth();
        $previousMonth = now()->subMonth()->startOfMonth();

        $currentCustomers = Customer::where('tenant_id', $tenantId)
            ->whereHas('invoices', function($query) use ($currentMonth) {
                $query->whereBetween('created_at', [$currentMonth, $currentMonth->endOfMonth()]);
            })
            ->pluck('id')
            ->toArray();

        $previousCustomers = Customer::where('tenant_id', $tenantId)
            ->whereHas('invoices', function($query) use ($previousMonth) {
                $query->whereBetween('created_at', [$previousMonth, $previousMonth->endOfMonth()]);
            })
            ->pluck('id')
            ->toArray();

        $retainedCustomers = array_intersect($currentCustomers, $previousCustomers);

        if (empty($previousCustomers)) {
            return 0;
        }

        return round((count($retainedCustomers) / count($previousCustomers)) * 100, 2);
    }

    private function calculateAverageOrderValue(int $tenantId, array $filters): float
    {
        $totalRevenue = $this->calculateTotalRevenue($tenantId, $filters['start_date'] ?? now()->startOfMonth(), $filters['end_date'] ?? now()->endOfMonth());
        $totalOrders = Invoice::where('tenant_id', $tenantId)
            ->where('status', 'paid')
            ->whereBetween('created_at', [$filters['start_date'] ?? now()->startOfMonth(), $filters['end_date'] ?? now()->endOfMonth()])
            ->count();

        return $totalOrders > 0 ? round($totalRevenue / $totalOrders, 2) : 0;
    }

    private function calculateConversionRate(int $tenantId, array $filters): float
    {
        $totalBudgets = Budget::where('tenant_id', $tenantId)
            ->whereBetween('created_at', [$filters['start_date'] ?? now()->startOfMonth(), $filters['end_date'] ?? now()->endOfMonth()])
            ->count();

        $convertedBudgets = Budget::where('tenant_id', $tenantId)
            ->where('status', 'converted')
            ->whereBetween('created_at', [$filters['start_date'] ?? now()->startOfMonth(), $filters['end_date'] ?? now()->endOfMonth()])
            ->count();

        return $totalBudgets > 0 ? round(($convertedBudgets / $totalBudgets) * 100, 2) : 0;
    }

    private function calculateInventoryTurnover(int $tenantId, array $filters): float
    {
        $totalSales = $this->calculateTotalRevenue($tenantId, $filters['start_date'] ?? now()->startOfMonth(), $filters['end_date'] ?? now()->endOfMonth());

        $avgInventoryValue = Product::where('tenant_id', $tenantId)
            ->where('type', 'physical')
            ->with('inventory')
            ->get()
            ->sum(function($product) {
                return $product->inventory ? $product->price * $product->inventory->quantity : 0;
            });

        return $avgInventoryValue > 0 ? round($totalSales / $avgInventoryValue, 2) : 0;
    }

    private function calculateDaysSalesOutstanding(int $tenantId, array $filters): float
    {
        $totalReceivables = Invoice::where('tenant_id', $tenantId)
            ->where('status', 'pending')
            ->sum('total') ?? 0;

        $totalSales = $this->calculateTotalRevenue($tenantId, $filters['start_date'] ?? now()->startOfMonth(), $filters['end_date'] ?? now()->endOfMonth());

        $dailySales = $totalSales / 30; // Média diária

        return $dailySales > 0 ? round($totalReceivables / $dailySales, 2) : 0;
    }

    private function getInactiveCustomersCount(int $tenantId): int
    {
        return Customer::where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->whereDoesntHave('invoices', function($query) {
                $query->where('created_at', '>=', now()->subDays(90));
            })
            ->count();
    }

    private function getLatestInvoices(int $tenantId, int $limit): array
    {
        return Invoice::where('tenant_id', $tenantId)
            ->with('customer')
            ->latest()
            ->limit($limit)
            ->get()
            ->map(function($invoice) {
                return [
                    'id' => $invoice->id,
                    'customer_name' => $invoice->customer->name,
                    'total' => $invoice->total,
                    'status' => $invoice->status,
                    'created_at' => $invoice->created_at,
                ];
            })
            ->toArray();
    }

    private function getLatestBudgets(int $tenantId, int $limit): array
    {
        return Budget::where('tenant_id', $tenantId)
            ->with('customer')
            ->latest()
            ->limit($limit)
            ->get()
            ->map(function($budget) {
                return [
                    'id' => $budget->id,
                    'customer_name' => $budget->customer->name,
                    'total' => $budget->total,
                    'status' => $budget->status,
                    'created_at' => $budget->created_at,
                ];
            })
            ->toArray();
    }

    private function getLatestCustomers(int $tenantId, int $limit): array
    {
        return Customer::where('tenant_id', $tenantId)
            ->with('commonData')
            ->latest()
            ->limit($limit)
            ->get()
            ->map(function($customer) {
                return [
                    'id' => $customer->id,
                    'name' => $customer->commonData->first_name . ' ' . $customer->commonData->last_name,
                    'status' => $customer->status,
                    'created_at' => $customer->created_at,
                ];
            })
            ->toArray();
    }

    private function getLatestMovements(int $tenantId, int $limit): array
    {
        return InventoryMovement::where('tenant_id', $tenantId)
            ->with('product')
            ->latest()
            ->limit($limit)
            ->get()
            ->map(function($movement) {
                return [
                    'id' => $movement->id,
                    'product_name' => $movement->product->name,
                    'type' => $movement->type,
                    'quantity' => $movement->quantity,
                    'reason' => $movement->reason,
                    'created_at' => $movement->created_at,
                ];
            })
            ->toArray();
    }
}
```

## 🧪 Testes e Validação

### **✅ Testes de Geração de Relatórios**

```php
public function testFinancialReportGeneration()
{
    $tenant = Tenant::factory()->create();

    // Criar dados de teste
    Invoice::factory()->count(5)->create(['tenant_id' => $tenant->id, 'status' => 'paid']);
    Invoice::factory()->count(3)->create(['tenant_id' => $tenant->id, 'status' => 'pending']);

    $filters = [
        'start_date' => now()->startOfMonth(),
        'end_date' => now()->endOfMonth(),
    ];

    $result = $this->reportService->generateReport('financial_summary', $filters, $tenant->id);
    $this->assertTrue($result->isSuccess());

    $reportData = $result->getData();
    $this->assertArrayHasKey('type', $reportData);
    $this->assertArrayHasKey('data', $reportData);
    $this->assertArrayHasKey('summary', $reportData);
}

public function testReportExportToPdf()
{
    Storage::fake('public');

    $tenant = Tenant::factory()->create();
    $reportData = [
        'type' => 'financial_summary',
        'data' => [],
        'summary' => [],
    ];

    $result = $this->exportService->exportReport($reportData, 'pdf', $tenant->id);
    $this->assertTrue($result->isSuccess());

    $exportData = $result->getData();
    $this->assertEquals('pdf', $exportData['format']);
    $this->assertNotNull($exportData['download_url']);
}

public function testReportScheduling()
{
    $tenant = Tenant::factory()->create();

    $scheduleData = [
        'report_type' => 'financial_summary',
        'filters' => [
            'start_date' => now()->startOfMonth(),
            'end_date' => now()->endOfMonth(),
        ],
        'schedule_type' => 'monthly',
        'schedule_config' => [
            'day_of_month' => 1,
            'hour' => 9,
            'minute' => 0,
        ],
        'recipients' => ['admin@empresa.com'],
        'formats' => ['pdf', 'excel'],
    ];

    $result = $this->scheduleService->scheduleReport($scheduleData, $tenant->id);
    $this->assertTrue($result->isSuccess());

    $schedule = $result->getData();
    $this->assertEquals('financial_summary', $schedule->report_type);
    $this->assertEquals('active', $schedule->status);
}

public function testReportCache()
{
    Cache::clear();

    $tenant = Tenant::factory()->create();
    $filters = [
        'start_date' => now()->startOfMonth(),
        'end_date' => now()->endOfMonth(),
    ];

    // Primeira chamada - deve gerar relatório
    $result1 = $this->cacheService->getCachedReport('financial_summary', $filters, $tenant->id);
    $this->assertTrue($result1->isSuccess());

    // Segunda chamada - deve usar cache
    $result2 = $this->cacheService->getCachedReport('financial_summary', $filters, $tenant->id);
    $this->assertTrue($result2->isSuccess());

    // Verificar que os dados são iguais
    $this->assertEquals($result1->getData(), $result2->getData());
}

public function testDashboardGeneration()
{
    $tenant = Tenant::factory()->create();

    // Criar dados de teste
    Customer::factory()->count(10)->create(['tenant_id' => $tenant->id]);
    Invoice::factory()->count(20)->create(['tenant_id' => $tenant->id]);

    $result = $this->dashboardService->getExecutiveDashboard($tenant->id);
    $this->assertTrue($result->isSuccess());

    $dashboardData = $result->getData();
    $this->assertArrayHasKey('summary', $dashboardData);
    $this->assertArrayHasKey('charts', $dashboardData);
    $this->assertArrayHasKey('metrics', $dashboardData);
    $this->assertArrayHasKey('alerts', $dashboardData);
}
```

## 🚀 Implementação Gradual

### **Fase 1: Foundation**
- [ ] Implementar ReportService básico
- [ ] Sistema de validação de filtros
- [ ] Exportação em PDF básico
- [ ] Cache simples para relatórios

### **Fase 2: Core Features**
- [ ] Exportação em Excel e CSV
- [ ] Sistema de agendamento de relatórios
- [ ] Dashboard executivo básico
- [ ] Integração com módulos principais

### **Fase 3: Advanced Features**
- [ ] Charts e visualizações avançadas
- [ ] Métricas de performance
- [ ] Alertas e notificações
- [ ] Histórico de relatórios

### **Fase 4: Integration**
- [ ] API RESTful completa
- [ ] Exportação em lote
- [ ] Templates de relatórios personalizados
- [ ] Integração com sistemas externos

## 📚 Documentação Relacionada

- [ReportService](../../app/Services/Domain/ReportService.php)
- [ReportExportService](../../app/Services/Infrastructure/ReportExportService.php)
- [ReportFilterService](../../app/Services/Domain/ReportFilterService.php)
- [ReportScheduleService](../../app/Services/Domain/ReportScheduleService.php)
- [ReportCacheService](../../app/Services/Infrastructure/ReportCacheService.php)
- [ReportIntegrationService](../../app/Services/Domain/ReportIntegrationService.php)
- [DashboardService](../../app/Services/Domain/DashboardService.php)
- [Report Model](../../app/Models/Report.php)
- [ReportSchedule Model](../../app/Models/ReportSchedule.php)

## 🎯 Benefícios

### **✅ Análise de Dados Completa**
- Relatórios financeiros detalhados
- Análise de performance de vendas
- Métricas de clientes e produtos
- Dashboards executivos em tempo real

### **✅ Flexibilidade de Exportação**
- Múltiplos formatos (PDF, Excel, CSV)
- Formatação profissional
- Dados estruturados e organizados
- Compatibilidade com ferramentas externas

### **✅ Automatização de Processos**
- Relatórios agendados automaticamente
- Distribuição por e-mail programada
- Cache inteligente para performance
- Integração com workflows empresariais

### **✅ Tomada de Decisão Inteligente**
- Dados em tempo real
- Métricas de performance claras
- Alertas proativos
- Histórico de tendências

---

**Última atualização:** 11/01/2026
**Versão:** 1.0.0
**Status:** ✅ Implementado e em uso
