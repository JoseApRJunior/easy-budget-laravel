# Guia de Implementação - Webhooks Mercado Pago (Laravel 12)

## 📋 Visão Geral

Guia prático para implementar o sistema de webhooks do Mercado Pago no Laravel 12, baseado na análise do sistema antigo.

---

## 🏗️ Estrutura de Arquivos

```
app/
├── Http/
│   ├── Controllers/
│   │   └── Webhooks/
│   │       └── MercadoPagoWebhookController.php
│   └── Middleware/
│       └── ValidateMercadoPagoWebhook.php
├── Jobs/
│   └── ProcessMercadoPagoWebhook.php
├── Services/
│   └── Infrastructure/
│       └── Payment/
│           ├── MercadoPagoWebhookService.php
│           ├── MercadoPagoInvoicePaymentService.php
│           └── MercadoPagoPlanPaymentService.php
├── Events/
│   ├── InvoicePaymentReceived.php
│   └── PlanPaymentReceived.php
└── Listeners/
    ├── UpdateInvoiceStatus.php
    ├── UpdatePlanSubscriptionStatus.php
    ├── SendPaymentNotification.php
    └── LogPaymentActivity.php
```

---

## 📝 Implementação Passo a Passo

### Passo 1: Controller

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessMercadoPagoWebhook;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MercadoPagoWebhookController extends Controller
{
    public function handleInvoiceWebhook(Request $request): JsonResponse
    {
        return $this->processWebhook($request, 'invoice');
    }

    public function handlePlanWebhook(Request $request): JsonResponse
    {
        return $this->processWebhook($request, 'plan');
    }

    private function processWebhook(Request $request, string $type): JsonResponse
    {
        $webhookData = $request->all();
        
        Log::info("Mercado Pago webhook received", [
            'type' => $type,
            'topic' => $webhookData['topic'] ?? $webhookData['type'] ?? null,
            'id' => $webhookData['data']['id'] ?? null,
        ]);

        // Valida estrutura básica
        if (!isset($webhookData['type']) || !isset($webhookData['data']['id'])) {
            Log::warning("Invalid webhook structure", ['data' => $webhookData]);
            return response()->json(['error' => 'Invalid webhook structure'], 400);
        }

        // Ignora notificações que não são de pagamento
        if ($webhookData['type'] !== 'payment') {
            Log::info("Ignoring non-payment webhook", ['type' => $webhookData['type']]);
            return response()->json(['status' => 'ignored'], 200);
        }

        // Despacha para fila
        ProcessMercadoPagoWebhook::dispatch($webhookData, $type);

        return response()->json(['status' => 'accepted'], 200);
    }
}
```

---

### Passo 2: Middleware de Validação

```php
<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ValidateMercadoPagoWebhook
{
    public function handle(Request $request, Closure $next)
    {
        // Valida X-Request-Id
        $xRequestId = $request->header('X-Request-Id') ?? $request->header('x-request-id');
        if (!$xRequestId) {
            Log::warning("Webhook rejected: Missing X-Request-Id");
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        // Valida assinatura
        if (!$this->validateSignature($request)) {
            Log::warning("Webhook rejected: Invalid signature", [
                'x_request_id' => $xRequestId,
            ]);
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $next($request);
    }

    private function validateSignature(Request $request): bool
    {
        $xSignature = $request->header('X-Signature') ?? $request->header('x-signature');
        $xRequestId = $request->header('X-Request-Id') ?? $request->header('x-request-id');
        
        if (!$xSignature || !$xRequestId) {
            return false;
        }

        // Parse signature (formato: ts=123456,v1=hash)
        $parts = explode(',', $xSignature);
        $ts = null;
        $hash = null;
        
        foreach ($parts as $part) {
            [$key, $value] = explode('=', $part, 2);
            $key = trim($key);
            $value = trim($value);
            
            if ($key === 'ts') {
                $ts = $value;
            } elseif ($key === 'v1') {
                $hash = $value;
            }
        }

        if (!$ts || !$hash) {
            return false;
        }

        // Valida idade do webhook (máximo 5 minutos)
        $currentTime = time();
        if (abs($currentTime - (int)$ts) > 300) {
            Log::warning("Webhook rejected: Timestamp too old", [
                'timestamp' => $ts,
                'current_time' => $currentTime,
            ]);
            return false;
        }

        // Extrai ID do pagamento
        $data = $request->all();
        $dataId = $data['data']['id'] ?? $data['data.id'] ?? $data['data_id'] ?? $data['id'] ?? null;
        
        if (!$dataId) {
            return false;
        }

        // Constrói manifest
        $manifest = "id:{$dataId};request-id:{$xRequestId};ts:{$ts};";

        // Calcula hash
        $secret = config('services.mercadopago.webhook_secret');
        $calculatedHash = hash_hmac('sha256', $manifest, $secret);

        // Compara hashes (timing-safe)
        return hash_equals($calculatedHash, $hash);
    }
}
```

---

### Passo 3: Job de Processamento

```php
<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\Infrastructure\Payment\MercadoPagoWebhookService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessMercadoPagoWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;
    public int $timeout = 120;

    public function __construct(
        private array $webhookData,
        private string $type
    ) {}

    public function handle(MercadoPagoWebhookService $webhookService): void
    {
        try {
            $paymentId = $this->webhookData['data']['id'];

            Log::info("Processing Mercado Pago webhook", [
                'type' => $this->type,
                'payment_id' => $paymentId,
                'attempt' => $this->attempts(),
            ]);

            $result = match ($this->type) {
                'plan' => $webhookService->processPlanPayment($paymentId),
                'invoice' => $webhookService->processInvoicePayment($paymentId),
                default => throw new \InvalidArgumentException("Invalid payment type: {$this->type}"),
            };

            if (!$result['success']) {
                throw new \Exception($result['message'] ?? 'Payment processing failed');
            }

            Log::info("Webhook processed successfully", [
                'type' => $this->type,
                'payment_id' => $paymentId,
            ]);
        } catch (\Exception $e) {
            Log::error("Webhook processing failed", [
                'type' => $this->type,
                'payment_id' => $this->webhookData['data']['id'] ?? null,
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);

            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::critical("Webhook processing failed permanently", [
            'type' => $this->type,
            'payment_id' => $this->webhookData['data']['id'] ?? null,
            'error' => $exception->getMessage(),
            'attempts' => $this->tries,
        ]);

        // Aqui você pode enviar notificação para equipe técnica
    }
}
```

---

### Passo 4: Service Principal

```php
<?php

declare(strict_types=1);

namespace App\Services\Infrastructure\Payment;

use App\Events\InvoicePaymentReceived;
use App\Events\PlanPaymentReceived;
use App\Services\Domain\InvoiceService;
use App\Services\Domain\PlanService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use MercadoPago\Client\Payment\PaymentClient;
use MercadoPago\MercadoPagoConfig;

class MercadoPagoWebhookService
{
    public function __construct(
        private MercadoPagoInvoicePaymentService $invoicePaymentService,
        private MercadoPagoPlanPaymentService $planPaymentService,
        private InvoiceService $invoiceService,
        private PlanService $planService
    ) {}

    public function processInvoicePayment(string $paymentId): array
    {
        return DB::transaction(function () use ($paymentId) {
            // 1. Busca detalhes do pagamento na API do MP
            $paymentData = $this->getPaymentDetails($paymentId);
            
            if (!$paymentData) {
                return ['success' => false, 'message' => 'Payment not found in Mercado Pago API'];
            }

            // 2. Cria/atualiza registro de pagamento
            $paymentResult = $this->invoicePaymentService->createOrUpdatePayment($paymentData);
            
            if ($paymentResult['status'] !== 'success') {
                return ['success' => false, 'message' => $paymentResult['message']];
            }

            // 3. Atualiza status da fatura
            $invoiceResult = $this->invoiceService->updateFromPayment($paymentData);
            
            if ($invoiceResult['status'] !== 'success') {
                return ['success' => false, 'message' => $invoiceResult['message']];
            }

            // 4. Dispara evento (listeners cuidam de notificações e logs)
            $alreadyProcessed = $paymentResult['already_exists'] ?? false;
            
            if (!$alreadyProcessed) {
                event(new InvoicePaymentReceived(
                    $paymentResult['data'],
                    $invoiceResult['data'],
                    $paymentData
                ));
            }

            return ['success' => true, 'message' => 'Invoice payment processed successfully'];
        });
    }

    public function processPlanPayment(string $paymentId): array
    {
        return DB::transaction(function () use ($paymentId) {
            // 1. Busca detalhes do pagamento
            $paymentData = $this->getPaymentDetails($paymentId);
            
            if (!$paymentData) {
                return ['success' => false, 'message' => 'Payment not found'];
            }

            // 2. Cria/atualiza registro de pagamento
            $paymentResult = $this->planPaymentService->createOrUpdatePayment($paymentData);
            
            if ($paymentResult['status'] !== 'success') {
                return ['success' => false, 'message' => $paymentResult['message']];
            }

            // 3. Atualiza assinatura do plano
            $planResult = $this->planService->updateSubscriptionFromPayment($paymentData);
            
            if ($planResult['status'] !== 'success') {
                return ['success' => false, 'message' => $planResult['message']];
            }

            // 4. Dispara evento
            $alreadyProcessed = $paymentResult['already_exists'] ?? false;
            
            if (!$alreadyProcessed) {
                event(new PlanPaymentReceived(
                    $paymentResult['data'],
                    $planResult['data'],
                    $paymentData
                ));
            }

            return ['success' => true, 'message' => 'Plan payment processed successfully'];
        });
    }

    private function getPaymentDetails(string $paymentId): ?array
    {
        try {
            $this->authenticate();
            
            $client = new PaymentClient();
            $payment = $client->get($paymentId);
            
            if (!$payment) {
                return null;
            }

            // Decodifica external reference
            $externalReference = html_entity_decode($payment->external_reference ?? '');
            $externalData = json_decode($externalReference, true) ?? [];

            return [
                'payment_id' => $payment->id,
                'status' => $payment->status,
                'payment_method' => $payment->payment_method_id,
                'transaction_amount' => $payment->transaction_amount,
                'transaction_date' => $payment->date_last_updated,
                'external_data' => $externalData,
            ];
        } catch (\Exception $e) {
            Log::error("Failed to get payment details from Mercado Pago", [
                'payment_id' => $paymentId,
                'error' => $e->getMessage(),
            ]);
            
            return null;
        }
    }

    private function authenticate(): void
    {
        $accessToken = config('services.mercadopago.access_token');
        
        if (!$accessToken) {
            throw new \Exception("Mercado Pago access token not configured");
        }

        $environment = app()->environment('production') 
            ? MercadoPagoConfig::SERVER 
            : MercadoPagoConfig::LOCAL;
            
        MercadoPagoConfig::setRuntimeEnviroment($environment);
        MercadoPagoConfig::setAccessToken($accessToken);
    }
}
```

---

### Passo 5: Configuração de Rotas

```php
// routes/api.php

use App\Http\Controllers\Webhooks\MercadoPagoWebhookController;

Route::prefix('webhooks/mercadopago')->group(function () {
    Route::post('/invoices', [MercadoPagoWebhookController::class, 'handleInvoiceWebhook'])
        ->middleware('validate.mercadopago.webhook')
        ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class])
        ->name('webhooks.mercadopago.invoices');

    Route::post('/plans', [MercadoPagoWebhookController::class, 'handlePlanWebhook'])
        ->middleware('validate.mercadopago.webhook')
        ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class])
        ->name('webhooks.mercadopago.plans');
});
```

---

### Passo 6: Registrar Middleware

```php
// app/Http/Kernel.php

