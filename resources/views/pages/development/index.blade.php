@extends( 'layout' )

@section( 'content' )
    <div class="container-fluid d-flex align-items-center justify-content-center mb-2">
        <div class="text-center">
            <div class="mb-4">
                <i class="bi bi-gear-fill text-primary display-1 rotate"></i>
            </div>
            <h1 class="mb-4">Em Desenvolvimento</h1>
            <p class="lead mb-4">Esta página está sendo construída. Em breve estará disponível!</p>
            <a href="{{ url( '/' ) }}" class="btn btn-primary">
                <i class="bi bi-house-fill me-2"></i>Voltar para Home
            </a>
        </div>
    </div>
@endsection

@section( 'styles' )
    <style>
        @keyframes rotate {
            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }
        }

        .rotate {
            animation: rotate 4s linear infinite;
            display: inline-block;
            /* Adicionado para garantir que o elemento seja rotacionável */
        }

        .container-fluid {
            height: 77vh;
            /* Adicionado para garantir que o container ocupe a altura total da tela */
        }
    </style>
@endsection
