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
     * Retorna uma descrição para o tipo de token
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
     * Retorna a cor associada a cada tipo para interface
     *
     * @return string Cor em formato hexadecimal
     */
    public function getColor(): string
    {
        return match ( $this ) {
            self::EMAIL_VERIFICATION        => '#007bff', // Azul
            self::PASSWORD_RESET            => '#dc3545', // Vermelho
            self::ACCOUNT_CONFIRMATION      => '#28a745', // Verde
            self::TWO_FACTOR_AUTH           => '#ffc107', // Amarelo
            self::EMAIL_CHANGE_VERIFICATION => '#6f42c1', // Roxo
            self::PHONE_VERIFICATION        => '#17a2b8', // Azul claro
            self::PAYMENT_VERIFICATION      => '#fd7e14', // Laranja
            self::SUBSCRIPTION_VERIFICATION => '#20c997', // Verde claro
        };
    }

    /**
     * Retorna o ícone associado a cada tipo
     *
     * @return string Nome do ícone para interface
     */
    public function getIcon(): string
    {
        return match ( $this ) {
            self::EMAIL_VERIFICATION        => 'bi-envelope-check',
            self::PASSWORD_RESET            => 'bi-key',
            self::ACCOUNT_CONFIRMATION      => 'bi-person-check',
            self::TWO_FACTOR_AUTH           => 'bi-shield-check',
            self::EMAIL_CHANGE_VERIFICATION => 'bi-envelope-at',
            self::PHONE_VERIFICATION        => 'bi-phone',
            self::PAYMENT_VERIFICATION      => 'bi-credit-card',
            self::SUBSCRIPTION_VERIFICATION => 'bi-receipt',
        };
    }

    /**
     * Verifica se o tipo de token está ativo/disponível
     *
     * @return bool True se estiver ativo
     */
    public function isActive(): bool
    {
        return match ( $this ) {
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
     *
     * @return array<string, mixed> Array com descrição, cor, ícone e flags
     */
    public function getMetadata(): array
    {
        return [
            'value'                      => $this->value,
            'description'                => $this->getDescription(),
            'color'                      => $this->getColor(),
            'icon'                       => $this->getIcon(),
            'is_active'                  => $this->isActive(),
            'is_finished'                => $this->isFinished(),
            'default_expiration_minutes' => $this->getDefaultExpirationMinutes(),
        ];
    }

    /**
     * Cria instância do enum a partir de string
     *
     * @param string $value Valor do tipo
     * @return TokenType|null Instância do enum ou null se inválido
     */
    public static function fromString( string $value ): ?self
    {
        foreach ( self::cases() as $case ) {
            if ( $case->value === $value ) {
                return $case;
            }
        }
        return null;
    }

    /**
     * Retorna opções formatadas para uso em formulários/selects
     *
     * @param bool $includeFinished Incluir tipos finalizados
     * @return array<string, string> Array associativo [valor => descrição]
     */
    public static function getOptions( bool $includeFinished = true ): array
    {
        $options = [];

        foreach ( self::cases() as $type ) {
            if ( !$includeFinished && $type->isFinished() ) {
                continue;
            }
            $options[ $type->value ] = $type->getDescription();
        }

        return $options;
    }

    /**
     * Ordena tipos por categoria para exibição
     *
     * @param bool $includeFinished Incluir tipos finalizados na ordenação
     * @return array<TokenType> Tipos ordenados por categoria
     */
    public static function getOrdered( bool $includeFinished = true ): array
    {
        $types = self::cases();

        usort( $types, function ( TokenType $a, TokenType $b ) {
            // Ordem: EMAIL, PASSWORD, ACCOUNT, 2FA, EMAIL_CHANGE, PHONE, PAYMENT, SUBSCRIPTION
            $order = [
                self::EMAIL_VERIFICATION->value        => 1,
                self::EMAIL_CHANGE_VERIFICATION->value => 2,
                self::PASSWORD_RESET->value            => 3,
                self::ACCOUNT_CONFIRMATION->value      => 4,
                self::TWO_FACTOR_AUTH->value           => 5,
                self::PHONE_VERIFICATION->value        => 6,
                self::PAYMENT_VERIFICATION->value      => 7,
                self::SUBSCRIPTION_VERIFICATION->value => 8,
            ];
            return ( $order[ $a->value ] ?? 99 ) <=> ( $order[ $b->value ] ?? 99 );
        } );

        return $types;
    }

    /**
     * Calcula métricas de tipos para dashboards
     *
     * @param array<TokenType> $types Lista de tipos para análise
     * @return array<string, int> Métricas [ativo, finalizado, total]
     */
    public static function calculateMetrics( array $types ): array
    {
        $total    = count( $types );
        $active   = 0;
        $finished = 0;

        foreach ( $types as $type ) {
            if ( $type->isActive() ) {
                $active++;
            } elseif ( $type->isFinished() ) {
                $finished++;
            }
        }

        return [
            'total'               => $total,
            'active'              => $active,
            'finished'            => $finished,
            'active_percentage'   => $total > 0 ? round( ( $active / $total ) * 100, 1 ) : 0,
            'finished_percentage' => $total > 0 ? round( ( $finished / $total ) * 100, 1 ) : 0,
        ];
    }

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
