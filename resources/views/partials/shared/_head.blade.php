<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="Content-Security-Policy"
        content="script-src 'self' 'unsafe-inline' https://code.jquery.com https://cdn.jsdelivr.net; object-src 'none';">
    <meta name="description" content="Easy Budget - Simplificando a gestão financeira para prestadores de serviços" />
    <title>Easy Budget - @yield( 'title', 'Página Inicial' )</title>

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset( 'assets/img/favicon.ico' ) }}" />

    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" />
    {{--
    TODO testar css novo quando der tempo
    --}}
    <link rel="stylesheet" href="{{ asset( 'assets/css/layout.css' ) }}" />
    <link rel="stylesheet" href="{{ asset( 'assets/css/alert/alert.css' ) }}">
    <link rel="preload" href="{{ asset( 'assets/img/logo.png' ) }}" as="image" type="image/png">
    @stack( 'styles' )
</head>
