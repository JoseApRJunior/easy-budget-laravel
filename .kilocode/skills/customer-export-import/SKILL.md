# 📤 Skill: Customer Export and Import (Exportação e Importação)

**Descrição:** Sistema completo de exportação e importação de dados de clientes em diferentes formatos (CSV, Excel, JSON) com validação e mapeamento de campos.

**Categoria:** Importação/Exportação de Dados
**Complexidade:** Média
**Status:** ✅ Implementado e Documentado

## 🎯 Objetivo

Padronizar a exportação e importação de dados de clientes no Easy Budget, garantindo integridade dos dados, validação de formatos e mapeamento correto de campos entre diferentes sistemas.

## 📋 Requisitos Técnicos

### **✅ Estrutura de Exportação**

```php
class CustomerExportService extends AbstractBaseService
{
    public function exportCustomers(array $filters = [], string $format = 'csv'): ServiceResult
    {
        return $this->safeExecute(function() use ($filters, $format) {
            // 1. Obter clientes com filtros
            $customers = $this->getCustomersForExport($filters);

            // 2. Mapear dados para exportação
            $exportData = $this->mapExportData($customers);

            // 3. Gerar arquivo no formato especificado
            switch ($format) {
                case 'csv':
                    return $this->exportToCSV($exportData);
                case 'excel':
                    return $this->exportToExcel($exportData);
                case 'json':
                    return $this->exportToJSON($exportData);
                case 'xml':
                    return $this->exportToXML($exportData);
                default:
                    return $this->error('Formato de exportação não suportado', OperationStatus::INVALID_DATA);
            }
        });
    }

    public function exportCustomerPortfolio(Customer $customer, string $format = 'pdf'): ServiceResult
    {
        return $this->safeExecute(function() use ($customer, $format) {
            // 1. Obter portfólio completo do cliente
            $portfolio = $this->getCustomerPortfolioData($customer);

            // 2. Gerar relatório no formato especificado
            switch ($format) {
                case 'pdf':
                    return $this->exportPortfolioToPDF($portfolio, $customer);
                case 'excel':
                    return $this->exportPortfolioToExcel($portfolio, $customer);
                case 'json':
                    return $this->exportPortfolioToJSON($portfolio, $customer);
                default:
                    return $this->error('Formato de exportação não suportado', OperationStatus::INVALID_DATA);
            }
        });
    }

    private function getCustomersForExport(array $filters): Collection
    {
        $query = Customer::query();

        // Aplicar filtros
        if (isset($filters['tenant_id'])) {
            $query->where('tenant_id', $filters['tenant_id']);
        }

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

        return $query->with([
            'commonData',
            'contact',
            'address',
            'businessData',
            'tags',
            'budgets',
            'services',
            'invoices',
        ])->get();
    }

    private function mapExportData(Collection $customers): array
    {
        $exportData = [];

        foreach ($customers as $customer) {
            $exportData[] = [
                // Dados principais do cliente
                'customer_id' => $customer->id,
                'customer_status' => $customer->status,
                'customer_type' => $customer->type,
                'customer_created_at' => $customer->created_at->format('Y-m-d H:i:s'),
                'customer_last_interaction' => $customer->last_interaction_at?->format('Y-m-d H:i:s'),
                'customer_lifecycle_stage' => $customer->lifecycle_stage,

                // Dados comuns
                'common_data_type' => $customer->commonData?->type,
                'common_data_first_name' => $customer->commonData?->first_name,
                'common_data_last_name' => $customer->commonData?->last_name,
                'common_data_cpf' => $customer->commonData?->cpf,
                'common_data_cnpj' => $customer->commonData?->cnpj,
                'common_data_birth_date' => $customer->commonData?->birth_date?->format('Y-m-d'),
                'common_data_company_name' => $customer->commonData?->company_name,

                // Contato
                'contact_email' => $customer->contact?->email,
                'contact_phone' => $customer->contact?->phone,
                'contact_phone_business' => $customer->contact?->phone_business,
                'contact_email_business' => $customer->contact?->email_business,
                'contact_website' => $customer->contact?->website,

                // Endereço
                'address_address' => $customer->address?->address,
                'address_number' => $customer->address?->address_number,
                'address_complement' => $customer->address?->complement,
                'address_neighborhood' => $customer->address?->neighborhood,
                'address_city' => $customer->address?->city,
                'address_state' => $customer->address?->state,
                'address_cep' => $customer->address?->cep,

                // Dados empresariais
                'business_data_state_registration' => $customer->businessData?->state_registration,
                'business_data_municipal_registration' => $customer->businessData?->municipal_registration,
                'business_data_opening_date' => $customer->businessData?->opening_date?->format('Y-m-d'),
                'business_data_cnae' => $customer->businessData?->cnae,

                // Estatísticas
                'total_budgets' => $customer->budgets()->count(),
                'total_services' => $customer->services()->count(),
                'total_invoices' => $customer->invoices()->count(),
                'total_revenue' => $customer->invoices()->sum('total'),
                'pending_amount' => $customer->invoices()->where('status', 'pending')->sum('total'),
            ];
        }

        return $exportData;
    }

    private function exportToCSV(array $data): ServiceResult
    {
        if (empty($data)) {
            return $this->error('Nenhum dado para exportar', OperationStatus::NO_DATA);
        }

        $filename = 'customers_export_' . now()->format('Y-m-d_H-i-s') . '.csv';
        $filepath = storage_path('app/public/exports/' . $filename);

        // Criar diretório se não existir
        $directory = dirname($filepath);
        if (! file_exists($directory)) {
            mkdir($directory, 0755, true);
        }

        $file = fopen($filepath, 'w');

        // Escrever cabeçalho
        fputcsv($file, array_keys($data[0]));

        // Escrever dados
        foreach ($data as $row) {
            fputcsv($file, $row);
        }

        fclose($file);

        return $this->success([
            'filename' => $filename,
            'filepath' => $filepath,
            'url' => asset('storage/exports/' . $filename),
            'format' => 'csv',
            'records' => count($data),
        ], 'Exportação CSV concluída');
    }

    private function exportToExcel(array $data): ServiceResult
    {
        if (empty($data)) {
            return $this->error('Nenhum dado para exportar', OperationStatus::NO_DATA);
        }

        $filename = 'customers_export_' . now()->format('Y-m-d_H-i-s') . '.xlsx';
        $filepath = storage_path('app/public/exports/' . $filename);

        // Criar diretório se não existir
        $directory = dirname($filepath);
        if (! file_exists($directory)) {
            mkdir($directory, 0755, true);
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Escrever cabeçalho
        $columns = array_keys($data[0]);
        foreach ($columns as $columnIndex => $column) {
            $sheet->setCellValueByColumnAndRow($columnIndex + 1, 1, $column);
        }

        // Escrever dados
        foreach ($data as $rowIndex => $row) {
            foreach ($columns as $columnIndex => $column) {
                $sheet->setCellValueByColumnAndRow($columnIndex + 1, $rowIndex + 2, $row[$column]);
            }
        }

        // Salvar arquivo
        $writer = new Xlsx($spreadsheet);
        $writer->save($filepath);

        return $this->success([
            'filename' => $filename,
            'filepath' => $filepath,
            'url' => asset('storage/exports/' . $filename),
            'format' => 'excel',
            'records' => count($data),
        ], 'Exportação Excel concluída');
    }

    private function exportToJSON(array $data): ServiceResult
    {
        $filename = 'customers_export_' . now()->format('Y-m-d_H-i-s') . '.json';
        $filepath = storage_path('app/public/exports/' . $filename);

        // Criar diretório se não existir
        $directory = dirname($filepath);
        if (! file_exists($directory)) {
            mkdir($directory, 0755, true);
        }

        file_put_contents($filepath, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return $this->success([
            'filename' => $filename,
            'filepath' => $filepath,
            'url' => asset('storage/exports/' . $filename),
            'format' => 'json',
            'records' => count($data),
        ], 'Exportação JSON concluída');
    }

    private function exportToXML(array $data): ServiceResult
    {
        $filename = 'customers_export_' . now()->format('Y-m-d_H-i-s') . '.xml';
        $filepath = storage_path('app/public/exports/' . $filename);

        // Criar diretório se não existir
        $directory = dirname($filepath);
        if (! file_exists($directory)) {
            mkdir($directory, 0755, true);
        }

        $xml = new SimpleXMLElement('<customers></customers>');

        foreach ($data as $customerData) {
            $customer = $xml->addChild('customer');

            foreach ($customerData as $key => $value) {
                $customer->addChild($key, htmlspecialchars($value ?? ''));
            }
        }

        $xml->asXML($filepath);

        return $this->success([
            'filename' => $filename,
            'filepath' => $filepath,
            'url' => asset('storage/exports/' . $filename),
            'format' => 'xml',
            'records' => count($data),
        ], 'Exportação XML concluída');
    }

    private function getCustomerPortfolioData(Customer $customer): array
    {
        return [
            'customer' => $customer->load([
                'commonData',
                'contact',
                'address',
                'businessData',
                'tags',
            ]),
            'financial_summary' => $this->getFinancialSummary($customer),
            'activity_summary' => $this->getActivitySummary($customer),
            'budgets' => $customer->budgets()->with([
                'services.items.product',
                'services.invoices',
            ])->orderBy('created_at', 'desc')->get(),
            'services' => $customer->services()->with([
                'budget',
                'items.product',
                'invoices',
            ])->orderBy('created_at', 'desc')->get(),
            'invoices' => $customer->invoices()->with([
                'service.budget',
                'items.product',
            ])->orderBy('created_at', 'desc')->get(),
            'interactions' => $customer->interactions()->with('createdBy')
                ->orderBy('interaction_date', 'desc')->get(),
            'lifecycle_history' => $customer->lifecycleHistory()->with('movedBy')
                ->orderBy('created_at', 'desc')->get(),
        ];
    }

    private function exportPortfolioToPDF(array $portfolio, Customer $customer): ServiceResult
    {
        $filename = 'customer_portfolio_' . $customer->id . '_' . now()->format('Y-m-d_H-i-s') . '.pdf';
        $filepath = storage_path('app/public/exports/' . $filename);

        // Criar diretório se não existir
        $directory = dirname($filepath);
        if (! file_exists($directory)) {
            mkdir($directory, 0755, true);
        }

        // Renderizar view para PDF
        $html = view('exports.customer_portfolio_pdf', [
            'portfolio' => $portfolio,
            'customer' => $portfolio['customer'],
        ])->render();

        $pdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_top' => 10,
            'margin_bottom' => 10,
            'margin_left' => 10,
            'margin_right' => 10,
        ]);

        $pdf->WriteHTML($html);
        $pdf->Output($filepath, 'F');

        return $this->success([
            'filename' => $filename,
            'filepath' => $filepath,
            'url' => asset('storage/exports/' . $filename),
            'format' => 'pdf',
            'customer_id' => $customer->id,
        ], 'Exportação de portfólio PDF concluída');
    }

    private function exportPortfolioToExcel(array $portfolio, Customer $customer): ServiceResult
    {
        $filename = 'customer_portfolio_' . $customer->id . '_' . now()->format('Y-m-d_H-i-s') . '.xlsx';
        $filepath = storage_path('app/public/exports/' . $filename);

        // Criar diretório se não existir
        $directory = dirname($filepath);
        if (! file_exists($directory)) {
            mkdir($directory, 0755, true);
        }

        $spreadsheet = new Spreadsheet();
        $excelWriter = app(CustomerExcelExportService::class);

        // Exportar diferentes abas
        $excelWriter->exportPortfolioToExcel($spreadsheet, $portfolio, $customer);

        // Salvar arquivo
        $writer = new Xlsx($spreadsheet);
        $writer->save($filepath);

        return $this->success([
            'filename' => $filename,
            'filepath' => $filepath,
            'url' => asset('storage/exports/' . $filename),
            'format' => 'excel',
            'customer_id' => $customer->id,
        ], 'Exportação de portfólio Excel concluída');
    }

    private function exportPortfolioToJSON(array $portfolio, Customer $customer): ServiceResult
    {
        $filename = 'customer_portfolio_' . $customer->id . '_' . now()->format('Y-m-d_H-i-s') . '.json';
        $filepath = storage_path('app/public/exports/' . $filename);

        // Criar diretório se não existir
        $directory = dirname($filepath);
        if (! file_exists($directory)) {
            mkdir($directory, 0755, true);
        }

        // Converter objetos para arrays
        $portfolioData = [
            'customer' => $portfolio['customer']->toArray(),
            'financial_summary' => $portfolio['financial_summary'],
            'activity_summary' => $portfolio['activity_summary'],
            'budgets' => $portfolio['budgets']->toArray(),
            'services' => $portfolio['services']->toArray(),
            'invoices' => $portfolio['invoices']->toArray(),
            'interactions' => $portfolio['interactions']->toArray(),
            'lifecycle_history' => $portfolio['lifecycle_history']->toArray(),
        ];

        file_put_contents($filepath, json_encode($portfolioData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return $this->success([
            'filename' => $filename,
            'filepath' => $filepath,
            'url' => asset('storage/exports/' . $filename),
            'format' => 'json',
            'customer_id' => $customer->id,
        ], 'Exportação de portfólio JSON concluída');
    }

    private function getFinancialSummary(Customer $customer): array
    {
        return [
            'total_budgets' => $customer->budgets()->count(),
            'total_services' => $customer->services()->count(),
            'total_invoices' => $customer->invoices()->count(),
            'total_revenue' => $customer->invoices()->sum('total'),
            'pending_amount' => $customer->invoices()->where('status', 'pending')->sum('total'),
            'paid_amount' => $customer->invoices()->where('status', 'paid')->sum('total'),
            'average_invoice_value' => $customer->invoices()->avg('total') ?? 0,
        ];
    }

    private function getActivitySummary(Customer $customer): array
    {
        return [
            'last_interaction' => $customer->interactions()->latest('interaction_date')->first()?->interaction_date,
            'interaction_count' => $customer->interactions()->count(),
            'last_budget_date' => $customer->budgets()->latest('created_at')->first()?->created_at,
            'last_service_date' => $customer->services()->latest('created_at')->first()?->created_at,
            'last_invoice_date' => $customer->invoices()->latest('created_at')->first()?->created_at,
            'current_stage' => $customer->lifecycle_stage,
        ];
    }
}
```

