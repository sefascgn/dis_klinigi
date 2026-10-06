<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Doctor;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_user_with_role(): void
    {
        $this->actingAs($this->admin())->post('/kullanicilar', [
            'name' => 'Yeni Sekreter',
            'email' => 'yeni@klinik.test',
            'role' => 'secretary',
            'is_active' => '1',
            'password' => 'GucluSifre123',
            'password_confirmation' => 'GucluSifre123',
        ])->assertSessionHasNoErrors()->assertRedirect('/kullanicilar');

        $user = User::where('email', 'yeni@klinik.test')->firstOrFail();
        $this->assertSame(UserRole::Secretary, $user->role);
        $this->assertTrue(Hash::check('GucluSifre123', $user->password));
    }

    public function test_password_is_optional_on_update(): void
    {
        $user = $this->secretary();
        $hash = $user->password;

        $this->actingAs($this->admin())->put("/kullanicilar/{$user->id}", [
            'name' => 'Değişti', 'email' => $user->email, 'role' => 'secretary', 'is_active' => '1',
        ])->assertSessionHasNoErrors();

        $this->assertSame('Değişti', $user->fresh()->name);
        $this->assertSame($hash, $user->fresh()->password);
    }

    public function test_admin_cannot_demote_deactivate_or_delete_self(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->put("/kullanicilar/{$admin->id}", [
            'name' => $admin->name, 'email' => $admin->email, 'role' => 'secretary', 'is_active' => '0',
        ])->assertSessionHasErrors(['role', 'is_active']);

        $this->actingAs($admin)->delete("/kullanicilar/{$admin->id}")->assertForbidden();
        $this->assertTrue($admin->fresh()->isAdmin());
    }

    public function test_changing_role_from_doctor_unlinks_doctor_record(): void
    {
        $user = $this->doctorUser();
        $doctor = $user->doctor;

        $this->actingAs($this->admin())->put("/kullanicilar/{$user->id}", [
            'name' => $user->name, 'email' => $user->email, 'role' => 'secretary', 'is_active' => '1',
        ])->assertSessionHasNoErrors();

        $this->assertNull($doctor->fresh()->user_id);
    }

    public function test_deleting_user_keeps_linked_doctor_record(): void
    {
        $user = $this->doctorUser();
        $doctorId = $user->doctor->id;

        $this->actingAs($this->admin())->delete("/kullanicilar/{$user->id}")->assertRedirect();

        $this->assertModelMissing($user);
        $this->assertNull(Doctor::find($doctorId)->user_id);
    }

    public function test_non_admins_cannot_manage_users(): void
    {
        $this->actingAs($this->secretary())->post('/kullanicilar', [])->assertForbidden();
        $this->actingAs($this->doctorUser())->get('/kullanicilar/yeni')->assertForbidden();
    }

    public function test_admin_can_update_clinic_settings(): void
    {
        $this->actingAs($this->admin())->put('/ayarlar/klinik', [
            'name' => 'Gülüş Diş Kliniği',
            'phone' => '0212 555 00 00',
            'opening_time' => '08:30',
            'closing_time' => '19:00',
            'default_appointment_duration' => 45,
            'recall_months' => 6,
        ])->assertSessionHasNoErrors();

        $this->actingAs($this->admin())->get('/panel')->assertSee('Gülüş Diş Kliniği');

        $this->actingAs($this->admin())->put('/ayarlar/klinik', [
            'name' => 'X', 'opening_time' => '18:00', 'closing_time' => '09:00', 'default_appointment_duration' => 30, 'recall_months' => 6,
        ])->assertSessionHasErrors('closing_time');

        $this->actingAs($this->secretary())->put('/ayarlar/klinik', ['name' => 'Y'])->assertForbidden();
    }

    public function test_user_with_financial_records_cannot_be_deleted(): void
    {
        $secretary = $this->secretary();
        Payment::factory()->create(['received_by' => $secretary->id]);

        $this->actingAs($this->admin())->delete("/kullanicilar/{$secretary->id}")
            ->assertSessionHas('error');

        $this->assertModelExists($secretary);
    }
}
