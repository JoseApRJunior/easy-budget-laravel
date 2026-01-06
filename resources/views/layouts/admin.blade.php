@extends( 'layouts.app' )

@section( 'content' )
    @include( 'partials.components.alerts' )

    <div class="container-fluid py-4">
        @yield( 'admin_content' )
    </div>
@endsection
