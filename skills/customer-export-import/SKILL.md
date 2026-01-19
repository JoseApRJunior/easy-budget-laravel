# 📤 Skill: Customer Export and Import (Exportação e Importação)

**Descrição:** Sistema completo de exportação e importação de dados de clientes em múltiplos formatos, com validação de dados, mapeamento de campos e processamento em lote.

**Categoria:** Importação/Exportação de Dados
**Complexidade:** Média
**Status:** ✅ Implementado e Documentado

## 🎯 Objetivo

Fornecer ferramentas robustas para exportação e importação de dados de clientes em diversos formatos, garantindo integridade dos dados e facilitando a migração e integração com outros sistemas.

## 📋 Requisitos Técnicos

### **✅ Sistema de Exportação**

```php
class CustomerExportService extends AbstractBaseService
{
    public function exportCustomers(int $tenantId, string $format, array $filters = [], array $fields = []): ServiceResult
    {
        return $this->safeExecute(function() use ($tenantId, $format, $filters, $fields) {
            // 1. Validar parâmetros de exportação
            $validation = $this->validateExportParameters($format, $fields);
            if (!$validation->isSuccess()) {
                return $validation;
            }

            // 2. Obter dados dos clientes
            $customers = $this->getCustomersForExport($tenantId, $filters);
            if ($customers->isEmpty()) {
                return $this->error('Nenhum cliente encontrado para exportação', OperationStatus::NOT_FOUND);
            }

            // 3. Mapear campos para exportação
            $mappedData = $this->mapExportFields($customers, $fields);

            // 4. Gerar arquivo no formato especificado
            $exportResult = $this->generateExportFile($mappedData, $format, $tenantId);

            if (!$exportResult->isSuccess()) {
                return $exportResult;
            }

            // 5. Criar registro de exportação
            $exportRecord = $this->createExportRecord($tenantId, $format, $filters, $fields, $exportResult->getData());

            return $this->success([
                'export_record' => $exportRecord,
                'file_path' => $exportResult->getData()['file_path'],
                'file_name' => $exportResult->getData()['file_name'],
                'total_records' => $customers->count(),
                'exported_at' => now(),
            ], 'Exportação de clientes concluída');
        });
    }

    public function exportCustomerPortfolio(Customer $customer, string $format): ServiceResult
    {
        return $this->safeExecute(function() use ($customer, $format) {
            // 1. Obter portfólio completo do cliente
            $portfolio = $this->getCustomerPortfolioForExport($customer);

            // 2. Gerar exportação do portfólio
            $exportResult = $this->generatePortfolioExport($portfolio, $format, $customer);

            if (!$exportResult->isSuccess()) {
                return $exportResult;
            }

            return $this->success([
                'file_path' => $exportResult->getData()['file_path'],
                'file_name' => $exportResult->getData()['file_name'],
                'portfolio_data' => $portfolio,
            ], 'Exportação do portfólio do cliente concluída');
        });
    }

    public function exportCustomerBatch(array $customerIds, string $format): ServiceResult
    {
        return $this->safeExecute(function() use ($customerIds, $format) {
            // 1. Validar IDs de clientes
            $validation = $this->validateCustomerIds($customerIds);
            if (!$validation->isSuccess()) {
                return $validation;
            }

            // 2. Obter clientes
            $customers = Customer::whereIn('id', $customerIds)->get();

            // 3. Gerar exportação em lote
            $exportResult = $this->generateBatchExport($customers, $format);

            if (!$exportResult->isSuccess()) {
                return $exportResult;
            }

            return $this->success([
                'file_path' => $exportResult->getData()['file_path'],
                'file_name' => $exportResult->getData()['file_name'],
                'total_customers' => $customers->count(),
            ], 'Exportação em lote concluída');
        });
    }

    private function validateExportParameters(string $format, array $fields): ServiceResult
    {
        // Validar formato suportado
        $supportedFormats = ['csv', 'xlsx', 'json', 'pdf'];
        if (!in_array($format, $supportedFormats)) {
            return $this->error('Formato de exportação não suportado', OperationStatus::INVALID_DATA);
        }

        // Validar campos selecionados
        $availableFields = $this->getAvailableExportFields();
        $invalidFields = array_diff($fields, $availableFields);

        if (!empty($invalidFields)) {
            return $this->error('Campos inválidos para exportação: ' . implode(', ', $invalidFields), OperationStatus::INVALID_DATA);
        }

        return $this->success(null, 'Parâmetros de exportação válidos');
    }

    private function getCustomersForExport(int $tenantId, array $filters): Collection
    {
        $query = Customer::where('tenant_id', $tenantId)
            ->with(['commonData', 'contact', 'address', 'businessData']);

        // Aplicar filtros
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        if (isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function($q) use ($search) {
                $q->whereHas('commonData', function($subquery) use ($search) {
                    $subquery->where('first_name', 'like', "%{$search}%")
                             ->orWhere('last_name', 'like', "%{$search}%")
                             ->orWhere('company_name', 'like', "%{$search}%");
                })->orWhereHas('contact', function($subquery) use ($search) {
                    $subquery->where('email', 'like', "%{$search}%")
                             ->orWhere('phone', 'like', "%{$search}%");
                });
            });
        }

        return $query->get();
    }

    private function mapExportFields(Collection $customers, array $fields): array
    {
        $mappedData = [];

        foreach ($customers as $customer) {
            $rowData = [];

            foreach ($fields as $field) {
                $rowData[$field] = $this->getFieldValue($customer, $field);
            }

            $mappedData[] = $rowData;
        }

        return $mappedData;
    }

    private function getFieldValue(Customer $customer, string $field): mixed
    {
        // Mapeamento de campos para exportação
        $fieldMapping = [
            // Dados do cliente
            'customer_id' => $customer->id,
            'customer_status' => $customer->status,
            'customer_type' => $customer->type,
            'customer_created_at' => $customer->created_at?->format('Y-m-d H:i:s'),
            'customer_updated_at' => $customer->updated_at?->format('Y-m-d H:i:s'),

            // Dados comuns
            'first_name' => $customer->commonData?->first_name,
            'last_name' => $customer->commonData?->last_name,
            'full_name' => $customer->commonData?->first_name . ' ' . $customer->commonData?->last_name,
            'cpf' => $customer->commonData?->cpf,
            'cnpj' => $customer->commonData?->cnpj,
            'company_name' => $customer->commonData?->company_name,
            'birth_date' => $customer->commonData?->birth_date?->format('Y-m-d'),
            'area_of_activity' => $customer->commonData?->areaOfActivity?->name,
            'profession' => $customer->commonData?->profession?->name,

            // Contatos
            'email' => $customer->contact?->email,
            'phone' => $customer->contact?->phone,
            'phone_business' => $customer->contact?->phone_business,
            'email_business' => $customer->contact?->email_business,
            'website' => $customer->contact?->website,

            // Endereço
            'address' => $customer->address?->address,
            'address_number' => $customer->address?->address_number,
            'neighborhood' => $customer->address?->neighborhood,
            'city' => $customer->address?->city,
            'state' => $customer->address?->state,
            'cep' => $customer->address?->cep,

            // Dados empresariais
            'business_name' => $customer->businessData?->business_name,
            'business_phone' => $customer->businessData?->business_phone,
            'business_email' => $customer->businessData?->business_email,
            'business_address' => $customer->businessData?->business_address,
            'business_address_number' => $customer->businessData?->business_address_number,
            'business_neighborhood' => $customer->businessData?->business_neighborhood,
            'business_city' => $customer->businessData?->business_city,
            'business_state' => $customer->businessData?->business_state,
            'business_cep' => $customer->businessData?->business_cep,

            // Estatísticas
            'total_budgets' => $customer->total_budgets,
            'total_services' => $customer->total_services,
            'total_invoices' => $customer->total_invoices,
            'total_revenue' => $customer->total_revenue,
            'pending_amount' => $customer->pending_amount,
            'last_interaction_at' => $customer->last_interaction_at?->format('Y-m-d H:i:s'),
            'last_budget_at' => $customer->last_budget_at?->format('Y-m-d H:i:s'),
            'last_service_at' => $customer->last_service_at?->format('Y-m-d H:i:s'),
            'last_invoice_at' => $customer->last_invoice_at?->format('Y-m-d H:i:s'),
        ];

        return $fieldMapping[$field] ?? null;
    }

    private function generateExportFile(array $data, string $format, int $tenantId): ServiceResult
    {
        $fileName = $this->generateFileName($format);
        $filePath = storage_path('app/public/exports/' . $fileName);

        // Criar diretório se não existir
        $directory = dirname($filePath);
        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }

        try {
            switch ($format) {
                case 'csv':
                    return $this->generateCSV($data, $filePath);
                case 'xlsx':
                    return $this->generateExcel($data, $filePath);
                case 'json':
                    return $this->generateJSON($data, $filePath);
                case 'pdf':
                    return $this->generatePDF($data, $filePath, $tenantId);
                default:
                    return $this->error('Formato não suportado', OperationStatus::INVALID_DATA);
            }
        } catch (Exception $e) {
            return $this->error('Erro ao gerar arquivo: ' . $e->getMessage(), OperationStatus::INTERNAL_ERROR);
        }
    }

    private function generateCSV(array $data, string $filePath): ServiceResult
    {
        $file = fopen($filePath, 'w');

        if (!$file) {
            return $this->error('Não foi possível criar o arquivo CSV', OperationStatus::FILE_ERROR);
        }

        // Escrever cabeçalho
        if (!empty($data)) {
            fputcsv($file, array_keys($data[0]));
        }

        // Escrever dados
        foreach ($data as $row) {
            fputcsv($file, $row);
        }

        fclose($file);

        return $this->success([
            'file_path' => $filePath,
            'file_name' => basename($filePath),
        ], 'Arquivo CSV gerado com sucesso');
    }

    private function generateExcel(array $data, string $filePath): ServiceResult
    {
        try {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();

            // Escrever cabeçalho
            if (!empty($data)) {
                $headers = array_keys($data[0]);
                $sheet->fromArray($headers, null, 'A1');

                // Escrever dados
                $sheet->fromArray($data, null, 'A2');

                // Formatar cabeçalho
                $headerStyle = [
                    'font' => ['bold' => true],
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'DDDDDD']],
                ];
                $sheet->getStyle('A1:' . $sheet->getHighestColumn() . '1')->applyFromArray($headerStyle);
            }

            // Salvar arquivo
            $writer = new Xlsx($spreadsheet);
            $writer->save($filePath);

            return $this->success([
                'file_path' => $filePath,
                'file_name' => basename($filePath),
            ], 'Arquivo Excel gerado com sucesso');

        } catch (Exception $e) {
            return $this->error('Erro ao gerar arquivo Excel: ' . $e->getMessage(), OperationStatus::INTERNAL_ERROR);
        }
    }

    private function generateJSON(array $data, string $filePath): ServiceResult
    {
        $jsonContent = json_encode([
            'export_date' => now()->toISOString(),
            'total_records' => count($data),
            'data' => $data,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        if (file_put_contents($filePath, $jsonContent) === false) {
            return $this->error('Não foi possível criar o arquivo JSON', OperationStatus::FILE_ERROR);
        }

        return $this->success([
            'file_path' => $filePath,
            'file_name' => basename($filePath),
        ], 'Arquivo JSON gerado com sucesso');
    }

    private function generatePDF(array $data, string $filePath, int $tenantId): ServiceResult
    {
        try {
            $mpdf = new Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'margin_left' => 10,
                'margin_right' => 10,
                'margin_top' => 10,
                'margin_bottom' => 10,
            ]);

            $tenant = Tenant::find($tenantId);
            $html = $this->generatePDFContent($data, $tenant);

            $mpdf->WriteHTML($html);
            $mpdf->Output($filePath, 'F');

            return $this->success([
                'file_path' => $filePath,
                'file_name' => basename($filePath),
            ], 'Arquivo PDF gerado com sucesso');

        } catch (Exception $e) {
            return $this->error('Erro ao gerar arquivo PDF: ' . $e->getMessage(), OperationStatus::INTERNAL_ERROR);
        }
    }

    private function generatePDFContent(array $data, Tenant $tenant): string
    {
        $html = '<!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; }
                .header { text-align: center; margin-bottom: 20px; }
                .tenant-name { font-size: 18px; font-weight: bold; }
                .export-info { font-size: 12px; color: #666; margin-bottom: 20px; }
                table { width: 100%; border-collapse: collapse; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background-color: #f2f2f2; font-weight: bold; }
                tr:nth-child(even) { background-color: #f9f9f9; }
                .footer { margin-top: 20px; font-size: 10px; color: #999; text-align: center; }
            </style>
        </head>
        <body>
            <div class="header">
                <div class="tenant-name">' . $tenant->name . '</div>
                <div class="export-info">Exportação de Clientes - ' . now()->format('d/m/Y H:i:s') . '</div>
            </div>';

        if (!empty($data)) {
            $html .= '<table>
                <thead>
                    <tr>';

            foreach (array_keys($data[0]) as $header) {
                $html .= '<th>' . ucfirst(str_replace('_', ' ', $header)) . '</th>';
            }

            $html .= '</tr>
                </thead>
                <tbody>';

            foreach ($data as $row) {
                $html .= '<tr>';
                foreach ($row as $value) {
                    $html .= '<td>' . ($value ?? '') . '</td>';
                }
                $html .= '</tr>';
            }

            $html .= '</tbody>
            </table>';
        }

        $html .= '<div class="footer">Documento gerado automaticamente pelo sistema Easy Budget</div>
        </body>
        </html>';

        return $html;
    }

    private function generateFileName(string $format): string
    {
        $timestamp = now()->format('Y-m-d_H-i-s');
        $random = Str::random(6);
        return "customers_export_{$timestamp}_{$random}.{$format}";
    }

    private function createExportRecord(int $tenantId, string $format, array $filters, array $fields, array $fileData): CustomerExport
    {
        return CustomerExport::create([
            'tenant_id' => $tenantId,
            'format' => $format,
            'filters' => $filters,
            'fields' => $fields,
            'file_path' => $fileData['file_path'],
            'file_name' => $fileData['file_name'],
            'total_records' => 0, // Será atualizado após a exportação
            'status' => 'completed',
            'exported_by' => auth()->id(),
        ]);
    }

    private function getAvailableExportFields(): array
    {
        return [
            'customer_id', 'customer_status', 'customer_type', 'customer_created_at', 'customer_updated_at',
            'first_name', 'last_name', 'full_name', 'cpf', 'cnpj', 'company_name', 'birth_date', 'area_of_activity', 'profession',
            'email', 'phone', 'phone_business', 'email_business', 'website',
            'address', 'address_number', 'neighborhood', 'city', 'state', 'cep',
            'business_name', 'business_phone', 'business_email', 'business_address', 'business_address_number', 'business_neighborhood', 'business_city', 'business_state', 'business_cep',
            'total_budgets', 'total_services', 'total_invoices', 'total_revenue', 'pending_amount', 'last_interaction_at', 'last_budget_at', 'last_service_at', 'last_invoice_at',
        ];
    }

    private function getCustomerPortfolioForExport(Customer $customer): array
    {
        return [
            'customer_info' => $this->getCustomerInfoForExport($customer),
            'budgets' => $this->getBudgetsForExport($customer),
            'services' => $this->getServicesForExport($customer),
            'invoices' => $this->getInvoicesForExport($customer),
            'interactions' => $this->getInteractionsForExport($customer),
            'financial_summary' => $this->getFinancialSummaryForExport($customer),
        ];
    }

    private function getCustomerInfoForExport(Customer $customer): array
    {
        return [
            'basic_info' => [
                'id' => $customer->id,
                'status' => $customer->status,
                'type' => $customer->type,
                'created_at' => $customer->created_at,
                'updated_at' => $customer->updated_at,
            ],
            'common_data' => $customer->commonData,
            'contact' => $customer->contact,
            'address' => $customer->address,
            'business_data' => $customer->businessData,
        ];
    }

    private function getBudgetsForExport(Customer $customer): array
    {
        return $customer->budgets()->with([
            'services.items.product',
            'services.invoices',
            'statusHistory',
        ])->get()->toArray();
    }

    private function getServicesForExport(Customer $customer): array
    {
        return $customer->services()->with([
            'budget',
            'items.product',
            'invoices',
            'statusHistory',
        ])->get()->toArray();
    }

    private function getInvoicesForExport(Customer $customer): array
    {
        return $customer->invoices()->with([
            'service.budget',
            'items.product',
            'statusHistory',
        ])->get()->toArray();
    }

    private function getInteractionsForExport(Customer $customer): array
    {
        return $customer->interactions()->with('createdBy')->get()->toArray();
    }

    private function getFinancialSummaryForExport(Customer $customer): array
    {
        return [
            'total_budgets' => $customer->budgets()->count(),
            'total_services' => $customer->services()->count(),
            'total_invoices' => $customer->invoices()->count(),
            'total_revenue' => $customer->invoices()->where('status', 'paid')->sum('total'),
            'pending_amount' => $customer->invoices()->where('status', 'pending')->sum('total'),
            'paid_amount' => $customer->invoices()->where('status', 'paid')->sum('total'),
            'overdue_amount' => $customer->invoices()->where('due_date', '<', now())->where('status', 'pending')->sum('total'),
            'average_invoice_value' => $customer->invoices()->avg('total') ?? 0,
            'budget_conversion_rate' => $this->calculateBudgetConversionRate($customer),
            'service_completion_rate' => $this->calculateServiceCompletionRate($customer),
        ];
    }

    private function generatePortfolioExport(array $portfolio, string $format, Customer $customer): ServiceResult
    {
        switch ($format) {
            case 'xlsx':
                return $this->generatePortfolioExcel($portfolio, $customer);
            case 'pdf':
                return $this->generatePortfolioPDF($portfolio, $customer);
            default:
                return $this->error('Formato não suportado para exportação de portfólio', OperationStatus::INVALID_DATA);
        }
    }

    private function generatePortfolioExcel(array $portfolio, Customer $customer): ServiceResult
    {
        try {
            $spreadsheet = new Spreadsheet();
            $writer = new Xlsx($spreadsheet);

            // Planilha 1: Informações do Cliente
            $sheet1 = $spreadsheet->getActiveSheet();
            $sheet1->setTitle('Informações do Cliente');
            $this->addCustomerInfoToSheet($sheet1, $portfolio['customer_info']);

            // Planilha 2: Orçamentos
            $sheet2 = $spreadsheet->createSheet();
            $sheet2->setTitle('Orçamentos');
            $this->addBudgetsToSheet($sheet2, $portfolio['budgets']);

            // Planilha 3: Serviços
            $sheet3 = $spreadsheet->createSheet();
            $sheet3->setTitle('Serviços');
            $this->addServicesToSheet($sheet3, $portfolio['services']);

            // Planilha 4: Faturas
            $sheet4 = $spreadsheet->createSheet();
            $sheet4->setTitle('Faturas');
            $this->addInvoicesToSheet($sheet4, $portfolio['invoices']);

            // Planilha 5: Interações
            $sheet5 = $spreadsheet->createSheet();
            $sheet5->setTitle('Interações');
            $this->addInteractionsToSheet($sheet5, $portfolio['interactions']);

            // Planilha 6: Resumo Financeiro
            $sheet6 = $spreadsheet->createSheet();
            $sheet6->setTitle('Resumo Financeiro');
            $this->addFinancialSummaryToSheet($sheet6, $portfolio['financial_summary']);

            // Salvar arquivo
            $fileName = "portfolio_{$customer->id}_" . now()->format('Y-m-d_H-i-s') . ".xlsx";
            $filePath = storage_path("app/public/exports/{$fileName}");

            $writer->save($filePath);

            return $this->success([
                'file_path' => $filePath,
                'file_name' => $fileName,
            ], 'Exportação do portfólio em Excel concluída');

        } catch (Exception $e) {
            return $this->error('Erro ao gerar Excel do portfólio: ' . $e->getMessage(), OperationStatus::INTERNAL_ERROR);
        }
    }

    private function generatePortfolioPDF(array $portfolio, Customer $customer): ServiceResult
    {
        try {
            $mpdf = new Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'margin_left' => 10,
                'margin_right' => 10,
                'margin_top' => 10,
                'margin_bottom' => 10,
            ]);

            $html = $this->generatePortfolioPDFContent($portfolio, $customer);
            $mpdf->WriteHTML($html);

            $fileName = "portfolio_{$customer->id}_" . now()->format('Y-m-d_H-i-s') . ".pdf";
            $filePath = storage_path("app/public/exports/{$fileName}");

            $mpdf->Output($filePath, 'F');

            return $this->success([
                'file_path' => $filePath,
                'file_name' => $fileName,
            ], 'Exportação do portfólio em PDF concluída');

        } catch (Exception $e) {
            return $this->error('Erro ao gerar PDF do portfólio: ' . $e->getMessage(), OperationStatus::INTERNAL_ERROR);
        }
    }

    private function generatePortfolioPDFContent(array $portfolio, Customer $customer): string
    {
        $html = '<!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; }
                .header { text-align: center; margin-bottom: 20px; }
                .customer-name { font-size: 20px; font-weight: bold; }
                .section { margin-bottom: 30px; }
                .section-title { font-size: 16px; font-weight: bold; border-bottom: 2px solid #333; padding-bottom: 5px; margin-bottom: 15px; }
                table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background-color: #f2f2f2; font-weight: bold; }
                .summary-box { background-color: #f9f9f9; padding: 15px; border: 1px solid #ddd; margin-bottom: 20px; }
                .summary-item { display: inline-block; margin-right: 20px; }
                .summary-label { font-weight: bold; }
            </style>
        </head>
        <body>';

        // Cabeçalho
        $html .= '<div class="header">
            <div class="customer-name">' . $customer->commonData?->first_name . ' ' . $customer->commonData?->last_name . '</div>
            <div class="export-info">Portfólio do Cliente - ' . now()->format('d/m/Y H:i:s') . '</div>
        </div>';

        // Informações do Cliente
        $html .= '<div class="section">
            <div class="section-title">Informações do Cliente</div>
            <div class="summary-box">
                <div class="summary-item"><span class="summary-label">ID:</span> ' . $customer->id . '</div>
                <div class="summary-item"><span class="summary-label">Status:</span> ' . $customer->status . '</div>
                <div class="summary-item"><span class="summary-label">Tipo:</span> ' . $customer->type . '</div>
                <div class="summary-item"><span class="summary-label">Criado em:</span> ' . $customer->created_at->format('d/m/Y') . '</div>
            </div>
        </div>';

        // Resumo Financeiro
        $html .= '<div class="section">
            <div class="section-title">Resumo Financeiro</div>
            <div class="summary-box">';

        foreach ($portfolio['financial_summary'] as $key => $value) {
            $label = ucfirst(str_replace('_', ' ', $key));
            $html .= '<div class="summary-item"><span class="summary-label">' . $label . ':</span> ' . $value . '</div>';
        }

        $html .= '</div>
        </div>';

        // Orçamentos
        if (!empty($portfolio['budgets'])) {
            $html .= '<div class="section">
                <div class="section-title">Orçamentos</div>
                <table>
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Descrição</th>
                            <th>Status</th>
                            <th>Valor</th>
                            <th>Data</th>
                        </tr>
                    </thead>
                    <tbody>';

            foreach ($portfolio['budgets'] as $budget) {
                $html .= '<tr>
                    <td>' . $budget['code'] . '</td>
                    <td>' . $budget['description'] . '</td>
                    <td>' . $budget['status'] . '</td>
                    <td>R$ ' . number_format($budget['total_value'], 2, ',', '.') . '</td>
                    <td>' . $budget['created_at'] . '</td>
                </tr>';
            }

            $html .= '</tbody>
                </table>
            </div>';
        }

        // Serviços
        if (!empty($portfolio['services'])) {
            $html .= '<div class="section">
                <div class="section-title">Serviços</div>
                <table>
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Descrição</th>
                            <th>Status</th>
                            <th>Valor</th>
                            <th>Data</th>
                        </tr>
                    </thead>
                    <tbody>';

            foreach ($portfolio['services'] as $service) {
                $html .= '<tr>
                    <td>' . $service['code'] . '</td>
                    <td>' . $service['description'] . '</td>
                    <td>' . $service['status'] . '</td>
                    <td>R$ ' . number_format($service['total'], 2, ',', '.') . '</td>
                    <td>' . $service['created_at'] . '</td>
                </tr>';
            }

            $html .= '</tbody>
                </table>
            </div>';
        }

        // Faturas
        if (!empty($portfolio['invoices'])) {
            $html .= '<div class="section">
                <div class="section-title">Faturas</div>
                <table>
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Descrição</th>
                            <th>Status</th>
                            <th>Valor</th>
                            <th>Vencimento</th>
                        </tr>
                    </thead>
                    <tbody>';

            foreach ($portfolio['invoices'] as $invoice) {
                $html .= '<tr>
                    <td>' . $invoice['code'] . '</td>
                    <td>' . $invoice['description'] . '</td>
                    <td>' . $invoice['status'] . '</td>
                    <td>R$ ' . number_format($invoice['total'], 2, ',', '.') . '</td>
                    <td>' . $invoice['due_date'] . '</td>
                </tr>';
            }

            $html .= '</tbody>
                </table>
            </div>';
        }

        $html .= '</body></html>';

        return $html;
    }

    private function addCustomerInfoToSheet(Worksheet $sheet, array $customerInfo): void
    {
        $sheet->setCellValue('A1', 'Informações do Cliente');
        $sheet->setCellValue('A2', 'ID');
        $sheet->setCellValue('B2', $customerInfo['basic_info']['id']);
        $sheet->setCellValue('A3', 'Status');
        $sheet->setCellValue('B3', $customerInfo['basic_info']['status']);
        $sheet->setCellValue('A4', 'Tipo');
        $sheet->setCellValue('B4', $customerInfo['basic_info']['type']);
        $sheet->setCellValue('A5', 'Criado em');
        $sheet->setCellValue('B5', $customerInfo['basic_info']['created_at']);
    }

    private function addBudgetsToSheet(Worksheet $sheet, array $budgets): void
    {
        $sheet->setCellValue('A1', 'Orçamentos');
        $sheet->setCellValue('A2', 'Código');
        $sheet->setCellValue('B2', 'Descrição');
        $sheet->setCellValue('C2', 'Status');
        $sheet->setCellValue('D2', 'Valor');
        $sheet->setCellValue('E2', 'Data');

        $row = 3;
        foreach ($budgets as $budget) {
            $sheet->setCellValue('A' . $row, $budget['code']);
            $sheet->setCellValue('B' . $row, $budget['description']);
            $sheet->setCellValue('C' . $row, $budget['status']);
            $sheet->setCellValue('D' . $row, $budget['total_value']);
            $sheet->setCellValue('E' . $row, $budget['created_at']);
            $row++;
        }
    }

    private function addServicesToSheet(Worksheet $sheet, array $services): void
    {
        $sheet->setCellValue('A1', 'Serviços');
        $sheet->setCellValue('A2', 'Código');
        $sheet->setCellValue('B2', 'Descrição');
        $sheet->setCellValue('C2', 'Status');
        $sheet->setCellValue('D2', 'Valor');
        $sheet->setCellValue('E2', 'Data');

        $row = 3;
        foreach ($services as $service) {
            $sheet->setCellValue('A' . $row, $service['code']);
            $sheet->setCellValue('B' . $row, $service['description']);
            $sheet->setCellValue('C' . $row, $service['status']);
            $sheet->setCellValue('D' . $row, $service['total']);
            $sheet->setCellValue('E' . $row, $service['created_at']);
            $row++;
        }
    }

    private function addInvoicesToSheet(Worksheet $sheet, array $invoices): void
    {
        $sheet->setCellValue('A1', 'Faturas');
        $sheet->setCellValue('A2', 'Código');
        $sheet->setCellValue('B2', 'Descrição');
        $sheet->setCellValue('C2', 'Status');
        $sheet->setCellValue('D2', 'Valor');
        $sheet->setCellValue('E2', 'Vencimento');

        $row = 3;
        foreach ($invoices as $invoice) {
            $sheet->setCellValue('A' . $row, $invoice['code']);
            $sheet->setCellValue('B' . $row, $invoice['description']);
            $sheet->setCellValue('C' . $row, $invoice['status']);
            $sheet->setCellValue('D' . $row, $invoice['total']);
            $sheet->setCellValue('E' . $row, $invoice['due_date']);
            $row++;
        }
    }

    private function addInteractionsToSheet(Worksheet $sheet, array $interactions): void
    {
        $sheet->setCellValue('A1', 'Interações');
        $sheet->setCellValue('A2', 'Tipo');
        $sheet->setCellValue('B2', 'Descrição');
        $sheet->setCellValue('C2', 'Data');
        $sheet->setCellValue('D2', 'Criado por');

        $row = 3;
        foreach ($interactions as $interaction) {
            $sheet->setCellValue('A' . $row, $interaction['interaction_type']);
            $sheet->setCellValue('B' . $row, $interaction['description']);
            $sheet->setCellValue('C' . $row, $interaction['interaction_date']);
            $sheet->setCellValue('D' . $row, $interaction['created_by']);
            $row++;
        }
    }

    private function addFinancialSummaryToSheet(Worksheet $sheet, array $summary): void
    {
        $sheet->setCellValue('A1', 'Resumo Financeiro');

        $row = 2;
        foreach ($summary as $key => $value) {
            $label = ucfirst(str_replace('_', ' ', $key));
            $sheet->setCellValue('A' . $row, $label);
            $sheet->setCellValue('B' . $row, $value);
            $row++;
        }
    }

    private function validateCustomerIds(array $customerIds): ServiceResult
    {
        if (empty($customerIds)) {
            return $this->error('Nenhum ID de cliente fornecido', OperationStatus::INVALID_DATA);
        }

        $existingIds = Customer::whereIn('id', $customerIds)->pluck('id')->toArray();
        $missingIds = array_diff($customerIds, $existingIds);

        if (!empty($missingIds)) {
            return $this->error('Clientes não encontrados: ' . implode(', ', $missingIds), OperationStatus::NOT_FOUND);
        }

        return $this->success(null, 'IDs de clientes válidos');
    }

    private function generateBatchExport(Collection $customers, string $format): ServiceResult
    {
        $data = $this->mapExportFields($customers, $this->getAvailableExportFields());
        return $this->generateExportFile($data, $format, $customers->first()->tenant_id);
    }

    private function calculateBudgetConversionRate(Customer $customer): float
    {
        $totalBudgets = $customer->budgets()->count();
        $convertedBudgets = $customer->budgets()->whereHas('services', function($query) {
            $query->where('status', 'active');
        })->count();

        return $totalBudgets > 0 ? ($convertedBudgets / $totalBudgets) * 100 : 0;
    }

    private function calculateServiceCompletionRate(Customer $customer): float
    {
        $totalServices = $customer->services()->count();
        $completedServices = $customer->services()->where('status', 'completed')->count();

        return $totalServices > 0 ? ($completedServices / $totalServices) * 100 : 0;
    }
}
```

