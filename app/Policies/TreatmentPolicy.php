<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Treatment;
use App\Models\User;

class TreatmentPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->hasRole(UserRole::Admin, UserRole::Doctor);
    }

    public function update(User $user, Treatment $treatment): bool
    {
        return $user->isAdmin()
            || ($user->isDoctor() && $user->doctor?->id === $treatment->doctor_id);
    }

    public function delete(User $user, Treatment $treatment): bool
    {
        return $user->isAdmin();
    }
}
