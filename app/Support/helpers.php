<?php

use App\Models\PlanSubscription;

/**
 * Helper functions for Easy Budget Laravel
 */

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
        return \Carbon\Carbon::parse( $date )
            ->locale( 'pt_BR' )
            ->translatedFormat( 'F/Y' );
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
