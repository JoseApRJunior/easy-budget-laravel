<?php

namespace App\Services\AI;

class LinearRegressionService
{
    /**
     * Calculate the slope and intercept for the linear regression line
     * formula: y = mx + b
     */
    public function calculate(array $x, array $y): array
    {
        $n = count($x);
        if ($n !== count($y) || $n === 0) {
            return ['slope' => 0, 'intercept' => 0];
        }

        $sumX = array_sum($x);
        $sumY = array_sum($y);

        $sumXY = 0;
        $sumXX = 0;

        for ($i = 0; $i < $n; $i++) {
            $sumXY += ($x[$i] * $y[$i]);
            $sumXX += ($x[$i] * $x[$i]);
        }

        $denominator = ($n * $sumXX) - ($sumX * $sumX);

        if ($denominator == 0) {
            return ['slope' => 0, 'intercept' => 0];
        }

        $slope = (($n * $sumXY) - ($sumX * $sumY)) / $denominator;
        $intercept = ($sumY - ($slope * $sumX)) / $n;

        return [
            'slope' => $slope,
            'intercept' => $intercept,
        ];
    }

    /**
     * Predict a value for a given x using the regression logic
     */
    public function predict(float $x, float $slope, float $intercept): float
    {
        return ($slope * $x) + $intercept;
    }
}
