@extends( 'layouts.app' )

@section( 'content' )
    <div class="min-h-screen bg-gray-50 flex flex-col justify-center items-center py-12 sm:px-6 lg:px-8">
        <div class="sm:mx-auto sm:w-full sm:max-w-md">
            <div class="bg-white py-8 px-4 shadow-xl sm:rounded-lg sm:px-10">
                <div class="text-center">
                    <h2 class="text-2xl font-semibold text-indigo-600">Easy Budget</h2>
                </div>

                @if ( isset( $confirmAccount ) && $confirmAccount )
                    <div class="mt-6 text-center">
                        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100">
                            <i class="bi bi-check-circle-fill text-green-600 text-3xl"></i>
                        </div>
                        <h3 class="mt-5 text-lg leading-6 font-medium text-gray-900">Conta Confirmada!</h3>
                        <div class="mt-2 px-7 py-3">
                            <p class="text-sm text-gray-500">
                                Sua conta foi confirmada com sucesso. Agora você pode fazer login e começar a usar o Easy
                                Budget.
                            </p>
                        </div>
                        <div class="mt-4">
                            <a href="{{ route( 'login' ) }}"
                                class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Ir para o Login
                            </a>
                        </div>
                    </div>
                @else
                    <div class="mt-6 text-center">
                        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                            <i class="bi bi-x-circle-fill text-red-600 text-3xl"></i>
                        </div>
                        <h3 class="mt-5 text-lg leading-6 font-medium text-gray-900">Falha na Confirmação</h3>
                        <div class="mt-2 px-7 py-3">
                            <p class="text-sm text-gray-500">
                                Desculpe, não foi possível confirmar sua conta. O link pode ter expirado ou já ter sido usado.
                            </p>
                        </div>
                        <div class="mt-4 space-y-3">
                            <a href="{{ route( 'verification.resend' ) }}"
                                class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Reenviar Link de Confirmação
                            </a>
                            <a href="{{ route( 'register' ) }}"
                                class="w-full flex justify-center py-2 px-4 border border-gray-300 rounded-md shadow-sm bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
                                Registrar Novamente
                            </a>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
