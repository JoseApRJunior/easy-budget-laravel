<?php

use app\enums\InvoiceStatusEnum;
use app\enums\MerchantOrderOrderStatusMercadoPagoEnum;
use app\enums\MerchantOrderStatusMercadoPagoEnum;
use app\enums\PaymentStatusMercadoPagoEnum;
use app\enums\PlanSubscriptionsStatusEnum;
use core\functions\DateUtils;
use core\library\Session;
use core\support\Logger;

function generateRandomPassword( int $length = 6 ): string
{
    if ( $length < 6 ) {
        throw new \InvalidArgumentException( 'Password length must be at least 6 characters.' );
    }
    $lowercase = 'abcdefghijklmnopqrstuvwxyz';
    $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $numbers   = '0123456789';
    $symbols   = '@#$!%*?&';

    $all_characters = $lowercase . $uppercase . $numbers . $symbols;
    $password       = '';

    // Garantir pelo menos um caractere de cada tipo
    $password .= $lowercase[ random_int( 0, strlen( $lowercase ) - 1 ) ];
    $password .= $uppercase[ random_int( 0, strlen( $uppercase ) - 1 ) ];
    $password .= $numbers[ random_int( 0, strlen( $numbers ) - 1 ) ];
    $password .= $symbols[ random_int( 0, strlen( $symbols ) - 1 ) ];

    // Preencher o resto da senha
    for ( $i = strlen( $password ); $i < $length; $i++ ) {
        $password .= $all_characters[ random_int( 0, strlen( $all_characters ) - 1 ) ];
    }

    // Embaralhar a senha para evitar padrões previsíveis
    return str_shuffle( $password );
}

/**
 * Gera um token e sua data de expiração.
 *
 * @param string $timeExpires Tempo de expiração
 * @return array<int, string> Array contendo token e data de expiração
 */
function generateTokenExpirate( string $timeExpires = '+24 hours' ): array
{
    $token      = bin2hex( random_bytes( 32 ) ); // Gera um token aleatório
    $expiryDate = ( new DateTime() )->modify( $timeExpires )->format( 'Y-m-d H:i:s' );

    return [ $token, $expiryDate ];
}

function dateExpirate( string $timeExpires = '+1 month ' ): string
{
    return ( new DateTime() )->modify( $timeExpires )->format( 'Y-m-d H:i:s' );
}

/**
 * Verifica se um token já expirou baseado na sua data de expiração.
 *
 * @param DateTime|string $expiryDate Data de expiração do token (DateTime ou string)
 * @return bool Retorna true se o token já expirou, false se ainda é válido
 *
 * @example
 * // Com DateTime
 * $expiry = new DateTime('+1 hour');
 * hasTokenExpirate($expiry); // false (ainda válido)
 *
 * $expiry = new DateTime('-1 hour');
 * hasTokenExpirate($expiry); // true (já expirou)
 *
 * // Com string
 * hasTokenExpirate('2024-12-31 23:59:59'); // false se ainda não passou
 * hasTokenExpirate('2020-01-01 00:00:00'); // true (já expirou)
 */
function hasTokenExpirate( $expiryDate ): bool
{
    if ( !$expiryDate instanceof DateTime ) {
        $expiryDate = new DateTime( $expiryDate );
    }
    $now = new DateTime();

    return $expiryDate < $now;
}

// Função para formatar valor para exibição em moeda brasileira
function formatMoney( mixed $value ): string
{
    if ( $value === null || $value === '' ) {
        return '';
    }

    // Converte para float e formata
    $value = (float) $value;

    return 'R$ ' . number_format( $value, 2, ',', '.' );
}

// Função para converter valor de moeda para float
function convertMoneyToFloat( mixed $value ): ?float
{
    if ( $value === null || $value === '' ) {
        return null;
    }

    // Garante que o valor é uma string antes de processar
    if ( !is_string( $value ) ) {
        return null;
    }

    // Remove R$ e quaisquer caracteres não numéricos exceto , e .
    $value = preg_replace( '/[^\d,.-]/', '', $value );

    // Trata valores negativos
    $isNegative = str_contains( $value, '-' );
    $value      = str_replace( '-', '', $value );

    // Se tiver vírgula e ponto, verifica qual é o separador decimal
    if ( str_contains( $value, ',' ) && str_contains( $value, '.' ) ) {
        // Se a vírgula vier depois do ponto, ela é o separador decimal
        if ( strrpos( $value, ',' ) > strrpos( $value, '.' ) ) {
            $value = str_replace( '.', '', $value ); // Remove pontos
            $value = str_replace( ',', '.', $value ); // Converte vírgula para ponto
        } else {
            $value = str_replace( ',', '', $value ); // Remove vírgulas
        }
    } else {
        // Se tiver apenas vírgula, converte para ponto
        $value = str_replace( ',', '.', $value );
    }

    // Converte para float
    $value = (float) $value;

    // Aplica o sinal negativo se necessário
    return $isNegative ? ( $value * -1 ) : $value;
}

