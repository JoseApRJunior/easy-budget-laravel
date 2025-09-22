<?php

namespace core\services;

class CacheService
{
    private string $cachePath;

    public function __construct()
    {
        $this->cachePath = BASE_PATH . '/storage/cache/';
        if ( !is_dir( $this->cachePath ) ) {
            mkdir( $this->cachePath, 0755, true );
        }
    }

    public function remember( string $key, int $ttl, callable $callback ): mixed
    {
        $cached = $this->get( $key );

        if ( $cached !== null ) {
            return $cached;
        }

        $result = $callback();
        $this->put( $key, $result, $ttl );

        return $result;
    }

    public function get( string $key ): mixed
    {
        $file = $this->getFilePath( $key );

        if ( !file_exists( $file ) ) {
            return null;
        }

        $data = unserialize( file_get_contents( $file ) );

        // Verificar se expirou
        if ( $data[ 'expires' ] < time() ) {
            unlink( $file );

            return null;
        }

        return $data[ 'value' ];
    }

    public function put( string $key, mixed $value, int $ttl ): bool
    {
        $data = [ 
            'value'   => $value,
            'expires' => time() + $ttl,
        ];

        return file_put_contents(
            $this->getFilePath( $key ),
            serialize( $data ),
        ) !== false;
    }

    private function getFilePath( string $key ): string
    {
        return $this->cachePath . md5( $key ) . '.cache';
    }

}

// TODO TESTAR AQUI
// ver uso pratico cache
// Otimizar queries
//  Usar eager loading
// $customers = $this->customerRepository->findWithRelations( [ 'orders', 'invoices' ] );

// Implementar cache com Redis/Memcached
