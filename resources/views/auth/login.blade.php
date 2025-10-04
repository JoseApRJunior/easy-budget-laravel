{{-- resources/views/auth/login.blade.php --}}
{{-- Página de Login moderna com TailwindCSS --}}

@extends( 'layouts.app' )

@section( 'content' )
    {{-- Breadcrumbs --}}
    @section( 'breadcrumbs' )
        <li>
            <span class="text-gray-900 font-medium flex items-center">
                <i class="bi bi-box-arrow-in-right mr-1"></i>
                Login
            </span>
        </li>
    @endsection

    {{-- Login Section --}}
    <section class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            {{-- Header --}}
            <div class="text-center">
                <div class="inline-flex items-center justify-center w-20 h-20 bg-primary-600 rounded-full mb-6">
                    <svg class="w-10 h-10 text-white" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-6-3a2 2 0 11-4 0 2 2 0 014 0zm-2 4a5 5 0 00-4.546 2.916A5.986 5.986 0 0010 16a5.986 5.986 0 004.546-2.084A5 5 0 0010 11z"
                            clip-rule="evenodd" />
                    </svg>
                </div>
                <h2 class="text-3xl font-bold text-gray-900 mb-2">Entrar na sua conta</h2>
                <p class="text-gray-600">Acesse o painel do Easy Budget</p>
            </div>

            {{-- Login Form --}}
            <div class="card shadow-xl">
                <div class="card-body p-8">
                    <form method="POST" action="{{ route( 'login' ) }}" class="space-y-6">
                        @csrf

                        {{-- Email Field --}}
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                                Endereço de E-mail
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                                        <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
                                    </svg>
                                </div>
                                <input id="email" name="email" type="email" autocomplete="email" required
                                    value="{{ old( 'email' ) }}" class="form-input pl-10" placeholder="seu@email.com">
                            </div>
                            @error( 'email' )
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Password Field --}}
                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                                Senha
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </div>
                                <input id="password" name="password" type="password" autocomplete="current-password"
                                    required class="form-input pl-10 pr-12" placeholder="••••••••">
                                <button type="button"
                                    class="absolute inset-y-0 right-0 pr-3 flex items-center password-toggle"
                                    data-input="password">
                                    <svg class="h-5 w-5 text-gray-400 hover:text-gray-600" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </button>
                            </div>
                            @error( 'password' )
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Remember Me --}}
                        <div class="flex items-center justify-between">
                            <div class="flex items-center">
                                <input id="remember_me" name="remember" type="checkbox"
                                    class="rounded border-gray-300 text-primary-600 shadow-sm focus:border-primary-300 focus:ring focus:ring-primary-200">
                                <label for="remember_me" class="ml-2 block text-sm text-gray-700">
                                    Lembrar de mim
                                </label>
                            </div>

                            @if ( Route::has( 'password.request' ) )
                                <a href="{{ route( 'password.request' ) }}"
                                    class="text-sm text-primary-600 hover:text-primary-800">
                                    Esqueceu a senha?
                                </a>
                            @endif
                        </div>

                        {{-- Submit Button --}}
                        <div>
                            <button type="submit"
                                class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-lg text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-colors duration-200">
                                <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                                    <svg class="h-5 w-5 text-primary-500 group-hover:text-primary-400" fill="currentColor"
                                        viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </span>
                                Entrar
                            </button>
                        </div>

                        {{-- Register Link --}}
                        <div class="text-center">
                            <p class="text-sm text-gray-600">
                                Não tem uma conta?
                                <a href="{{ route( 'register' ) }}"
                                    class="font-medium text-primary-600 hover:text-primary-800">
                                    Cadastre-se gratuitamente
                                </a>
                            </p>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Demo Credentials (only in development) --}}
            @if( app()->environment( 'local' ) )
                <div class="card bg-yellow-50 border-yellow-200">
                    <div class="card-body p-6">
                        <h3 class="text-sm font-medium text-yellow-800 mb-2">Credenciais de Teste</h3>
                        <div class="text-sm text-yellow-700 space-y-1">
                            <p><strong>Admin:</strong> admin@easybudget.com / password</p>
                            <p><strong>Usuário:</strong> user@easybudget.com / password</p>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </section>
@endsection

{{-- Custom Scripts --}}
@section( 'scripts' )
    <script>
        // Password visibility toggle
        document.addEventListener( 'DOMContentLoaded', function () {
            const passwordToggles = document.querySelectorAll( '.password-toggle' );

            passwordToggles.forEach( toggle => {
                toggle.addEventListener( 'click', function () {
                    const inputId = this.getAttribute( 'data-input' );
                    const input = document.getElementById( inputId );

                    if ( input ) {
                        const type = input.getAttribute( 'type' ) === 'password' ? 'text' : 'password';
                        input.setAttribute( 'type', type );

                        // Toggle icon
                        const icon = this.querySelector( 'svg path' );
                        if ( type === 'text' ) {
                            icon.setAttribute( 'd', 'M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21' );
                        } else {
                            icon.setAttribute( 'd', 'M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z' );
                        }
                    }
                } );
            } );
        } );
    </script>
@endsection
