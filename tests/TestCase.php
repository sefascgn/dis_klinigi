<?php

namespace Tests;

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
}