// Função para validar valor monetário
function validateMoneyValue( mixed $value ): bool
{
    if ( $value === null || $value === '' ) {
        return true;
    }

    $cleanValue = preg_replace( '/[^\d,.-]/', '', $value );

    // Verifica se há caracteres válidos após a limpeza
    if ( empty( $cleanValue ) ) {
        return false;
    }

    // Tenta converter para float
    $floatValue = convertMoneyToFloat( $value );

    // Verifica se é um número válido e está dentro dos limites aceitáveis
    return is_numeric( $floatValue ) && $floatValue >= 0 && $floatValue <= 999999999.99;
}

function convertToDateTime( mixed $dateTimeString ): ?DateTime
{
    if ( $dateTimeString === null || $dateTimeString === '' ) {
        return null;
    }

    try {
        // Cria o objeto DateTime
        $dateTime = new DateTime( $dateTimeString );

        // Ajusta para o timezone local
        $dateTime->setTimezone( new DateTimeZone( env( 'APP_TIMEZONE' ) ) );

        // Formata sem microssegundos e recria o objeto
        $formatted = $dateTime->format( 'Y-m-d H:i:s' );
        $date      = DateTime::createFromFormat( 'Y-m-d H:i:s', $formatted, new DateTimeZone( env( 'APP_TIMEZONE' ) ) );

        return $date === false ? null : $date;

    } catch ( Exception $e ) {
        error_log( "Erro ao converter data: " . $e->getMessage() );
        return null;
    }
}

function convertDateLocale( mixed $dateCreated, string $locale ): DateTime
{
    $date = new DateTime( $dateCreated );
    $date->setTimezone( new DateTimeZone( $locale ) );

    return $date;
}

function convertToDateTimeString( mixed $dateTimeString, string $format = 'Y-m-d H:i:s' ): ?string
{
    if ( $dateTimeString === null || $dateTimeString === '' ) {
        return null;
    }
    // Lista de formatos a serem testados, do mais específico para o mais geral.
    $formats = [ 
        'Y-m-d\TH:i:s', // Formato ISO 8601 com segundos
        'Y-m-d\TH:i',   // Formato do <input type="datetime-local">
        'Y-m-d H:i:s',  // Formato padrão do banco de dados
        'Y-m-d',        // Apenas data
    ];

    foreach ( $formats as $format ) {
        $dateTime = DateTime::createFromFormat( $format, $dateTimeString );
        if ( $dateTime !== false ) {
            // Se o formato for apenas de data, zera a hora para consistência.
            if ( strpos( $format, 'H' ) === false && strpos( $format, 'i' ) === false ) {
                $dateTime->setTime( 0, 0, 0 );
            }

            return $dateTime->format( $format );
        }
    }

    // Se nenhum formato funcionou, retorna null e loga o erro.
    error_log( "Não foi possível converter a string '{$dateTimeString}' para um formato de data/hora válido." );

    return null;
}

function mapPaymentStatusMercadoPago( string $paymentStatus ): PaymentStatusMercadoPagoEnum
{
    return match ( $paymentStatus ) {
        'approved'     => PaymentStatusMercadoPagoEnum::approved,
        'pending'      => PaymentStatusMercadoPagoEnum::pending,
        'authorized'   => PaymentStatusMercadoPagoEnum::authorized,
        'in_process'   => PaymentStatusMercadoPagoEnum::in_process,
        'in_mediation' => PaymentStatusMercadoPagoEnum::in_mediation,
        'rejected'     => PaymentStatusMercadoPagoEnum::rejected,
        'cancelled'    => PaymentStatusMercadoPagoEnum::cancelled,
        'refunded'     => PaymentStatusMercadoPagoEnum::refunded,
        'charged_back' => PaymentStatusMercadoPagoEnum::charged_back,
        'recovered'    => PaymentStatusMercadoPagoEnum::recovered,
        default        => PaymentStatusMercadoPagoEnum::pending
    };
}

function mapPaymentStatusToPlanSubscriptionsStatus( string $paymentStatus ): PlanSubscriptionsStatusEnum
{
    return match ( $paymentStatus ) {
        'approved', 'recovered'                               => PlanSubscriptionsStatusEnum::active,
        'pending', 'authorized', 'in_process', 'in_mediation' => PlanSubscriptionsStatusEnum::pending,
        'rejected', 'cancelled', 'refunded', 'charged_back'   => PlanSubscriptionsStatusEnum::cancelled,

        default                                               => PlanSubscriptionsStatusEnum::pending
    };
}

