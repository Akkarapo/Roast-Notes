<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=anuphan:400,500,600,700|sriracha:400&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-cobalt antialiased bg-cream">
        <div class="min-h-screen grid lg:grid-cols-2">
            <!-- Brand panel -->
            <aside class="hidden lg:flex flex-col justify-between bg-cobalt text-paper p-12">
                <a href="{{ route('home') }}" class="flex items-center gap-3 text-2xl font-bold">
                    <x-application-logo class="h-9 w-9" />
                    <span>Roast <span class="font-script font-normal">&amp;</span> Notes</span>
                </a>
                <div>
                    <p class="font-script text-6xl leading-tight">sip your notes!</p>
                    <p class="mt-4 max-w-sm text-paper/80">{{ __('Tell us the flavor notes you love and every bean shows how well it matches you.') }}</p>
                </div>
                <x-illustrations.pour-over class="w-full max-w-md self-end" />
            </aside>

            <!-- Form -->
            <main class="flex flex-col items-center justify-center px-4 py-10">
                <a href="{{ route('home') }}" class="lg:hidden flex items-center gap-2 text-xl font-bold mb-6">
                    <x-application-logo class="h-8 w-8" />
                    <span>Roast <span class="font-script font-normal">&amp;</span> Notes</span>
                </a>
                <div class="w-full sm:max-w-md px-6 py-6 bg-paper border-2 border-cobalt rounded-card">
                    {{ $slot }}
                </div>
            </main>
        </div>
    </body>
</html>
