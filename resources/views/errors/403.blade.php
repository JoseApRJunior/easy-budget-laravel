@extends( 'layouts.guest' )

@section( 'title', 'Acesso negado' )

@section( 'content' )
    <div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4">
        <div class="max-w-md w-full text-center">
            <!-- Ilustração -->
            <div class="mb-8">
                <i class="bi bi-shield-x text-6xl text-red-600"></i>
                <h1 class="mt-4 text-4xl font-bold text-gray-900">403</h1>
            </div>

            <!-- Mensagens -->
            <h2 class="text-2xl font-semibold text-gray-900 mb-4">
                Acesso negado
            </h2>

            <p class="text-gray-600 mb-8">
                Você não tem permissão para acessar esta página ou recurso.
            </p>

            <!-- Ações -->
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ route( 'dashboard' ) }}" class="btn btn-primary">
                    <i class="bi bi-house-door mr-2"></i>Página Inicial
                </a>
                <button onclick="history.back()" class="btn btn-secondary">
                    <i class="bi bi-arrow-left mr-2"></i>Voltar
                </button>
            </div>

            <!-- Contato suporte -->
            <div class="mt-8 pt-8 border-t border-gray-200">
                <p class="text-sm text-gray-500 mb-2">
                    Precisa de acesso? Entre em contato:
                </p>
                <a href="mailto:suporte@easybudget.com" class="text-sm text-blue-600 hover:text-blue-700">
                    <i class="bi bi-envelope mr-1"></i>suporte@easybudget.com
                </a>
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