protected $middlewareAliases = [
    // ... outros middlewares
    'validate.mercadopago.webhook' => \App\Http\Middleware\ValidateMercadoPagoWebhook::class,
];
```

---

### Passo 7: Configuração

```php
// config/services.php

return [
    // ... outros serviços
    
    'mercadopago' => [
        'access_token' => env('MERCADO_PAGO_ACCESS_TOKEN'),
        'public_key' => env('MERCADO_PAGO_PUBLIC_KEY'),
        'webhook_secret' => env('MERCADO_PAGO_WEBHOOK_SECRET'),
    ],
];
```

```env
# .env

MERCADO_PAGO_ACCESS_TOKEN=your_access_token_here
MERCADO_PAGO_PUBLIC_KEY=your_public_key_here
MERCADO_PAGO_WEBHOOK_SECRET=your_webhook_secret_here
```

---

## 🧪 Testes

### Teste do Middleware

```php
<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\ValidateMercadoPagoWebhook;
use Illuminate\Http\Request;
use Tests\TestCase;

class ValidateMercadoPagoWebhookTest extends TestCase
{
    public function test_rejects_webhook_without_x_request_id(): void
    {
        $request = Request::create('/webhooks/mercadopago/invoices', 'POST');
        $middleware = new ValidateMercadoPagoWebhook();
        
        $response = $middleware->handle($request, fn() => response()->json(['ok' => true]));
        
        $this->assertEquals(401, $response->getStatusCode());
    }

