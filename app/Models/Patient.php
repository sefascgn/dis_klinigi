<?php

namespace App\Models;

use App\Enums\AppointmentStatus;
use App\Enums\Gender;
use App\Enums\MedicalCondition;
use App\Enums\TreatmentStatus;
use App\Support\Phone;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\AsEnumCollection;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Patient extends Model
{
    /** @use HasFactory<\Database\Factories\PatientFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'first_name',
        'last_name',
        'national_id',
        'birth_date',
        'gender',
        'phone',
        'email',
        'address',
        'allergies',
        'medical_conditions',
        'medical_notes',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'gender' => Gender::class,
            'medical_conditions' => AsEnumCollection::of(MedicalCondition::class),
        ];
    }

    public function documents(): HasMany
    {
        // chaperone: belge yetki kontrolündeki $document->patient ek sorgu atmaz.
        return $this->hasMany(PatientDocument::class)->chaperone();
    }

    /**
     * Kartta uyarı olarak gösterilecek sağlık bilgisi var mı?
     */
    public function hasMedicalAlerts(): bool
    {
        return filled($this->allergies) || $this->medical_conditions?->isNotEmpty();
    }

    public function appointments(): HasMany
    {
        // chaperone: yüklenen randevulara bu hasta ek sorgu olmadan bağlanır.
        return $this->hasMany(Appointment::class)->chaperone();
    }

    public function treatments(): HasMany
    {
        return $this->hasMany(Treatment::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    protected function fullName(): Attribute
    {
        return Attribute::get(fn () => "{$this->first_name} {$this->last_name}");
    }

    protected function formattedPhone(): Attribute
    {
        return Attribute::get(fn () => Phone::format($this->phone));
    }

    /**
     * Listelerde T.C. kimlik no maskelenir (KVKK): 12345678901 → 123******01
     */
    protected function maskedNationalId(): Attribute
    {
        return Attribute::get(fn () => $this->national_id
            ? substr($this->national_id, 0, 3).str_repeat('*', strlen($this->national_id) - 5).substr($this->national_id, -2)
            : null);
    }

    protected function age(): Attribute
    {
        return Attribute::get(fn () => $this->birth_date?->age);
    }

    /**
     * Borç hesabına giren tedaviler: başlamış veya tamamlanmış olanlar.
     * Planlanan (teklif aşamasındaki) ve iptal edilen tedaviler borç doğurmaz.
     */
    public function billableTreatments(): HasMany
    {
        return $this->treatments()->whereIn('status', [TreatmentStatus::InProgress, TreatmentStatus::Completed]);
    }

    /**
     * Liste ekranlarında N+1 olmadan bakiye göstermek için toplamları ekler.
     */
    public function scopeWithBalance(Builder $query): void
    {
        $query->withSum('billableTreatments as treatments_total', 'price')
            ->withSum('payments as payments_total', 'amount');
    }

    public function totalTreatmentAmount(): float
    {
        return (float) ($this->treatments_total ?? $this->billableTreatments()->sum('price'));
    }

    public function totalPaid(): float
    {
        return (float) ($this->payments_total ?? $this->payments()->sum('amount'));
    }

    public function balance(): float
    {
        return round($this->totalTreatmentAmount() - $this->totalPaid(), 2);
    }

    /**
     * Kalan borcu olan hastalar (withBalance ile birlikte kullanılır), en yüksek borç önce.
     */
    public function scopeDebtors(Builder $query): void
    {
        $query->havingRaw('COALESCE(treatments_total, 0) - COALESCE(payments_total, 0) > 0')
            ->orderByRaw('COALESCE(treatments_total, 0) - COALESCE(payments_total, 0) DESC');
    }

    /**
     * Kontrol zamanı gelen hastalar: son tamamlanan randevusu en az $months ay önce
     * olan ve ileri tarihli aktif randevusu bulunmayanlar.
     */
    public function scopeDueForRecall(Builder $query, int $months): void
    {
        $cutoff = now()->subMonths($months);

        $query->whereHas('appointments', fn (Builder $q) => $q->where('status', AppointmentStatus::Completed))
            ->whereDoesntHave('appointments', fn (Builder $q) => $q->where('status', AppointmentStatus::Completed)->where('starts_at', '>', $cutoff))
            ->whereDoesntHave('appointments', fn (Builder $q) => $q->upcoming())
            ->withMax(['appointments as last_visit_at' => fn (Builder $q) => $q->where('status', AppointmentStatus::Completed)], 'starts_at')
            ->orderBy('last_visit_at');
    }

    /**
     * Doktor yalnızca kendi hastalarını görür: kendisinde randevusu veya tedavisi
     * olan ya da kaydını kendisi açtığı hastalar. Diğer roller tüm hastaları görür.
     */
    public function scopeVisibleTo(Builder $query, User $user): void
    {
        if (! $user->isDoctor()) {
            return;
        }

        $doctorId = $user->doctor?->id ?? 0;

        $query->where(fn (Builder $q) => $q->where('created_by', $user->id)
            ->orWhereHas('appointments', fn (Builder $a) => $a->where('doctor_id', $doctorId))
            ->orWhereHas('treatments', fn (Builder $t) => $t->where('doctor_id', $doctorId)));
    }

    public function isVisibleTo(User $user): bool
    {
        return ! $user->isDoctor()
            || static::withTrashed()->visibleTo($user)->whereKey($this->getKey())->exists();
    }

    public function scopeSearch(Builder $query, ?string $term): void
    {
        $query->when($term, function (Builder $query, string $term) {
            $query->where(function (Builder $query) use ($term) {
                $query->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$term}%"])
                    ->orWhere('phone', 'like', "%{$term}%")
                    ->orWhere('national_id', 'like', "{$term}%")
                    ->orWhere('email', 'like', "%{$term}%");
            });
        });
    }

    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('first_name')->orderBy('last_name');
    }
}