### **✅ Estrutura de Importação**

```php
class CustomerImportService extends AbstractBaseService
{
    public function importCustomersFromFile(UploadedFile $file, array $mapping = []): ServiceResult
    {
        return $this->safeExecute(function() use ($file, $mapping) {
            // 1. Validar arquivo
            $validation = $this->validateImportFile($file);
            if (!$validation->isSuccess()) {
                return $validation;
            }

            // 2. Detectar formato
            $format = $this->detectFileFormat($file);

            // 3. Importar dados
            switch ($format) {
                case 'csv':
                    return $this->importFromCSV($file, $mapping);
                case 'excel':
                    return $this->importFromExcel($file, $mapping);
                case 'json':
                    return $this->importFromJSON($file, $mapping);
                default:
                    return $this->error('Formato de importação não suportado', OperationStatus::INVALID_DATA);
            }
        });
    }

    public function validateImportData(array $data): ServiceResult
    {
        return $this->safeExecute(function() use ($data) {
            $validationResults = [];
            $errors = [];
            $warnings = [];

            foreach ($data as $rowIndex => $row) {
                $rowValidation = $this->validateRow($row, $rowIndex);

                if (! $rowValidation['valid']) {
                    $errors = array_merge($errors, $rowValidation['errors']);
                } else {
                    $warnings = array_merge($warnings, $rowValidation['warnings']);
                }

                $validationResults[] = [
                    'row' => $rowIndex + 1,
                    'valid' => $rowValidation['valid'],
                    'errors' => $rowValidation['errors'],
                    'warnings' => $rowValidation['warnings'],
                    'data' => $row,
                ];
            }

            $status = empty($errors) ? 'valid' : (empty($warnings) ? 'warning' : 'error');

            return $this->success([
                'status' => $status,
                'total_rows' => count($data),
                'valid_rows' => count(array_filter($validationResults, fn($r) => $r['valid'])),
                'invalid_rows' => count(array_filter($validationResults, fn($r) => ! $r['valid'])),
                'errors' => $errors,
                'warnings' => $warnings,
                'validation_results' => $validationResults,
            ], 'Validação de importação concluída');
        });
    }

    private function validateImportFile(UploadedFile $file): ServiceResult
    {
        // Validar tamanho do arquivo (máximo 10MB)
        if ($file->getSize() > 10 * 1024 * 1024) {
            return $this->error('Arquivo muito grande. Tamanho máximo: 10MB', OperationStatus::INVALID_DATA);
        }

        // Validar extensão
        $allowedExtensions = ['csv', 'xlsx', 'xls', 'json'];
        $extension = $file->getClientOriginalExtension();

        if (! in_array(strtolower($extension), $allowedExtensions)) {
            return $this->error('Extensão de arquivo não suportada', OperationStatus::INVALID_DATA);
        }

        return $this->success(null, 'Arquivo válido');
    }

    private function detectFileFormat(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();

        switch (strtolower($extension)) {
            case 'csv':
                return 'csv';
            case 'xlsx':
            case 'xls':
                return 'excel';
            case 'json':
                return 'json';
            default:
                return 'unknown';
        }
    }

    private function importFromCSV(UploadedFile $file, array $mapping): ServiceResult
    {
        $handle = fopen($file->getPathname(), 'r');
        $headers = fgetcsv($handle, 0, ',');
        $data = [];

        while (($row = fgetcsv($handle, 0, ',')) !== false) {
            $data[] = array_combine($headers, $row);
        }

        fclose($handle);

        return $this->processImportData($data, $mapping);
    }

    private function importFromExcel(UploadedFile $file, array $mapping): ServiceResult
    {
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getPathname());
        $worksheet = $spreadsheet->getActiveSheet();
        $data = $worksheet->toArray();

        if (empty($data)) {
            return $this->error('Nenhum dado encontrado no arquivo Excel', OperationStatus::NO_DATA);
        }

        $headers = array_shift($data); // Primeira linha como cabeçalho
        $mappedData = [];

        foreach ($data as $row) {
            $mappedData[] = array_combine($headers, $row);
        }

        return $this->processImportData($mappedData, $mapping);
    }

    private function importFromJSON(UploadedFile $file, array $mapping): ServiceResult
    {
        $content = file_get_contents($file->getPathname());
        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return $this->error('Erro ao decodificar JSON', OperationStatus::INVALID_DATA);
        }

        if (! is_array($data)) {
            return $this->error('Formato JSON inválido', OperationStatus::INVALID_DATA);
        }

        return $this->processImportData($data, $mapping);
    }

    private function processImportData(array $data, array $mapping): ServiceResult
    {
        return $this->safeExecute(function() use ($data, $mapping) {
            $importResults = [];
            $successCount = 0;
            $errorCount = 0;

            foreach ($data as $rowIndex => $row) {
                $result = $this->processImportRow($row, $mapping);

                if ($result->isSuccess()) {
                    $successCount++;
                } else {
                    $errorCount++;
                }

                $importResults[] = [
                    'row' => $rowIndex + 1,
                    'success' => $result->isSuccess(),
                    'message' => $result->getMessage(),
                    'data' => $result->getData(),
                ];
            }

            return $this->success([
                'total_rows' => count($data),
                'success_count' => $successCount,
                'error_count' => $errorCount,
                'results' => $importResults,
            ], 'Importação concluída');
        });
    }

    private function processImportRow(array $row, array $mapping): ServiceResult
    {
        return $this->safeExecute(function() use ($row, $mapping) {
            // 1. Mapear campos
            $mappedData = $this->mapImportFields($row, $mapping);

            // 2. Validar dados
            $validation = $this->validateImportRow($mappedData);
            if (! $validation->isSuccess()) {
                return $validation;
            }

            // 3. Verificar duplicidade
            $existingCustomer = $this->findExistingCustomer($mappedData);
            if ($existingCustomer) {
                return $this->updateExistingCustomer($existingCustomer, $mappedData);
            }

            // 4. Criar novo cliente
            return $this->createNewCustomer($mappedData);
        });
    }

    private function mapImportFields(array $row, array $mapping): array
    {
        $mappedData = [];

        // Mapeamento padrão se não fornecido
        if (empty($mapping)) {
            $mapping = $this->getDefaultMapping();
        }

        foreach ($mapping as $sourceField => $targetField) {
            if (isset($row[$sourceField])) {
                $mappedData[$targetField] = $row[$sourceField];
            }
        }

        return $mappedData;
    }

    private function getDefaultMapping(): array
    {
        return [
            // Dados principais
            'customer_status' => 'status',
            'customer_type' => 'type',
            'customer_created_at' => 'created_at',

            // Dados comuns
            'common_data_type' => 'common_data.type',
            'common_data_first_name' => 'common_data.first_name',
            'common_data_last_name' => 'common_data.last_name',
            'common_data_cpf' => 'common_data.cpf',
            'common_data_cnpj' => 'common_data.cnpj',
            'common_data_birth_date' => 'common_data.birth_date',
            'common_data_company_name' => 'common_data.company_name',

            // Contato
            'contact_email' => 'contact.email',
            'contact_phone' => 'contact.phone',
            'contact_phone_business' => 'contact.phone_business',
            'contact_email_business' => 'contact.email_business',
            'contact_website' => 'contact.website',

            // Endereço
            'address_address' => 'address.address',
            'address_number' => 'address.address_number',
            'address_complement' => 'address.complement',
            'address_neighborhood' => 'address.neighborhood',
            'address_city' => 'address.city',
            'address_state' => 'address.state',
            'address_cep' => 'address.cep',

            // Dados empresariais
            'business_data_state_registration' => 'business_data.state_registration',
            'business_data_municipal_registration' => 'business_data.municipal_registration',
            'business_data_opening_date' => 'business_data.opening_date',
            'business_data_cnae' => 'business_data.cnae',
        ];
    }

    private function validateImportRow(array $data): ServiceResult
    {
        $errors = [];

        // Validar tipo de cliente
        if (! in_array($data['type'] ?? '', ['individual', 'company'])) {
            $errors[] = 'Tipo de cliente inválido';
        }

        // Validar status
        if (! in_array($data['status'] ?? '', ['active', 'inactive', 'pending'])) {
            $errors[] = 'Status de cliente inválido';
        }

        // Validar CPF/CNPJ
        if (isset($data['common_data']['cpf']) && $data['common_data']['cpf']) {
            if (! $this->isValidCPF($data['common_data']['cpf'])) {
                $errors[] = 'CPF inválido';
            }
        }

        if (isset($data['common_data']['cnpj']) && $data['common_data']['cnpj']) {
            if (! $this->isValidCNPJ($data['common_data']['cnpj'])) {
                $errors[] = 'CNPJ inválido';
            }
        }

        // Validar e-mail
        if (isset($data['contact']['email']) && $data['contact']['email']) {
            if (! filter_var($data['contact']['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'E-mail inválido';
            }
        }

        // Validar CEP
        if (isset($data['address']['cep']) && $data['address']['cep']) {
            if (! $this->isValidCEP($data['address']['cep'])) {
                $errors[] = 'CEP inválido';
            }
        }

        if (! empty($errors)) {
            return $this->error(implode(', ', $errors), OperationStatus::INVALID_DATA);
        }

        return $this->success(null, 'Linha válida');
    }

    private function findExistingCustomer(array $data): ?Customer
    {
        // Buscar por CPF/CNPJ
        if (isset($data['common_data']['cpf']) && $data['common_data']['cpf']) {
            return Customer::whereHas('commonData', function($query) use ($data) {
                $query->where('cpf', $data['common_data']['cpf']);
            })->first();
        }

        if (isset($data['common_data']['cnpj']) && $data['common_data']['cnpj']) {
            return Customer::whereHas('commonData', function($query) use ($data) {
                $query->where('cnpj', $data['common_data']['cnpj']);
            })->first();
        }

        // Buscar por e-mail
        if (isset($data['contact']['email']) && $data['contact']['email']) {
            return Customer::whereHas('contact', function($query) use ($data) {
                $query->where('email', $data['contact']['email']);
            })->first();
        }

        return null;
    }

    private function updateExistingCustomer(Customer $customer, array $data): ServiceResult
    {
        // Atualizar dados do cliente
        $customer->update([
            'status' => $data['status'] ?? $customer->status,
            'type' => $data['type'] ?? $customer->type,
        ]);

        // Atualizar dados comuns
        if (isset($data['common_data'])) {
            $customer->commonData()->updateOrCreate([], $data['common_data']);
        }

        // Atualizar contato
        if (isset($data['contact'])) {
            $customer->contact()->updateOrCreate([], $data['contact']);
        }

        // Atualizar endereço
        if (isset($data['address'])) {
            $customer->address()->updateOrCreate([], $data['address']);
        }

        // Atualizar dados empresariais
        if (isset($data['business_data'])) {
            $customer->businessData()->updateOrCreate([], $data['business_data']);
        }

        return $this->success($customer, 'Cliente atualizado com sucesso');
    }

    private function createNewCustomer(array $data): ServiceResult
    {
        return $this->safeExecute(function() use ($data) {
            // Criar cliente
            $customer = Customer::create([
                'tenant_id' => $this->getTenantId(),
                'status' => $data['status'] ?? 'active',
                'type' => $data['type'] ?? 'individual',
            ]);

            // Criar dados comuns
            if (isset($data['common_data'])) {
                $customer->commonData()->create($data['common_data']);
            }

            // Criar contato
            if (isset($data['contact'])) {
                $customer->contact()->create($data['contact']);
            }

            // Criar endereço
            if (isset($data['address'])) {
                $customer->address()->create($data['address']);
            }

            // Criar dados empresariais
            if (isset($data['business_data'])) {
                $customer->businessData()->create($data['business_data']);
            }

            return $this->success($customer, 'Cliente criado com sucesso');
        });
    }

    private function validateRow(array $row, int $rowIndex): array
    {
        $errors = [];
        $warnings = [];

        // Validar campos obrigatórios
        if (! isset($row['customer_type']) || empty($row['customer_type'])) {
            $errors[] = 'Tipo de cliente é obrigatório';
        }

        if (! isset($row['common_data_first_name']) || empty($row['common_data_first_name'])) {
            $errors[] = 'Nome é obrigatório';
        }

        // Validar CPF/CNPJ
        if (isset($row['common_data_cpf']) && $row['common_data_cpf']) {
            if (! $this->isValidCPF($row['common_data_cpf'])) {
                $errors[] = 'CPF inválido';
            }
        }

        if (isset($row['common_data_cnpj']) && $row['common_data_cnpj']) {
            if (! $this->isValidCNPJ($row['common_data_cnpj'])) {
                $errors[] = 'CNPJ inválido';
            }
        }

        // Validar e-mail
        if (isset($row['contact_email']) && $row['contact_email']) {
            if (! filter_var($row['contact_email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'E-mail inválido';
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }
}
```