    public function test_accepts_webhook_with_valid_signature(): void
    {
        $paymentId = '123456789';
        $requestId = 'req-' . uniqid();
        $timestamp = time();
        $secret = config('services.mercadopago.webhook_secret');
        
        $manifest = "id:{$paymentId};request-id:{$requestId};ts:{$timestamp};";
        $hash = hash_hmac('sha256', $manifest, $secret);
        
        $request = Request::create('/webhooks/mercadopago/invoices', 'POST', [
            'type' => 'payment',
            'data' => ['id' => $paymentId],
        ]);
        
        $request->headers->set('X-Request-Id', $requestId);
        $request->headers->set('X-Signature', "ts={$timestamp},v1={$hash}");
        
        $middleware = new ValidateMercadoPagoWebhook();
        $response = $middleware->handle($request, fn() => response()->json(['ok' => true]));
        
        $this->assertEquals(200, $response->getStatusCode());
    }
}
```

---

## 📊 Monitoramento

### Logs Estruturados

```php
// Adicione contexto em todos os logs

Log::info("Webhook received", [
    'type' => 'invoice',
    'payment_id' => $paymentId,
    'tenant_id' => $tenantId,
    'timestamp' => now()->toIso8601String(),
]);

Log::error("Webhook processing failed", [
    'type' => 'plan',
    'payment_id' => $paymentId,
    'error' => $exception->getMessage(),
    'trace' => $exception->getTraceAsString(),
    'attempt' => $this->attempts(),
]);
```

### Métricas

```php
// Use Laravel Telescope ou similar para monitorar:
// - Taxa de sucesso de webhooks
// - Tempo médio de processamento
// - Número de retries
// - Webhooks em dead letter queue
```

---

## 🔒 Segurança

### Checklist de Segurança

- ✅ Validar assinatura HMAC-SHA256
- ✅ Validar timestamp (máximo 5 minutos)
- ✅ Usar hash_equals() para comparação
- ✅ Rate limiting nas rotas de webhook
- ✅ Logs de todas as tentativas
- ✅ Desabilitar CSRF apenas para webhooks
- ✅ Processar em fila (evita timeout)

---

**Fim do Guia**
