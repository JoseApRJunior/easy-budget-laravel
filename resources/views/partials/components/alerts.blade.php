<div class="max-w-7xl mx-auto sm:px-6 lg:px-8 my-4">
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
            <x-alert :type="$type" :message="session( $type )" class="mb-4" />
        @endif
    @endforeach
</div>