### **✅ Serviço de Exportação Excel Avançada**

```php
class CustomerExcelExportService extends AbstractBaseService
{
    public function exportPortfolioToExcel(Spreadsheet $spreadsheet, array $portfolio, Customer $customer): void
    {
        // Aba 1: Informações do Cliente
        $this->exportCustomerInfo($spreadsheet, $portfolio['customer'], $customer);

        // Aba 2: Resumo Financeiro
        $this->exportFinancialSummary($spreadsheet, $portfolio['financial_summary']);

        // Aba 3: Orçamentos
        $this->exportBudgets($spreadsheet, $portfolio['budgets']);

        // Aba 4: Serviços
        $this->exportServices($spreadsheet, $portfolio['services']);

        // Aba 5: Faturas
        $this->exportInvoices($spreadsheet, $portfolio['invoices']);

        // Aba 6: Interações
        $this->exportInteractions($spreadsheet, $portfolio['interactions']);

        // Aba 7: Histórico de Ciclo de Vida
        $this->exportLifecycleHistory($spreadsheet, $portfolio['lifecycle_history']);
    }

    private function exportCustomerInfo(Spreadsheet $spreadsheet, $customer, Customer $originalCustomer): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Informações do Cliente');

        $row = 1;

        // Dados principais
        $sheet->setCellValue('A' . $row, 'ID do Cliente:');
        $sheet->setCellValue('B' . $row, $originalCustomer->id);
        $row++;

        $sheet->setCellValue('A' . $row, 'Status:');
        $sheet->setCellValue('B' . $row, $originalCustomer->status);
        $row++;

        $sheet->setCellValue('A' . $row, 'Tipo:');
        $sheet->setCellValue('B' . $row, $originalCustomer->type);
        $row++;

        // Dados comuns
        if ($customer->commonData) {
            $row++;
            $sheet->setCellValue('A' . $row, '--- Dados Comuns ---');
            $row++;

            $sheet->setCellValue('A' . $row, 'Nome:');
            $sheet->setCellValue('B' . $row, $customer->commonData->first_name . ' ' . $customer->commonData->last_name);
            $row++;

            $sheet->setCellValue('A' . $row, 'Tipo de Pessoa:');
            $sheet->setCellValue('B' . $row, $customer->commonData->type);
            $row++;

            if ($customer->commonData->cpf) {
                $sheet->setCellValue('A' . $row, 'CPF:');
                $sheet->setCellValue('B' . $row, $customer->commonData->cpf);
                $row++;
            }

            if ($customer->commonData->cnpj) {
                $sheet->setCellValue('A' . $row, 'CNPJ:');
                $sheet->setCellValue('B' . $row, $customer->commonData->cnpj);
                $row++;
            }

            if ($customer->commonData->birth_date) {
                $sheet->setCellValue('A' . $row, 'Data de Nascimento:');
                $sheet->setCellValue('B' . $row, $customer->commonData->birth_date->format('d/m/Y'));
                $row++;
            }

            if ($customer->commonData->company_name) {
                $sheet->setCellValue('A' . $row, 'Razão Social:');
                $sheet->setCellValue('B' . $row, $customer->commonData->company_name);
                $row++;
            }
        }

        // Contato
        if ($customer->contact) {
            $row++;
            $sheet->setCellValue('A' . $row, '--- Contato ---');
            $row++;

            if ($customer->contact->email) {
                $sheet->setCellValue('A' . $row, 'E-mail:');
                $sheet->setCellValue('B' . $row, $customer->contact->email);
                $row++;
            }

            if ($customer->contact->phone) {
                $sheet->setCellValue('A' . $row, 'Telefone:');
                $sheet->setCellValue('B' . $row, $customer->contact->phone);
                $row++;
            }

            if ($customer->contact->website) {
                $sheet->setCellValue('A' . $row, 'Website:');
                $sheet->setCellValue('B' . $row, $customer->contact->website);
                $row++;
            }
        }

        // Endereço
        if ($customer->address) {
            $row++;
            $sheet->setCellValue('A' . $row, '--- Endereço ---');
            $row++;

            $sheet->setCellValue('A' . $row, 'Endereço:');
            $sheet->setCellValue('B' . $row, $customer->address->address);
            $row++;

            if ($customer->address->address_number) {
                $sheet->setCellValue('A' . $row, 'Número:');
                $sheet->setCellValue('B' . $row, $customer->address->address_number);
                $row++;
            }

            if ($customer->address->complement) {
                $sheet->setCellValue('A' . $row, 'Complemento:');
                $sheet->setCellValue('B' . $row, $customer->address->complement);
                $row++;
            }

            $sheet->setCellValue('A' . $row, 'Bairro:');
            $sheet->setCellValue('B' . $row, $customer->address->neighborhood);
            $row++;

            $sheet->setCellValue('A' . $row, 'Cidade:');
            $sheet->setCellValue('B' . $row, $customer->address->city);
            $row++;

            $sheet->setCellValue('A' . $row, 'Estado:');
            $sheet->setCellValue('B' . $row, $customer->address->state);
            $row++;

            $sheet->setCellValue('A' . $row, 'CEP:');
            $sheet->setCellValue('B' . $row, $customer->address->cep);
            $row++;
        }
    }

    private function exportFinancialSummary(Spreadsheet $spreadsheet, array $financialSummary): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Resumo Financeiro');

        $row = 1;

        $sheet->setCellValue('A' . $row, 'Métrica');
        $sheet->setCellValue('B' . $row, 'Valor');
        $row++;

        $sheet->setCellValue('A' . $row, 'Total de Orçamentos');
        $sheet->setCellValue('B' . $row, $financialSummary['total_budgets']);
        $row++;

        $sheet->setCellValue('A' . $row, 'Total de Serviços');
        $sheet->setCellValue('B' . $row, $financialSummary['total_services']);
        $row++;

        $sheet->setCellValue('A' . $row, 'Total de Faturas');
        $sheet->setCellValue('B' . $row, $financialSummary['total_invoices']);
        $row++;

        $sheet->setCellValue('A' . $row, 'Receita Total');
        $sheet->setCellValue('B' . $row, 'R$ ' . number_format($financialSummary['total_revenue'], 2, ',', '.'));
        $row++;

        $sheet->setCellValue('A' . $row, 'Valor Pendente');
        $sheet->setCellValue('B' . $row, 'R$ ' . number_format($financialSummary['pending_amount'], 2, ',', '.'));
        $row++;

        $sheet->setCellValue('A' . $row, 'Valor Pago');
        $sheet->setCellValue('B' . $row, 'R$ ' . number_format($financialSummary['paid_amount'], 2, ',', '.'));
        $row++;

        $sheet->setCellValue('A' . $row, 'Valor Médio de Fatura');
        $sheet->setCellValue('B' . $row, 'R$ ' . number_format($financialSummary['average_invoice_value'], 2, ',', '.'));
    }

    private function exportBudgets(Spreadsheet $spreadsheet, Collection $budgets): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Orçamentos');

        $row = 1;

        // Cabeçalho
        $headers = [
            'ID', 'Código', 'Descrição', 'Valor Total', 'Status', 'Data de Criação', 'Data de Vencimento'
        ];

        foreach ($headers as $colIndex => $header) {
            $sheet->setCellValueByColumnAndRow($colIndex + 1, $row, $header);
        }
        $row++;

        // Dados
        foreach ($budgets as $budget) {
            $sheet->setCellValueByColumnAndRow(1, $row, $budget->id);
            $sheet->setCellValueByColumnAndRow(2, $row, $budget->code);
            $sheet->setCellValueByColumnAndRow(3, $row, $budget->description);
            $sheet->setCellValueByColumnAndRow(4, $row, 'R$ ' . number_format($budget->total_value, 2, ',', '.'));
            $sheet->setCellValueByColumnAndRow(5, $row, $budget->status);
            $sheet->setCellValueByColumnAndRow(6, $row, $budget->created_at->format('d/m/Y H:i:s'));
            $sheet->setCellValueByColumnAndRow(7, $row, $budget->due_date?->format('d/m/Y'));
            $row++;
        }
    }

    private function exportServices(Spreadsheet $spreadsheet, Collection $services): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Serviços');

        $row = 1;

        // Cabeçalho
        $headers = [
            'ID', 'Código', 'Descrição', 'Valor Total', 'Status', 'Data de Criação', 'Orçamento'
        ];

        foreach ($headers as $colIndex => $header) {
            $sheet->setCellValueByColumnAndRow($colIndex + 1, $row, $header);
        }
        $row++;

        // Dados
        foreach ($services as $service) {
            $sheet->setCellValueByColumnAndRow(1, $row, $service->id);
            $sheet->setCellValueByColumnAndRow(2, $row, $service->code);
            $sheet->setCellValueByColumnAndRow(3, $row, $service->description);
            $sheet->setCellValueByColumnAndRow(4, $row, 'R$ ' . number_format($service->total, 2, ',', '.'));
            $sheet->setCellValueByColumnAndRow(5, $row, $service->status);
            $sheet->setCellValueByColumnAndRow(6, $row, $service->created_at->format('d/m/Y H:i:s'));
            $sheet->setCellValueByColumnAndRow(7, $row, $service->budget->code);
            $row++;
        }
    }

    private function exportInvoices(Spreadsheet $spreadsheet, Collection $invoices): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Faturas');

        $row = 1;

        // Cabeçalho
        $headers = [
            'ID', 'Código', 'Descrição', 'Valor Total', 'Status', 'Data de Criação', 'Data de Vencimento', 'Data de Pagamento', 'Método de Pagamento'
        ];

        foreach ($headers as $colIndex => $header) {
            $sheet->setCellValueByColumnAndRow($colIndex + 1, $row, $header);
        }
        $row++;

        // Dados
        foreach ($invoices as $invoice) {
            $sheet->setCellValueByColumnAndRow(1, $row, $invoice->id);
            $sheet->setCellValueByColumnAndRow(2, $row, $invoice->code);
            $sheet->setCellValueByColumnAndRow(3, $row, $invoice->description);
            $sheet->setCellValueByColumnAndRow(4, $row, 'R$ ' . number_format($invoice->total, 2, ',', '.'));
            $sheet->setCellValueByColumnAndRow(5, $row, $invoice->status);
            $sheet->setCellValueByColumnAndRow(6, $row, $invoice->created_at->format('d/m/Y H:i:s'));
            $sheet->setCellValueByColumnAndRow(7, $row, $invoice->due_date?->format('d/m/Y'));
            $sheet->setCellValueByColumnAndRow(8, $row, $invoice->transaction_date?->format('d/m/Y'));
            $sheet->setCellValueByColumnAndRow(9, $row, $invoice->payment_method);
            $row++;
        }
    }

    private function exportInteractions(Spreadsheet $spreadsheet, Collection $interactions): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Interações');

        $row = 1;

        // Cabeçalho
        $headers = [
            'ID', 'Tipo', 'Descrição', 'Data', 'Criado Por', 'Status', 'Resultado'
        ];

        foreach ($headers as $colIndex => $header) {
            $sheet->setCellValueByColumnAndRow($colIndex + 1, $row, $header);
        }
        $row++;

        // Dados
        foreach ($interactions as $interaction) {
            $sheet->setCellValueByColumnAndRow(1, $row, $interaction->id);
            $sheet->setCellValueByColumnAndRow(2, $row, $interaction->interaction_type);
            $sheet->setCellValueByColumnAndRow(3, $row, $interaction->description);
            $sheet->setCellValueByColumnAndRow(4, $row, $interaction->interaction_date->format('d/m/Y H:i:s'));
            $sheet->setCellValueByColumnAndRow(5, $row, $interaction->createdBy?->name);
            $sheet->setCellValueByColumnAndRow(6, $row, $interaction->status);
            $sheet->setCellValueByColumnAndRow(7, $row, $interaction->outcome);
            $row++;
        }
    }

    private function exportLifecycleHistory(Spreadsheet $spreadsheet, Collection $lifecycleHistory): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Histórico de Ciclo de Vida');

        $row = 1;

        // Cabeçalho
        $headers = [
            'ID', 'Estágio Anterior', 'Estágio Atual', 'Motivo', 'Observações', 'Movido Por', 'Data'
        ];

        foreach ($headers as $colIndex => $header) {
            $sheet->setCellValueByColumnAndRow($colIndex + 1, $row, $header);
        }
        $row++;

        // Dados
        foreach ($lifecycleHistory as $history) {
            $sheet->setCellValueByColumnAndRow(1, $row, $history->id);
            $sheet->setCellValueByColumnAndRow(2, $row, $history->from_stage);
            $sheet->setCellValueByColumnAndRow(3, $row, $history->to_stage);
            $sheet->setCellValueByColumnAndRow(4, $row, $history->reason);
            $sheet->setCellValueByColumnAndRow(5, $row, $history->notes);
            $sheet->setCellValueByColumnAndRow(6, $row, $history->movedBy?->name);
            $sheet->setCellValueByColumnAndRow(7, $row, $history->created_at->format('d/m/Y H:i:s'));
            $row++;
        }
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

    $result = $this->exportService->exportCustomers([
        'tenant_id' => $tenant->id,
    ], 'csv');

    $this->assertTrue($result->isSuccess());

    $exportData = $result->getData();
    $this->assertEquals('csv', $exportData['format']);
    $this->assertEquals(5, $exportData['records']);
    $this->assertFileExists($exportData['filepath']);
}

public function testCustomerExportExcel()
{
    $tenant = Tenant::factory()->create();
    Customer::factory()->count(3)->create(['tenant_id' => $tenant->id]);

    $result = $this->exportService->exportCustomers([
        'tenant_id' => $tenant->id,
    ], 'excel');

    $this->assertTrue($result->isSuccess());

    $exportData = $result->getData();
    $this->assertEquals('excel', $exportData['format']);
    $this->assertEquals(3, $exportData['records']);
    $this->assertFileExists($exportData['filepath']);
}

public function testCustomerPortfolioExportPDF()
{
    $customer = Customer::factory()->create();
    Budget::factory()->count(2)->create(['customer_id' => $customer->id]);

    $result = $this->exportService->exportCustomerPortfolio($customer, 'pdf');

    $this->assertTrue($result->isSuccess());

    $exportData = $result->getData();
    $this->assertEquals('pdf', $exportData['format']);
    $this->assertEquals($customer->id, $exportData['customer_id']);
    $this->assertFileExists($exportData['filepath']);
}

public function testCustomerPortfolioExportExcel()
{
    $customer = Customer::factory()->create();
    Budget::factory()->count(2)->create(['customer_id' => $customer->id]);

    $result = $this->exportService->exportCustomerPortfolio($customer, 'excel');

    $this->assertTrue($result->isSuccess());

    $exportData = $result->getData();
    $this->assertEquals('excel', $exportData['format']);
    $this->assertEquals($customer->id, $exportData['customer_id']);
    $this->assertFileExists($exportData['filepath']);
}
```

