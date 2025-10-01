<!DOCTYPE html>
<html lang="{{ str_replace( '_', '-', app()->getLocale() ) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ config( 'app.name', 'Easy Budget' ) }} - @yield( 'title', 'Relatório' )</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Styles -->
    @vite( [ 'resources/css/app.css' ] )
    @stack( 'styles' )
</head>

<body class="font-sans antialiased bg-white">
    <!-- Page Content -->
    <div class="max-w-none mx-auto">
        @yield( 'content' )
    </div>

    @stack( 'scripts' )
</body>

</html>
