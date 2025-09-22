<?php

namespace app\database\entitiesORM;

use app\interfaces\EntityORMInterface;
use DateTime;
use DateTimeImmutable;
use ReflectionClass;
use ReflectionParameter;
use ReflectionProperty;

/**
 * Classe base abstrata para todas as entidades ORM.
 *
 * Esta classe fornece funcionalidades comuns para todas as entidades ORM,
 * incluindo o método create para instanciar entidades a partir de arrays.
 *
 * @package app\database\entitiesORM
 */
abstract class AbstractEntityORM implements EntityORMInterface
{
    /**
     * Cria uma nova instância da entidade a partir de um array de propriedades.
     *
     * Este método utiliza reflexão para mapear as propriedades do array
     * para os parâmetros do construtor da entidade.
     *
     * @param array $properties Array associativo com as propriedades para inicializar a entidade.
     * @return static Uma nova instância da entidade.
     * @throws \Exception Se houver erro ao processar as propriedades.
     */
    public static function create( array $properties ): static
    {
        try {
            $reflection  = new ReflectionClass( static::class);
            $constructor = $reflection->getConstructor();

            if ( !$constructor ) {
                // Se não há construtor, criar instância e popular propriedades
                $instance = $reflection->newInstanceWithoutConstructor();
                return $instance->populate( $properties );
            }

            $parameters      = $constructor->getParameters();
            $constructorArgs = [];

            foreach ( $parameters as $parameter ) {
                $paramName = $parameter->getName();

                // Verifica se a propriedade existe no array
                if ( array_key_exists( $paramName, $properties ) ) {
                    $value = $properties[ $paramName ];

                    // Aplica conversões básicas de tipo
                    $value             = self::convertValue( $value, $parameter );
                    $constructorArgs[] = $value;
                } else if ( $parameter->isDefaultValueAvailable() ) {
                    // Usa o valor padrão se disponível
                    $constructorArgs[] = $parameter->getDefaultValue();
                } else if ( $parameter->allowsNull() ) {
                    // Usa null se o parâmetro permite
                    $constructorArgs[] = null;
                } else {
                    throw new \Exception( "Propriedade obrigatória '{$paramName}' não encontrada para a classe " . static::class);
                }
            }

            // Criar a instância usando reflexão
            return $reflection->newInstanceArgs( $constructorArgs );

        } catch ( \Throwable $e ) {
            throw new \Exception(
                "Erro ao criar instância de " . static::class . ": " . $e->getMessage(),
                0,
                $e,
            );
        }
    }

    /**
     * Converte um valor baseado no tipo do parâmetro.
     *
     * @param mixed $value O valor a ser convertido
     * @param ReflectionParameter $parameter O parâmetro de referência
     * @return mixed O valor convertido
     */
    protected static function convertValue( $value, ReflectionParameter $parameter )
    {
        if ( $value === null ) {
            return null;
        }

        $paramType = $parameter->getType();
        if ( !$paramType ) {
            return $value;
        }

        // Conversão para DateTime/DateTimeImmutable
        $typeName = (string) $paramType;
        if ( strpos( $typeName, 'DateTime' ) !== false && is_string( $value ) ) {
            $dateTime = DateTime::createFromFormat( 'Y-m-d H:i:s', $value );
            if ( $dateTime === false ) {
                $dateTime = DateTime::createFromFormat( 'Y-m-d', $value );
                if ( $dateTime !== false ) {
                    $dateTime->setTime( 0, 0, 0 );
                }
            }

            if ( $dateTime !== false ) {
                return ( strpos( $typeName, 'Immutable' ) !== false )
                    ? DateTimeImmutable::createFromMutable( $dateTime )
                    : $dateTime;
            }
        }

        // Conversões básicas
        if ( strpos( $typeName, 'int' ) !== false && is_numeric( $value ) ) {
            return (int) $value;
        }
        if ( strpos( $typeName, 'float' ) !== false && is_numeric( $value ) ) {
            return (float) $value;
        }
        if ( strpos( $typeName, 'bool' ) !== false ) {
            return is_bool( $value ) ? $value : (bool) $value;
        }
        if ( strpos( $typeName, 'string' ) !== false ) {
            return (string) $value;
        }

        return $value;
    }

    /**
     * Popula as propriedades da entidade existente a partir de um array.
     *
     * Este método utiliza reflexão para mapear as propriedades do array
     * para as propriedades da entidade, aplicando conversões de tipo quando necessário.
     *
     * @param array<string, mixed> $properties Array associativo com as propriedades para atualizar a entidade.
     * @return static A própria instância da entidade para permitir method chaining.
     * @throws \Exception Se houver erro ao processar as propriedades.
     */
    public function populate( array $properties ): static
    {
        try {
            $reflection      = new ReflectionClass( $this );
            $classProperties = $reflection->getProperties();

            foreach ( $classProperties as $property ) {
                $propertyName = $property->getName();

                // Verifica se a propriedade existe no array de dados
                if ( array_key_exists( $propertyName, $properties ) ) {
                    $value = $properties[ $propertyName ];

                    // Aplica conversões básicas
                    $value = $this->convertPropertyValue( $value, $property );

                    // Define o valor na propriedade
                    $property->setAccessible( true );
                    $property->setValue( $this, $value );
                }
            }

            return $this;

        } catch ( \Throwable $e ) {
            throw new \Exception(
                "Erro ao popular propriedades de " . static::class . ": " . $e->getMessage(),
                0,
                $e,
            );
        }
    }

