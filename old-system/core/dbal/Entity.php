<?php

namespace core\dbal;

use app\interfaces\EntityInterface;
use DateTime;

abstract class Entity implements EntityInterface
{
    public function __construct( protected array $data = [] ) {}

    /**
     * Cria uma nova instância da entidade com as propriedades fornecidas.
     *
     * @param array|bool $properties Um array associativo contendo as propriedades para inicializar a entidade.
     *                               Se `false`, um objeto vazio é criado.
     *
     * @return Entity Uma nova instância da entidade.
     */
    public static function create( array|bool $properties ): Entity
    {
        try {
            if ( !is_array( $properties ) ) {
                return new EntityNotFound();
            }

            $decodedProperties = [];
            foreach ( $properties as $key => $value ) {
                if ( is_string( $value ) && str_ends_with( (string) $value, '_id' ) ) {
                    $value = (int) $value;
                }
                if ( is_string( $value ) ) {
                    // Tenta decodificar JSON
                    $decoded = json_decode( $value, true );
                    if ( json_last_error() === JSON_ERROR_NONE ) {
                        $decodedProperties[ $key ] = $decoded;
                    } else {
                        // Tenta converter para DateTime se for uma string de data válida
                        $dateTime = DateTime::createFromFormat( 'Y-m-d H:i:s', $value );
                        if ( $dateTime === false ) {
                            $dateTime = DateTime::createFromFormat( 'Y-m-d', $value );
                            // Só zera a hora para campos específicos de data (birth_date, etc.)
                            if ( $dateTime !== false && str_ends_with( $key, '_date' ) && !str_contains( $key, 'transaction' ) && !str_contains( $key, 'updated' ) && !str_contains( $key, 'created' ) ) {
                                $dateTime->setTime( 0, 0, 0 );
                            }
                        }
                        $decodedProperties[ $key ] = ( $dateTime !== false ) ? $dateTime : $value;
                    }
                } elseif ( $value instanceof DateTime ) {
                    // Remove microssegundos de objetos DateTime
                    $cleanDateTime = DateTime::createFromFormat( 'Y-m-d H:i:s', $value->format( 'Y-m-d H:i:s' ) );
                    $cleanDateTime->setTimezone( $value->getTimezone() );

                    // Só zera a hora para campos específicos de data
                    if ( str_ends_with( $key, '_date' ) && !str_contains( $key, 'transaction' ) && !str_contains( $key, 'updated' ) && !str_contains( $key, 'created' ) ) {
                        $cleanDateTime->setTime( 0, 0, 0 );
                    }

                    $decodedProperties[ $key ] = $cleanDateTime;
                } else {
                    $decodedProperties[ $key ] = $value;
                }
            }

            /** @phpstan-ignore-next-line */
            return new static( ...$decodedProperties );
        } catch ( \Throwable $e ) {
            throw new \Exception( "Erro ao processar a solicitação, tente novamente mais tarde ou entre em contato com suporte!", 0, $e );
        }
    }

    public function toArray(): array
    {
        $array = get_object_vars( $this );
        foreach ( $array as $key => $value ) {
            if ( $value instanceof DateTime ) {
                // Remove microssegundos criando um novo DateTime limpo
                $cleanDateTime = DateTime::createFromFormat( 'Y-m-d H:i:s', $value->format( 'Y-m-d H:i:s' ) );
                $cleanDateTime->setTimezone( $value->getTimezone() );

                // Só zera a hora se for especificamente um campo de data (sem hora)
                // Como birth_date, created_date, etc. - não transaction_date
                if ( str_ends_with( $key, '_date' ) && !str_contains( $key, 'transaction' ) && !str_contains( $key, 'updated' ) && !str_contains( $key, 'created' ) ) {
                    $cleanDateTime->setTime( 0, 0, 0 );
                }

                $array[ $key ] = $cleanDateTime;
            }

            if ( is_array( $value ) ) {
                $array[ $key ] = json_encode( $value );
            }
        }

        return $array;
    }

}
