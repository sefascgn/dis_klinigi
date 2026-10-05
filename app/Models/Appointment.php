<?php

namespace App\Models;

use App\Enums\AppointmentStatus;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Appointment extends Model
{
    /** @use HasFactory<\Database\Factories\AppointmentFactory> */
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'doctor_id',
        'starts_at',
        'ends_at',
        'status',
        'notes',
        'cancellation_reason',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'status' => AppointmentStatus::class,
        ];
    }

    // Geçmiş kayıtlar, hasta/doktor arşivlense (soft delete) bile görüntülenebilmeli.
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class)->withTrashed();
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class)->withTrashed();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function treatments(): HasMany
    {
        return $this->hasMany(Treatment::class);
    }

    public function durationInMinutes(): int
    {
        return (int) $this->starts_at->diffInMinutes($this->ends_at);
    }

    public function isActive(): bool
    {
        return in_array($this->status, AppointmentStatus::active(), true);
    }

    /**
     * Doktorlar yalnızca kendi randevularını görür; diğer roller hepsini.
     */
    public function scopeVisibleTo(Builder $query, User $user): void
    {
        if ($user->isDoctor()) {
            $query->where('doctor_id', $user->doctor?->id ?? 0);
        }
    }

    public function scopeOnDate(Builder $query, CarbonInterface $date): void
    {
        $query->whereBetween('starts_at', [$date->copy()->startOfDay(), $date->copy()->endOfDay()]);
    }

    public function scopeUpcoming(Builder $query): void
    {
        $query->where('starts_at', '>=', now())
            ->whereIn('status', AppointmentStatus::active())
            ->orderBy('starts_at');
    }

    /**
     * Aynı doktorun verilen aralıkla çakışan aktif randevuları.
     */
    public function scopeOverlapping(Builder $query, int $doctorId, CarbonInterface $start, CarbonInterface $end, ?int $ignoreId = null): void
    {
        $query->where('doctor_id', $doctorId)
            ->whereIn('status', AppointmentStatus::active())
            ->where('starts_at', '<', $end)
            ->where('ends_at', '>', $start)
            ->when($ignoreId, fn (Builder $q) => $q->whereKeyNot($ignoreId));
    }
}
