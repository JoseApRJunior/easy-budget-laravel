<?php

use App\Models\PlanSubscription;

/**
 * Helper functions for Easy Budget Laravel
 */

/**
 * Check if user is authenticated
 */
if ( !function_exists( 'user_auth' ) ) {
    function user_auth(): ?array
    {
        // Use only Laravel's auth system for consistency
        $authManager = app( 'auth' );
        if ( $authManager->check() ) {
            $user = $authManager->user();
            return [
                'id'       => $user->id,
                'name'     => $user->name ?? ( $user->first_name . ' ' . $user->last_name ),
                'email'    => $user->email,
                'role'     => $user->role ?? 'provider', // Default to provider for existing users
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
            ->where( 'status', PlanSubscription::STATUS_ACTIVE )
            ->where( 'end_date', '>', now() )
            ->with( 'plan' )
            ->first();

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

/**
 * Check if current user has trial plan
 */
if ( !function_exists( 'isTrial' ) ) {
    function isTrial(): bool
    {
        $user = user_auth();
        if ( !$user ) {
            return false;
        }

        // Get authenticated Laravel user
        $authManager = app( 'auth' );
        if ( !$authManager->check() ) {
            return false;
        }

        $laravelUser = $authManager->user();
        if ( !$laravelUser ) {
            return false;
        }

        // Find provider for current user
        $provider = \App\Models\Provider::where( 'user_id', $laravelUser->id )->first();
        if ( !$provider ) {
            return true; // No provider = trial by default
        }

        // Get active plan subscription
        $activeSubscription = $provider->planSubscriptions()
            ->where( 'status', PlanSubscription::STATUS_ACTIVE )
            ->where( 'end_date', '>', now() )
            ->first();

        if ( !$activeSubscription ) {
            return true; // No active subscription = trial
        }

        // Check if it's trial (payment_method = trial and amount = 0)
        return strtolower( $activeSubscription->payment_method ) === 'trial' &&
            $activeSubscription->transaction_amount <= 0;
    }
}

/**
 * Check if trial plan is expired
 */
if ( !function_exists( 'isTrialExpired' ) ) {
    function isTrialExpired(): bool
    {
        $user = user_auth();
        if ( !$user ) {
            return false;
        }

        // Get authenticated Laravel user
        $authManager = app( 'auth' );
        if ( !$authManager->check() ) {
            return false;
        }

        $laravelUser = $authManager->user();
        if ( !$laravelUser ) {
            return false;
        }

        // Find provider for current user
        $provider = \App\Models\Provider::where( 'user_id', $laravelUser->id )->first();
        if ( !$provider ) {
            return false; // No provider = trial not expired
        }

        // Get active plan subscription
        $activeSubscription = $provider->planSubscriptions()
            ->where( 'status', PlanSubscription::STATUS_ACTIVE )
            ->where( 'end_date', '>', now() )
            ->first();

        if ( !$activeSubscription ) {
            return false; // No active subscription = trial not expired
        }

        // Check if trial is expired (end_date passed)
        return $activeSubscription->end_date < now();
    }
}