function mapPaymentStatusToInvoiceStatus( string $paymentStatus ): InvoiceStatusEnum
{
    return match ( $paymentStatus ) {
        'approved', 'recovered'                               => InvoiceStatusEnum::paid,
        'pending', 'authorized', 'in_process', 'in_mediation' => InvoiceStatusEnum::pending,
        'rejected', 'cancelled', 'refunded', 'charged_back'   => InvoiceStatusEnum::cancelled,

        default                                               => InvoiceStatusEnum::pending
    };
}

function mapMerchantOrderStatusMercadoPago( mixed $merchantOrderStatus ): MerchantOrderStatusMercadoPagoEnum
{
    return match ( $merchantOrderStatus ) {
        'opened'  => MerchantOrderStatusMercadoPagoEnum::opened,
        'closed'  => MerchantOrderStatusMercadoPagoEnum::closed,
        'expired' => MerchantOrderStatusMercadoPagoEnum::expired,
        default   => MerchantOrderStatusMercadoPagoEnum::opened
    };
}

function mapMerchantOrderOrderStatusMercadoPago( mixed $merchantOrderOrderStatus ): MerchantOrderOrderStatusMercadoPagoEnum
{
    return match ( $merchantOrderOrderStatus ) {
        'payment_required'     => MerchantOrderOrderStatusMercadoPagoEnum::payment_required,
        'payment_in_process'   => MerchantOrderOrderStatusMercadoPagoEnum::payment_in_process,
        'reverted'             => MerchantOrderOrderStatusMercadoPagoEnum::reverted,
        'paid'                 => MerchantOrderOrderStatusMercadoPagoEnum::paid,
        'partially_reverted'   => MerchantOrderOrderStatusMercadoPagoEnum::partially_reverted,
        'partially_paid'       => MerchantOrderOrderStatusMercadoPagoEnum::partially_paid,
        'partially_in_process' => MerchantOrderOrderStatusMercadoPagoEnum::partially_in_process,
        'undefined'            => MerchantOrderOrderStatusMercadoPagoEnum::undefined,
        'expired'              => MerchantOrderOrderStatusMercadoPagoEnum::expired,
        default                => MerchantOrderOrderStatusMercadoPagoEnum::undefined
    };
}

function logger(): Logger
{
    // Singleton ou container
    static $logger = null;
    if ( $logger === null ) {
        $logger = new Logger();
    }

    return $logger;
}

/**
 * Summary of validateMercadoPagoAuthenticity
 * @param mixed $data
 * @return bool
 */
function validateMercadoPagoAuthenticity( $data ): bool
{
    try {
        // Headers
        $headers    = getallheaders();
        $xSignature = $headers[ 'X-Signature' ] ?? $headers[ 'x-signature' ];
        $xRequestId = $headers[ 'X-Request-Id' ] ?? $headers[ 'x-request-id' ];

        // Parse signature
        $parts = explode( ',', $xSignature );
        $ts    = null;
        $hash  = null;
        foreach ( $parts as $part ) {
            $keyValue = explode( '=', $part, 2 );
            if ( count( $keyValue ) == 2 ) {
                $key   = trim( $keyValue[ 0 ] );
                $value = trim( $keyValue[ 1 ] );
                if ( $key === "ts" ) {
                    $ts = $value;
                } elseif ( $key === "v1" ) {
                    $hash = $value;
                }
            }
        }

        // Get ID
        $dataId = $data[ 'data.id' ] ?? $data[ 'data_id' ] ?? $data[ 'id' ];

        // Build manifest
        $manifest = "id:{$dataId};request-id:{$xRequestId};ts:{$ts};";

        // Calculate hash
        $calculatedHash = hash_hmac( 'sha256', $manifest, env( 'MERCADO_PAGO_WEBHOOK_SECRET' ) );

        $valid = hash_equals( $calculatedHash, $hash );
        if ( !$valid ) {
            logger()->info( "Webhook não autorizado," .
                " ID: {$dataId}, Request ID: {$xRequestId}, Timestamp: {$ts}, Hash: {$hash}, Calculated Hash: {$calculatedHash}" );
        }

        return $valid;

    } catch ( Exception $e ) {
        logger()->error( 'Webhook error', [ 'error' => $e->getMessage() ] );

        return false;
    }
}

