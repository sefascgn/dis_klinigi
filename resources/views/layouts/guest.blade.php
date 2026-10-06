<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $clinic->name }}</title>

        <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased bg-white">
        <div class="flex min-h-screen">
            <!-- Marka paneli (geniş ekran) -->
            <aside class="relative hidden lg:flex lg:w-1/2 xl:w-[55%] flex-col justify-between overflow-hidden bg-gradient-to-br from-primary-700 via-primary-800 to-slate-900 p-12 text-white">
                <!-- Dekoratif arka plan -->
                <div class="pointer-events-none absolute -top-32 -left-32 h-96 w-96 rounded-full bg-primary-400/30 blur-3xl"></div>
                <div class="pointer-events-none absolute -bottom-40 -right-24 h-[28rem] w-[28rem] rounded-full bg-cyan-400/20 blur-3xl"></div>
                <div class="pointer-events-none absolute inset-0 opacity-[0.07]"
                     style="background-image: radial-gradient(circle at 1px 1px, white 1px, transparent 0); background-size: 28px 28px;"></div>
                <x-icon name="tooth" class="pointer-events-none absolute -right-16 top-1/2 h-[26rem] w-[26rem] -translate-y-1/2 text-white/[0.06]" />

                <a href="/" class="relative flex items-center gap-3">
                    <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-white/15 ring-1 ring-white/25 backdrop-blur">
                        <x-icon name="tooth" class="h-6 w-6" />
                    </span>
                    <span class="text-lg font-semibold tracking-tight">{{ $clinic->name }}</span>
                </a>

                <div class="relative max-w-lg">
                    <h1 class="text-4xl font-bold leading-tight tracking-tight xl:text-5xl">
                        Kliniğinizi tek panelden, zahmetsizce yönetin.
                    </h1>
                    <p class="mt-4 text-base text-primary-100/90">
                        Randevular, hasta kayıtları, tedaviler ve finans; hepsi güvenli ve düzenli bir arada.
                    </p>

                    <ul class="mt-10 space-y-4">
                        @foreach ([
                            ['calendar', 'Akıllı randevu takvimi', 'Gün, hafta ve ay görünümü; çakışma kontrolü'],
                            ['users', 'Detaylı hasta kartı', 'Anamnez, diş şeması, belgeler ve hasta geçmişi'],
                            ['chart', 'Gelir–gider raporları', 'Net kâr, tahsilat ve aylık karşılaştırmalar'],
                        ] as [$icon, $title, $text])
                            <li class="flex items-start gap-4">
                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-white/10 ring-1 ring-white/15">
                                    <x-icon :name="$icon" class="h-5 w-5 text-primary-100" />
                                </span>
                                <div>
                                    <p class="font-semibold">{{ $title }}</p>
                                    <p class="text-sm text-primary-100/80">{{ $text }}</p>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="relative flex flex-wrap items-center gap-x-6 gap-y-2 text-sm text-primary-100/70">
                    <span>&copy; {{ date('Y') }} {{ $clinic->name }}</span>
                    @if ($clinic->phone)
                        <span class="inline-flex items-center gap-1.5"><x-icon name="phone" class="h-4 w-4" /> {{ $clinic->phone }}</span>
                    @endif
                    @if ($clinic->email)
                        <span class="inline-flex items-center gap-1.5"><x-icon name="mail" class="h-4 w-4" /> {{ $clinic->email }}</span>
                    @endif
                </div>
            </aside>

            <!-- Form alanı -->
            <main class="flex w-full flex-1 flex-col bg-gray-50 lg:bg-white">
                <div class="flex flex-1 items-center justify-center px-4 py-10 sm:px-8">
                    <div class="w-full max-w-md">
                        <!-- Mobil logo -->
                        <a href="/" class="mb-8 flex items-center justify-center gap-3 lg:hidden">
                            <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-primary-500 to-primary-700 text-white shadow-lg shadow-primary-600/30">
                                <x-icon name="tooth" class="h-7 w-7" />
                            </span>
                            <span class="text-xl font-semibold tracking-tight text-gray-900">{{ $clinic->name }}</span>
                        </a>

                        <div class="rounded-2xl bg-white p-6 shadow-xl shadow-gray-200/60 ring-1 ring-gray-100 sm:p-8 lg:p-0 lg:shadow-none lg:ring-0">
                            {{ $slot }}
                        </div>
                    </div>
                </div>

                <p class="pb-6 text-center text-xs text-gray-400 lg:hidden">&copy; {{ date('Y') }} {{ $clinic->name }}</p>
            </main>
        </div>
    </body>
</html>
