<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $clinic->name }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <div class="min-h-screen flex flex-col sm:justify-center items-center px-4 pt-10 sm:pt-0 bg-gradient-to-br from-slate-900 via-slate-800 to-primary-900">
            <a href="/" class="flex flex-col items-center gap-3">
                <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-primary-500 text-white shadow-lg">
                    <x-icon name="tooth" class="h-8 w-8" />
                </span>
                <span class="text-xl font-semibold text-white">{{ $clinic->name }}</span>
            </a>

            <div class="w-full sm:max-w-md mt-6 px-6 py-6 bg-white shadow-xl overflow-hidden rounded-xl">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
