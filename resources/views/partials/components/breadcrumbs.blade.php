{{-- resources/views/partials/components/breadcrumbs.blade.php --}}
{{-- Sistema de breadcrumbs moderno com TailwindCSS --}}

@props( [ 'breadcrumbs' => [], 'class' => '' ] )

@if( isset( $breadcrumbs ) && count( $breadcrumbs ) > 0 )
    <nav {{ $attributes->merge( [ 'class' => "bg-gray-50 border-b border-gray-200 px-4 py-3 {$class}" ] ) }}
        aria-label="Breadcrumb">
        <div class="max-w-7xl mx-auto">
            <ol class="flex items-center space-x-2 text-sm">
                {{-- Home link --}}
                <li>
                    <a href="{{ url( '/' ) }}"
                        class="text-gray-500 hover:text-primary-600 transition-colors duration-200 flex items-center">
                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path
                                d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z" />
                        </svg>
                        <span class="sr-only">Início</span>
                    </a>
                </li>

                {{-- Breadcrumb items --}}
                @foreach( $breadcrumbs as $index => $breadcrumb )
                    {{-- Separator --}}
                    @if( $index > 0 )
                        <li class="flex items-center">
                            <svg class="w-4 h-4 text-gray-400 mx-2" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z"
                                    clip-rule="evenodd" />
                            </svg>
                        </li>
                    @endif

                    {{-- Breadcrumb item --}}
                    <li>
                        @if( isset( $breadcrumb[ 'url' ] ) && $breadcrumb[ 'url' ] )
                            <a href="{{ $breadcrumb[ 'url' ] }}"
                                class="text-gray-700 hover:text-primary-600 transition-colors duration-200 flex items-center">
                                @if( isset( $breadcrumb[ 'icon' ] ) && $breadcrumb[ 'icon' ] )
                                    <i class="{{ $breadcrumb[ 'icon' ] }} mr-1"></i>
                                @endif
                                {{ $breadcrumb[ 'title' ] }}
                            </a>
                        @else
                            <span class="text-gray-900 font-medium flex items-center">
                                @if( isset( $breadcrumb[ 'icon' ] ) && $breadcrumb[ 'icon' ] )
                                    <i class="{{ $breadcrumb[ 'icon' ] }} mr-1"></i>
                                @endif
                                {{ $breadcrumb[ 'title' ] }}
                            </span>
                        @endif
                    </li>
                @endforeach
            </ol>
        </div>
    </nav>
@endif

{{-- Alternative: Simple breadcrumb without array --}}
@if( !isset( $breadcrumbs ) || count( $breadcrumbs ) === 0 )
    @hasSection( 'breadcrumbs' )
        <nav class="bg-gray-50 border-b border-gray-200 px-4 py-3" aria-label="Breadcrumb">
            <div class="max-w-7xl mx-auto">
                <ol class="flex items-center space-x-2 text-sm">
                    <li>
                        <a href="{{ url( '/' ) }}" class="text-gray-500 hover:text-primary-600 transition-colors duration-200">
                            <svg class="w-4 h-4 mr-1 inline" fill="currentColor" viewBox="0 0 20 20">
                                <path
                                    d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z" />
                            </svg>
                            Início
                        </a>
                    </li>
                    @yield( 'breadcrumbs' )
                </ol>
            </div>
        </nav>
    @endif
@endif
