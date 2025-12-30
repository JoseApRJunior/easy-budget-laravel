<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Abstracts\Controller;
use App\Http\Requests\ReportGenerateRequest;
use App\Models\Customer;
use App\Services\Domain\ReportService;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Controlador principal para gerenciamento de relatórios
 * Gerencia interface web e operações básicas
 */
class ReportController extends Controller
{
    /**
     * Índice de relatórios
     */
    public function index(Request $request): View
    {
        $filters = $request->only(['search', 'type', 'status', 'format', 'start_date', 'end_date']);
        $result = app(ReportService::class)->getFilteredReports($filters, ['user']);
        if (! $result->isSuccess()) {
            abort(500, 'Erro ao carregar relatórios');
        }

        $stats = app(ReportService::class)->getReportStats();
        $recentReports = app(ReportService::class)->getRecentReports(10);

        return view('pages.report.index', [
            'reports' => $result->getData(),
            'recent_reports' => $recentReports->getData(),
            'filters' => $filters,
            'stats' => $stats->getData(),
        ]);
    }

    /**
     * Dashboard de relatórios
     */
    public function dashboard(): View
    {
        $stats = app(ReportService::class)->getReportStats();
        $recentReports = app(ReportService::class)->getRecentReports(10);

        $statsData = $stats->getData();

        // Prepare stats for dashboard
        $dashboardStats = [
            'total_reports' => $statsData['total_reports'] ?? 0,
            'recent_reports' => $recentReports->getData(),
            'reports_by_type' => collect($statsData['by_type'] ?? []),
            'most_used_report' => $this->getMostUsedReportType($statsData['by_type'] ?? []),
        ];

        return view('pages.report.dashboard', [
            'stats' => $dashboardStats,
        ]);
    }

    /**
     * Get most used report type
     */
    private function getMostUsedReportType(array $reportsByType): ?string
    {
        if (empty($reportsByType)) {
            return null;
        }

        $maxCount = max(array_column($reportsByType, 'count'));
        foreach ($reportsByType as $report) {
            if ($report['count'] === $maxCount) {
                return $report['type'];
            }
        }

        return null;
    }

    /**
     * Formulário de geração de relatório
     */
    public function create(): View
    {
        return view('reports.create', [
            'types' => ['budget' => 'Orçamentos', 'customer' => 'Clientes', 'product' => 'Produtos', 'service' => 'Serviços'],
            'formats' => ['pdf' => 'PDF', 'excel' => 'Excel', 'csv' => 'CSV'],
        ]);
    }

    /**
     * Solicitar geração de relatório
     */
    public function store(ReportGenerateRequest $request)
    {
        $result = app(ReportService::class)->generateReport($request->validated());
        if ($result->isSuccess()) {
            return redirect()->route('reports.index')->with('success', $result->getMessage());
        } else {
            return redirect()->back()->with('error', $result->getMessage())->withInput();
        }
    }

    /**
     * Fazer download do relatório
     */
    public function download(string $hash): BinaryFileResponse
    {
        $result = app(ReportService::class)->downloadReport($hash);
        if (! $result->isSuccess()) {
            abort(404, $result->getMessage());
        }

        $data = $result->getData();
        $filePath = Storage::disk('reports')->path($data['file_path']);

        return response()->download($filePath, $data['file_name'], [
            'Content-Type' => $data['mime_type'],
        ]);
    }

    /**
     * Relatório financeiro
     */
    public function financial(): View
    {
        return view('pages.report.financial.financial');
    }

    /**
     * Relatório de clientes
     */
    public function customers(): View
    {
        return view('pages.report.customer.customer');
    }

    /**
     * Relatório de produtos
     */
    public function products(): View
    {
        return view('pages.report.product.product');
    }

    /**
     * Relatório de orçamentos
     */
    public function budgets(Request $request): View
    {
        $filters = $request->only(['code', 'start_date', 'end_date', 'customer_name', 'total_min', 'status']);

        $budgetService = app(\App\Services\Domain\BudgetService::class);
        $result = $budgetService->getFilteredBudgets($filters);

        if (! $result->isSuccess()) {
            abort(500, 'Erro ao buscar orçamentos');
        }

        return view('pages.report.budget.budget', [
            'budgets' => $result->getData(),
            'filters' => $filters,
        ]);
    }

