<?php

namespace Tests\Unit\AI;

use App\Services\AI\CustomerSegmentationService;
use App\Services\AI\LinearRegressionService;
use PHPUnit\Framework\TestCase;

class AIServicesTest extends TestCase
{
    /** @test */
    public function linear_regression_predicts_correctly()
    {
        $service = new LinearRegressionService();

        // Simple line: y = x (slope = 1, intercept = 0)
        $x = [1, 2, 3, 4, 5];
        $y = [1, 2, 3, 4, 5];

        $result = $service->calculate($x, $y);

        $this->assertEquals(1.0, $result['slope']);
        $this->assertEquals(0.0, $result['intercept']);

        $prediction = $service->predict(6, $result['slope'], $result['intercept']);
        $this->assertEquals(6.0, $prediction);
    }

    /** @test */
    public function customer_segmentation_correctly_segments_customers()
    {
        $service = new CustomerSegmentationService();

        $customers = [
            101 => [
                'id' => 101,
                'last_purchase_date' => now()->subDays(10)->toDateTimeString(), // Recency score 5
                'total_purchases' => 15, // Frequency score 5
                'total_spent' => 6000 // Monetary score 5
            ], // Should be Champion
            102 => [
                'id' => 102,
                'last_purchase_date' => now()->subDays(300)->toDateTimeString(), // Recency score 1
                'total_purchases' => 1, // Frequency score 1
                'total_spent' => 100 // Monetary score 1
            ], // Should be Lost
        ];

        $segments = $service->segment($customers);

        $this->assertEquals('Champions', $segments[101]);
        $this->assertEquals('Lost', $segments[102]);
    }
}
