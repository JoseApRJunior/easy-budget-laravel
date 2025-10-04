@extends( 'layout' )

@section( 'content' )
    <div class="container-fluid py-1">
        @include( 'partials.settings.header' )

        <div class="row g-4">
            <!-- Menu Lateral -->
            <div class="col-12 col-md-3">
                @include( 'partials.settings.sidebar' )
            </div>

            <!-- Conteúdo -->
            <div class="col-12 col-md-9">
                <div class="tab-content">
                    @include( 'partials.settings.tabs.profile' )
                    @include( 'partials.settings.tabs.general' )
                    @include( 'partials.settings.tabs.notifications' )
                    @include( 'partials.settings.tabs.security' )
                    @include( 'partials.settings.tabs.integration' )
                </div>
            </div>
        </div>
    </div>
@endsection

@section( 'scripts' )
    @parent
    <script src="{{ asset( 'assets/js/settings.js' ) }}"></script>
@endsection
