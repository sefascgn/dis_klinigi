<?php

namespace App\Policies;

use App\Models\ClinicSetting;
use App\Models\User;

class ClinicSettingPolicy
{
    public function update(User $user, ClinicSetting $setting): bool
    {
        return $user->isAdmin();
    }
}
