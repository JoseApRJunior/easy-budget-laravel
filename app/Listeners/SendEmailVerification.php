<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\EmailVerificationRequested;
use App\Support\ServiceResult;

/**
 * Listener responsável por enviar e-mail de verificação quando um usuário solicita verificação de e-mail.
 *
 * REFATORADO: Agora utiliza AbstractEmailListener para reduzir duplicação
 * e melhorar manutenibilidade.
 *
 * Benefícios da refatoração:
 * - Redução de ~80% no código duplicado
 * - Tratamento padronizado de erros e logging
 * - Métricas de performance integradas
 * - Facilidade de manutenção e evolução
 *
 * Arquitetura: AbstractEmailListener → Template Method → Custom Implementation
 * - Herda funcionalidades comuns (logging, tratamento de erro, métricas)
 * - Implementa apenas lógica específica de verificação de e-mail
 * - Mantém compatibilidade total com sistema de filas
 */
class SendEmailVerification extends AbstractEmailListener
{
    /**
     * Implementação específica: Processa o envio de e-mail de verificação.
     *
     * Contém apenas a lógica específica deste tipo de e-mail,
     * aproveitando toda a infraestrutura comum da classe abstrata.
     *
     * @param EmailVerificationRequested $event Evento de solicitação de verificação
     * @return ServiceResult Resultado do processamento
     */
    protected function processEmail( $event ): ServiceResult
    {
        // Gera URL de verificação segura usando serviço centralizado
        $confirmationLink = $this->buildVerificationConfirmationLink( $event->verificationToken );

        // Envia e-mail usando o serviço injetado
        return $this->mailerService->sendEmailVerificationMail(
            $event->user,
            $event->tenant,
            $confirmationLink,
        );
    }

    /**
     * Implementação específica: Validação avançada para e-mail de verificação.
     *
     * Implementa validação rigorosa do token de verificação para garantir
     * segurança consistente com outros listeners de e-mail.
     *
     * @param EmailVerificationRequested $event Evento a ser validado
     */
    protected function validateEvent( $event ): void
    {
        parent::validateEvent( $event );

        // Validação específica de verificação de e-mail usando método utilitário padronizado
        // Token é obrigatório para e-mail de verificação
        $this->validateVerificationToken( $event->verificationToken, true );
    }

    /**
     * Implementação específica: Descrição do evento para logging.
     *
     * @return string Descrição do evento
     */
    protected function getEventDescription(): string
    {
        return 'Processando evento EmailVerificationRequested para envio de e-mail de verificação';
    }

    /**
     * Implementação específica: Tipo do evento para categorização.
     *
     * @return string Tipo do evento
     */
    protected function getEventType(): string
    {
        return 'email_verification';
    }

}
