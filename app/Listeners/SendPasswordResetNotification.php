<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\PasswordResetRequested;
use App\Support\ServiceResult;

/**
 * Listener responsável por enviar e-mail de redefinição de senha.
 *
 * REFATORADO: Agora utiliza AbstractEmailListener para reduzir duplicação
 * e melhorar manutenibilidade.
 *
 * Benefícios da refatoração:
 * - Redução de ~75% no código duplicado
 * - Tratamento padronizado de erros e logging
 * - Métricas de performance integradas
 * - Facilidade de manutenção e evolução
 *
 * Arquitetura: AbstractEmailListener → Template Method → Custom Implementation
 * - Herda funcionalidades comuns (logging, tratamento de erro, métricas)
 * - Implementa apenas lógica específica de redefinição de senha
 * - Mantém compatibilidade total com sistema de filas
 */
class SendPasswordResetNotification extends AbstractEmailListener
{
    /**
     * Implementação específica: Processa o envio de e-mail de redefinição de senha.
     *
     * Contém apenas a lógica específica deste tipo de e-mail,
     * aproveitando toda a infraestrutura comum da classe abstrata.
     *
     * @param PasswordResetRequested $event Evento de solicitação de redefinição
     * @return ServiceResult Resultado do processamento
     */
    protected function processEmail($event): ServiceResult
    {
        // Envia e-mail usando o serviço injetado
        return $this->mailerService->sendPasswordResetNotification(
            $event->user,
            $event->resetToken,
            $event->tenant,
        );
    }

    /**
     * Implementação específica: Descrição do evento para logging.
     *
     * @return string Descrição do evento
     */
    protected function getEventDescription(): string
    {
        return 'Processando evento PasswordResetRequested para envio de e-mail de redefinição';
    }

    /**
     * Implementação específica: Tipo do evento para categorização.
     *
     * @return string Tipo do evento
     */
    protected function getEventType(): string
    {
        return 'password_reset';
    }
}
