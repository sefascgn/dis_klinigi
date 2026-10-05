<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Rol bazlı sayfa erişim matrisi.
 */
class AuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public static function pageAccessProvider(): array
    {
        // [url, admin, secretary, doctor]
        return [
            'dashboard' => ['/dashboard', 200, 200, 200],
            'hastalar' => ['/patients', 200, 200, 200],
            'hasta ekle' => ['/patients/create', 200, 200, 200],
            'doktorlar' => ['/doctors', 200, 200, 200],
            'doktor ekle' => ['/doctors/create', 200, 403, 403],
            'randevular' => ['/appointments', 200, 200, 200],
            'randevu ekle' => ['/appointments/create', 200, 200, 403],
            'tedaviler' => ['/treatments', 200, 200, 200],
            'tedavi ekle' => ['/treatments/create', 200, 403, 200],
            'ödemeler' => ['/payments', 200, 200, 403],
            'ödeme al' => ['/payments/create', 200, 200, 403],
            'işlem tanımları' => ['/treatment-types', 200, 403, 403],
            'kullanıcılar' => ['/users', 200, 403, 403],
            'klinik bilgileri' => ['/settings/clinic', 200, 403, 403],
            'raporlar' => ['/reports', 200, 403, 403],
            'giderler' => ['/expenses', 200, 403, 403],
            'gider ekle' => ['/expenses/create', 200, 403, 403],
            'kontrolü gelenler' => ['/patients?filter=recall', 200, 200, 200],
            'borçlular' => ['/patients?filter=debtors', 200, 200, 403],
            'profil' => ['/profile', 200, 200, 200],
        ];
    }

    #[DataProvider('pageAccessProvider')]
    public function test_role_based_page_access(string $url, int $admin, int $secretary, int $doctor): void
    {
        $this->actingAs($this->admin())->get($url)->assertStatus($admin);
        $this->actingAs($this->secretary())->get($url)->assertStatus($secretary);
        $this->actingAs($this->doctorUser())->get($url)->assertStatus($doctor);
    }

    public function test_guests_are_redirected_to_login(): void
    {
        foreach (['/patients', '/appointments', '/payments', '/users'] as $url) {
            $this->get($url)->assertRedirect('/login');
        }
    }

    public function test_inactive_user_cannot_log_in(): void
    {
        $user = User::factory()->inactive()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_user_deactivated_during_session_is_logged_out(): void
    {
        $user = $this->secretary();
        $this->actingAs($user)->get('/dashboard')->assertOk();

        $user->forceFill(['is_active' => false])->save();

        $this->actingAs($user->fresh())->get('/dashboard')->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_menu_only_shows_permitted_modules(): void
    {
        $this->actingAs($this->doctorUser())->get('/dashboard')
            ->assertSee('Tedaviler')
            ->assertDontSee('Kullanıcılar')
            ->assertDontSee('Ödemeler');

        $this->actingAs($this->admin())->get('/dashboard')
            ->assertSee('Kullanıcılar')
            ->assertSee('Klinik Bilgileri');
    }
}
