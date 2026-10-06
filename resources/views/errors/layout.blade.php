{{--
    Hata sayfaları için ortak görünüm. Veritabanına dokunmaz (500 hatası veritabanı
    kaynaklı olabilir); klinik adı yerine uygulama adı kullanılır.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="robots" content="noindex">

        <title>@yield('title') · {{ config('app.name') }}</title>

        <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css'])
    </head>
    <body class="font-sans text-gray-900 antialiased bg-gray-50">
        <main class="flex min-h-screen flex-col items-center justify-center px-6 py-12">
            <a href="{{ url('/') }}" class="mb-10 flex items-center gap-3 text-gray-900">
                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-primary-600 text-white shadow-lg shadow-primary-600/25">
                    <x-icon name="tooth" class="h-6 w-6" />
                </span>
                <span class="text-lg font-semibold tracking-tight">{{ config('app.name') }}</span>
            </a>

            <div class="w-full max-w-md rounded-2xl border border-gray-200 bg-white p-8 text-center shadow-sm sm:p-10">
                <p class="text-sm font-semibold tracking-widest text-primary-600">HATA @yield('code')</p>
                <h1 class="mt-3 text-2xl font-bold tracking-tight text-gray-900 sm:text-3xl">@yield('title')</h1>
                <p class="mt-3 text-sm leading-relaxed text-gray-500">@yield('message')</p>

                <div class="mt-8 flex flex-col-reverse gap-3 sm:flex-row sm:justify-center">
                    <button type="button" onclick="history.length > 1 ? history.back() : location.assign('{{ url('/') }}')"
                            class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50">
                        Geri dön
                    </button>
                    <a href="{{ url('/') }}"
                       class="inline-flex items-center justify-center gap-2 rounded-lg bg-primary-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-700">
                        <x-icon name="home" class="h-4 w-4" />
                        Ana sayfaya git
                    </a>
                </div>
            </div>
        </main>
    </body>
</html>