### **✅ Sistema de Importação**

```php
class CustomerImportService extends AbstractBaseService
{
    public function importCustomers(int $tenantId, string $filePath, string $format, array $mapping = []): ServiceResult
    {
        return $this->safeExecute(function() use ($tenantId, $filePath, $format, $mapping) {
            // 1. Validar arquivo de importação
            $validation = $this->validateImportFile($filePath, $format);
            if (!$validation->isSuccess()) {
                return $validation;
            }

            // 2. Ler dados do arquivo
            $fileData = $this->readImportFile($filePath, $format);
            if (!$fileData->isSuccess()) {
                return $fileData;
            }

            $data = $fileData->getData();

            // 3. Validar mapeamento de campos
            $mappingValidation = $this->validateFieldMapping($mapping, $data);
            if (!$mappingValidation->isSuccess()) {
                return $mappingValidation;
            }

            // 4. Validar dados dos clientes
            $validationResults = $this->validateImportData($data, $mapping);
            if (!$validationResults['valid']) {
                return $this->error('Dados de importação inválidos', OperationStatus::INVALID_DATA, [
                    'validation_errors' => $validationResults['errors'],
                ]);
            }

            // 5. Processar importação
            $importResult = $this->processImport($tenantId, $data, $mapping);

            // 6. Criar registro de importação
            $this->createImportRecord($tenantId, $format, $mapping, $importResult);

            return $this->success([
                'import_result' => $importResult,
                'total_records' => count($data),
                'imported_records' => $importResult['imported'],
                'failed_records' => $importResult['failed'],
                'errors' => $importResult['errors'],
            ], 'Importação de clientes concluída');
        });
    }

    public function validateImportFile(string $filePath, string $format): ServiceResult
    {
        // Verificar se o arquivo existe
        if (!file_exists($filePath)) {
            return $this->error('Arquivo de importação não encontrado', OperationStatus::FILE_ERROR);
        }

        // Verificar tamanho do arquivo (máximo 10MB)
        $fileSize = filesize($filePath);
        if ($fileSize > 10 * 1024 * 1024) {
            return $this->error('Arquivo muito grande (máximo 10MB)', OperationStatus::FILE_ERROR);
        }

        // Verificar extensão do arquivo
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        $supportedFormats = ['csv', 'xlsx', 'json'];

        if (!in_array($extension, $supportedFormats)) {
            return $this->error('Formato de arquivo não suportado', OperationStatus::INVALID_DATA);
        }

        // Verificar se o formato corresponde à extensão
        if ($extension !== $format) {
            return $this->error('Formato especificado não corresponde à extensão do arquivo', OperationStatus::INVALID_DATA);
        }

        return $this->success(null, 'Arquivo de importação válido');
    }

    public function readImportFile(string $filePath, string $format): ServiceResult
    {
        try {
            switch ($format) {
                case 'csv':
                    return $this->readCSVFile($filePath);
                case 'xlsx':
                    return $this->readExcelFile($filePath);
                case 'json':
                    return $this->readJSONFile($filePath);
                default:
                    return $this->error('Formato de importação não suportado', OperationStatus::INVALID_DATA);
            }
        } catch (Exception $e) {
            return $this->error('Erro ao ler arquivo: ' . $e->getMessage(), OperationStatus::FILE_ERROR);
        }
    }

    private function readCSVFile(string $filePath): ServiceResult
    {
        $file = fopen($filePath, 'r');
        if (!$file) {
            return $this->error('Não foi possível abrir o arquivo CSV', OperationStatus::FILE_ERROR);
        }

        $headers = fgetcsv($file);
        $data = [];

        while (($row = fgetcsv($file)) !== false) {
            $data[] = array_combine($headers, $row);
        }

        fclose($file);

        return $this->success($data, 'Dados CSV lidos com sucesso');
    }

    private function readExcelFile(string $filePath): ServiceResult
    {
        try {
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
            $spreadsheet = $reader->load($filePath);
            $worksheet = $spreadsheet->getActiveSheet();
            $data = $worksheet->toArray();

            if (empty($data)) {
                return $this->error('Arquivo Excel vazio', OperationStatus::INVALID_DATA);
            }

            $headers = array_shift($data);
            $result = [];

            foreach ($data as $row) {
                $result[] = array_combine($headers, $row);
            }

            return $this->success($result, 'Dados Excel lidos com sucesso');

        } catch (Exception $e) {
            return $this->error('Erro ao ler arquivo Excel: ' . $e->getMessage(), OperationStatus::FILE_ERROR);
        }
    }

    private function readJSONFile(string $filePath): ServiceResult
    {
        $jsonContent = file_get_contents($filePath);
        if ($jsonContent === false) {
            return $this->error('Não foi possível ler o arquivo JSON', OperationStatus::FILE_ERROR);
        }

        $data = json_decode($jsonContent, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return $this->error('Arquivo JSON inválido: ' . json_last_error_msg(), OperationStatus::INVALID_DATA);
        }

        if (!isset($data['data']) || !is_array($data['data'])) {
            return $this->error('Formato JSON inválido - campo "data" não encontrado ou não é array', OperationStatus::INVALID_DATA);
        }

        return $this->success($data['data'], 'Dados JSON lidos com sucesso');
    }

    private function validateFieldMapping(array $mapping, array $data): ServiceResult
    {
        if (empty($mapping)) {
            // Gerar mapeamento automático baseado nos cabeçalhos
            $mapping = $this->generateAutoMapping($data);
        }

        // Validar campos mapeados
        $availableFields = $this->getAvailableImportFields();
        $invalidFields = array_diff(array_values($mapping), $availableFields);

        if (!empty($invalidFields)) {
            return $this->error('Campos de mapeamento inválidos: ' . implode(', ', $invalidFields), OperationStatus::INVALID_DATA);
        }

        // Verificar campos obrigatórios
        $requiredFields = ['customer_type', 'first_name', 'email'];
        $mappedFields = array_values($mapping);
        $missingRequired = array_diff($requiredFields, $mappedFields);

        if (!empty($missingRequired)) {
            return $this->error('Campos obrigatórios não mapeados: ' . implode(', ', $missingRequired), OperationStatus::INVALID_DATA);
        }

        return $this->success($mapping, 'Mapeamento de campos válido');
    }

    private function generateAutoMapping(array $data): array
    {
        if (empty($data)) {
            return [];
        }

        $headers = array_keys($data[0]);
        $mapping = [];

        // Mapeamento automático baseado em nomes de colunas comuns
        $fieldMappings = [
            'customer_type' => ['type', 'customer_type', 'tipo', 'tipo_cliente'],
            'first_name' => ['first_name', 'nome', 'nome_completo', 'name'],
            'last_name' => ['last_name', 'sobrenome', 'surname'],
            'cpf' => ['cpf', 'document', 'documento'],
            'cnpj' => ['cnpj', 'company_document', 'documento_empresa'],
            'email' => ['email', 'e-mail', 'mail'],
            'phone' => ['phone', 'telefone', 'celular'],
            'address' => ['address', 'endereco', 'endereço'],
            'city' => ['city', 'cidade'],
            'state' => ['state', 'estado', 'uf'],
            'cep' => ['cep', 'postal_code', 'codigo_postal'],
        ];

        foreach ($fieldMappings as $targetField => $possibleHeaders) {
            foreach ($possibleHeaders as $header) {
                if (in_array($header, $headers)) {
                    $mapping[$header] = $targetField;
                    break;
                }
            }
        }

        return $mapping;
    }

    private function validateImportData(array $data, array $mapping): array
    {
        $errors = [];
        $valid = true;

        foreach ($data as $index => $row) {
            $rowErrors = [];

            // Validar campos obrigatórios
            $requiredFields = ['customer_type', 'first_name', 'email'];
            foreach ($requiredFields as $field) {
                $mappedField = array_search($field, $mapping);
                if (!$mappedField || empty($row[$mappedField])) {
                    $rowErrors[] = "Campo obrigatório '$field' não encontrado ou vazio";
                }
            }

            // Validar tipo de cliente
            $customerTypeField = array_search('customer_type', $mapping);
            if ($customerTypeField && isset($row[$customerTypeField])) {
                $customerType = $row[$customerTypeField];
                if (!in_array($customerType, ['individual', 'company'])) {
                    $rowErrors[] = "Tipo de cliente inválido: '$customerType'";
                }
            }

            // Validar e-mail
            $emailField = array_search('email', $mapping);
            if ($emailField && isset($row[$emailField])) {
                $email = $row[$emailField];
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $rowErrors[] = "E-mail inválido: '$email'";
                }
            }

            // Validar CPF/CNPJ
            $cpfField = array_search('cpf', $mapping);
            $cnpjField = array_search('cnpj', $mapping);

            if ($cpfField && isset($row[$cpfField]) && !empty($row[$cpfField])) {
                if (!$this->isValidCPF($row[$cpfField])) {
                    $rowErrors[] = "CPF inválido: '" . $row[$cpfField] . "'";
                }
            }

            if ($cnpjField && isset($row[$cnpjField]) && !empty($row[$cnpjField])) {
                if (!$this->isValidCNPJ($row[$cnpjField])) {
                    $rowErrors[] = "CNPJ inválido: '" . $row[$cnpjField] . "'";
                }
            }

            if (!empty($rowErrors)) {
                $errors[$index + 1] = $rowErrors;
                $valid = false;
            }
        }

        return [
            'valid' => $valid,
            'errors' => $errors,
        ];
    }

    private function processImport(int $tenantId, array $data, array $mapping): array
    {
        $imported = 0;
        $failed = 0;
        $errors = [];
        $duplicates = [];

        foreach ($data as $index => $row) {
            try {
                // Mapear dados para o formato do CustomerDTO
                $customerData = $this->mapImportData($row, $mapping);

                // Verificar duplicidade
                $duplicateCheck = $this->checkDuplicateImport($customerData, $tenantId);
                if ($duplicateCheck->isSuccess()) {
                    $duplicates[] = [
                        'row' => $index + 1,
                        'data' => $customerData,
                        'message' => $duplicateCheck->getMessage(),
                    ];
                    $failed++;
                    continue;
                }

                // Criar cliente
                $customerService = app(CustomerService::class);
                $result = $customerService->create($customerData, auth()->user());

                if ($result->isSuccess()) {
                    $imported++;
                } else {
                    $failed++;
                    $errors[] = [
                        'row' => $index + 1,
                        'data' => $customerData,
                        'error' => $result->getMessage(),
                    ];
                }

            } catch (Exception $e) {
                $failed++;
                $errors[] = [
                    'row' => $index + 1,
                    'data' => $row,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return [
            'imported' => $imported,
            'failed' => $failed,
            'errors' => $errors,
            'duplicates' => $duplicates,
        ];
    }

    private function mapImportData(array $row, array $mapping): CustomerDTO
    {
        $customerData = [
            'status' => 'active',
            'type' => $row[array_search('customer_type', $mapping)] ?? 'individual',
        ];

        // Dados comuns
        $commonData = [];

        if ($customerData['type'] === 'individual') {
            $commonData['first_name'] = $row[array_search('first_name', $mapping)] ?? '';
            $commonData['last_name'] = $row[array_search('last_name', $mapping)] ?? '';
            $commonData['cpf'] = $row[array_search('cpf', $mapping)] ?? '';
            $commonData['type'] = 'individual';
        } else {
            $commonData['company_name'] = $row[array_search('company_name', $mapping)] ?? '';
            $commonData['cnpj'] = $row[array_search('cnpj', $mapping)] ?? '';
            $commonData['type'] = 'company';
        }

        // Contato
        $contact = [
            'email' => $row[array_search('email', $mapping)] ?? '',
            'phone' => $row[array_search('phone', $mapping)] ?? '',
        ];

        // Endereço
        $address = [
            'address' => $row[array_search('address', $mapping)] ?? '',
            'city' => $row[array_search('city', $mapping)] ?? '',
            'state' => $row[array_search('state', $mapping)] ?? '',
            'cep' => $row[array_search('cep', $mapping)] ?? '',
        ];

        return new CustomerDTO([
            'status' => $customerData['status'],
            'type' => $customerData['type'],
            'common_data' => $commonData,
            'contact' => $contact,
            'address' => $address,
        ]);
    }

    private function checkDuplicateImport(CustomerDTO $customerData, int $tenantId): ServiceResult
    {
        // Verificar duplicidade por CPF/CNPJ
        if ($customerData->type === 'individual' && !empty($customerData->common_data['cpf'])) {
            $duplicate = Customer::where('tenant_id', $tenantId)
                ->whereHas('commonData', function($query) use ($customerData) {
                    $query->where('cpf', $customerData->common_data['cpf']);
                })
                ->exists();

            if ($duplicate) {
                return $this->error('Já existe cliente com este CPF', OperationStatus::DUPLICATE_ENTRY);
            }
        }

        if ($customerData->type === 'company' && !empty($customerData->common_data['cnpj'])) {
            $duplicate = Customer::where('tenant_id', $tenantId)
                ->whereHas('commonData', function($query) use ($customerData) {
                    $query->where('cnpj', $customerData->common_data['cnpj']);
                })
                ->exists();

            if ($duplicate) {
                return $this->error('Já existe cliente com este CNPJ', OperationStatus::DUPLICATE_ENTRY);
            }
        }

        // Verificar duplicidade por e-mail
        if (!empty($customerData->contact['email'])) {
            $duplicate = Customer::where('tenant_id', $tenantId)
                ->whereHas('contact', function($query) use ($customerData) {
                    $query->where('email', $customerData->contact['email']);
                })
                ->exists();

            if ($duplicate) {
                return $this->error('Já existe cliente com este e-mail', OperationStatus::DUPLICATE_ENTRY);
            }
        }

        return $this->success(null, 'Cliente não é duplicado');
    }

    private function createImportRecord(int $tenantId, string $format, array $mapping, array $importResult): void
    {
        CustomerImport::create([
            'tenant_id' => $tenantId,
            'format' => $format,
            'mapping' => $mapping,
            'total_records' => $importResult['imported'] + $importResult['failed'],
            'imported_records' => $importResult['imported'],
            'failed_records' => $importResult['failed'],
            'errors' => $importResult['errors'],
            'imported_by' => auth()->id(),
            'status' => 'completed',
        ]);
    }

    private function getAvailableImportFields(): array
    {
        return [
            'customer_status', 'customer_type', 'first_name', 'last_name', 'cpf', 'cnpj', 'company_name',
            'email', 'phone', 'address', 'city', 'state', 'cep',
        ];
    }

    private function isValidCPF(string $cpf): bool
    {
        $cpf = preg_replace('/[^0-9]/', '', $cpf);

        if (strlen($cpf) !== 11) return false;
        if (preg_match('/^(\d)\1+$/', $cpf)) return false;

        for ($t = 9; $t < 11; $t++) {
            for ($d = 0, $c = 0; $c < $t; $c++) {
                $d += $cpf[$c] * (($t + 1) - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if ($cpf[$c] != $d) return false;
        }

        return true;
    }

    private function isValidCNPJ(string $cnpj): bool
    {
        $cnpj = preg_replace('/[^0-9]/', '', $cnpj);

        if (strlen($cnpj) !== 14) return false;
        if (preg_match('/^(\d)\1+$/', $cnpj)) return false;

        $length = strlen($cnpj);
        $weight = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];

        for ($t = 0; $t < 2; $t++) {
            $sum = 0;
            $weightIndex = 0;

            for ($i = $length - 2 + $t; $i >= 0; $i--) {
                $sum += $cnpj[$i] * $weight[$weightIndex++];
            }

            $sum = 11 - ($sum % 11);
            $digit = $sum > 9 ? 0 : $sum;

            if ($cnpj[$length - 1 - $t] != $digit) return false;
        }

        return true;
    }
}
```

