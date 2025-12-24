<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Abstracts\Controller;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\Domain\PaymentService;
use App\DTOs\Payment\PaymentDTO;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controller para gerenciamento de pagamentos.
 */
class PaymentController extends Controller
{
    public function __construct(
        private readonly PaymentService $paymentService
    ) {}

    /**
     * Lista pagamentos com filtros.
     */
    public function index(Request $request): View
    {
        $filters = $request->only(['status', 'method', 'customer_id', 'date_from', 'date_to']);

        $result = $this->paymentService->getFilteredPayments($filters);

        if ($result->isError()) {
            abort(500, 'Erro ao carregar pagamentos');
        }

        return view('pages.payment.index', [
            'payments' => $result->getData(),
            'filters' => $filters,
            'statusOptions' => PaymentStatus::getOptions(),
            'methodOptions' => Payment::getPaymentMethods(),
        ]);
    }

    /**
     * Processa um novo pagamento.
     */
    public function process(Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'invoice_id' => 'required|exists:invoices,id',
            'method' => 'required|in:' . implode(',', array_keys(Payment::getPaymentMethods())),
            'amount' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string|max:500',
        ]);

        // Adiciona o customer_id da fatura ao request para o DTO
        $invoice = Invoice::findOrFail($request->invoice_id);
        $data = $request->validated();
        $data['customer_id'] = $invoice->customer_id;

        $dto = PaymentDTO::fromRequest($data);
        $result = $this->paymentService->processPayment($dto);

        if ($result->isError()) {
            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => $result->getMessage()], 400)
                : redirect()->back()->with('error', $result->getMessage());
        }

        $payment = $result->getData();

        return $request->expectsJson()
            ? response()->json(['success' => true, 'data' => $payment])
            : redirect()->route('provider.invoices.show', $payment->invoice->code)
            ->with('success', 'Pagamento processado com sucesso!');
    }

    /**
     * Confirma um pagamento.
     */
    public function confirm(Payment $payment, Request $request): JsonResponse|RedirectResponse
    {
        $request->validate([
            'transaction_id' => 'nullable|string',
            'notes' => 'nullable|string|max:500',
        ]);

        $result = $this->paymentService->confirmPayment($payment->id, $request->validated());

        if ($result->isError()) {
            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => $result->getMessage()], 400)
                : redirect()->back()->with('error', $result->getMessage());
        }

        return $request->expectsJson()
            ? response()->json(['success' => true, 'message' => 'Pagamento confirmado'])
            : redirect()->back()->with('success', 'Pagamento confirmado com sucesso!');
    }

    /**
     * Webhook para receber confirmações de pagamento.
     */
    public function webhook(Request $request): JsonResponse
    {
        $result = $this->paymentService->processWebhook($request->all());

        return response()->json([
            'success' => $result->isSuccess(),
            'message' => $result->getMessage(),
        ], $result->isSuccess() ? 200 : 400);
    }

    /**
     * Dashboard de pagamentos.
     */
    public function dashboard(): View
    {
        $result = $this->paymentService->getPaymentStats();

        if ($result->isError()) {
            abort(500, 'Erro ao carregar estatísticas do dashboard.');
        }

        return view('pages.payment.dashboard', [
            'stats' => $result->getData(),
        ]);
    }
}
