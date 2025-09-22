<?php

namespace core\library;

class Session
{
    private static bool $initialized = false;

    /**
     * Inicializa a sessão se ainda não estiver iniciada
     */
    public static function init(): void
    {
        if ( !self::$initialized ) {
            if ( session_status() === PHP_SESSION_NONE ) {
                session_start();
            }
            self::$initialized = true;
        }
    }

    /**
     * Define um valor na sessão
     */
    public static function set( string $key, mixed $value ): void
    {
        self::init();
        $_SESSION[ $key ] = $value;
    }

    /**
     * Verifica se uma chave existe na sessão
     */
    public static function has( string $key ): bool
    {
        self::init();

        return isset( $_SESSION[ $key ] );
    }

    /**
     * Obtém um valor da sessão
     */
    public static function get( string $key, mixed $default = false ): mixed
    {
        self::init();

        return self::has( $key ) ? $_SESSION[ $key ] : $default;
    }

    /**
     * Marca uma mensagem como lida
     */
    public static function markAsRead( string $key ): void
    {
        self::set( $key . '_read', true );
    }

    /**
     * Verifica se uma mensagem foi lida
     */
    public static function isRead( string $key ): bool
    {
        return self::get( $key . '_read' ) === true;
    }

    /**
     * Remove uma chave específica da sessão
     */
    public static function remove( string $key ): void
    {
        self::init();
        if ( self::has( $key ) ) {
            unset( $_SESSION[ $key ] );
        }
    }

    /**
     * Remove todas as sessões
     */
    public static function removeAll(): void
    {
        self::init();
        $_SESSION = [];

        // Limpa o cookie da sessão
        $sessionName = session_name();
        if ( $sessionName !== false && isset( $_COOKIE[ $sessionName ] ) ) {
            setcookie( $sessionName, '', time() - 3600, '/' );
        }

        session_destroy();
        self::$initialized = false;
    }

    /**
     * Cria múltiplas flash messages
     */
    /**
     * @param array<string, mixed> $messages
     */
    public static function flashs(
        array $messages,
        string $icon = '<i class="bi bi-exclamation-circle"></i>',
        string $type = 'info',
    ): void {
        foreach ( $messages as $index => $message ) {
            self::flash( $index, $message, $icon, $type );
        }
    }

    /**
     * Cria uma flash message
     */
    public static function flash(
        string $key,
        mixed $message,
        string $icon = '<i class="bi bi-exclamation-circle"></i>',
        string $type = 'info',
    ): void {
        self::init();
        $_SESSION[ '__flash' ][ $key ] = [ 
            'message'    => $icon . " " . $message,
            'type'       => $type,
            'viewed'     => false,
            'created_at' => time(),
        ];
    }

    /**
     * Cria uma flash message com script
     */
    public static function flashScript( string $key, mixed $message ): void
    {
        self::init();
        $_SESSION[ '__flash' ][ $key ] = [ 
            'message'    => $message,
            'type'       => 'script',
            'viewed'     => false,
            'created_at' => time(),
        ];
    }

    /**
     * Marca flash messages como visualizadas
     */
    public static function markAsViewed( ?string $key = null ): void
    {
        self::init();
        if ( isset( $_SESSION[ '__flash' ] ) ) {
            if ( $key ) {
                if ( isset( $_SESSION[ '__flash' ][ $key ] ) ) {
                    $_SESSION[ '__flash' ][ $key ][ 'viewed' ] = true;
                }
            } else {
                foreach ( $_SESSION[ '__flash' ] as $flashKey => $message ) {
                    $_SESSION[ '__flash' ][ $flashKey ][ 'viewed' ] = true;
                }
            }
        }
    }

    /**
     * Remove flash messages visualizadas
     */
    public static function removeViewedFlash(): void
    {
        if ( $_SERVER[ 'REQUEST_METHOD' ] === 'GET' && self::has( '__flash' ) ) {
            foreach ( $_SESSION[ '__flash' ] as $key => $flash ) {
                // Remove mensagens visualizadas ou expiradas (mais de 5 minutos)
                if ( $flash[ 'viewed' ] || ( time() - $flash[ 'created_at' ] > 300 ) ) {
                    unset( $_SESSION[ '__flash' ][ $key ] );
                }
            }
            // Remove a chave __flash se estiver vazia
            if ( empty( $_SESSION[ '__flash' ] ) ) {
                self::remove( '__flash' );
            }
        }
    }

    /**
     * Obtém todas as flash messages não visualizadas
     */
    /**
     * @return array<string, mixed>
     */
    public static function getFlashes(): array
    {
        return self::get( '__flash', [] );
    }

    /**
     * Regenera o ID da sessão
     */
    public static function regenerate(): bool
    {
        self::init();

        return session_regenerate_id( true );
    }

}