<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ isset($title) ? $title.' · ' : '' }}{{ $clinic->name }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased text-gray-800">
        <div x-data="{ sidebarOpen: false }" class="min-h-screen bg-gray-50">
            @include('layouts.navigation')

            <div class="lg:pl-64">
                <!-- Üst bar -->
                <div class="sticky top-0 z-20 flex h-16 items-center gap-3 border-b border-gray-200 bg-white/90 px-4 backdrop-blur sm:px-6 lg:px-8">
                    <button type="button" class="btn-ghost -ml-2 p-2 lg:hidden" @click="sidebarOpen = true" aria-label="Menüyü aç">
                        <x-icon name="menu" class="h-6 w-6" />
                    </button>

                    <div class="min-w-0 flex-1">
                        @isset($header)
                            {{ $header }}
                        @endisset
                    </div>

                    {{-- Hızlı hasta araması (her sayfadan) --}}
                    <div x-data="quickSearch(@js(route('patients.search')), @js(url('patients')))" class="relative hidden w-64 shrink-0 md:block"
                         @click.outside="open = false" @keydown.escape="open = false">
                        <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                        <input type="search" x-model="query" @input.debounce.300ms="search()" @focus="open = results.length > 0"
                               @keydown.arrow-down.prevent="move(1)" @keydown.arrow-up.prevent="move(-1)" @keydown.enter.prevent="go()"
                               placeholder="Hasta ara…" class="form-input py-1.5 pl-9" autocomplete="off" aria-label="Hasta ara">
                        <div x-show="open" x-cloak class="absolute right-0 z-40 mt-1 max-h-80 w-80 overflow-y-auto rounded-lg border border-gray-200 bg-white shadow-lg">
                            <template x-for="(p, i) in results" :key="p.id">
                                <a :href="url + '/' + p.id" :class="i === active ? 'bg-primary-50' : ''" class="block px-3 py-2 hover:bg-primary-50">
                                    <span class="block text-sm font-medium text-gray-900" x-text="p.name"></span>
                                    <span class="block text-xs text-gray-500" x-text="p.phone"></span>
                                </a>
                            </template>
                            <p x-show="results.length === 0" class="px-3 py-2 text-sm text-gray-500">Hasta bulunamadı.</p>
                        </div>
                    </div>
                </div>

                <!-- Sayfa içeriği -->
                <main class="px-4 py-6 sm:px-6 lg:px-8">
                    <x-flash />
                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>
