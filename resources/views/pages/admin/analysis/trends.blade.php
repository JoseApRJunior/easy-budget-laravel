<x-app-layout title="Tendências">
    <x-layout.page-header
        title="Tendências"
        icon="graph-up-arrow"
        :breadcrumb-items="[
            'Análise' => url('/admin/analysis'),
            'Tendências' => '#'
        ]">
    </x-layout.page-header>

    <div class="card">
        <div class="card-header">
            <h5>Análise de Tendências</h5>
        </div>
        <div class="card-body">
            @if ( isset( $error ) )
                <div class="alert alert-danger">{{ $error }}</div>
            @else
                <canvas id="trendsChart"></canvas>
            @endif
        </div>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
            @if ( !isset( $error ) && isset( $trends ) )
                const ctx = document.getElementById('trendsChart').getContext('2d');
                new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: @json( array_column( $trends, 'date' ) ),
                        datasets: [{
                            label: 'Performance',
                            data: @json( array_column( $trends, 'value' ) ),
                            borderColor: 'rgb(75, 192, 192)',
                            tension: 0.1
                        }]
                    }
                });
            @endif
        </script>
    @endpush
</x-app-layout>