## 🧪 Testes e Validação

### **✅ Testes de Exportação**

```php
public function testCustomerExportCSV()
{
    $tenant = Tenant::factory()->create();
    Customer::factory()->count(5)->create(['tenant_id' => $tenant->id]);

    $result = $this->exportService->exportCustomers($tenant->id, 'csv');
    $this->assertTrue($result->isSuccess());

    $exportData = $result->getData();
    $this->assertFileExists($exportData['file_path']);
    $this->assertStringEndsWith('.csv', $exportData['file_name']);
    $this->assertEquals(5, $exportData['total_records']);
}

public function testCustomerExportExcel()
{
    $tenant = Tenant::factory()->create();
    Customer::factory()->count(3)->create(['tenant_id' => $tenant->id]);

    $result = $this->exportService->exportCustomers($tenant->id, 'xlsx');
    $this->assertTrue($result->isSuccess());

    $exportData = $result->getData();
    $this->assertFileExists($exportData['file_path']);
    $this->assertStringEndsWith('.xlsx', $exportData['file_name']);
}

public function testCustomerExportJSON()
{
    $tenant = Tenant::factory()->create();
    Customer::factory()->count(2)->create(['tenant_id' => $tenant->id]);

    $result = $this->exportService->exportCustomers($tenant->id, 'json');
    $this->assertTrue($result->isSuccess());

    $exportData = $result->getData();
    $this->assertFileExists($exportData['file_path']);
    $this->assertStringEndsWith('.json', $exportData['file_name']);

    // Verificar conteúdo JSON
    $jsonContent = json_decode(file_get_contents($exportData['file_path']), true);
    $this->assertArrayHasKey('export_date', $jsonContent);
    $this->assertArrayHasKey('total_records', $jsonContent);
    $this->assertArrayHasKey('data', $jsonContent);
}

public function testCustomerPortfolioExport()
{
    $customer = Customer::factory()->create();
    Budget::factory()->count(2)->create(['customer_id' => $customer->id]);
    Service::factory()->count(3)->create(['customer_id' => $customer->id]);
    Invoice::factory()->count(4)->create([
        'customer_id' => $customer->id,
        'service_id' => Service::factory()->create(['customer_id' => $customer->id])->id,
    ]);

    $result = $this->exportService->exportCustomerPortfolio($customer, 'xlsx');
    $this->assertTrue($result->isSuccess());

    $exportData = $result->getData();
    $this->assertFileExists($exportData['file_path']);
    $this->assertStringEndsWith('.xlsx', $exportData['file_name']);
}
```

