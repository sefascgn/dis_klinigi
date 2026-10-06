<?php

namespace Tests;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function admin(): User
    {
        return User::factory()->admin()->create();
    }

    protected function secretary(): User
    {
        return User::factory()->secretary()->create();
    }

    /**
     * Bağlı doktor kaydıyla birlikte doktor rolünde kullanıcı.
     */
    protected function doctorUser(): User
    {
        return User::factory()->doctor()->create()->load('doctor');
    }

    /**
     * Hastada randevusu olan (yani hastayı görebilen) doktor kullanıcısı.
     */
    protected function doctorUserOf(Patient $patient): User
    {
        $user = $this->doctorUser();
        Appointment::factory()->create(['patient_id' => $patient->id, 'doctor_id' => $user->doctor->id]);

        return $user;
    }
}
