{{-- Randevu satırlarında durum değiştirme ve hızlı işlemler. --}}
@props(['appointment', 'compact' => false])

@php
    use App\Enums\AppointmentStatus;

    // Mevcut durumdan geçilebilecek durumlar (iptal ayrı form ile yapılır).
    $targets = match ($appointment->status) {
        AppointmentStatus::Pending => [AppointmentStatus::Confirmed, AppointmentStatus::Completed],
        AppointmentStatus::Confirmed => [AppointmentStatus::Completed],
        AppointmentStatus::Cancelled => [AppointmentStatus::Pending],
        default => [],
    };
    $canChange = auth()->user()->can('changeStatus', $appointment);
@endphp

{{-- Menü "fixed" konumlanır; böylece kaydırılabilir tablo kabı tarafından kırpılmaz. --}}
<div x-data="{
        open: false,
        cancelling: false,
        style: '',
        toggle() {
            const r = this.$refs.button.getBoundingClientRect();
            const left = Math.max(8, r.right - 224);
            this.style = r.bottom + 280 > window.innerHeight
                ? `left:${left}px;bottom:${window.innerHeight - r.top + 4}px`
                : `left:${left}px;top:${r.bottom + 4}px`;
            this.open = !this.open;
        },
     }"
     class="inline-block text-left" @click.outside="open = false; cancelling = false" @scroll.window="open = false" @resize.window="open = false">
    @if ($compact)
        <button type="button" x-ref="button" @click.stop="toggle()" class="rounded p-0.5 text-current opacity-60 hover:bg-black/5 hover:opacity-100" aria-label="Randevu işlemleri">
            <x-icon name="dots" class="h-4 w-4" />
        </button>
    @else
        <button type="button" x-ref="button" @click="toggle()" class="btn-secondary btn-sm">
            İşlemler
            <svg class="h-3 w-3" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd"/></svg>
        </button>
    @endif

    <div x-show="open" x-cloak x-transition.opacity :style="style"
         class="fixed z-50 w-56 rounded-lg border border-gray-200 bg-white py-1 text-left text-sm font-normal text-gray-700 no-underline shadow-lg">
        @if ($canChange)
            @foreach ($targets as $target)
                <form method="POST" action="{{ route('appointments.status', $appointment) }}">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="status" value="{{ $target->value }}">
                    <button type="submit" class="block w-full px-4 py-2 text-left hover:bg-gray-50">
                        {{ $target === AppointmentStatus::Pending ? 'Yeniden aç' : $target->label().' olarak işaretle' }}
                    </button>
                </form>
            @endforeach

            @if ($appointment->isActive())
                <button type="button" x-show="!cancelling" @click="cancelling = true" class="block w-full px-4 py-2 text-left text-red-600 hover:bg-red-50">
                    Randevuyu iptal et
                </button>
                <form x-show="cancelling" x-cloak method="POST" action="{{ route('appointments.status', $appointment) }}" class="space-y-2 px-3 py-2">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="status" value="{{ AppointmentStatus::Cancelled->value }}">
                    <input type="text" name="cancellation_reason" required maxlength="255" placeholder="İptal nedeni" class="form-input py-1.5 text-xs">
                    <button type="submit" class="btn-danger btn-sm w-full">İptal et</button>
                </form>
            @endif
        @endif

        @if ($appointment->status === AppointmentStatus::Completed)
            @can('create', App\Models\Treatment::class)
                <a href="{{ route('treatments.create', ['appointment_id' => $appointment->id]) }}" class="block px-4 py-2 hover:bg-gray-50">Tedavi ekle</a>
            @endcan
        @endif

        @can('update', $appointment)
            <a href="{{ route('appointments.edit', $appointment) }}" class="block px-4 py-2 hover:bg-gray-50">Düzenle</a>
        @endcan

        <a href="{{ route('patients.show', $appointment->patient_id) }}" class="block px-4 py-2 hover:bg-gray-50">Hasta kartı</a>

        @can('delete', $appointment)
            <form method="POST" action="{{ route('appointments.destroy', $appointment) }}" onsubmit="return confirm('Randevu kalıcı olarak silinsin mi?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="block w-full border-t border-gray-100 px-4 py-2 text-left text-red-600 hover:bg-red-50">Sil</button>
            </form>
        @endcan
    </div>
</div>