/**
 * Summary of removeUnnecessaryIndexes
 * @param array<string, mixed>|object $originalData Array or object com os dados originais
 * @param array<int, string> $excludesPropertiesData Array com as propriedades a serem excluídas
 * @param array<string, mixed> $requestData Array com os dados da requisição
 * @return array<string, mixed> Array  $requestData com os dados filtrados do array $originalData e removido do array $excludesPropertiesData
 */
function removeUnnecessaryIndexes( mixed $originalData, array $excludesPropertiesData, array $requestData ): array
{
    if ( is_object( $originalData ) ) {
        $originalData = get_object_vars( $originalData );
    }

    $originalFiltered = array_diff_key( $originalData, array_flip( $excludesPropertiesData ) );

    $filteredRequest = array_intersect_key( $requestData, $originalFiltered );

    return array_replace( $originalFiltered, $filteredRequest );
}

/**
 * Obtém as propriedades do construtor de uma classe
 *
 * @param class-string $class Nome da classe
 * @return array<string, mixed> Array com os nomes das propriedades do construtor
 */
function getConstructorProperties( string $class ): array
{
    if ( !class_exists( $class ) ) {
        return [];
    }

    $reflectionClass = new ReflectionClass( $class );
    $constructor     = $reflectionClass->getConstructor();

    if ( !$constructor ) {
        return [];
    }

    $parameters = $constructor->getParameters();

    $properties = [];
    foreach ( $parameters as $parameter ) {
        $properties[ $parameter->getName()] = null; // Inicializa com null ou outro valor padrão
    }

    return $properties;
}

/**
 * Compara dois objetos ou arrays, ignorando campos específicos e tratando objetos DateTime
 *
 * @param mixed $objLast Primeiro objeto ou array para comparação
 * @param mixed $objNew Segundo objeto ou array para comparação
 * @param array<int, string> $ignoreFields Campos a serem ignorados na comparação
 * @return bool Retorna true se os objetos forem iguais, false caso contrário
 */
function compareObjects( mixed $objLast, mixed $objNew, array $ignoreFields = [] ): bool
{
    // Converte objetos para arrays
    $array1 = (array) $objLast;
    $array2 = (array) $objNew;

    // Remove campos a serem ignorados
    $array1 = array_diff_key( $array1, array_flip( $ignoreFields ) );
    $array2 = array_diff_key( $array2, array_flip( $ignoreFields ) );

    // Se os arrays têm chaves diferentes, não são iguais
    if ( array_keys( $array1 ) !== array_keys( $array2 ) ) {
        return false;
    }

    // Compara cada valor
    foreach ( $array1 as $key => $value1 ) {
        $value2 = $array2[ $key ];

        // Trata objetos DateTime
        if ( $value1 instanceof DateTime && $value2 instanceof DateTime ) {
            // Compara timestamps para evitar problemas com microssegundos
            if ( $value1->getTimestamp() !== $value2->getTimestamp() ) {
                return false;
            }
        }
        // Trata arrays aninhados ou objetos
        elseif ( is_array( $value1 ) || is_object( $value1 ) ) {
            if ( !compareObjects( $value1, $value2, $ignoreFields ) ) {
                return false;
            }
        }
        // Compara valores escalares
        elseif ( $value1 !== $value2 ) {
            return false;
        }
    }

    return true;
}

function dateUtils(): string
{
    return DateUtils::class;
}

/**
 * Gera um token CSRF (Cross-Site Request Forgery) único e o armazena na sessão.
 *
 * @return string Retorna o token CSRF gerado como uma string hexadecimal.
 *
 * @throws Exception Lança uma exceção se não for possível gerar bytes aleatórios.
 */
function generateCSRFToken(): string
{
    $token = bin2hex( random_bytes( 32 ) );
    Session::set( 'csrf_token', $token );

    return $token;
}

function generateReportHash( mixed $content, mixed $data, mixed $user_id, mixed $tenant_id ): string
{
    // Normaliza o conteúdo
    $normalized = [ 
        'content'  => preg_replace( '/\s+/', ' ', trim( $content ) ),
        'params'   => array_filter( $data, fn( $key ) => $key !== 'csrf_token', ARRAY_FILTER_USE_KEY ),
        'metadata' => [ 
            'tenant_id' => $tenant_id,
            'user_id'   => $user_id,
        ],
    ];

    // Ordena os parâmetros para garantir consistência
    ksort( $normalized[ 'params' ] );

    // Normaliza arrays dentro dos parâmetros
    array_walk_recursive( $normalized[ 'params' ], function (&$item) {
        if ( is_array( $item ) ) {
            sort( $item );
        }
    } );

    // Gera o hash apenas do conteúdo normalizado
    $jsonContent = json_encode( $normalized, JSON_UNESCAPED_UNICODE );

    if ( $jsonContent === false ) {
        return '';
    }

    return hash( 'sha256', $jsonContent );
}