### **✅ Testes de Importação**

```php
public function testCustomerImportCSV()
{
    $csvContent = "customer_type,common_data_first_name,common_data_last_name,contact_email\n";
    $csvContent .= "individual,John,Doe,john@example.com\n";
    $csvContent .= "company,Acme Corp,,contact@acme.com\n";

    $file = tmpfile();
    fwrite($file, $csvContent);
    $filePath = stream_get_meta_data($file)['uri'];

    $uploadedFile = new UploadedFile($filePath, 'test.csv', 'text/csv', null, true);

    $result = $this->importService->importCustomersFromFile($uploadedFile);

    $this->assertTrue($result->isSuccess());

    $importData = $result->getData();
    $this->assertEquals(2, $importData['total_rows']);
    $this->assertEquals(2, $importData['success_count']);
    $this->assertEquals(0, $importData['error_count']);
}

public function testCustomerImportExcel()
{
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    $sheet->setCellValue('A1', 'customer_type');
    $sheet->setCellValue('B1', 'common_data_first_name');
    $sheet->setCellValue('C1', 'common_data_last_name');
    $sheet->setCellValue('D1', 'contact_email');

    $sheet->setCellValue('A2', 'individual');
    $sheet->setCellValue('B2', 'John');
    $sheet->setCellValue('C2', 'Doe');
    $sheet->setCellValue('D2', 'john@example.com');

    $writer = new Xlsx($spreadsheet);
    $tempFile = tempnam(sys_get_temp_dir(), 'test');
    $writer->save($tempFile);

    $uploadedFile = new UploadedFile($tempFile, 'test.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

    $result = $this->importService->importCustomersFromFile($uploadedFile);

    $this->assertTrue($result->isSuccess());

    $importData = $result->getData();
    $this->assertEquals(1, $importData['total_rows']);
    $this->assertEquals(1, $importData['success_count']);
}

public function testCustomerImportValidation()
{
    $csvContent = "customer_type,common_data_first_name,common_data_last_name,contact_email,common_data_cpf\n";
    $csvContent .= "individual,John,Doe,invalid-email,12345678909\n"; // CPF inválido

    $file = tmpfile();
    fwrite($file, $csvContent);
    $filePath = stream_get_meta_data($file)['uri'];

    $uploadedFile = new UploadedFile($filePath, 'test.csv', 'text/csv', null, true);

    $result = $this->importService->validateImportData([
        [
            'customer_type' => 'individual',
            'common_data_first_name' => 'John',
            'common_data_last_name' => 'Doe',
            'contact_email' => 'invalid-email',
            'common_data_cpf' => '12345678909',
        ]
    ]);

    $this->assertTrue($result->isSuccess());

    $validationData = $result->getData();
    $this->assertEquals('error', $validationData['status']);
    $this->assertEquals(1, $validationData['total_rows']);
    $this->assertEquals(0, $validationData['valid_rows']);
    $this->assertGreaterThan(0, count($validationData['errors']));
}
```

