@extends( 'layouts.app' )

@section( 'content' )
    @include( 'partials.components.alerts' )

    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="{{ url( '/admin' ) }}"><i class="bi bi-house-door"></i>
                                    Admin</a></li>
                            @yield( 'breadcrumb' )
                        </ol>
                    </nav>
                    @yield( 'page_actions' )
                </div>
            </div>
        </div>

        @yield( 'admin_content' )
    </div>
@endsection
