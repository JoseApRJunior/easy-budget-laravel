{{-- partials/shared/head.blade.php --}}

<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="Content-Security-Policy"
        content="script-src 'self' 'unsafe-inline' https://code.jquery.com https://cdn.jsdelivr.net https://static.cloudflareinsights.com; object-src 'none';">
    <meta name="description" content="Easy Budget - Simplificando a gestão financeira para prestadores de serviços" />

    <title>Easy Budget - @yield('title', 'Página Inicial')</title>

    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="{{ asset('assets/img/favicon.ico') }}" />

    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css"
        integrity="sha384-tViUnnbYAV00FLIhhi3v/dWt3Jxw4gZQcNoSCxCIFNJVCx7/D55/wXsrNIRANwdD" crossorigin="anonymous">
    <link rel="stylesheet" href="{{ asset('assets/css/layout.css') }}?v=1.0.0" />
    <link rel="stylesheet" href="{{ asset('assets/css/components/alerts.css') }}?v=1.0.0">
    <link rel="preload" href="{{ asset('assets/img/logo.png') }}" as="image" type="image/png">

    @stack('styles')

</head>