## 🚀 Implementação Gradual

### **Fase 1: Foundation**
- [ ] Implementar CustomerExportService básico
- [ ] Criar CustomerImportService básico
- [ ] Implementar validações de arquivos
- [ ] Sistema de mapeamento de campos

### **Fase 2: Core Features**
- [ ] Implementar exportação para diferentes formatos (CSV, Excel, JSON)
- [ ] Criar CustomerExcelExportService avançado
- [ ] Sistema de importação com validação
- [ ] Exportação de portfólio completo

### **Fase 3: Advanced Features**
- [ ] Sistema de templates de exportação
- [ ] Importação com mapeamento inteligente
- [ ] Processamento em lote para grandes volumes
- [ ] Sistema de auditoria de importações/exportações

### **Fase 4: Integration**
- [ ] Integração com sistemas externos
- [ ] API REST para importação/exportação
- [ ] Sistema de agendamento de exportações
- [ ] Integração com cloud storage

## 📚 Documentação Relacionada

- [CustomerExportService](../../app/Services/Domain/CustomerExportService.php)
- [CustomerImportService](../../app/Services/Domain/CustomerImportService.php)
- [CustomerExcelExportService](../../app/Services/Domain/CustomerExcelExportService.php)
- [Export Views](../../resources/views/exports/)

## 🎯 Benefícios

### **✅ Integração com Sistemas Externos**
- Exportação em múltiplos formatos
- Importação de dados de outros sistemas
- Mapeamento flexível de campos
- Validação de dados durante importação

### **✅ Backup e Recuperação**
- Exportação completa de dados
- Backup de portfólio de clientes
- Recuperação de dados em caso de problemas
- Histórico de exportações

### **✅ Análise de Dados**
- Exportação para ferramentas de BI
- Relatórios detalhados
- Análise de portfólio completo
- Métricas financeiras detalhadas

### **✅ Conformidade**
- Exportação para auditoria
- Conformidade com requisitos legais
- Histórico de alterações
- Rastreabilidade de dados

---

**Última atualização:** 10/01/2026
**Versão:** 1.0.0
**Status:** ✅ Implementado e em uso
