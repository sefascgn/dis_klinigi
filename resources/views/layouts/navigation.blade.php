@php
    $user = Auth::user();

    // [rota, aktiflik deseni, etiket, ikon, görünürlük]
    $sections = [
        'Genel' => [
            ['dashboard', 'dashboard', 'Gösterge Paneli', 'home', true],
            ['appointments.index', 'appointments.*', 'Randevular', 'calendar', $user->can('viewAny', App\Models\Appointment::class)],
            ['patients.index', 'patients.*', 'Hastalar', 'users', $user->can('viewAny', App\Models\Patient::class)],
            ['doctors.index', 'doctors.*', 'Doktorlar', 'doctor', $user->can('viewAny', App\Models\Doctor::class)],
        ],
        'Klinik' => [
            ['treatments.index', 'treatments.*', 'Tedaviler', 'clipboard', $user->can('viewAny', App\Models\Treatment::class)],
            ['payments.index', 'payments.*', 'Ödemeler', 'cash', $user->can('viewAny', App\Models\Payment::class)],
        ],
        'Yönetim' => [
            ['treatment-types.index', 'treatment-types.*', 'İşlem Tanımları', 'list', $user->can('viewAny', App\Models\TreatmentType::class)],
            ['users.index', 'users.*', 'Kullanıcılar', 'user', $user->can('viewAny', App\Models\User::class)],
            ['clinic-settings.edit', 'clinic-settings.*', 'Klinik Bilgileri', 'building', $user->can('update', $clinic)],
        ],
    ];
@endphp

<!-- Mobil arka plan -->
<div x-show="sidebarOpen" x-cloak x-transition.opacity class="fixed inset-0 z-30 bg-gray-900/50 lg:hidden" @click="sidebarOpen = false"></div>

<aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
       class="fixed inset-y-0 left-0 z-40 flex w-64 -translate-x-full flex-col bg-slate-900 text-slate-300 transition-transform duration-200 lg:translate-x-0">
    <!-- Logo -->
    <div class="flex h-16 items-center justify-between gap-2 px-5">
        <a href="{{ route('dashboard') }}" class="flex min-w-0 items-center gap-2.5">
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-primary-500 text-white">
                <x-icon name="tooth" class="h-5 w-5" />
            </span>
            <span class="truncate text-base font-semibold text-white">{{ $clinic->name }}</span>
        </a>
        <button type="button" class="rounded p-1 text-slate-400 hover:text-white lg:hidden" @click="sidebarOpen = false" aria-label="Menüyü kapat">
            <x-icon name="x" />
        </button>
    </div>

    <!-- Menü -->
    <nav class="flex-1 space-y-6 overflow-y-auto px-3 py-4">
        @foreach ($sections as $heading => $items)
            @php $items = array_filter($items, fn ($item) => $item[4]); @endphp
            @if ($items)
                <div>
                    <p class="px-3 pb-2 text-xs font-semibold uppercase tracking-wider text-slate-500">{{ $heading }}</p>
                    <div class="space-y-1">
                        @foreach ($items as [$route, $pattern, $label, $icon])
                            <a href="{{ route($route) }}"
                               @class([
                                   'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition',
                                   'bg-primary-600 text-white' => request()->routeIs($pattern),
                                   'hover:bg-slate-800 hover:text-white' => ! request()->routeIs($pattern),
                               ])>
                                <x-icon :name="$icon" />
                                {{ $label }}
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        @endforeach
    </nav>

    <!-- Kullanıcı -->
    <div class="border-t border-slate-800 p-3">
        <a href="{{ route('profile.edit') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 hover:bg-slate-800">
            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-slate-700 text-sm font-semibold text-white">
                {{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}
            </span>
            <span class="min-w-0">
                <span class="block truncate text-sm font-medium text-white">{{ $user->name }}</span>
                <span class="block truncate text-xs text-slate-400">{{ $user->role->label() }}</span>
            </span>
        </a>
        <form method="POST" action="{{ route('logout') }}" class="mt-1">
            @csrf
            <button type="submit" class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium hover:bg-slate-800 hover:text-white">
                <x-icon name="logout" />
                {{ __('Log Out') }}
            </button>
        </form>
    </div>
</aside>
