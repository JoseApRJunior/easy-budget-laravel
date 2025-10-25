<?php

/**
 * Helper functions for Easy Budget Laravel
 */

/**
 * Gera um token seguro usando padrão criptograficamente seguro.
 *
 * Utiliza random_bytes() para gerar bytes aleatórios e converte para o formato
 * especificado. Cada byte gera 2 caracteres em hexadecimal ou ~1.37 caracteres em base64.
 * O comprimento final do token varia conforme o formato:
 * - 'hex': 2 * $length caracteres
 * - 'base64': ceil(4 * $length / 3) caracteres (aproximado, devido ao padding)
 * - 'base64url': semelhante ao base64, mas seguro para URLs (sem +, /, =)
 * - 'alphanumeric': apenas letras e números, comprimento = $length
 *
 * @param int $length Número de bytes aleatórios a gerar (padrão: 32).
 *                    Deve ser um inteiro positivo e não exceder 128 para evitar uso excessivo de memória.
 * @param string $format Formato do token ('hex', 'base64', 'base64url' ou 'alphanumeric').
 *                       Padrão: 'hex'.
 * @return string Token seguro no formato especificado.
 * @throws InvalidArgumentException Se $length for inválido (não positivo ou muito grande) ou $format for inválido.
 * @throws Exception Se a geração de bytes aleatórios falhar (ex.: entropia insuficiente).
 */
function generateSecureToken( int $length = 32, string $format = 'hex' ): string
{
    if ( $length <= 0 ) {
        throw new InvalidArgumentException( 'O comprimento deve ser um inteiro positivo.' );
    }

    if ( $length > 128 ) {
        throw new InvalidArgumentException( 'O comprimento não pode exceder 128 bytes para evitar uso excessivo de memória.' );
    }

    if ( !in_array( $format, [ 'hex', 'base64', 'base64url', 'alphanumeric' ] ) ) {
        throw new InvalidArgumentException( 'Formato inválido. Use "hex", "base64", "base64url" ou "alphanumeric".' );
    }

    $bytes = random_bytes( $length );

    return match ( $format ) {
        'hex'          => bin2hex( $bytes ),
        'base64'       => base64_encode( $bytes ),
        'base64url'    => rtrim( strtr( base64_encode( $bytes ), '+/', '-_' ), '=' ),
        'alphanumeric' => generateAlphanumericToken( $length ),
    };
}

/**
 * Gera um token alfanumérico seguro.
 *
 * @param int $length Comprimento do token.
 * @return string
 */
function generateAlphanumericToken( int $length ): string
{
    $alphabet       = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    $alphabetLength = strlen( $alphabet );
    $token          = '';

    for ( $i = 0; $i < $length; $i++ ) {
        $index = random_int( 0, $alphabetLength - 1 );
        $token .= $alphabet[ $index ];
    }

    return $token;
}

/**
 * Valida e sanitiza um token de acordo com o formato esperado.
 *
 * @param string $token  O token recebido (ex.: via URL).
 * @param string $format Formato esperado: 'hex', 'base64', 'base64url' ou 'alphanumeric'.
 * @return string|null Token validado e normalizado, ou null se inválido.
 */
function validateAndSanitizeToken( string $token, string $format = 'hex' ): ?string
{
    if ( empty( $token ) ) {
        return null;
    }

    $patterns = [
        // 32 bytes em hex → 64 caracteres hexadecimais
        'hex'          => '/^[a-f0-9]{64}$/i',

        // 32 bytes em base64 → 43 ou 44 caracteres (com padding "=")
        'base64'       => '/^[A-Za-z0-9+\/]{42,43}=*$/',

        // 32 bytes em base64url → 43 caracteres, sem + / =
        'base64url'    => '/^[A-Za-z0-9\-_]{43,44}$/',

        // Token alfanumérico genérico de 64 caracteres
        'alphanumeric' => '/^[a-zA-Z0-9]{64}$/',
    ];

    if ( !isset( $patterns[ $format ] ) ) {
        return null;
    }

    if ( !preg_match( $patterns[ $format ], $token ) ) {
        return null;
    }

    // Normalização: hex em minúsculo, outros formatos mantidos
    return $format === 'hex' ? strtolower( $token ) : $token;
}

if ( !function_exists( 'money' ) ) {
    function money( $value, $decimals = 2 )
    {
        return app( App\Helpers\CurrencyHelper::class)->format( $value, $decimals );
    }
}

if ( !function_exists( 'format_date' ) ) {
    function format_date( $date, $format = 'd/m/Y' )
    {
        return app( App\Helpers\DateHelper::class)->formatDateOrDefault( $date, $format );
    }
}

/**
 * Format month/year in Brazilian Portuguese
 */
if ( !function_exists( 'month_year_pt' ) ) {
    function month_year_pt( $date ): string
    {
        return \Carbon\Carbon::parse( $date )
            ->locale( 'pt_BR' )
            ->translatedFormat( 'F/Y' );
    }
}

/**
 * Format time difference in human readable format
 */
