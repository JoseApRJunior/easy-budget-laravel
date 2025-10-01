@extends( 'layouts.guest' )

@section( 'title', 'Recuperar senha - Easy Budget' )

@section( 'content' )
    <div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4">
    <div class="max-w-md w-full">
        <!-- Card Principal -->
        <div class="bg-white rounded-lg shadow-lg p-8">
            <!-- Header -->
            <div class="text-center mb-8">
                <img src="{{ asset( 'img/logo.png' ) }}" alt="Easy Budget" class="h-12 mx-auto mb-4">
                <h1 class="text-2xl font-bold text-gray-900">Recuperar senha</h1>
                <p class="text-gray-600">Digite seu email para receber instruções</p>
            </div>

            <!-- Flash Messages -->
            <x-flash-messages />

            <!-- Formulário -->
            <form method="POST" action="{{ route( 'password.email' ) }}" class="space-y-6">
                @csrf

                <!-- Email -->
                <div>
                    <x-form.input label="Email" name="email" type="email" value="{{ old( 'email' ) }}"
                        placeholder="seu@email.com" required autofocus :error="$errors->first('email')" />
                </div>

                <!-- Botão Submit -->
                <x-ui.button type="submit" variant="primary" size="lg" class="w-full">
                    <i class="bi bi-envelope-arrow-up mr-2"></i>
                    Enviar instruções
                </x-ui.button>
            </form>

            <!-- Links Adicionais -->
            <div class="mt-6 text-center">
                <a href="{{ route( 'login' ) }}" class="text-blue-600 hover:text-blue-700">
                    <i class="bi bi-arrow-left mr-1"></i>
                    Voltar ao login
                </a>
            </div>
        </div>

        <!-- Ajuda -->
        <div class="text-center mt-8">
            <p class="text-sm text-gray-500">
                Não consegue acessar?
                <a href="mailto:suporte@easybudget.com" class="text-blue-600 hover:text-blue-700">
                    Entre em contato conosco
                </a>
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

        .text-2xl {
            font-size: 1.5rem;
            line-height: 2rem;
        }

        @media (max-width: 640px) {
            .text-2xl {
                font-size: 1.25rem;
            }
        }
    </style>
@endpush
