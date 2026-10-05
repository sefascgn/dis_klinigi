{{-- Yazdırılabilir belgeler için sade A4 düzeni. Tarayıcıdan yazdırılır veya PDF olarak kaydedilir. --}}
@props(['title'])

@php $clinic = App\Models\ClinicSetting::current(); @endphp

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} · {{ $clinic->name }}</title>
    @vite(['resources/css/app.css'])
    <style>
        @page { size: A4; margin: 14mm; }
        @media print { .no-print { display: none !important; } body { background: #fff !important; } .sheet { box-shadow: none !important; margin: 0 !important; padding: 0 !important; } }
    </style>
</head>
<body class="bg-gray-100 font-sans text-sm text-gray-800 antialiased">
    <div class="no-print sticky top-0 z-10 flex items-center justify-center gap-2 border-b border-gray-200 bg-white/90 px-4 py-3 backdrop-blur">
        <button type="button" onclick="window.print()" class="btn-primary">Yazdır / PDF kaydet</button>
        <button type="button" onclick="window.close()" class="btn-secondary">Kapat</button>
    </div>

    <div class="sheet mx-auto my-6 max-w-[210mm] bg-white p-10 shadow">
        <header class="flex items-start justify-between gap-6 border-b-2 border-gray-800 pb-4">
            <div>
                <p class="text-xl font-bold text-gray-900">{{ $clinic->name }}</p>
                @if ($clinic->address)<p class="mt-1 whitespace-pre-line text-xs text-gray-600">{{ $clinic->address }}</p>@endif
                <p class="text-xs text-gray-600">
                    {{ collect([$clinic->phone, $clinic->email])->filter()->implode(' · ') }}
                </p>
                @if ($clinic->tax_number)
                    <p class="text-xs text-gray-600">{{ $clinic->tax_office }} V.D. · {{ $clinic->tax_number }}</p>
                @endif
            </div>
            <div class="text-right">
                <p class="text-lg font-semibold uppercase tracking-wide text-gray-900">{{ $title }}</p>
                <p class="text-xs text-gray-600">Tarih: {{ now()->format('d.m.Y') }}</p>
                {{ $meta ?? '' }}
            </div>
        </header>

        <main class="mt-6">
            {{ $slot }}
        </main>

        @isset($footer)
            <footer class="mt-10 border-t border-gray-200 pt-4 text-xs text-gray-500">{{ $footer }}</footer>
        @endisset
    </div>
</body>
</html>
