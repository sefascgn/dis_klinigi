<x-app-layout>
    <x-slot name="title">Tedaviler</x-slot>
    <x-slot name="header">
        <x-page-title title="Tedaviler" :subtitle="auth()->user()->isDoctor() ? 'Uyguladığınız tedaviler' : $treatments->total().' kayıt'">
            @can('create', App\Models\Treatment::class)
                <a href="{{ route('treatments.create') }}" class="btn-primary"><x-icon name="plus" class="h-4 w-4" />Tedavi ekle</a>
            @endcan
        </x-page-title>
    </x-slot>

    <x-card :padding="false">
        <div class="border-b border-gray-100 p-4">
            <x-search-form placeholder="Hasta adı veya telefon">
                <select name="treatment_type_id" class="form-input sm:w-48" aria-label="İşlem">
                    <option value="">Tüm işlemler</option>
                    @foreach ($treatmentTypes as $type)
                        <option value="{{ $type->id }}" @selected(request('treatment_type_id') == $type->id)>{{ $type->name }}</option>
                    @endforeach
                </select>
                @unless (auth()->user()->isDoctor())
                    <select name="doctor_id" class="form-input sm:w-48" aria-label="Doktor">
                        <option value="">Tüm doktorlar</option>
                        @foreach ($doctors as $doctor)
                            <option value="{{ $doctor->id }}" @selected(request('doctor_id') == $doctor->id)>{{ $doctor->full_name }}</option>
                        @endforeach
                    </select>
                @endunless
                <select name="status" class="form-input sm:w-40" aria-label="Durum">
                    <option value="">Tüm durumlar</option>
                    @foreach (App\Enums\TreatmentStatus::options() as $value => $label)
                        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </x-search-form>
        </div>

        @include('treatments._table', ['treatments' => $treatments, 'showPatient' => true])

        @if ($treatments->hasPages())
            <div class="border-t border-gray-100 px-4 py-3">{{ $treatments->links() }}</div>
        @endif
    </x-card>
</x-app-layout>