if ( !function_exists( 'time_diff' ) ) {
    function time_diff( $datetime ): string
    {
        $now  = now();
        $diff = $now->diff( $datetime );

        if ( $diff->days > 0 ) {
            return $diff->days . ' dia' . ( $diff->days > 1 ? 's' : '' ) . ' atrás';
        } elseif ( $diff->h > 0 ) {
            return $diff->h . ' hora' . ( $diff->h > 1 ? 's' : '' ) . ' atrás';
        } elseif ( $diff->i > 0 ) {
            return $diff->i . ' minuto' . ( $diff->i > 1 ? 's' : '' ) . ' atrás';
        } else {
            return 'agora mesmo';
        }
    }
}

// /**
//  * Check current user's plan
//  */
// if ( !function_exists( 'checkPlan' ) ) {
//     function checkPlan(): ?object
//     {
//         if ( !auth()->check() ) {
//             return null;
//         }
//         $laravelUser = auth()->user();

//         // Get authenticated Laravel user
//         $authManager = app( 'auth' );
//         if ( !$authManager->check() ) {
//             return null;
//         }

//         $laravelUser = $authManager->user();
//         if ( !$laravelUser ) {
//             return null;
//         }

//         // Find provider for current user
//         $provider = \App\Models\Provider::where( 'user_id', $laravelUser->id )->first();
//         if ( !$provider ) {
//             return null;
//         }

//         // Get active plan subscription
//         $activeSubscription = $provider->planSubscriptions()
//             ->where( 'status', PlanSubscription::STATUS_ACTIVE )
//             ->where( 'end_date', '>', now() )
//             ->with( 'plan' )
//             ->first();

//         return $activeSubscription->plan;
//     }
// }

// /**
//  * Check for pending plan subscription
//  */
// if ( !function_exists( 'checkPlanPending' ) ) {
//     function checkPlanPending(): ?object
//     {
//         $user = user_auth();
//         if ( !$user ) {
//             return null;
//         }

//         // Get authenticated Laravel user
//         $authManager = app( 'auth' );
//         if ( !$authManager->check() ) {
//             return null;
//         }

//         $laravelUser = $authManager->user();
//         if ( !$laravelUser ) {
//             return null;
//         }

//         // Find provider for current user
//         $provider = \App\Models\Provider::where( 'user_id', $laravelUser->id )->first();
//         if ( !$provider ) {
//             return null;
//         }

//         // Get pending plan subscription
//         $pendingSubscription = $provider->planSubscriptions()
//             ->where( 'status', \App\Models\PlanSubscription::STATUS_PENDING )
//             ->with( 'plan' )
//             ->first();

//         if ( !$pendingSubscription || !$pendingSubscription->plan ) {
//             return null;
//         }

//         // Add status to the response object
//         $result                     = $pendingSubscription->plan;
//         $result->status             = $pendingSubscription->status;
//         $result->subscription_id    = $pendingSubscription->id;
//         $result->transaction_amount = $pendingSubscription->transaction_amount;

//         return $result;
//     }
// }

// /**
//  * Check if current user has trial plan
//  */
// if ( !function_exists( 'isTrial' ) ) {
//     function isTrial(): bool
//     {
//         $user = user_auth();
//         if ( !$user ) {
//             return false;
//         }

//         // Get authenticated Laravel user
//         $authManager = app( 'auth' );
//         if ( !$authManager->check() ) {
//             return false;
//         }

//         $laravelUser = $authManager->user();
//         if ( !$laravelUser ) {
//             return false;
//         }

//         // Find provider for current user
//         $provider = \App\Models\Provider::where( 'user_id', $laravelUser->id )->first();
//         if ( !$provider ) {
//             return true; // No provider = trial by default
//         }

//         // Get active plan subscription
//         $activeSubscription = $provider->planSubscriptions()
//             ->where( 'status', PlanSubscription::STATUS_ACTIVE )
//             ->where( 'end_date', '>', now() )
//             ->first();

//         if ( !$activeSubscription ) {
//             return true; // No active subscription = trial
//         }

//         // Check if it's trial (payment_method = trial and amount = 0)
//         return strtolower( $activeSubscription->payment_method ) === 'trial' &&
//             $activeSubscription->transaction_amount <= 0;
//     }
// }

// /**
//  * Check if trial plan is expired
//  */
// if ( !function_exists( 'isTrialExpired' ) ) {
//     function isTrialExpired(): bool
//     {
//         $user = user_auth();
//         if ( !$user ) {
//             return false;
//         }

//         // Get authenticated Laravel user
//         $authManager = app( 'auth' );
//         if ( !$authManager->check() ) {
//             return false;
//         }

//         $laravelUser = $authManager->user();
//         if ( !$laravelUser ) {
//             return false;
//         }

//         // Find provider for current user
//         $provider = \App\Models\Provider::where( 'user_id', $laravelUser->id )->first();
//         if ( !$provider ) {
//             return false; // No provider = trial not expired
//         }

//         // Get active plan subscription
//         $activeSubscription = $provider->planSubscriptions()
//             ->where( 'status', PlanSubscription::STATUS_ACTIVE )
//             ->where( 'end_date', '>', now() )
//             ->first();

//         if ( !$activeSubscription ) {
//             return false; // No active subscription = trial not expired
//         }

//         // Check if trial is expired (end_date passed)
//         return $activeSubscription->end_date < now();
//     }
// }