function generateDescriptionPipe( mixed $data ): string
{
    return implode(
        ' | ',
        array_map(
            fn( $key, $item ) => $key . ': ' . ( is_array( $item ) ? implode( ', ', $item ) : $item ),
            array_keys( $data ),
            array_values( $data ),
        ),
    );
}

function formatDescription( mixed $data ): string
{
    return implode(
        ' | ',
        array_map(
            function ($key, $item) {
                // Converte DateTime para string
                if ( $item instanceof DateTime ) {
                    $item = $item->format( 'Y-m-d H:i:s' );
                }

                // Trata arrays
                if ( is_array( $item ) ) {
                    // Converte possíveis DateTime dentro do array
                    $item = array_map( function ($value) {
                        if ( $value instanceof DateTime ) {
                            return $value->format( 'Y-m-d H:i:s' );
                        }

                        return $value;
                    }, $item );

                    return $key . ': ' . implode( ', ', $item );
                }

                // Trata outros tipos de objetos
                if ( is_object( $item ) ) {
                    if ( method_exists( $item, '__toString' ) ) {
                        return $key . ': ' . (string) $item;
                    }
                    if ( method_exists( $item, 'toArray' ) ) {
                        return $key . ': ' . implode( ', ', $item->toArray() );
                    }

                    // Se não puder converter, usa o nome da classe
                    return $key . ': [' . get_class( $item ) . ']';
                }

                return $key . ': ' . $item;
            },
            array_keys( $data ),
            array_values( $data ),
        ),
    );
}
function getTranslatedMessage( string $entity = 'default', string $method = 'index', string $indiceParameter = 'default', string $indiceValidator = 'default', ?string $language = null ): string
{
    // Se não foi especificado um idioma, usa o padrão do sistema
    $language = $language ?? env( 'LANG' );

    // Define o caminho do arquivo de traduções
    $messagesFile = BASE_PATH . "/translations/{$language}/{$entity}/{$method}.php";

    try {
        // Verifica se o arquivo existe
        if ( !file_exists( $messagesFile ) ) {
            throw new RuntimeException( "Arquivo de traduções não encontrado para o idioma: {$language}" );
        }

        // Carrega as mensagens
        $messages = require $messagesFile;

        // Verifica se existe a tradução para o parâmetro e validador específicos
        if ( isset( $messages[ $indiceParameter ][ $indiceValidator ] ) ) {
            return $messages[ $indiceParameter ][ $indiceValidator ];
        }

        // Se não encontrou a mensagem específica, tenta encontrar uma mensagem genérica
        if ( isset( $messages[ $indiceParameter ][ 'default' ] ) ) {
            return $messages[ $indiceParameter ][ 'default' ];
        }

        // Se não encontrou nenhuma mensagem, retorna uma mensagem padrão
        return "Validação falhou para o campo {$indiceParameter}";

    } catch ( Exception $e ) {
        // Log do erro
        error_log( "Erro ao buscar tradução: " . $e->getMessage() );

        // Retorna uma mensagem genérica em caso de erro
        return "Erro de validação no formulário";
    }
}

/**
 * Função auxiliar para carregar todas as mensagens de um idioma
 *
 * @param string $entity Entidade
 * @param string $method Método
 * @param string|null $language O idioma desejado
 * @return array<string, mixed> Array com todas as mensagens
 */
function loadMessages( string $entity = 'default', string $method = 'index', ?string $language = null ): array
{
    $language     = $language ?? env( 'LANG' );
    $messagesFile = BASE_PATH . "/translations/{$language}/{$entity}/{$method}.php";

    try {
        if ( !file_exists( $messagesFile ) ) {
            throw new RuntimeException( "Arquivo de traduções não encontrado" );
        }

        return require $messagesFile;

    } catch ( Exception $e ) {
        error_log( "Erro ao carregar mensagens: " . $e->getMessage() );

        return [];
    }
}

/**
 * Gera uma URL completa ou relativa com base no caminho fornecido.
 *
 * @param string $path O caminho para ser adicionado à URL base.
 * @param bool $absolute Se true, retorna a URL completa; se false, retorna o caminho relativo.
 * @return string A URL completa ou o caminho relativo.
 */
function buildUrl( string $path, bool $absolute = true ): string
{
    $baseUrl = env( 'APP_URL' );

    return $absolute ? rtrim( $baseUrl, '/' ) . $path : $path;
}