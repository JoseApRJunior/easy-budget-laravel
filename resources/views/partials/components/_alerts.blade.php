<div class="container mt-4">
    @php
        $flashTypes = [
            'error'   => 'danger',
            'success' => 'success',
            'message' => 'info',
            'warning' => 'warning',
        ];
    @endphp

    @foreach ( $flashTypes as $type => $style )
        @if ( session()->has( $type ) )
            <div class="alert alert-{{ $style }} alert-dismissible fade show text-center" role="alert">
                {!! session( $type ) !!}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif
    @endforeach
</div>
