@extends( 'layouts.app' )

@section( 'content' )
    <!-- Banner Principal com gradiente e animação -->
    <x-home.hero>
        <!-- Alerta de teste -->
        <x-ui.alert type="warning" :noContainer="true" class="mb-4">
            <strong>Ambiente de Testes!</strong> Os dados podem ser resetados a qualquer momento. Não utilize dados reais.
        </x-ui.alert>
    </x-home.hero>

    <x-home.features-section />

    <x-home.plans-section :plans="$plans">
        <x-home.cta-section />
    </x-home.plans-section>
@endsection

@push( 'scripts' )
    <script type="module" src="{{ asset( 'assets/js/home.js' ) }}"></script>
@endpush
