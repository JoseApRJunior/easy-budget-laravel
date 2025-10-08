<?php

namespace App\Helpers;

class MathHelper
{
    /**
     * Calculate the percentage of a value in relation to a total.
     *
     * @param float|int $value
     * @param float|int $total
     * @return float
     */
    public static function calculatePercentage( $value, $total ): float
    {
        if ( $total <= 0 ) {
            return 0;
        }

        return round( ( $value / $total ) * 100 );
    }

}
