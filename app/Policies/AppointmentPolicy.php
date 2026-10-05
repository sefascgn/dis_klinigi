<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Appointment;
use App\Models\User;

class AppointmentPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Appointment $appointment): bool
    {
        return ! $user->isDoctor() || $this->ownsAppointment($user, $appointment);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(UserRole::Admin, UserRole::Secretary);
    }

    public function update(User $user, Appointment $appointment): bool
    {
        return $user->hasRole(UserRole::Admin, UserRole::Secretary);
    }

    /**
     * Durum değişikliği (onayla / tamamla / iptal): doktor kendi randevusu için yapabilir.
     */
    public function changeStatus(User $user, Appointment $appointment): bool
    {
        return $user->hasRole(UserRole::Admin, UserRole::Secretary)
            || $this->ownsAppointment($user, $appointment);
    }

    public function delete(User $user, Appointment $appointment): bool
    {
        return $user->isAdmin();
    }

    private function ownsAppointment(User $user, Appointment $appointment): bool
    {
        return $user->isDoctor() && $user->doctor?->id === $appointment->doctor_id;
    }
}
