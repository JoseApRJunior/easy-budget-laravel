<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield( 'title', 'Easy Budget - Sistema de Orçamentos' )</title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">

    <!-- Estilos customizados -->
    @stack( 'styles' )
</head>

<body>
    <!-- Navigation -->
    @include( 'components.navigation' )

    <!-- Main Content -->
    <main class="container-fluid py-4">
        @if ( session( 'success' ) )
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>
                {{ session( 'success' ) }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if ( session( 'error' ) )
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                {{ session( 'error' ) }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if ( $errors->any() )
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <strong>Por favor, corrija os seguintes erros:</strong>
                <ul class="mb-0 mt-2">
                    @foreach ( $errors->all() as $error )
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @yield( 'content' )
    </main>

    <!-- Bootstrap 5 JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Scripts customizados -->
    @stack( 'scripts' )

    <!-- Script para confirmação de exclusão -->
    <script>
        function confirmDelete( url, itemName = 'este item' ) {
            if ( confirm( `Tem certeza que deseja excluir ${itemName}? Esta ação não pode ser desfeita.` ) ) {
                // Criar formulário para POST request com CSRF token
                const form = document.createElement( 'form' );
                form.method = 'POST';
                form.action = url;

                const csrfInput = document.createElement( 'input' );
                csrfInput.type = 'hidden';
                csrfInput.name = '_token';
                csrfInput.value = document.querySelector( 'meta[name="csrf-token"]' ).getAttribute( 'content' );

                const methodInput = document.createElement( 'input' );
                methodInput.type = 'hidden';
                methodInput.name = '_method';
                methodInput.value = 'DELETE';

                form.appendChild( csrfInput );
                form.appendChild( methodInput );
                document.body.appendChild( form );
                form.submit();
            }
        }
    </script>

    @yield( 'page-scripts' )
</body>

</html>
