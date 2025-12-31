<?php

namespace App\Services\AI;

use Carbon\Carbon;

class CustomerSegmentationService
{
    /**
     * Segment customers based on RFM scores
     *
     * @param array $customers Array of customer data with 'last_purchase_date', 'total_purchases', 'total_spent'
     */
    public function segment(array $customers): array
    {
        if (empty($customers)) {
            return [];
        }

        $rfmScores = $this->calculateRFMScores($customers);
        $segments = [];

        foreach ($rfmScores as $customerId => $scores) {
            $segments[$customerId] = $this->determineSegment($scores['r_score'], $scores['f_score'], $scores['m_score']);
        }

        return $segments;
    }

    private function calculateRFMScores(array $customers): array
    {
        // Get max values for normalization
        $maxRecency = 0; // Days since last purchase (lower is better, so we'll invert later or handle logic)
        $maxFrequency = 0;
        $maxMonetary = 0;

        $processedCustomers = [];
        $now = Carbon::now();

        foreach ($customers as $customer) {
            $recency = $customer['last_purchase_date']
                ? Carbon::parse($customer['last_purchase_date'])->diffInDays($now)
                : 365; // Default to 1 year if no purchase

            $frequency = $customer['total_purchases'] ?? 0;
            $monetary = $customer['total_spent'] ?? 0;

            if ($recency > $maxRecency) $maxRecency = $recency;
            if ($frequency > $maxFrequency) $maxFrequency = $frequency;
            if ($monetary > $maxMonetary) $maxMonetary = $monetary;

            $processedCustomers[$customer['id']] = [
                'recency' => $recency,
                'frequency' => $frequency,
                'monetary' => $monetary
            ];
        }

        $rfmScores = [];

        foreach ($processedCustomers as $id => $data) {
            // Simple Quintile Scoring (1-5)
            // Recency: Lower is better (5 = recent, 1 = old)
            $r_score = $data['recency'] <= 30 ? 5 : ($data['recency'] <= 60 ? 4 : ($data['recency'] <= 90 ? 3 : ($data['recency'] <= 180 ? 2 : 1)));

            // Frequency: Higher is better
            $f_score = $data['frequency'] >= 10 ? 5 : ($data['frequency'] >= 6 ? 4 : ($data['frequency'] >= 4 ? 3 : ($data['frequency'] >= 2 ? 2 : 1)));

            // Monetary: Higher is better (Thresholds can be dynamic, using fixed for simplicity now)
            $m_score = $data['monetary'] >= 5000 ? 5 : ($data['monetary'] >= 2000 ? 4 : ($data['monetary'] >= 1000 ? 3 : ($data['monetary'] >= 500 ? 2 : 1)));

            $rfmScores[$id] = [
                'r_score' => $r_score,
                'f_score' => $f_score,
                'm_score' => $m_score
            ];
        }

        return $rfmScores;
    }

    private function determineSegment(int $r, int $f, int $m): string
    {
        $avg = ($r + $f + $m) / 3;

        if ($r >= 4 && $f >= 4 && $m >= 4) {
            return 'Champions'; // Bought recently, buy often, and spend the most
        }

        if ($f >= 3 && $m >= 3) {
            return 'Loyal'; // Spend good money and often
        }

        if ($r >= 4 && $f <= 2) {
            return 'New'; // Bought most recently, but not often
        }

        if ($r <= 2 && $f >= 3 && $m >= 3) {
            return 'At Risk'; // Spent big money and purchased often but long time ago
        }

        if ($r <= 2 && $f <= 2 && $m <= 2) {
            return 'Lost'; // Lowest recency, frequency and monetary scores
        }

        return 'Potential Loyalist'; // Recent customers, but spent a good amount
    }
}