### **✅ Testes de Importação**

```php
public function testCustomerImportCSV()
{
    $tenant = Tenant::factory()->create();

    // Criar arquivo CSV de teste
    $csvContent = "customer_type,first_name,last_name,email,phone\n";
    $csvContent .= "individual,João,Silva,joao@teste.com,(11) 98765-4321\n";
    $csvContent .= "company,Empresa Teste,,empresa@teste.com,(21) 12345-6789\n";

    $csvFile = tempnam(sys_get_temp_dir(), 'test_import');
    file_put_contents($csvFile, $csvContent);

    $result = $this->importService->importCustomers($tenant->id, $csvFile, 'csv');
    $this->assertTrue($result->isSuccess());

    $importData = $result->getData();
    $this->assertEquals(2, $importData['total_records']);
    $this->assertEquals(2, $importData['imported_records']);
    $this->assertEquals(0, $importData['failed_records']);

    // Verificar clientes criados
    $this->assertEquals(2, Customer::where('tenant_id', $tenant->id)->count());
}

public function testCustomerImportWithValidationErrors()
{
    $tenant = Tenant::factory()->create();

    // Criar arquivo CSV com dados inválidos
    $csvContent = "customer_type,first_name,email\n";
    $csvContent .= "individual,João,joao-invalido\n"; // E-mail inválido
    $csvContent .= ",Maria,maria@teste.com\n"; // Tipo vazio

    $csvFile = tempnam(sys_get_temp_dir(), 'test_import');
    file_put_contents($csvFile, $csvContent);

    $result = $this->importService->importCustomers($tenant->id, $csvFile, 'csv');
    $this->assertFalse($result->isSuccess());
    $this->assertArrayHasKey('validation_errors', $result->getData());
}

public function testCustomerImportJSON()
{
    $tenant = Tenant::factory()->create();

    // Criar arquivo JSON de teste
    $jsonData = [
        'export_date' => now()->toISOString(),
        'total_records' => 2,
        'data' => [
            [
                'customer_type' => 'individual',
                'first_name' => 'Ana',
                'last_name' => 'Santos',
                'email' => 'ana@teste.com',
                'phone' => '(31) 98765-4321',
            ],
            [
                'customer_type' => 'company',
                'company_name' => 'Empresa Ana',
                'email' => 'empresa@teste.com',
                'phone' => '(41) 12345-6789',
            ],
        ],
    ];

    $jsonFile = tempnam(sys_get_temp_dir(), 'test_import');
    file_put_contents($jsonFile, json_encode($jsonData, JSON_PRETTY_PRINT));

    $result = $this->importService->importCustomers($tenant->id, $jsonFile, 'json');
    $this->assertTrue($result->isSuccess());

    $importData = $result->getData();
    $this->assertEquals(2, $importData['total_records']);
    $this->assertEquals(2, $importData['imported_records']);
}

public function testCustomerImportWithDuplicates()
{
    $tenant = Tenant::factory()->create();
    $existingCustomer = Customer::factory()->create(['tenant_id' => $tenant->id]);

    // Criar arquivo CSV com cliente duplicado
    $csvContent = "customer_type,first_name,last_name,email,phone\n";
    $csvContent .= "individual,João,Silva,{$existingCustomer->contact->email},(11) 98765-4321\n";

    $csvFile = tempnam(sys_get_temp_dir(), 'test_import');
    file_put_contents($csvFile, $csvContent);

    $result = $this->importService->importCustomers($tenant->id, $csvFile, 'csv');
    $this->assertTrue($result->isSuccess());

    $importData = $result->getData();
    $this->assertEquals(1, $importData['total_records']);
    $this->assertEquals(0, $importData['imported_records']);
    $this->assertEquals(1, $importData['failed_records']);
}
```

