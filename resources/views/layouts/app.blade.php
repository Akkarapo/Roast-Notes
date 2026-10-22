<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="icon" href="/favicon-32.png" type="image/png" sizes="32x32">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=anuphan:400,500,600,700|sriracha:400&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-cream text-cobalt">
        <div class="min-h-screen flex flex-col">
            @include('layouts.navigation')

            <x-flash />

            <!-- Page Heading -->
            @isset($header)
                <header class="max-w-7xl w-full mx-auto pt-10 px-4 sm:px-6 lg:px-8">
                    {{ $header }}
                </header>
            @endisset

            <!-- Page Content -->
            <main class="flex-1">
                {{ $slot }}
            </main>

            <footer class="max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8">
                <div class="mt-16 border-t-2 border-cobalt py-6 flex flex-col sm:flex-row gap-2 justify-between text-sm">
                    <span>Roast &amp; Notes · Specialty Coffee Beans</span>
                    <span class="font-script text-lg">come for the beans, stay for the notes</span>
                </div>
            </footer>
        </div>
    </body>
</html>
