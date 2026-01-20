<x-app-layout title="Gargalos">
    <x-layout.page-header
        title="Gargalos"
        icon="exclamation-triangle"
        :breadcrumb-items="[
            'Análise' => url('/admin/analysis'),
            'Gargalos' => '#'
        ]">
    </x-layout.page-header>

    <div class="card">
        <div class="card-header">
            <h5>Identificação de Gargalos - {{ $period[ 'start' ] }} a {{ $period[ 'end' ] }}</h5>
        </div>
        <div class="card-body">
            @if ( isset( $error ) )
                <div class="alert alert-danger">{{ $error }}</div>
            @else
                @if ( isset( $bottlenecks ) && count( $bottlenecks ) > 0 )
                    <div class="alert alert-warning">
                        <h6>Gargalos Identificados:</h6>
                        <ul class="mb-0">
                            @foreach ( $bottlenecks as $bottleneck )
                                <li>{{ $bottleneck[ 'description' ] }}</li>
                            @endforeach
                        </ul>
                    </div>
                @else
                    <div class="alert alert-success">Nenhum gargalo crítico identificado no período.</div>
                @endif
            @endif
        </div>
    </div>
</x-app-layout>
