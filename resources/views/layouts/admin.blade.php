@extends( 'layouts.app' )

@section( 'title', 'Admin - ' . config( 'app.name', 'Easy Budget' ) )

@section( 'content' )
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item">
                                <a href="{{ route( 'admin.dashboard' ) }}" class="text-decoration-none">
                                    <i class="bi bi-house-door"></i> Admin
                                </a>
                            </li>
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

@push( 'scripts' )
    @yield( 'admin_scripts' )
@endpush
