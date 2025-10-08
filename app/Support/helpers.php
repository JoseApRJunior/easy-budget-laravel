<?php

/**
 * Helper functions for Easy Budget Laravel
 */

/**
 * Check if user is authenticated
 */
if ( !function_exists( 'user_auth' ) ) {
    function user_auth(): ?array
    {
        // Check if user is authenticated via session
        if ( session()->has( 'auth' ) ) {
            return session( 'auth' );
        }

        // Check if user is authenticated via Laravel's auth system
        $authManager = app( 'auth' );
        if ( $authManager->check() ) {
            $user = $authManager->user();
            return [
                'id'       => $user->id,
                'name'     => $user->name,
                'email'    => $user->email,
                'role'     => $user->role ?? 'user',
                'is_admin' => $user->role === 'admin' || $user->role === 'super_admin'
            ];
        }

        return null;
    }
}

/**
 * Check if current user is admin
 */
if ( !function_exists( 'admin' ) ) {
    function admin(): bool
    {
        $user = user_auth();
        return $user && isset( $user[ 'is_admin' ] ) && $user[ 'is_admin' ] === true;
    }
}

/**
 * Check if user is authenticated
 */
if ( !function_exists( 'is_authenticated' ) ) {
    function is_authenticated(): bool
    {
        return user_auth() !== null;
    }
}

/**
 * Get current user
 */
if ( !function_exists( 'current_user' ) ) {
    function current_user(): ?array
    {
        return user_auth();
    }
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
        return app( App\Helpers\DateHelper::class)->format( $date, $format );
    }
}

/**
 * Format month/year in Brazilian Portuguese
 */
if ( !function_exists( 'month_year_pt' ) ) {
    function month_year_pt( $date ): string
    {
        // Handle Carbon instances
        if ( $date instanceof \Carbon\Carbon ) {
            $carbonDate = $date;
        }
        // Handle string dates
        elseif ( is_string( $date ) ) {
            try {
                $carbonDate = \Carbon\Carbon::parse( $date );
            } catch ( \Exception $e ) {
                return 'Data inválida';
            }
        }
        // Handle other date formats
        else {
            try {
                $carbonDate = \Carbon\Carbon::make( $date );
            } catch ( \Exception $e ) {
                return 'Data inválida';
            }
        }

        // Brazilian Portuguese month names
        $months = [
            1  => 'Janeiro',
            2  => 'Fevereiro',
            3  => 'Março',
            4  => 'Abril',
            5  => 'Maio',
            6  => 'Junho',
            7  => 'Julho',
            8  => 'Agosto',
            9  => 'Setembro',
            10 => 'Outubro',
            11 => 'Novembro',
            12 => 'Dezembro'
        ];

        $monthName = $months[ $carbonDate->month ] ?? 'Mês inválido';
        $year      = $carbonDate->year;

        return "{$monthName}/{$year}";
    }
}

/**
 * Check current user's plan
 */
if ( !function_exists( 'checkPlan' ) ) {
    function checkPlan(): ?object
    {
        $user = user_auth();
        if ( !$user ) {
            return null;
        }

        // Get authenticated Laravel user
        $authManager = app( 'auth' );
        if ( !$authManager->check() ) {
            return null;
        }

        $laravelUser = $authManager->user();
        if ( !$laravelUser ) {
            return null;
        }

        // Find provider for current user
        $provider = \App\Models\Provider::where( 'user_id', $laravelUser->id )->first();
        if ( !$provider ) {
            return null;
        }

        // Get active plan subscription
        $activeSubscription = $provider->planSubscriptions()
            ->where( 'status', \App\Models\PlanSubscription::STATUS_ACTIVE )
            ->where( 'end_date', '>', now() )
            ->with( 'plan' )
            ->first();

        if ( !$activeSubscription || !$activeSubscription->plan ) {
            // Return free plan as default
            return (object) [
                'id'          => 0,
                'name'        => 'Plano Gratuito',
                'slug'        => 'free',
                'description' => 'Plano gratuito com funcionalidades básicas',
                'price'       => 0.00,
                'status'      => true,
                'max_budgets' => 3,
                'max_clients' => 5,
                'features'    => []
            ];
        }

        return $activeSubscription->plan;
    }
}

/**
 * Check for pending plan subscription
 */
if ( !function_exists( 'checkPlanPending' ) ) {
    function checkPlanPending(): ?object
    {
        $user = user_auth();
        if ( !$user ) {
            return null;
        }

        // Get authenticated Laravel user
        $authManager = app( 'auth' );
        if ( !$authManager->check() ) {
            return null;
        }

        $laravelUser = $authManager->user();
        if ( !$laravelUser ) {
            return null;
        }

        // Find provider for current user
        $provider = \App\Models\Provider::where( 'user_id', $laravelUser->id )->first();
        if ( !$provider ) {
            return null;
        }

        // Get pending plan subscription
        $pendingSubscription = $provider->planSubscriptions()
            ->where( 'status', \App\Models\PlanSubscription::STATUS_PENDING )
            ->with( 'plan' )
            ->first();

        if ( !$pendingSubscription || !$pendingSubscription->plan ) {
            return null;
        }

        // Add status to the response object
        $result                     = $pendingSubscription->plan;
        $result->status             = $pendingSubscription->status;
        $result->subscription_id    = $pendingSubscription->id;
        $result->transaction_amount = $pendingSubscription->transaction_amount;

        return $result;
    }
}
