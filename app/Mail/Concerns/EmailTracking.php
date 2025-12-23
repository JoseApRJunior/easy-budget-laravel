<?php

declare(strict_types=1);

namespace App\Mail\Concerns;

use Illuminate\Support\Str;

/**
 * Trait para adicionar funcionalidades de rastreamento aos e-mails.
 *
 * Esta trait fornece métodos para adicionar headers personalizados,
 * tags de rastreamento e metadados aos e-mails enviados pelo sistema.
 */
trait EmailTracking
{
    /**
     * Tags para categorização e rastreamento do e-mail.
     */
    protected array $trackingTags = [];

    /**
     * Metadados personalizados para rastreamento.
     */
    protected array $trackingMetadata = [];

    /**
     * ID único para rastreamento do e-mail.
     */
    protected string $trackingId;

    /**
     * Adiciona tags para rastreamento do e-mail.
     *
     * @param  array|string  $tags  Tags para categorização
     * @return $this
     */
    public function withTrackingTags(array|string $tags): static
    {
        $this->trackingTags = array_merge(
            $this->trackingTags,
            is_array($tags) ? $tags : [$tags]
        );

        return $this;
    }

    /**
     * Adiciona metadados para rastreamento do e-mail.
     *
     * @param  array  $metadata  Metadados personalizados
     * @return $this
     */
    public function withTrackingMetadata(array $metadata): static
    {
        $this->trackingMetadata = array_merge($this->trackingMetadata, $metadata);

        return $this;
    }

    /**
     * Define o ID de rastreamento do e-mail.
     *
     * @param  string|null  $trackingId  ID personalizado (opcional)
     * @return $this
     */
    public function withTrackingId(?string $trackingId = null): static
    {
        $this->trackingId = $trackingId ?? $this->generateTrackingId();

        return $this;
    }

    /**
     * Obtém as tags de rastreamento.
     *
     * @return array Tags de rastreamento
     */
    public function getTrackingTags(): array
    {
        return $this->trackingTags;
    }

    /**
     * Obtém os metadados de rastreamento.
     *
     * @return array Metadados de rastreamento
     */
    public function getTrackingMetadata(): array
    {
        return $this->trackingMetadata;
    }

    /**
     * Obtém o ID de rastreamento.
     *
     * @return string ID de rastreamento
     */
    public function getTrackingId(): string
    {
        return $this->trackingId ?? $this->generateTrackingId();
    }

    /**
     * Gera um ID único para rastreamento.
     *
     * @return string ID de rastreamento único
     */
    private function generateTrackingId(): string
    {
        return 'email_'.Str::uuid()->toString();
    }

    /**
     * Adiciona headers de rastreamento ao e-mail.
     *
     * @param  \Symfony\Component\Mime\Email  $message  Mensagem de e-mail
     * @return \Symfony\Component\Mime\Email Mensagem com headers adicionados
     */
    protected function addTrackingHeaders(\Symfony\Component\Mime\Email $message): \Symfony\Component\Mime\Email
    {
        // Adicionar ID de rastreamento
        if ($this->trackingId) {
            $message->getHeaders()->addTextHeader('X-Tracking-ID', $this->trackingId);
        }

        // Adicionar tags como headers
        foreach ($this->trackingTags as $tag) {
            $message->getHeaders()->addTextHeader('X-Tag', $tag);
        }

        // Adicionar metadados como headers
        foreach ($this->trackingMetadata as $key => $value) {
            $headerName = 'X-Metadata-'.str_replace([' ', '-'], '_', ucwords($key));
            $message->getHeaders()->addTextHeader($headerName, (string) $value);
        }

        // Adicionar timestamp de envio
        $message->getHeaders()->addDateHeader('X-Sent-At', now());

        return $message;
    }

    /**
     * Registra o envio do e-mail para rastreamento.
     */
    protected function logEmailTracking(): void
    {
        \Illuminate\Support\Facades\Log::info('Email enviado com rastreamento', [
            'tracking_id' => $this->getTrackingId(),
            'tags' => $this->trackingTags,
            'metadata' => $this->trackingMetadata,
            'mailable_class' => static::class,
            'sent_at' => now()->toISOString(),
        ]);
    }
}
