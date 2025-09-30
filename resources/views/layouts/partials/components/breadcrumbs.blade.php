{{--
Componente de Breadcrumbs para navegação
Uso: @include('components.breadcrumbs', ['breadcrumbs' => $breadcrumbs])
--}}

@props( [ 'breadcrumbs' => [] ] )

@if( count( $breadcrumbs ) > 0 )
    <nav aria-label="breadcrumb" class="mb-4">
    <ol class="breadcrumb">
        @foreach( $breadcrumbs as $breadcrumb )
                @if( $loop->last )
                        <li class="breadcrumb-item active" aria-current="page">
                    {{ $breadcrumb[ 'title' ] }}
                </li> @else     <li class="breadcrumb-item">
                @if( isset( $breadcrumb[ 'url' ] ) )
                    <a href="{{ $breadcrumb[ 'url' ] }}">{{ $breadcrumb[ 'title' ] }}</a>
                @else
                    {{ $breadcrumb[ 'title' ] }}
                @endif
                </li>
            @endif
        @endforeach
    </ol>
    </nav>
@endif
