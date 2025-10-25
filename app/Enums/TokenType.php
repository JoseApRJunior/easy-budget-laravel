<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Enum que define os tipos de token de confirmação suportados
 *
 * Este enum centraliza todos os tipos de token utilizados no sistema,
 * facilitando manutenção e expansão futura.
 */
enum TokenType: string
{
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

    /**
     * Retorna todos os tipos válidos como array
     *
     * @return array<string>
     */
    public static function getAllTypes(): array
    {
        return array_column( self::cases(), 'value' );
    }

    /**
     * Verifica se o tipo é válido
     *
     * @param string $type
     * @return bool
     */
    public static function isValid( string $type ): bool
    {
        return in_array( $type, self::getAllTypes() );
    }

    /**
     * Retorna descrição amigável do tipo
     *
     * @return string
     */
    public function getDescription(): string
    {
        return match ( $this ) {
            self::EMAIL_VERIFICATION        => 'Verificação de e-mail',
            self::PASSWORD_RESET            => 'Reset de senha',
            self::ACCOUNT_CONFIRMATION      => 'Confirmação de cadastro',
            self::TWO_FACTOR_AUTH           => 'Autenticação de 2 fatores',
            self::EMAIL_CHANGE_VERIFICATION => 'Verificação de mudança de e-mail',
            self::PHONE_VERIFICATION        => 'Verificação de telefone',
            self::PAYMENT_VERIFICATION      => 'Verificação de pagamento',
            self::SUBSCRIPTION_VERIFICATION => 'Verificação de assinatura',
        };
    }

    /**
     * Retorna o tempo de expiração padrão para cada tipo (em minutos)
     *
     * @return int
     */
    public function getDefaultExpirationMinutes(): int
    {
        return match ( $this ) {
            self::EMAIL_VERIFICATION        => 30,
            self::PASSWORD_RESET            => 15,
            self::ACCOUNT_CONFIRMATION      => 60,
            self::TWO_FACTOR_AUTH           => 10,
            self::EMAIL_CHANGE_VERIFICATION => 30,
            self::PHONE_VERIFICATION        => 15,
            self::PAYMENT_VERIFICATION      => 10,
            self::SUBSCRIPTION_VERIFICATION => 30,
        };
    }

}
