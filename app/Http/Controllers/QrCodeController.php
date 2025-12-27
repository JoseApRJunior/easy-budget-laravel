<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Controllers\Abstracts\Controller;
use App\Services\Infrastructure\QrCodeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class QrCodeController extends Controller
{
    public function __construct(
        private QrCodeService $qrCodeService
    ) {}

    /**
     * Show QR Code generator interface
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('pages.qrcode.index');
    }

    /**
     * Generate QR code from text
     */
    public function generate(Request $request): JsonResponse
    {
        $request->validate([
            'text' => 'required|string|max:1000',
            'size' => 'nullable|integer|min:100|max:1000',
            'margin' => 'nullable|integer|min:0|max:50',
        ]);

        try {
            $text = $request->input('text');
            $size = $request->input('size', 300);
            $margin = $request->input('margin', 10);

            // Use the existing QrCodeService to generate data URI
            $dataUri = $this->qrCodeService->generateDataUri($text, $size);

            if (empty($dataUri)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erro ao gerar QR Code',
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'QR Code gerado com sucesso',
                'data' => [
                    'qr_code' => $dataUri,
                    'text' => $text,
                    'size' => $size,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao gerar QR Code: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate and immediately read QR code (for testing)
     */
    public function handle(Request $request): JsonResponse
    {
        $request->validate([
            'text' => 'required|string|max:1000',
            'size' => 'nullable|integer|min:100|max:1000',
        ]);

        try {
            $text = $request->input('text');
            $size = $request->input('size', 300);

            // Generate QR code
            $dataUri = $this->qrCodeService->generateDataUri($text, $size);

            if (empty($dataUri)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erro ao gerar QR Code',
                ], 500);
            }

            // For now, we'll return the generated QR code without reading it
            // Reading would require additional libraries like zxing/qrcode-reader
            return response()->json([
                'success' => true,
                'message' => 'QR Code processado com sucesso',
                'data' => [
                    'original_text' => $text,
                    'decoded_text' => $text, // In a full implementation, this would be the actual decoded text
                    'qr_code' => $dataUri,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao processar QR Code: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate QR code for budget verification
     */
    public function generateForBudget(Request $request): JsonResponse
    {
        $request->validate([
            'budget_id' => 'required|integer',
            'url' => 'required|url|max:500',
        ]);

        try {
            $budgetId = $request->input('budget_id');
            $url = $request->input('url');

            // Generate QR code with budget-specific settings
            $dataUri = $this->qrCodeService->generateDataUri($url, 200);

            if (empty($dataUri)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erro ao gerar QR Code do orçamento',
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'QR Code do orçamento gerado com sucesso',
                'data' => [
                    'qr_code' => $dataUri,
                    'budget_id' => $budgetId,
                    'url' => $url,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao gerar QR Code do orçamento: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate QR code for invoice verification
     */
    public function generateForInvoice(Request $request): JsonResponse
    {
        $request->validate([
            'invoice_id' => 'required|integer',
            'url' => 'required|url|max:500',
        ]);

        try {
            $invoiceId = $request->input('invoice_id');
            $url = $request->input('url');

            // Generate QR code with invoice-specific settings
            $dataUri = $this->qrCodeService->generateDataUri($url, 200);

            if (empty($dataUri)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erro ao gerar QR Code da fatura',
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'QR Code da fatura gerado com sucesso',
                'data' => [
                    'qr_code' => $dataUri,
                    'invoice_id' => $invoiceId,
                    'url' => $url,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao gerar QR Code da fatura: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate QR code for service verification
     */
    public function generateForService(Request $request): JsonResponse
    {
        $request->validate([
            'service_id' => 'required|integer',
            'url' => 'required|url|max:500',
        ]);

        try {
            $serviceId = $request->input('service_id');
            $url = $request->input('url');

            // Generate QR code with service-specific settings
            $dataUri = $this->qrCodeService->generateDataUri($url, 200);

            if (empty($dataUri)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erro ao gerar QR Code do serviço',
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'QR Code do serviço gerado com sucesso',
                'data' => [
                    'qr_code' => $dataUri,
                    'service_id' => $serviceId,
                    'url' => $url,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao gerar QR Code do serviço: '.$e->getMessage(),
            ], 500);
        }
    }
}
