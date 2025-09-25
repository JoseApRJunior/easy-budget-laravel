<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportService
{
    public function __construct()
    {
        // Constructor
    }

    public function generateReport( Request $request ): JsonResponse
    {
        return response()->json( [ 'status' => 'success', 'message' => 'Report generated' ] );
    }

    public function getReportData( array $filters ): array
    {
        return [];
    }

}