    /**
     * Converte um valor baseado no tipo da propriedade.
     *
     * @param mixed $value O valor a ser convertido
     * @param ReflectionProperty $property A propriedade de referência
     * @return mixed O valor convertido
     */
    private function convertPropertyValue( $value, ReflectionProperty $property )
    {
        if ( $value === null || $value === '' ) {
            return null;
        }

        // Obtém o tipo da propriedade usando doc comment se ReflectionType não estiver disponível
        $docComment = $property->getDocComment();

        // Conversão para DateTime/DateTimeImmutable
        if ( $docComment && ( strpos( $docComment, 'DateTime' ) !== false || strpos( $docComment, 'DateTimeImmutable' ) !== false ) && is_string( $value ) ) {
            $dateTime = DateTime::createFromFormat( 'Y-m-d H:i:s', $value );
            if ( $dateTime === false ) {
                $dateTime = DateTime::createFromFormat( 'Y-m-d', $value );
                if ( $dateTime !== false ) {
                    $dateTime->setTime( 0, 0, 0 );
                }
            }

            if ( $dateTime !== false ) {
                return ( strpos( $docComment, 'DateTimeImmutable' ) !== false )
                    ? DateTimeImmutable::createFromMutable( $dateTime )
                    : $dateTime;
            }
        }

        // Conversões básicas baseadas no tipo de dados
        if ( is_string( $value ) ) {
            // Tentativa de conversão para tipos numéricos
            if ( is_numeric( $value ) ) {
                if ( strpos( $value, '.' ) !== false ) {
                    return (float) $value;
                } else {
                    return (int) $value;
                }
            }

            // Tentativa de conversão para boolean
            $lowerValue = strtolower( $value );
            if ( in_array( $lowerValue, [ 'true', '1', 'yes', 'on' ], true ) ) {
                return true;
            }
            if ( in_array( $lowerValue, [ 'false', '0', 'no', 'off' ], true ) ) {
                return false;
            }

            // Tentativa de decodificar JSON
            if ( ( $value[ 0 ] === '{' || $value[ 0 ] === '[' ) && json_decode( $value ) !== null ) {
                return json_decode( $value, true );
            }
        }

        return $value;
    }

    /**
     * Converte a entidade para um array associativo.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        try {
            $reflection = new ReflectionClass( $this );
            $properties = $reflection->getProperties();
            $data       = [];

            foreach ( $properties as $property ) {
                $property->setAccessible( true );
                $value        = $property->getValue( $this );
                $propertyName = $property->getName();

                // Conversão de tipos especiais para array
                if ( $value instanceof DateTime || $value instanceof DateTimeImmutable ) {
                    $data[ $propertyName ] = $value->format( 'Y-m-d H:i:s' );
                } elseif ( is_object( $value ) && method_exists( $value, 'toArray' ) ) {
                    $data[ $propertyName ] = $value->toArray();
                } elseif ( is_object( $value ) && method_exists( $value, 'jsonSerialize' ) ) {
                    $data[ $propertyName ] = $value->jsonSerialize();
                } else {
                    $data[ $propertyName ] = $value;
                }
            }

            return $data;

        } catch ( \Throwable $e ) {
            // Fallback para o método jsonSerialize em caso de erro
            return $this->jsonSerialize();
        }
    }

    /**
     * Serializa a entidade para JSON.
     *
     * @return array Array associativo representando a entidade.
     */
    public function jsonSerialize(): array
    {
        $reflection = new ReflectionClass( $this );
        $properties = $reflection->getProperties( ReflectionProperty::IS_PUBLIC );
        $data       = [];

        foreach ( $properties as $property ) {
            $value = $property->getValue( $this );

            // Conversão de DateTime para string
            if ( $value instanceof DateTime || $value instanceof DateTimeImmutable ) {
                $data[ $property->getName()] = $value->format( 'Y-m-d H:i:s' );
            } else if ( is_object( $value ) && method_exists( $value, 'jsonSerialize' ) ) {
                $data[ $property->getName()] = $value->jsonSerialize();
            } else if ( is_array( $value ) || is_object( $value ) ) {
                $data[ $property->getName()] = json_encode( $value );
            } else {
                $data[ $property->getName()] = $value;
            }
        }

        return $data;
    }

}