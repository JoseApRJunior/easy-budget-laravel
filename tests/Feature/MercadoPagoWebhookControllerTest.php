<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\ProcessMercadoPagoWebhook;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class MercadoPagoWebhookControllerTest extends TestCase
{
    use RefreshDatabase;
    public function testWebhookMissingRequestIdReturns400(): void
    {
        $payload = ['type' => 'payment', 'data' => ['id' => 123]];
        $resp = $this->postJson('/webhooks/mercadopago/invoices', $payload);
        $resp->assertStatus(400);
        $resp->assertJson(['status' => 'error']);
    }

    public function testWebhookInvalidSignatureReturns403(): void
    {
        config(['services.mercadopago.webhook_secret' => 'secret']);
        $payload = ['type' => 'payment', 'data' => ['id' => 123]];
        $payloadStr = json_encode($payload, JSON_THROW_ON_ERROR);
        $invalidSig = hash_hmac('sha256', $payloadStr, 'wrong');

        $resp = $this->withHeaders([
            'X-Request-Id' => 'req-1',
            'X-Signature'  => $invalidSig,
        ])->postJson('/webhooks/mercadopago/invoices', $payload);

        $resp->assertStatus(403);
        $resp->assertJson(['status' => 'error']);
    }

    public function testWebhookValidSignatureAcceptedAndQueued(): void
    {
        Queue::fake();
        config(['services.mercadopago.webhook_secret' => 'secret']);
        $payload = ['type' => 'payment', 'data' => ['id' => 999]];
        $payloadStr = json_encode($payload, JSON_THROW_ON_ERROR);
        $sig = hash_hmac('sha256', $payloadStr, 'secret');

        $resp = $this->withHeaders([
            'X-Request-Id' => 'req-2',
            'X-Signature'  => $sig,
        ])->postJson('/webhooks/mercadopago/invoices', $payload);

        $resp->assertStatus(200);
        $resp->assertJson(['status' => 'accepted']);

        Queue::assertPushed(ProcessMercadoPagoWebhook::class, function ($job) {
            return in_array('invoice', [$job->type], true);
        });
    }

    public function testWebhookDuplicateIsIgnored(): void
    {
        config(['services.mercadopago.webhook_secret' => 'secret']);
        $payload = ['type' => 'payment', 'data' => ['id' => 555]];
        $payloadStr = json_encode($payload, JSON_THROW_ON_ERROR);
        $sig = hash_hmac('sha256', $payloadStr, 'secret');

        // First request
        $this->withHeaders([
            'X-Request-Id' => 'req-dup',
            'X-Signature'  => $sig,
        ])->postJson('/webhooks/mercadopago/invoices', $payload)->assertStatus(200);

        // Duplicate
        $resp = $this->withHeaders([
            'X-Request-Id' => 'req-dup',
            'X-Signature'  => $sig,
        ])->postJson('/webhooks/mercadopago/invoices', $payload);

        $resp->assertStatus(200);
        $resp->assertJson(['status' => 'ignored']);
    }
}