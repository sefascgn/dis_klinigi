<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Patient;
use App\Models\User;

class PatientPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Doktor yalnızca kendi hastasının kartını görür (bkz. Patient::scopeVisibleTo).
     */
    public function view(User $user, Patient $patient): bool
    {
        return $patient->isVisibleTo($user);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(UserRole::Admin, UserRole::Secretary, UserRole::Doctor);
    }

    public function update(User $user, Patient $patient): bool
    {
        return $user->hasRole(UserRole::Admin, UserRole::Secretary, UserRole::Doctor)
            && $patient->isVisibleTo($user);
    }

    public function delete(User $user, Patient $patient): bool
    {
        return $user->isAdmin();
    }
}
