@extends( 'layouts.guest' )

@section( 'title', 'Erro interno do servidor' )

@section( 'content' )
    <div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4">
        <div class="max-w-md w-full text-center">
            <!-- Ilustração -->
            <div class="mb-8">
                <i class="bi bi-exclamation-triangle text-6xl text-yellow-600"></i>
                <h1 class="mt-4 text-4xl font-bold text-gray-900">500</h1>
            </div>

            <!-- Mensagens -->
            <h2 class="text-2xl font-semibold text-gray-900 mb-4">
                Erro interno do servidor
            </h2>

            <p class="text-gray-600 mb-4">
                Ocorreu um erro inesperado em nossos servidores.
            </p>

            <p class="text-sm text-gray-500 mb-8">
                Nossa equipe foi notificada e estamos trabalhando para resolver o problema.
            </p>

            <!-- Ações -->
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <button onclick="location.reload()" class="btn btn-primary">
                    <i class="bi bi-arrow-clockwise mr-2"></i>Tentar novamente
                </button>
                <a href="{{ route( 'dashboard' ) }}" class="btn btn-secondary">
                    <i class="bi bi-house-door mr-2"></i>Página Inicial
                </a>
            </div>

            <!-- ID do erro -->
            <div class="mt-8 pt-8 border-t border-gray-200">
                <p class="text-xs text-gray-400">
                    ID do erro: {{ uniqid() }}
                </p>
            </div>
        </div>
    </div>
@endsection

@push( 'styles' )
    <style>
        .btn {
            @apply inline-flex items-center justify-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg transition-colors duration-200;
        }

        .btn-primary {
            @apply bg-blue-600 text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2;
        }

        .btn-secondary {
            @apply bg-gray-200 text-gray-900 hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2;
        }

        .text-6xl {
            font-size: 4rem;
        }

        @media (max-width: 640px) {
            .text-6xl {
                font-size: 3rem;
            }

            .text-4xl {
                font-size: 2rem;
            }

            .text-2xl {
                font-size: 1.5rem;
            }
        }
    </style>
@endpush
