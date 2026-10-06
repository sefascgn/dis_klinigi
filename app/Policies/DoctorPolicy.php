<?php

namespace App\Policies;

use App\Models\Doctor;
use App\Models\User;

class DoctorPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Doktor sayfası randevu listesi içerir; doktor yalnızca kendi sayfasını açabilir.
     */
    public function view(User $user, Doctor $doctor): bool
    {
        return ! $user->isDoctor() || $user->doctor?->id === $doctor->id;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Doctor $doctor): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Doctor $doctor): bool
    {
        return $user->isAdmin();
    }
}
