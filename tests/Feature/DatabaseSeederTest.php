<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_refuses_weak_admin_password_outside_local(): void
    {
        foreach ([null, 'password', 'kisa123'] as $password) {
            config(['clinic.admin.password' => $password]);

            try {
                $this->seed(DatabaseSeeder::class);
                $this->fail("Zayıf şifre kabul edildi: '{$password}'");
            } catch (RuntimeException $e) {
                $this->assertStringContainsString('ADMIN_PASSWORD', $e->getMessage());
            }
        }

        $this->assertDatabaseCount('users', 0);
    }

    public function test_seeder_creates_admin_with_strong_password_and_no_demo_data(): void
    {
        config(['clinic.admin.password' => 'Guclu-Sifre-2026!']);

        $this->seed(DatabaseSeeder::class);

        $admin = User::sole();
        $this->assertTrue($admin->isAdmin());
        $this->assertTrue(Hash::check('Guclu-Sifre-2026!', $admin->password));
    }
}
