<!DOCTYPE html>
<html lang="{{ str_replace( '_', '-', app()->getLocale() ) }}" class="h-full">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config( 'app.name', 'Easy Budget' ) }} - @yield( 'title', 'Dashboard' )</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet">

    <!-- Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

    <!-- Styles -->
    @vite( [ 'resources/css/app.css', 'resources/js/app.js' ] )
    @stack( 'styles' )
</head>

<body class="h-full bg-gray-50 font-sans antialiased">
    <div class="min-h-full">
        <!-- Header -->
        <x-navigation.header />

        <!-- Main Content -->
        <main class="flex-1">
            <!-- Flash Messages -->
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                <x-flash-messages />
            </div>

            <!-- Page Content -->
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
                              @yield( 'content' )
            </div>
        </main>

        <!-- Footer -->
        <x-navigation.footer />
         </div>

        @stack( 'scripts' )
        </body> </html>
