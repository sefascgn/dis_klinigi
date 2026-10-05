<?php

namespace App\Policies;

use App\Models\TreatmentType;
use App\Models\User;

class TreatmentTypePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, TreatmentType $treatmentType): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, TreatmentType $treatmentType): bool
    {
        return $user->isAdmin();
    }
}
