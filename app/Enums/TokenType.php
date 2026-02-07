<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Enum que define os tipos de token de confirmação suportados
 *
 * Este enum centraliza todos os tipos de token utilizados no sistema,
 * facilitando manutenção e expansão futura.
 */
enum TokenType: string implements \App\Contracts\Interfaces\StatusEnumInterface
{
    use \App\Traits\Enums\HasStatusEnumMethods;

    /** Token para verificação de e-mail */
    case EMAIL_VERIFICATION = 'email_verification';

    /** Token para reset de senha */
    case PASSWORD_RESET = 'password_reset';

    /** Token para confirmação de cadastro */
    case ACCOUNT_CONFIRMATION = 'account_confirmation';

    /** Token para autenticação de 2 fatores */
    case TWO_FACTOR_AUTH = 'two_factor_auth';

    /** Token para verificação de mudança de e-mail */
    case EMAIL_CHANGE_VERIFICATION = 'email_change_verification';

    /** Token para verificação de telefone */
    case PHONE_VERIFICATION = 'phone_verification';

    /** Token para verificação de pagamentos */
    case PAYMENT_VERIFICATION = 'payment_verification';

    /** Token para verificação de assinaturas */
    case SUBSCRIPTION_VERIFICATION = 'subscription_verification';

    /** Token para agendamentos */
    case SCHEDULE_CONFIRMATION = 'schedule_confirmation';

    /**
     * Retorna uma descrição para o tipo de token
     */
    public function getDescription(): string
    {
        return match ($this) {
            self::EMAIL_VERIFICATION => 'Verificação de e-mail',
            self::PASSWORD_RESET => 'Reset de senha',
            self::ACCOUNT_CONFIRMATION => 'Confirmação de cadastro',
            self::TWO_FACTOR_AUTH => 'Autenticação de 2 fatores',
            self::EMAIL_CHANGE_VERIFICATION => 'Verificação de mudança de e-mail',
            self::PHONE_VERIFICATION => 'Verificação de telefone',
            self::PAYMENT_VERIFICATION => 'Verificação de pagamento',
            self::SUBSCRIPTION_VERIFICATION => 'Verificação de assinatura',
            self::SCHEDULE_CONFIRMATION => 'Confirmação de agendamento',
        };
    }

    /**
     * Retorna a cor associada a cada tipo para interface
     *
     * @return string Cor em formato hexadecimal
     */
    public function getColor(): string
    {
        return match ($this) {
            self::EMAIL_VERIFICATION => '#007bff', // Azul
            self::PASSWORD_RESET => '#dc3545', // Vermelho
            self::ACCOUNT_CONFIRMATION => '#28a745', // Verde
            self::TWO_FACTOR_AUTH => '#ffc107', // Amarelo
            self::EMAIL_CHANGE_VERIFICATION => '#6f42c1', // Roxo
            self::PHONE_VERIFICATION => '#17a2b8', // Azul claro
            self::PAYMENT_VERIFICATION => '#fd7e14', // Laranja
            self::SUBSCRIPTION_VERIFICATION => '#20c997', // Verde claro
            self::SCHEDULE_CONFIRMATION => '#00caca', // Ciano
        };
    }

    /**
     * Retorna o ícone associado a cada tipo
     *
     * @return string Nome do ícone para interface
     */
    public function getIcon(): string
    {
        return match ($this) {
            self::EMAIL_VERIFICATION => 'bi-envelope-check',
            self::PASSWORD_RESET => 'bi-key',
            self::ACCOUNT_CONFIRMATION => 'bi-person-check',
            self::TWO_FACTOR_AUTH => 'bi-shield-check',
            self::EMAIL_CHANGE_VERIFICATION => 'bi-envelope-at',
            self::PHONE_VERIFICATION => 'bi-phone',
            self::PAYMENT_VERIFICATION => 'bi-credit-card',
            self::SUBSCRIPTION_VERIFICATION => 'bi-receipt',
            self::SCHEDULE_CONFIRMATION => 'bi-calendar-check',
        };
    }

    /**
     * Verifica se o tipo de token está ativo/disponível
     *
     * @return bool True se estiver ativo
     */
    public function isActive(): bool
    {
        return match ($this) {
            self::EMAIL_VERIFICATION, self::PASSWORD_RESET, self::ACCOUNT_CONFIRMATION,
            self::TWO_FACTOR_AUTH, self::EMAIL_CHANGE_VERIFICATION, self::PHONE_VERIFICATION,
            self::PAYMENT_VERIFICATION, self::SUBSCRIPTION_VERIFICATION => true,
        };
    }

    /**
     * Verifica se o tipo de token está finalizado/concluído
     *
     * @return bool True se estiver finalizado
     */
    public function isFinished(): bool
    {
        // Tokens não são "finalizados" no sentido tradicional
        return false;
    }

    /**
     * Retorna metadados completos do tipo de token
     */
    public function getMetadata(): array
    {
        return array_merge($this->defaultMetadata(), [
            'default_expiration_minutes' => $this->getDefaultExpirationMinutes(),
        ]);
    }

    /**
     * Retorna o tempo de expiração padrão para cada tipo (em minutos)
     */
    public function getDefaultExpirationMinutes(): int
    {
        return match ($this) {
            self::EMAIL_VERIFICATION => 30,
            self::PASSWORD_RESET => 15,
            self::ACCOUNT_CONFIRMATION => 60,
            self::TWO_FACTOR_AUTH => 10,
            self::EMAIL_CHANGE_VERIFICATION => 30,
            self::PHONE_VERIFICATION => 15,
            self::PAYMENT_VERIFICATION => 10,
            self::SUBSCRIPTION_VERIFICATION => 30,
        };
    }
}