## 🚀 Implementação Gradual

### **Fase 1: Foundation**
- [ ] Implementar CustomerExportService básico
- [ ] Criar CustomerImportService básico
- [ ] Sistema de validação de arquivos
- [ ] Exportação em CSV e JSON

### **Fase 2: Core Features**
- [ ] Implementar exportação em Excel (XLSX)
- [ ] Implementar exportação em PDF
- [ ] Sistema de mapeamento de campos
- [ ] Importação com validação de dados

### **Fase 3: Advanced Features**
- [ ] Exportação de portfólio completo
- [ ] Importação em lote
- [ ] Sistema de correção automática de dados
- [ ] Auditoria de importações/exportações

### **Fase 4: Integration**
- [ ] Integração com sistemas externos
- [ ] API REST para importação/exportação
- [ ] Sistema de filas para processamento pesado
- [ ] Dashboard de importação/exportação

## 📚 Documentação Relacionada

- [CustomerExportService](../../app/Services/Domain/CustomerExportService.php)
- [CustomerImportService](../../app/Services/Domain/CustomerImportService.php)
- [CustomerExport](../../app/Models/CustomerExport.php)
- [CustomerImport](../../app/Models/CustomerImport.php)
- [CustomerExportJob](../../app/Jobs/CustomerExportJob.php)
- [CustomerImportJob](../../app/Jobs/CustomerImportJob.php)

## 🎯 Benefícios

### **✅ Flexibilidade de Formatos**
- Exportação em múltiplos formatos (CSV, Excel, JSON, PDF)
- Importação de diferentes fontes de dados
- Mapeamento flexível de campos
- Compatibilidade com sistemas externos

### **✅ Qualidade de Dados**
- Validação rigorosa de dados importados
- Detecção de duplicidades
- Correção automática de formatos
- Auditoria completa de operações

### **✅ Eficiência Operacional**
- Processamento em lote para grandes volumes
- Exportação de portfólio completo
- Integração com sistemas externos
- Redução de tempo em migrações

### **✅ Segurança e Controle**
- Registro de todas as operações
- Controle de acesso por tenant
- Validação de permissões
- Auditoria de alterações

---

**Última atualização:** 10/01/2026
**Versão:** 1.0.0
**Status:** ✅ Implementado e em uso
