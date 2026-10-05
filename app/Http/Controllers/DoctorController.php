<?php

namespace App\Http\Controllers;

use App\Enums\AppointmentStatus;
use App\Enums\UserRole;
use App\Http\Requests\DoctorRequest;
use App\Models\Doctor;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class DoctorController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Doctor::class);

        $doctors = Doctor::query()
            ->search($request->string('q')->trim()->value())
            ->with('user')
            ->withCount(['appointments as upcoming_count' => fn ($q) => $q->upcoming()])
            ->ordered()
            ->paginate(15)
            ->withQueryString();

        return view('doctors.index', compact('doctors'));
    }

    public function create(): View
    {
        Gate::authorize('create', Doctor::class);

        return view('doctors.create', [
            'doctor' => new Doctor(['is_active' => true]),
            'users' => $this->linkableUsers(),
        ]);
    }

    public function store(DoctorRequest $request): RedirectResponse
    {
        $doctor = Doctor::create($request->validated());

        return redirect()->route('doctors.show', $doctor)->with('success', 'Doktor kaydı oluşturuldu.');
    }

    public function show(Request $request, Doctor $doctor): View
    {
        Gate::authorize('view', $doctor);

        $period = $request->query('period') === 'past' ? 'past' : 'upcoming';

        $appointments = $doctor->appointments()
            ->with('patient')
            ->when(
                $period === 'upcoming',
                fn ($q) => $q->where('starts_at', '>=', today())->orderBy('starts_at'),
                fn ($q) => $q->where('starts_at', '<', today())->orderByDesc('starts_at'),
            )
            ->paginate(15)
            ->withQueryString();

        $stats = [
            'upcoming' => $doctor->appointments()->upcoming()->count(),
            'completed' => $doctor->appointments()->where('status', AppointmentStatus::Completed)->count(),
            'treatments' => $doctor->treatments()->count(),
        ];

        return view('doctors.show', compact('doctor', 'appointments', 'period', 'stats'));
    }

    public function edit(Doctor $doctor): View
    {
        Gate::authorize('update', $doctor);

        return view('doctors.edit', [
            'doctor' => $doctor,
            'users' => $this->linkableUsers($doctor),
        ]);
    }

    public function update(DoctorRequest $request, Doctor $doctor): RedirectResponse
    {
        $doctor->update($request->validated());

        return redirect()->route('doctors.show', $doctor)->with('success', 'Doktor bilgileri güncellendi.');
    }

    public function destroy(Doctor $doctor): RedirectResponse
    {
        Gate::authorize('delete', $doctor);

        if ($doctor->appointments()->upcoming()->exists()) {
            return back()->with('error', 'Doktorun ileri tarihli aktif randevuları var. Önce bu randevuları iptal edin veya başka doktora aktarın.');
        }

        // Kayıt arşivlenir; geçmiş randevu ve tedavilerde doktor adı görünmeye devam eder.
        $doctor->update(['user_id' => null, 'is_active' => false]);
        $doctor->delete();

        return redirect()->route('doctors.index')->with('success', 'Doktor kaydı silindi.');
    }

    /**
     * Doktor rolündeki ve henüz bir doktor kaydına bağlanmamış kullanıcılar.
     *
     * @return Collection<int, User>
     */
    private function linkableUsers(?Doctor $doctor = null): Collection
    {
        return User::query()
            ->where('role', UserRole::Doctor)
            ->where(fn ($q) => $q->whereDoesntHave('doctor')->when($doctor?->user_id, fn ($q, $id) => $q->orWhere('id', $id)))
            ->orderBy('name')
            ->get(['id', 'name', 'email']);
    }
}
