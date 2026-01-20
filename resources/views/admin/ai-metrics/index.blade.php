<x-app-layout title="AI Metrics & Analytics">
    <x-layout.page-container>
        <x-layout.page-header
            title="AI Metrics & Analytics"
            subtitle="Artificial Intelligence insights and predictions"
            icon="robot"
            :breadcrumb-items="[
                'Admin' => route('admin.dashboard'),
                'AI Metrics' => '#'
            ]">
            <x-slot:actions>
                <div class="d-flex gap-2">
                    <!-- Model Retraining -->
                    <button class="btn btn-primary" id="retrainModels" title="Retrain AI Models">
                        <i class="bi bi-cpu"></i> Retrain Models
                    </button>
                    <!-- Export Options -->
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="exportDropdown" data-bs-toggle="dropdown">
                            <i class="bi bi-download"></i> Export
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="{{ route('admin.ai.export', ['type' => 'metrics', 'format' => 'json']) }}">
                                <i class="bi bi-file-earmark-code me-2"></i>Export Metrics (JSON)
                            </a></li>
                            <li><a class="dropdown-item" href="{{ route('admin.ai.export', ['type' => 'predictions', 'format' => 'csv']) }}">
                                <i class="bi bi-file-earmark-text me-2"></i>Export Predictions (CSV)
                            </a></li>
                        </ul>
                    </div>
                </div>
            </x-slot:actions>
        </x-layout.page-header>

    <!-- AI Model Performance Cards -->
    <div class="row mb-4">
        <div class="col-lg-2 col-md-4 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Churn Prediction
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $metrics['churn_prediction_accuracy'] }}%
                            </div>
                            <div class="text-xs text-muted">Accuracy</div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-graph-down fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-2 col-md-4 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Revenue Forecast
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $metrics['revenue_forecast_accuracy'] }}%
                            </div>
                            <div class="text-xs text-muted">Accuracy</div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-graph-up fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-2 col-md-4 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Anomaly Detection
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $metrics['anomaly_detection_rate'] }}%
                            </div>
                            <div class="text-xs text-muted">Detection Rate</div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-exclamation-triangle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-2 col-md-4 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Fraud Detection
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $metrics['fraud_detection_accuracy'] }}%
                            </div>
                            <div class="text-xs text-muted">Accuracy</div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-shield-check fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-2 col-md-4 mb-4">
            <div class="card border-left-secondary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-secondary text-uppercase mb-1">
                                Customer Segmentation
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $metrics['customer_segmentation_score'] }}%
                            </div>
                            <div class="text-xs text-muted">Score</div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-people fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-2 col-md-4 mb-4">
            <div class="card border-left-dark shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-dark text-uppercase mb-1">
                                Market Analysis
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                {{ $metrics['market_trend_analysis'] }}%
                            </div>
                            <div class="text-xs text-muted">Confidence</div>
                        </div>
                        <div class="col-auto">
                            <i class="bi bi-globe fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="row mb-4">
        <div class="col-12">
            <ul class="nav nav-pills" id="aiMetricsTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="predictions-tab" data-bs-toggle="pill" data-bs-target="#predictions" type="button" role="tab">
                        <i class="bi bi-crystal-ball"></i> Predictions
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="anomalies-tab" data-bs-toggle="pill" data-bs-target="#anomalies" type="button" role="tab">
                        <i class="bi bi-bug"></i> Anomalies
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="insights-tab" data-bs-toggle="pill" data-bs-target="#insights" type="button" role="tab">
                        <i class="bi bi-lightbulb"></i> Insights
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="analytics-tab" data-bs-toggle="pill" data-bs-target="#analytics" type="button" role="tab">
                        <i class="bi bi-graph-up-arrow"></i> Analytics
                    </button>
                </li>
            </ul>
        </div>
    </div>

    <!-- Tab Content -->
    <div class="tab-content" id="aiMetricsTabContent">
        <!-- Predictions Tab -->
        <div class="tab-pane fade show active" id="predictions" role="tabpanel">
            <div class="row">
                <!-- Revenue Forecast -->
                <div class="col-lg-6 mb-4">
                    <div class="card shadow">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">
                                <i class="bi bi-currency-dollar"></i> Revenue Forecast
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-6">
                                    <div class="h4 font-weight-bold text-primary">
                                        R$ {{ number_format($predictions['revenue_forecast']['current_month'], 2, ',', '.') }}
                                    </div>
                                    <div class="text-xs text-uppercase text-muted">Current Month</div>
                                </div>
                                <div class="col-6">
                                    <div class="h4 font-weight-bold text-success">
                                        R$ {{ number_format($predictions['revenue_forecast']['next_month'], 2, ',', '.') }}
                                    </div>
                                    <div class="text-xs text-uppercase text-muted">Next Month</div>
                                </div>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between">
                                <span class="text-xs text-muted">Confidence: {{ $predictions['revenue_forecast']['confidence'] }}%</span>
                                <span class="badge bg-success">{{ $predictions['revenue_forecast']['trend'] }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Churn Prediction -->
                <div class="col-lg-6 mb-4">
                    <div class="card shadow">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">
                                <i class="bi bi-person-dash"></i> Churn Prediction
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-6">
                                    <div class="h4 font-weight-bold text-warning">
                                        {{ $predictions['churn_prediction']['churn_rate_forecast'] }}%
                                    </div>
                                    <div class="text-xs text-uppercase text-muted">Predicted Churn Rate</div>
                                </div>
                                <div class="col-6">
                                    <div class="h4 font-weight-bold text-danger">
                                        {{ count($predictions['churn_prediction']['high_risk_customers']) }}
                                    </div>
                                    <div class="text-xs text-uppercase text-muted">High Risk Customers</div>
                                </div>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between">
                                <span class="text-xs text-muted">Confidence: {{ $predictions['churn_prediction']['confidence'] }}%</span>
                                <button class="btn btn-sm btn-outline-primary" onclick="viewHighRiskCustomers()">View Details</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Growth Prediction -->
                <div class="col-lg-6 mb-4">
                    <div class="card shadow">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">
                                <i class="bi bi-trending-up"></i> Growth Prediction
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-4">
                                    <div class="h5 font-weight-bold text-info">
                                        {{ number_format($predictions['growth_prediction']['user_growth']['current_trend'], 1) }}%
                                    </div>
                                    <div class="text-xs text-uppercase text-muted">User Growth</div>
                                </div>
                                <div class="col-4">
                                    <div class="h5 font-weight-bold text-success">
                                        {{ number_format($predictions['growth_prediction']['provider_growth']['current_trend'], 1) }}%
                                    </div>
                                    <div class="text-xs text-uppercase text-muted">Provider Growth</div>
                                </div>
                                <div class="col-4">
                                    <div class="h5 font-weight-bold text-primary">
                                        {{ number_format($predictions['growth_prediction']['revenue_growth']['current_trend'], 1) }}%
                                    </div>
                                    <div class="text-xs text-uppercase text-muted">Revenue Growth</div>
                                </div>
                            </div>
                            <hr>
                            <div class="text-center">
                                <span class="text-xs text-muted">Overall Confidence: {{ $predictions['growth_prediction']['confidence'] }}%</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Subscription Forecast -->
                <div class="col-lg-6 mb-4">
                    <div class="card shadow">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">
                                <i class="bi bi-subscript"></i> Subscription Forecast
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row text-center">
                                <div class="col-4">
                                    <div class="h5 font-weight-bold text-success">
                                        {{ $predictions['subscription_forecast']['new_subscriptions']['predicted_count'] }}
                                    </div>
                                    <div class="text-xs text-uppercase text-muted">New Subs</div>
                                </div>
                                <div class="col-4">
                                    <div class="h5 font-weight-bold text-info">
                                        {{ $predictions['subscription_forecast']['renewals']['predicted_renewals'] }}
                                    </div>
                                    <div class="text-xs text-uppercase text-muted">Renewals</div>
                                </div>
                                <div class="col-4">
                                    <div class="h5 font-weight-bold text-warning">
                                        {{ $predictions['subscription_forecast']['upgrades']['predicted_upgrades'] }}
                                    </div>
                                    <div class="text-xs text-uppercase text-muted">Upgrades</div>
                                </div>
                            </div>
                            <hr>
                            <div class="text-center">
                                <span class="text-xs text-muted">Confidence: {{ $predictions['subscription_forecast']['confidence'] }}%</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Market Trends -->
                <div class="col-12 mb-4">
                    <div class="card shadow">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">
                                <i class="bi bi-globe-americas"></i> Market Trends & Analysis
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-lg-4">
                                    <h6 class="text-primary">Seasonal Patterns</h6>
                                    <div class="mb-2">
                                        <strong>Peak Months:</strong> {{ implode(', ', $predictions['market_trends']['seasonal_patterns']['peak_months']) }}
                                    </div>
                                    <div class="mb-2">
                                        <strong>Low Months:</strong> {{ implode(', ', $predictions['market_trends']['seasonal_patterns']['low_months']) }}
                                    </div>
                                    <div class="mb-2">
                                        <strong>Current Factor:</strong> {{ $predictions['market_trends']['seasonal_patterns']['current_season_factor'] }}
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <h6 class="text-success">Emerging Markets</h6>
                                    @foreach($predictions['market_trends']['emerging_markets'] as $market)
                                    <div class="mb-2">
                                        <strong>{{ $market['region'] }}:</strong> {{ $market['growth_rate'] }}% growth
                                        <span class="badge bg-{{ $market['potential'] == 'very_high' ? 'danger' : ($market['potential'] == 'high' ? 'warning' : 'info') }}">
                                            {{ ucfirst($market['potential']) }}
                                        </span>
                                    </div>
                                    @endforeach
                                </div>
                                <div class="col-lg-4">
                                    <h6 class="text-warning">Competitive Analysis</h6>
                                    <div class="mb-2">
                                        <strong>Threat Level:</strong> 
                                        <span class="badge bg-{{ $predictions['market_trends']['competitive_threats']['threat_level'] == 'high' ? 'danger' : ($predictions['market_trends']['competitive_threats']['threat_level'] == 'medium' ? 'warning' : 'success') }}">
                                            {{ ucfirst($predictions['market_trends']['competitive_threats']['threat_level']) }}
                                        </span>
                                    </div>
                                    @foreach($predictions['market_trends']['competitive_threats']['key_competitors'] as $competitor)
                                    <div class="mb-1">
                                        <small>{{ $competitor['name'] }}: {{ $competitor['market_share'] }}% market share</small>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Anomalies Tab -->
        <div class="tab-pane fade" id="anomalies" role="tabpanel">
            <div class="row">
                @foreach($anomalies as $type => $anomalyList)
                <div class="col-lg-6 mb-4">
                    <div class="card shadow">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">
                                <i class="bi bi-exclamation-triangle"></i> {{ ucfirst(str_replace('_', ' ', $type)) }}
                            </h6>
                        </div>
                        <div class="card-body">
                            @if(count($anomalyList) > 0)
                                @foreach($anomalyList as $anomaly)
                                <div class="alert alert-{{ $anomaly['severity'] == 'high' ? 'danger' : ($anomaly['severity'] == 'medium' ? 'warning' : 'info') }} alert-sm">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <strong>{{ ucfirst(str_replace('_', ' ', $anomaly['type'])) }}</strong>
                                            <p class="mb-1">{{ $anomaly['description'] }}</p>
                                            @if(isset($anomaly['affected_users']))
                                            <small class="text-muted">Affected: {{ $anomaly['affected_users'] }} users</small>
                                            @endif
                                            @if(isset($anomaly['amount']))
                                            <small class="text-muted">Amount: R$ {{ number_format($anomaly['amount'], 2, ',', '.') }}</small>
                                            @endif
                                        </div>
                                        <span class="badge bg-secondary">{{ $anomaly['confidence'] }}%</span>
                                    </div>
                                </div>
                                @endforeach
                            @else
                                <div class="text-center text-muted py-3">
                                    <i class="bi bi-check-circle text-success fa-2x"></i>
                                    <p>No anomalies detected</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <!-- Insights Tab -->
        <div class="tab-pane fade" id="insights" role="tabpanel">
            <div class="row">
                @foreach($insights as $category => $categoryInsights)
                <div class="col-lg-6 mb-4">
                    <div class="card shadow">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">
                                <i class="bi bi-lightbulb"></i> {{ ucfirst(str_replace('_', ' ', $category)) }}
                            </h6>
                        </div>
                        <div class="card-body">
                            @foreach($categoryInsights as $type => $data)
                                @if(is_array($data))
                                    <h6 class="text-secondary">{{ ucfirst(str_replace('_', ' ', $type)) }}</h6>
                                    @foreach($data as $key => $value)
                                    <div class="mb-2">
                                        <strong>{{ ucfirst(str_replace('_', ' ', $key)) }}:</strong> 
                                        @if(is_numeric($value) && $value > 0 && $value < 1)
                                            {{ number_format($value * 100, 1) }}%
                                        @else
                                            {{ is_array($value) ? implode(', ', $value) : $value }}
                                        @endif
                                    </div>
                                    @endforeach
                                @else
                                    <div class="mb-2">
                                        <strong>{{ ucfirst(str_replace('_', ' ', $type)) }}:</strong> {{ $data }}
                                    </div>
                                @endif
                                <hr class="my-3">
                            @endforeach
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

        <!-- Analytics Tab -->
        <div class="tab-pane fade" id="analytics" role="tabpanel">
            <div class="row">
                <div class="col-12">
                    <div class="card shadow">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">
                                <i class="bi bi-graph-up-arrow"></i> Advanced Analytics
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-lg-4">
                                    <div class="list-group">
                                        <a href="{{ route('admin.ai.analytics') }}" class="list-group-item list-group-item-action">
                                            <i class="bi bi-speedometer2"></i> Model Performance
                                        </a>
                                        <a href="{{ route('admin.ai.predictions') }}" class="list-group-item list-group-item-action">
                                            <i class="bi bi-crystal-ball"></i> Detailed Predictions
                                        </a>
                                        <a href="{{ route('admin.ai.anomalies') }}" class="list-group-item list-group-item-action">
                                            <i class="bi bi-bug"></i> Anomaly Analysis
                                        </a>
                                        <a href="{{ route('admin.ai.insights') }}" class="list-group-item list-group-item-action">
                                            <i class="bi bi-lightbulb"></i> Strategic Insights
                                        </a>
                                    </div>
                                </div>
                                <div class="col-lg-8">
                                    <div class="text-center py-5">
                                        <i class="bi bi-robot text-primary fa-3x mb-3"></i>
                                        <h5>AI-Powered Analytics</h5>
                                        <p class="text-muted">Access detailed analytics, model performance metrics, and advanced insights through our specialized analytics pages.</p>
                                        <div class="row mt-4">
                                            <div class="col-md-6">
                                                <div class="card border-0 bg-light">
                                                    <div class="card-body text-center">
                                                        <i class="bi bi-graph-up text-success fa-2x mb-2"></i>
                                                        <h6>Predictive Analytics</h6>
                                                        <small class="text-muted">Forecast trends and behaviors</small>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="card border-0 bg-light">
                                                    <div class="card-body text-center">
                                                        <i class="bi bi-search text-warning fa-2x mb-2"></i>
                                                        <h6>Anomaly Detection</h6>
                                                        <small class="text-muted">Identify unusual patterns</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </x-layout.page-container>
</x-app-layout>

@push('scripts')
<script>
    // Model retraining functionality
    document.getElementById('retrainModels').addEventListener('click', function() {
        if (confirm('Are you sure you want to retrain all AI models? This may take several minutes.')) {
            this.innerHTML = '<i class="bi bi-hourglass-split"></i> Retraining...';
            this.disabled = true;
            
            fetch('{{ route("admin.ai.retrain") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    models: ['churn', 'revenue', 'growth', 'anomaly']
                })
            })
            .then(response => response.json())
            .then(data => {
                alert('Models retrained successfully! ' + data.message);
                location.reload();
            })
            .catch(error => {
                alert('Error retraining models: ' + error.message);
                this.innerHTML = '<i class="bi bi-cpu"></i> Retrain Models';
                this.disabled = false;
            });
        }
    });

    // View high-risk customers function
    function viewHighRiskCustomers() {
        const highRiskCustomers = @json($predictions['churn_prediction']['high_risk_customers']);
        
        let modalContent = '<div class="table-responsive"><table class="table table-sm"><thead><tr><th>Customer</th><th>Risk Score</th><th>Predicted Churn</th><th>Factors</th></tr></thead><tbody>';
        
        highRiskCustomers.forEach(customer => {
            modalContent += `<tr>
                <td>${customer.name}</td>
                <td><span class="badge bg-${customer.risk_score > 0.8 ? 'danger' : 'warning'}">${(customer.risk_score * 100).toFixed(1)}%</span></td>
                <td>${customer.predicted_churn_date}</td>
                <td><small>${customer.risk_factors.join(', ')}</small></td>
            </tr>`;
        });
        
        modalContent += '</tbody></table></div>';
        
        // Create modal
        const modal = `
            <div class="modal fade" id="highRiskModal" tabindex="-1">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">High Risk Customers</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            ${modalContent}
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Add modal to body and show it
        document.body.insertAdjacentHTML('beforeend', modal);
        const modalElement = new bootstrap.Modal(document.getElementById('highRiskModal'));
        modalElement.show();
        
        // Remove modal after it's hidden
        document.getElementById('highRiskModal').addEventListener('hidden.bs.modal', function() {
            this.remove();
        });
    }

    // Auto-refresh functionality (optional)
    setInterval(function() {
        // You could add auto-refresh logic here if needed
        console.log('AI Metrics Dashboard - Auto refresh check');
    }, 300000); // Check every 5 minutes
</script>
@endsection