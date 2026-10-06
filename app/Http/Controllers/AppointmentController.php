<?php

namespace App\Http\Controllers;

use App\Enums\AppointmentStatus;
use App\Http\Requests\AppointmentRequest;
use App\Http\Requests\AppointmentStatusRequest;
use App\Models\Appointment;
use App\Models\ClinicSetting;
use App\Models\Doctor;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AppointmentController extends Controller
{
    /**
     * Takvim görünümleri: gün (doktor sütunlu saat ızgarası), hafta, ay ve sayfalı liste.
     */
    public const VIEWS = ['day', 'week', 'month', 'list'];

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Appointment::class);
        $request->validate([
            'date' => ['nullable', 'date'],
            'view' => ['nullable', Rule::in(self::VIEWS)],
        ]);

        $view = $request->query('view', 'day');
        $date = $request->date('date') ?? today();
        $query = $this->filteredQuery($request);

        $data = [
            'view' => $view,
            'date' => $date,
            'doctors' => Doctor::ordered()->get(),
        ];

        if ($view === 'list') {
            $data['appointments'] = $query->orderByDesc('starts_at')->paginate(20)->withQueryString();

            return view('appointments.index', $data);
        }

        [$from, $to] = $this->range($view, $date);
        $appointments = $query->whereBetween('starts_at', [$from, $to])->orderBy('starts_at')->get();

        $data += compact('appointments', 'from', 'to');
        if ($view === 'day') {
            $data += $this->dayGrid($request, $appointments);
        }

        return view('appointments.index', $data);
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', Appointment::class);

        $request->validate([
            'date' => ['nullable', 'date'],
            'time' => ['nullable', 'date_format:H:i'],
        ]);
        $clinic = ClinicSetting::current();

        // Varsayılan başlangıç: seçilen saat ya da açılış saati; geçmişte kalıyorsa bir sonraki yarım saat.
        $start = Carbon::parse($request->query('date') ?? today()->toDateString())
            ->setTimeFromTimeString($request->query('time') ?? $clinic->opening_time);
        if ($start->isPast()) {
            $start = now()->addMinutes(30 - now()->minute % 30)->seconds(0);
        }

        $appointment = new Appointment([
            'patient_id' => $request->integer('patient_id') ?: null,
            'doctor_id' => $request->integer('doctor_id') ?: null,
            'starts_at' => $start,
            'status' => AppointmentStatus::Pending,
        ]);

        return view('appointments.create', $this->formData($appointment, $clinic->default_appointment_duration));
    }

    public function store(AppointmentRequest $request): RedirectResponse
    {
        $appointment = new Appointment($request->appointmentData());
        $appointment->created_by = $request->user()->id;
        $appointment->save();

        return redirect()
            ->route('appointments.index', ['date' => $appointment->starts_at->toDateString()])
            ->with('success', 'Randevu oluşturuldu.');
    }

    public function edit(Appointment $appointment): View
    {
        Gate::authorize('update', $appointment);

        return view('appointments.edit', $this->formData($appointment, $appointment->durationInMinutes()));
    }

    public function update(AppointmentRequest $request, Appointment $appointment): RedirectResponse
    {
        $appointment->update($request->appointmentData());

        return redirect()
            ->route('appointments.index', ['date' => $appointment->starts_at->toDateString()])
            ->with('success', 'Randevu güncellendi.');
    }

    public function updateStatus(AppointmentStatusRequest $request, Appointment $appointment): RedirectResponse
    {
        $status = AppointmentStatus::from($request->validated('status'));

        // İptal edilmiş bir randevu yeniden aktifleşirken çakışma kontrolü yapılır.
        if (in_array($status, AppointmentStatus::active(), true) && ! $appointment->isActive()) {
            $conflict = Appointment::query()
                ->overlapping($appointment->doctor_id, $appointment->starts_at, $appointment->ends_at, $appointment->id)
                ->exists();

            if ($conflict) {
                return back()->with('error', 'Bu saat aralığında doktorun başka bir aktif randevusu olduğu için randevu yeniden açılamaz.');
            }
        }

        $appointment->update([
            'status' => $status,
            'cancellation_reason' => $status === AppointmentStatus::Cancelled ? $request->validated('cancellation_reason') : null,
        ]);

        return back()->with('success', "Randevu durumu \"{$status->label()}\" olarak güncellendi.");
    }

    public function destroy(Appointment $appointment): RedirectResponse
    {
        Gate::authorize('delete', $appointment);

        $appointment->delete();

        return back()->with('success', 'Randevu silindi.');
    }

    /**
     * Tüm görünümlerde ortak filtreler (rol kapsamı, doktor, durum, hasta araması).
     */
    private function filteredQuery(Request $request): Builder
    {
        $status = AppointmentStatus::tryFrom((string) $request->query('status'));

        return Appointment::query()
            ->visibleTo($request->user())
            ->with(['patient', 'doctor'])
            ->when($request->integer('doctor_id'), fn ($q, $id) => $q->where('doctor_id', $id))
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($request->string('q')->trim()->value(), fn ($q, $term) => $q->whereHas('patient', fn ($p) => $p->search($term)));
    }

    /**
     * Görünüme göre gösterilecek tarih aralığı. Ay görünümü, takvim ızgarasını
     * dolduran önceki/sonraki ay günlerini de kapsar. Haftalar pazartesi başlar.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    private function range(string $view, Carbon $date): array
    {
        return match ($view) {
            'week' => [$date->copy()->startOfWeek(Carbon::MONDAY), $date->copy()->endOfWeek(Carbon::SUNDAY)],
            'month' => [
                $date->copy()->startOfMonth()->startOfWeek(Carbon::MONDAY),
                $date->copy()->endOfMonth()->endOfWeek(Carbon::SUNDAY),
            ],
            default => [$date->copy()->startOfDay(), $date->copy()->endOfDay()],
        };
    }

    /**
     * Gün görünümü: doktor sütunları ve saat aralığı.
     *
     * @param  Collection<int, Appointment>  $appointments
     */
    private function dayGrid(Request $request, Collection $appointments): array
    {
        $user = $request->user();

        if ($user->isDoctor()) {
            $columns = collect([$user->doctor])->filter();
        } else {
            // Aktif doktorlar + o gün randevusu olan (sonradan pasifleşmiş) doktorlar.
            $columns = Doctor::active()->ordered()
                ->when($request->integer('doctor_id'), fn ($q, $id) => $q->whereKey($id))
                ->get()
                ->concat($appointments->pluck('doctor'))
                ->unique('id')
                ->values();
        }

        // Izgara, çalışma saatlerini ve mesai dışı randevuları kapsar.
        $clinic = ClinicSetting::current();
        $startHour = min((int) substr($clinic->opening_time, 0, 2), $appointments->min(fn ($a) => $a->starts_at->hour) ?? 23);
        $endHour = max(
            (int) ceil(Carbon::parse($clinic->closing_time)->secondsSinceMidnight() / 3600),
            $appointments->max(fn ($a) => $a->ends_at->isSameDay($a->starts_at) ? (int) ceil(($a->ends_at->secondsSinceMidnight()) / 3600) : 24) ?? 0,
        );

        return compact('columns', 'startHour', 'endHour');
    }

    private function formData(Appointment $appointment, int $duration): array
    {
        // Doğrulama ile aynı liste: formda görünen her süre kaydedilebilir.
        $durations = AppointmentRequest::durationOptions($appointment->exists ? $appointment : null);

        return [
            'appointment' => $appointment,
            'duration' => $duration,
            'durations' => $durations,
            'doctors' => Doctor::active()->ordered()->get(),
            'patient' => $appointment->patient_id ? Patient::find($appointment->patient_id) : null,
        ];
    }
}
