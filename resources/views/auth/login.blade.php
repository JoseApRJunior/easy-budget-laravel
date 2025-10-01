@extends('layouts.guest')

@section('title', 'Login - Easy Budget')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4">
    <div class="max-w-md w-full">
        <!-- Card Principal -->
        <div class="bg-white rounded-lg shadow-lg p-8">
            <!-- Header -->
            <div class="text-center mb-8">
                <img src="{{ asset('img/logo.png') }}" alt="Easy Budget" class="h-12 mx-auto mb-4">
                <h1 class="text-2xl font-bold text-gray-900">Seja bem-vindo</h1>
                <p class="text-gray-600">Faça login para continuar</p>
            </div>

            <!-- Flash Messages -->
            <x-flash-messages />

            <!-- Formulário -->
            <form method="POST" action="{{ route('login') }}" class="space-y-6">
                @csrf

                <!-- Email -->
                <div>
                    <x-form.input
                        label="Email"
                        name="email"
                        type="email"
                        value="{{ old('email') }}"
                        placeholder="seu@email.com"
                        required
                        autofocus
                        :error="$errors->first('email')"
                    />
                </div>

                <!-- Senha -->
                <div x-data="{ showPassword: false }">
                    <x-form.input
                        label="Senha"
                        name="password"
                        :type="showPassword ? 'text' : 'password'"
                        placeholder="Sua senha"
                        required
                        :error="$errors->first('password')"
                        container-class="relative"
                    >
                        <x-slot:hint>
                            <button
                                type="button"
                                @click="showPassword = !showPassword"
                                class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-gray-600"
                                tabindex="-1"
                            >
                                <i :class="showPassword ? 'bi-eye-slash' : 'bi-eye'"></i>
                            </button>
                        </x-slot>
                    </x-form.input>
                </div>

                <!-- Lembrar-me -->
                <div class="flex items-center justify-between">
                    <x-form.checkbox
                        name="remember"
                        :checked="old('remember')"
                    >
                        Lembrar-me
                    </x-form.checkbox>

                    <a href="{{ route('password.request') }}" class="text-sm text-blue-600 hover:text-blue-700">
                        Esqueceu a senha?
                    </a>
                </div>

                <!-- Botão Submit -->
                <x-ui.button
                    type="submit"
                    variant="primary"
                    size="lg"
                    class="w-full"
                >
                    <i class="bi bi-box-arrow-in-right mr-2"></i>
                    Entrar
                </x-ui.button>
            </form>

            <!-- Links Adicionais -->
            <div class="mt-6 text-center">
                <span class="text-gray-600">Não tem uma conta?</span>
                <a href="{{ route('register') }}" class="text-blue-600 hover:text-blue-700 ml-1">
                    Registre-se
                </a>
            </div>
        </div>

        <!-- Footer -->
        <div class="text-center mt-8">
            <div class="flex items-center justify-center gap-2 text-sm text-gray-500">
                <i class="bi bi-shield-lock"></i>
                <span>Login seguro via SSL</span>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
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
