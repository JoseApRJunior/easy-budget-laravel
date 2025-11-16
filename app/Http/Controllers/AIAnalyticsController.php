<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Abstracts\Controller;
use App\Services\Application\AIAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AIAnalyticsController extends Controller
{
    protected $aiAnalyticsService;

    public function __construct( AIAnalyticsService $aiAnalyticsService )
    {
        $this->aiAnalyticsService = $aiAnalyticsService;
    }

    public function index( Request $request )
    {
        $user      = Auth::user();
        $analytics = $this->aiAnalyticsService->getBusinessOverview( $user );

        return view( 'pages.provider.analytics.index', compact( 'analytics' ) );
    }

    public function trends( Request $request )
    {
        $user   = Auth::user();
        $period = $request->get( 'period', '6months' );
        $trends = $this->aiAnalyticsService->getBusinessTrends( $user, $period );

        return response()->json( $trends );
    }

    public function predictions( Request $request )
    {
        $user        = Auth::user();
        $predictions = $this->aiAnalyticsService->getPredictions( $user );

        return response()->json( $predictions );
    }

    public function suggestions( Request $request )
    {
        $user        = Auth::user();
        $suggestions = $this->aiAnalyticsService->getSuggestions( $user );

        return response()->json( $suggestions );
    }

    public function performance( Request $request )
    {
        $user        = Auth::user();
        $metrics     = $request->get( 'metrics', [ 'conversion_rate', 'average_ticket', 'customer_lifetime_value' ] );
        $performance = $this->aiAnalyticsService->getPerformanceMetrics( $user, $metrics );

        return response()->json( $performance );
    }

    public function customers( Request $request )
    {
        $user     = Auth::user();
        $insights = $this->aiAnalyticsService->getCustomerInsights( $user );

        return response()->json( $insights );
    }

    public function financial( Request $request )
    {
        $user      = Auth::user();
        $financial = $this->aiAnalyticsService->getFinancialHealth( $user );

        return response()->json( $financial );
    }

    public function efficiency( Request $request )
    {
        $user       = Auth::user();
        $efficiency = $this->aiAnalyticsService->getOperationalEfficiency( $user );

        return response()->json( $efficiency );
    }

}
