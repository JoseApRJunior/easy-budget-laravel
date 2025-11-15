<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Abstracts\Controller;
use App\Services\Domain\BudgetService;
use App\Services\Domain\CustomerService;
use App\Services\Domain\InvoiceService;
use App\Services\Domain\ProductService;
use App\Services\Domain\ServiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AjaxController extends Controller
{
    public function __construct(
        private BudgetService $budgetService,
        private ServiceService $serviceService,
        private CustomerService $customerService,
        private ProductService $productService,
        private InvoiceService $invoiceService,
    ) {}

    public function cep(Request $request): JsonResponse
    {
        $request->validate(['cep' => 'required|regex:/^\d{8}$/']);
        $cep = $request->input('cep');
        $response = Http::timeout(8)->get("https://brasilapi.com.br/api/cep/v1/{$cep}");
        if (!$response->ok()) {
            return response()->json(['success' => false, 'message' => 'CEP inválido ou serviço indisponível'], 400);
        }
        return response()->json(['success' => true, 'data' => $response->json()]);
    }

    public function budgetsFilter(Request $request): JsonResponse
    {
        $filters = $request->only(['filter_code','filter_start_date','filter_end_date','filter_customer','filter_min_value','filter_status','filter_order_by','per_page']);
        $result = $this->budgetService->getBudgetsForProvider(auth()->id(), $filters);
        return response()->json(['success' => true, 'data' => $result]);
    }

    public function servicesFilter(Request $request): JsonResponse
    {
        $filters = $request->only(['status','category_id','date_from','date_to','search']);
        $result = $this->serviceService->getFilteredServices($filters);
        return $result->isSuccess()
            ? response()->json(['success' => true, 'data' => $result->getData()])
            : response()->json(['success' => false, 'message' => $result->getMessage()], 400);
    }

    public function customersSearch(Request $request): JsonResponse
    {
        $filters = $request->only(['search','status','date_from','date_to']);
        $tenantId = auth()->user()->tenant_id;
        $result = $this->customerService->getFilteredCustomers($filters, $tenantId);
        return $result->isSuccess()
            ? response()->json(['success' => true, 'data' => $result->getData()])
            : response()->json(['success' => false, 'message' => $result->getMessage()], 400);
    }

    public function productsSearch(Request $request): JsonResponse
    {
        $filters = $request->only(['search','active','min_price','max_price','category_id']);
        $result = $this->productService->getFilteredProducts($filters, ['category']);
        return $result->isSuccess()
            ? response()->json(['success' => true, 'data' => $result->getData()])
            : response()->json(['success' => false, 'message' => $result->getMessage()], 400);
    }

    public function invoicesFilter(Request $request): JsonResponse
    {
        $filters = $request->only(['status','customer_id','service_id','date_from','date_to','due_date_from','due_date_to','min_amount','max_amount','search','sort_by','sort_direction']);
        $result = $this->invoiceService->getFilteredInvoices($filters, ['customer:id,name','service:id,code,description','invoiceStatus']);
        return $result->isSuccess()
            ? response()->json(['success' => true, 'data' => $result->getData()])
            : response()->json(['success' => false, 'message' => $result->getMessage()], 400);
    }
}