<?php

declare(strict_types=1);

namespace App\Services\Infrastructure;

use App\Models\Budget;
use Carbon\Carbon;
use Illuminate\Support\Str;

/**
 * Service for managing public tokens for budgets.
 *
 * Handles generation, validation, and regeneration of secure tokens
 * with expiration for public budget access.
 */
class BudgetTokenService
{
    private const TOKEN_EXPIRY_DAYS = 7;

    /**
     * Generate a unique public token for a budget.
     *
     * Creates a secure random token and sets an expiration date.
     *
     * @param Budget $budget The budget to generate token for
     * @return string The generated token
     */
    public function generateToken( Budget $budget ): string
    {
        $token     = $this->generateUniqueToken();
        $expiresAt = Carbon::now()->addDays( self::TOKEN_EXPIRY_DAYS );

        $budget->update( [
            'public_token'      => $token,
            'public_expires_at' => $expiresAt
        ] );

        return $token;
    }

    /**
     * Validate a public token.
     *
     * Checks if the token exists and hasn't expired.
     *
     * @param string $token The token to validate
     * @return array Validation result with 'valid', 'condition', and optionally 'budget'
     */
    public function validateToken( string $token ): array
    {
        $budget = Budget::where( 'public_token', $token )->first();

        if ( !$budget ) {
            return [ 'valid' => false, 'condition' => 'invalid' ];
        }

        if ( Carbon::now()->gt( $budget->public_expires_at ) ) {
            return [ 'valid' => false, 'condition' => 'expired', 'budget' => $budget ];
        }

        return [ 'valid' => true, 'condition' => 'valid', 'budget' => $budget ];
    }

    /**
     * Regenerate the public token for a budget.
     *
     * Generates a new token, effectively replacing the old one.
     *
     * @param Budget $budget The budget to regenerate token for
     * @return string The new generated token
     */
    public function regenerateToken( Budget $budget ): string
    {
        return $this->generateToken( $budget );
    }

    /**
     * Generate a unique token that doesn't exist in the database.
     *
     * @return string A unique 43-character random token
     */
    private function generateUniqueToken(): string
    {
        do {
            $token = Str::random( 43 );
        } while ( Budget::where( 'public_token', $token )->exists() );

        return $token;
    }

}
