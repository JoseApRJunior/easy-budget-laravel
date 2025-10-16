<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\UserRegistered;
use App\Support\ServiceResult;
use Illuminate\Support\Facades\Log;

/**
 * Listener responsável por enviar e-mail de boas-vindas quando um usuário se registra.
 *
 * REFATORADO: Agora utiliza AbstractEmailListener para reduzir duplicação
 * e melhorar manutenibilidade.
 *
 * Benefícios da refatoração:
 * - Redução de ~70% no código duplicado
 * - Tratamento padronizado de erros e logging
 * - Métricas de performance integradas
 * - Facilidade de manutenção e evolução
 *
 * Arquitetura: AbstractEmailListener → Template Method → Custom Implementation
 * - Herda funcionalidades comuns (logging, tratamento de erro, métricas)
 * - Implementa apenas lógica específica de boas-vindas
 * - Mantém compatibilidade total com sistema de filas
 */
class SendWelcomeEmail extends AbstractEmailListener
{
    /**
     * Implementação específica: Processa o envio de e-mail de boas-vindas.
     *
     * Contém apenas a lógica específica deste tipo de e-mail,
     * aproveitando toda a infraestrutura comum da classe abstrata.
     *
     * @param UserRegistered $event Evento de registro de usuário
     * @return ServiceResult Resultado do processamento
     */
    protected function processEmail( $event ): ServiceResult
    {
        // Gera URL de verificação segura usando serviço centralizado
        $confirmationLink = $this->buildWelcomeConfirmationLink( $event->verificationToken );

        // Envia e-mail usando o serviço injetado
        return $this->mailerService->sendWelcomeEmail(
            $event->user,
            $event->tenant,
            $confirmationLink,
        );
    }

    /**
     * Implementação específica: Validação avançada para e-mail de boas-vindas.
     *
     * @param UserRegistered $event Evento a ser validado
     */
    protected function validateEvent( $event ): void
    {
        parent::validateEvent( $event );

        // Validação específica de boas-vindas usando método utilitário padronizado
        // Token é obrigatório para e-mail de boas-vindas
        $this->validateVerificationToken( $event->verificationToken, true );
    }

    /**
     * Implementação específica: Descrição do evento para logging.
     *
     * @return string Descrição do evento
     */
    protected function getEventDescription(): string
    {
        return 'Processando evento UserRegistered para envio de e-mail de boas-vindas';
    }

    /**
     * Implementação específica: Tipo do evento para categorização.
     *
     * @return string Tipo do evento
     */
    protected function getEventType(): string
    {
        return 'welcome_email';
    }

}
