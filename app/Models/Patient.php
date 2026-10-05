<?php

namespace App\Models;

use App\Enums\Gender;
use App\Enums\TreatmentStatus;
use Illuminate\Database\Eloquent\Builder;
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
        'medical_notes',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'gender' => Gender::class,
        ];
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
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

    protected function age(): Attribute
    {
        return Attribute::get(fn () => $this->birth_date?->age);
    }

    /**
     * Borç hesabına giren tedaviler (iptal edilenler hariç).
     */
    public function billableTreatments(): HasMany
    {
        return $this->treatments()->where('status', '!=', TreatmentStatus::Cancelled->value);
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