    /**
     * Gerar PDF de orçamentos (LEGACY)
     */
    public function budgetsPdf(Request $request): Response
    {
        $filters = $request->only(['code', 'start_date', 'end_date', 'customer_name', 'total', 'status']);

        $budgetService = app(\App\Services\Domain\BudgetService::class);
        $result = $budgetService->getFilteredBudgets($filters);
        if (! $result->isSuccess()) {
            abort(500, 'Erro ao buscar dados');
        }

        $budgets = $result->getData();
        $totals = $this->calculateTotals($budgets);

        $html = view('pages.report.budget.pdf_budget', compact('budgets', 'totals', 'filters'))->render();
        $filename = $this->generateFileName('orcamentos', 'pdf', count($budgets));

        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 12,
            'margin_right' => 12,
            'margin_top' => 14,
            'margin_bottom' => 14,
        ]);
        $mpdf->WriteHTML($html);
        $content = $mpdf->Output('', 'S');

        return response($content, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }

    private function calculateTotals(Collection $budgets): array
    {
        return [
            'count' => $budgets->count(),
            'sum' => $budgets->sum('total'),
            'avg' => $budgets->avg('total') ?? 0,
        ];
    }

    private function generateFileName(string $type, string $format, int $count): string
    {
        $timestamp = now()->format('Ymd_His');
        $countFormatted = str_pad($count, 3, '0', STR_PAD_LEFT);

        return "relatorio_{$type}_{$timestamp}_{$countFormatted}_registros.{$format}";
    }

    /**
     * Exportação PDF de orçamentos
     */
    public function budgets_pdf(Request $request)
    {
        // Get filters from request
        $filters = $request->only(['code', 'start_date', 'end_date', 'customer_name', 'total', 'status']);

        // Get budget data
        $budgetService = app(\App\Services\Domain\BudgetService::class);
        $result = $budgetService->getFilteredBudgets($filters);

        if (! $result->isSuccess()) {
            return response()->json(['error' => 'Erro ao buscar dados'], 500);
        }

        $budgets = $result->getData();

        if ($budgets->isEmpty()) {
            return response()->json(['error' => 'Nenhum orçamento encontrado'], 404);
        }

        // Get provider data for header
        /** @var User $user */
        $user = auth()->user();
        $provider = $user->provider()->with(['commonData', 'contact', 'address', 'businessData'])->first();

        // Calculate totals
        $totals = $this->calculateTotals($budgets);

        // Generate HTML for PDF
        $html = view('pages.report.budget.pdf_budget', [
            'budgets' => $budgets,
            'totals' => $totals,
            'filters' => $filters,
            'provider' => $provider,
            'generated_at' => now()->format('d/m/Y H:i:s'),
            'generated_by' => auth()->user()->name,
        ])->render();

        // Create PDF using mPDF
        $mpdf = new \Mpdf\Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4',
            'margin_left' => 12,
            'margin_right' => 12,
            'margin_top' => 14,
            'margin_bottom' => 14,
            'margin_header' => 8,
            'margin_footer' => 8,
        ]);

        // Add header and footer
        $mpdf->SetHeader('Relatório de Orçamentos - '.config('app.name').'||Gerado em: '.now()->format('d/m/Y'));
        $mpdf->SetFooter('Página {PAGENO} de {nb}|Usuário: '.auth()->user()->name.'|'.config('app.url'));

        // Write HTML content
        $mpdf->WriteHTML($html);

        // Generate filename
        $count = $budgets->count();
        $timestamp = now()->format('Ymd_His');
        $filename = "relatorio_orcamentos_{$timestamp}_{$count}_registros.pdf";

        // Return PDF as download
        $content = $mpdf->Output('', 'S');

        return response($content, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="'.$filename.'"',
        ]);
    }

    /**
     * Exportação Excel de orçamentos
     */
    public function budgets_excel(Request $request)
    {
        // Get filters from request
        $filters = $request->only(['code', 'start_date', 'end_date', 'customer_name', 'total', 'status']);

        // Get budget data
        $budgetService = app(\App\Services\Domain\BudgetService::class);
        $result = $budgetService->getFilteredBudgets($filters);

        if (! $result->isSuccess()) {
            return response()->json(['error' => 'Erro ao buscar dados'], 500);
        }

        $budgets = $result->getData();

        if ($budgets->isEmpty()) {
            return response()->json(['error' => 'Nenhum orçamento encontrado'], 404);
        }

        // Get provider data for header
        /** @var User $user */
        $user = auth()->user();
        $provider = $user->provider()->with(['commonData', 'contact', 'address', 'businessData'])->first();
        $companyName = $provider && $provider->commonData ? ($provider->commonData->company_name ?: ($provider->commonData->first_name . ' ' . $provider->commonData->last_name)) : $user->name;

        // Prepare data for Excel
        $excelData = [];
        
        // Header Info
        $excelData[] = ['RELATÓRIO DE ORÇAMENTOS'];
        $excelData[] = ['Empresa:', $companyName];
        $excelData[] = ['Gerado em:', now()->format('d/m/Y H:i:s')];
        $excelData[] = ['Gerado por:', $user->name];
        $excelData[] = ['Total de Registros:', $budgets->count()];
        $excelData[] = []; // Empty row
        
        // Table Header
        $excelData[] = ['Nº Orçamento', 'Cliente', 'Descrição', 'Data Criação', 'Data Vencimento', 'Valor Total', 'Status'];

        foreach ($budgets as $budget) {
            $customerName = 'Não informado';
            if ($budget->customer && $budget->customer->commonData) {
                $commonData = $budget->customer->commonData;
                $customerName = $commonData->company_name ?: ($commonData->first_name.' '.$commonData->last_name);
            }

            $excelData[] = [
                $budget->code,
                $customerName,
                $budget->description ?: 'Sem descrição',
                $budget->created_at->format('d/m/Y'),
                $budget->due_date ? \Carbon\Carbon::parse($budget->due_date)->format('d/m/Y') : 'N/A',
                number_format($budget->total, 2, ',', '.'),
                $budget->status_label ?? $budget->status,
            ];
        }

        // Create Excel file using PhpSpreadsheet
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        // Set data
        $sheet->fromArray($excelData, null, 'A1');

        // Style Main Title
        $sheet->mergeCells('A1:G1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // Style labels
        $sheet->getStyle('A2:A5')->getFont()->setBold(true);

        // Format table header row (now at row 7)
        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4'],
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
        ];

        $sheet->getStyle('A7:G7')->applyFromArray($headerStyle);

        // Auto-size columns
        foreach (range('A', 'G') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        // Format currency column (starts at row 8)
        $currencyStyle = [
            'numberFormat' => [
                'formatCode' => '#,##0.00',
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT,
            ],
        ];

        $lastRow = count($excelData);
        $sheet->getStyle("F8:F{$lastRow}")->applyFromArray($currencyStyle);

        // Set title and metadata
        $sheet->setTitle('Orçamentos');
        $spreadsheet->getProperties()
            ->setCreator(auth()->user()->name)
            ->setTitle('Relatório de Orçamentos')
            ->setDescription('Relatório de orçamentos gerado em '.now()->format('d/m/Y H:i:s'));

        // Create filename
        $count = $budgets->count();
        $timestamp = now()->format('Ymd_His');
        $filename = "relatorio_orcamentos_{$timestamp}_{$count}_registros.xlsx";

        // Save to temporary file
        $tempFile = tempnam(sys_get_temp_dir(), 'excel_');
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($tempFile);

        // Return file as download
        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ])->deleteFileAfterSend(true);
    }

    /**
     * Relatório de serviços
     */
    public function services(Request $request): View
    {
        $filters = $request->only(['name', 'price_min', 'price_max', 'start_date', 'end_date']);

        $serviceService = app(\App\Services\Domain\ServiceService::class);
        $result = $serviceService->getFilteredServices($filters);

        if (! $result->isSuccess()) {
            abort(500, 'Erro ao buscar serviços');
        }

        return view('pages.report.service.service', [
            'services' => $result->getData(),
            'filters' => $filters,
        ]);
    }

    /**
     * Relatório de analytics
     */
    public function analytics(): View
    {
        return view('pages.report.analytics.analytics');
    }

    /**
     * Relatório de estoque
     */
    public function inventory(Request $request): View
    {
        $filters = $request->only(['product_name', 'status', 'min_quantity', 'max_quantity']);

        $inventoryService = app(\App\Services\Domain\InventoryService::class);
        $result = $inventoryService->getFilteredInventory($filters);

        if (! $result->isSuccess()) {
            abort(500, 'Erro ao buscar dados de inventário');
        }

        return view('pages.report.inventory.inventory', [
            'inventory' => $result->getData(),
            'filters' => $filters,
        ]);
    }

    /**
     * Busca dados para relatório de clientes
     */
    public function customersSearch(Request $request): JsonResponse
    {
        $name = $request->input('name', '');
        $document = $request->input('document', '');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        Log::info('Filtros recebidos:', [
            'name' => $name,
            'document' => $document,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ]);

        $query = Customer::with(['commonData', 'contact'])
            ->where('tenant_id', auth()->user()->tenant_id);

        // Aplicar filtros separadamente com AND
        if (! empty($name)) {
            $query->whereHas('commonData', function ($subQ) use ($name) {
                $subQ->where('first_name', 'like', "%{$name}%")
                    ->orWhere('last_name', 'like', "%{$name}%")
                    ->orWhere('company_name', 'like', "%{$name}%");
            });
        }

        if (! empty($document)) {
            $cleanDocument = clean_document_partial($document, 1);
            Log::info('Documento limpo:', ['original' => $document, 'clean' => $cleanDocument]);

            if (! empty($cleanDocument)) {
                $query->whereHas('commonData', function ($subQ) use ($cleanDocument) {
                    $subQ->where('cpf', 'like', "%{$cleanDocument}%")
                        ->orWhere('cnpj', 'like', "%{$cleanDocument}%");
                });
            }
        }

        // Filtro por data de cadastro
        if (! empty($startDate)) {
            $query->where('created_at', '>=', $startDate.' 00:00:00');
        }

        if (! empty($endDate)) {
            $query->where('created_at', '<=', $endDate.' 23:59:59');
        }

        $customers = $query->get();

        Log::info('Resultados encontrados:', ['count' => $customers->count()]);

        $result = $customers->map(function ($customer) {
            $commonData = $customer->commonData;
            $contact = $customer->contact;

            return [
                'id' => $customer->id,
                'customer_name' => $commonData ?
                    ($commonData->company_name ?: ($commonData->first_name.' '.$commonData->last_name)) :
                    'Nome não informado',
                'cpf' => $commonData?->cpf ?? '',
                'cnpj' => $commonData?->cnpj ?? '',
                'email' => $contact?->email_personal ?? '',
                'email_business' => $contact?->email_business ?? '',
                'phone' => $contact?->phone_personal ?? '',
                'phone_business' => $contact?->phone_business ?? '',
                'created_at' => $customer->created_at->toISOString(),
            ];
        });

        return response()->json($result);
    }

    /**
     * Exportação PDF de clientes
     */
    public function customersPdf(Request $request)
    {
        $name = $request->input('name', '');
        $document = $request->input('document', '');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $query = Customer::with(['commonData', 'contact'])
            ->where('tenant_id', auth()->user()->tenant_id);

        // Aplicar mesmos filtros da busca
        if (! empty($name)) {
            $query->whereHas('commonData', function ($subQ) use ($name) {
                $subQ->where('first_name', 'like', "%{$name}%")
                    ->orWhere('last_name', 'like', "%{$name}%")
                    ->orWhere('company_name', 'like', "%{$name}%");
            });
        }

        if (! empty($document)) {
            $cleanDocument = clean_document_partial($document, 1);
            if (! empty($cleanDocument)) {
                $query->whereHas('commonData', function ($subQ) use ($cleanDocument) {
                    $subQ->where('cpf', 'like', "%{$cleanDocument}%")
                        ->orWhere('cnpj', 'like', "%{$cleanDocument}%");
                });
            }
        }

        if (! empty($startDate)) {
            $query->where('created_at', '>=', $startDate.' 00:00:00');
        }

        if (! empty($endDate)) {
            $query->where('created_at', '<=', $endDate.' 23:59:59');
        }

        $customers = $query->get();

        // Get provider data for header
        /** @var User $user */
        $user = auth()->user();
        $provider = $user->provider()->with(['commonData', 'contact', 'address', 'businessData'])->first();

        $filters = [
            'name' => $name,
            'document' => $document,
            'start_date' => $startDate,
            'end_date' => $endDate,
        ];

        return view('pages.report.customer.pdf_customer', [
            'customers' => $customers,
            'filters' => $filters,
            'provider' => $provider,
        ]);
    }

    /**
     * Exportação Excel de clientes
     */
    public function customersExcel(Request $request)
    {
        $name = $request->input('name', '');
        $document = $request->input('document', '');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $query = Customer::with(['commonData', 'contact'])
            ->where('tenant_id', auth()->user()->tenant_id);

        // Aplicar mesmos filtros da busca
        if (! empty($name)) {
            $query->whereHas('commonData', function ($subQ) use ($name) {
                $subQ->where('first_name', 'like', "%{$name}%")
                    ->orWhere('last_name', 'like', "%{$name}%")
                    ->orWhere('company_name', 'like', "%{$name}%");
            });
        }

        if (! empty($document)) {
            $cleanDocument = clean_document_partial($document, 1);
            if (! empty($cleanDocument)) {
                $query->whereHas('commonData', function ($subQ) use ($cleanDocument) {
                    $subQ->where('cpf', 'like', "%{$cleanDocument}%")
                        ->orWhere('cnpj', 'like', "%{$cleanDocument}%");
                });
            }
        }

        if (! empty($startDate)) {
            $query->where('created_at', '>=', $startDate.' 00:00:00');
        }

        if (! empty($endDate)) {
            $query->where('created_at', '<=', $endDate.' 23:59:59');
        }

        $customers = $query->get();

        if ($customers->isEmpty()) {
            return response()->json(['error' => 'Nenhum cliente encontrado'], 404);
        }

        // Get provider data for header
        /** @var User $user */
        $user = auth()->user();
        $provider = $user->provider()->with(['commonData', 'contact', 'address', 'businessData'])->first();
        $companyName = $provider && $provider->commonData ? ($provider->commonData->company_name ?: ($provider->commonData->first_name . ' ' . $provider->commonData->last_name)) : $user->name;

        // Prepare data for Excel
        $excelData = [];
        
        // Header Info
        $excelData[] = ['RELATÓRIO DE CLIENTES'];
        $excelData[] = ['Empresa:', $companyName];
        $excelData[] = ['Gerado em:', now()->format('d/m/Y H:i:s')];
        $excelData[] = ['Gerado por:', $user->name];
        $excelData[] = ['Total de Registros:', $customers->count()];
        $excelData[] = []; // Empty row
        
        // Table Header
        $excelData[] = ['Nome/Razão Social', 'CPF/CNPJ', 'E-mail Pessoal', 'E-mail Comercial', 'Telefone Pessoal', 'Telefone Comercial', 'Data Cadastro'];

        foreach ($customers as $customer) {
            $commonData = $customer->commonData;
            $contact = $customer->contact;

            $customerName = 'Não informado';
            $documentNumber = '';

            if ($commonData) {
                $customerName = $commonData->company_name ?: ($commonData->first_name.' '.$commonData->last_name);
                $documentNumber = $commonData->cpf ?: $commonData->cnpj ?: '';
            }

            $excelData[] = [
                $customerName,
                $documentNumber,
                $contact?->email_personal ?: 'N/A',
                $contact?->email_business ?: 'N/A',
                $contact?->phone_personal ?: 'N/A',
                $contact?->phone_business ?: 'N/A',
                $customer->created_at->format('d/m/Y'),
            ];
        }

        // Create Excel file using PhpSpreadsheet
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        // Set data
        $sheet->fromArray($excelData, null, 'A1');

        // Style Main Title
        $sheet->mergeCells('A1:G1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

        // Style labels
        $sheet->getStyle('A2:A5')->getFont()->setBold(true);

        // Format table header row (now at row 7)
        $headerStyle = [
            'font' => [
                'bold' => true,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4'],
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
        ];

        $sheet->getStyle('A7:G7')->applyFromArray($headerStyle);

        // Auto-size columns
        foreach (range('A', 'G') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        // Set title and metadata
        $sheet->setTitle('Clientes');
        $spreadsheet->getProperties()
            ->setCreator(auth()->user()->name)
            ->setTitle('Relatório de Clientes')
            ->setDescription('Relatório de clientes gerado em '.now()->format('d/m/Y H:i:s'));

        // Create filename
        $count = $customers->count();
        $timestamp = now()->format('Ymd_His');
        $filename = "relatorio_clientes_{$timestamp}_{$count}_registros.xlsx";

        // Save to temporary file
        $tempFile = tempnam(sys_get_temp_dir(), 'excel_');
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $writer->save($tempFile);

        // Return file as download
        return response()->download($tempFile, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ])->deleteFileAfterSend(true);
    }
}
