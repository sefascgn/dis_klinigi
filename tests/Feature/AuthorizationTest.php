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
            'dashboard' => ['/panel', 200, 200, 200],
            'hastalar' => ['/hastalar', 200, 200, 200],
            'hasta ekle' => ['/hastalar/yeni', 200, 200, 200],
            'doktorlar' => ['/doktorlar', 200, 200, 200],
            'doktor ekle' => ['/doktorlar/yeni', 200, 403, 403],
            'randevular' => ['/randevular', 200, 200, 200],
            'randevu ekle' => ['/randevular/yeni', 200, 200, 403],
            'tedaviler' => ['/tedaviler', 200, 200, 200],
            'tedavi ekle' => ['/tedaviler/yeni', 200, 403, 200],
            'ödemeler' => ['/odemeler', 200, 200, 403],
            'ödeme al' => ['/odemeler/yeni', 200, 200, 403],
            'işlem tanımları' => ['/islem-turleri', 200, 403, 403],
            'kullanıcılar' => ['/kullanicilar', 200, 403, 403],
            'klinik bilgileri' => ['/ayarlar/klinik', 200, 403, 403],
            'raporlar' => ['/raporlar', 200, 403, 403],
            'giderler' => ['/giderler', 200, 403, 403],
            'gider ekle' => ['/giderler/yeni', 200, 403, 403],
            'kontrolü gelenler' => ['/hastalar?filter=recall', 200, 200, 200],
            'borçlular' => ['/hastalar?filter=debtors', 200, 200, 403],
            'profil' => ['/profil', 200, 200, 200],
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
        foreach (['/hastalar', '/randevular', '/odemeler', '/kullanicilar'] as $url) {
            $this->get($url)->assertRedirect('/giris');
        }
    }

    public function test_inactive_user_cannot_log_in(): void
    {
        $user = User::factory()->inactive()->create();

        $this->post('/giris', ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_user_deactivated_during_session_is_logged_out(): void
    {
        $user = $this->secretary();
        $this->actingAs($user)->get('/panel')->assertOk();

        $user->forceFill(['is_active' => false])->save();

        $this->actingAs($user->fresh())->get('/panel')->assertRedirect('/giris');
        $this->assertGuest();
    }

    public function test_menu_only_shows_permitted_modules(): void
    {
        $this->actingAs($this->doctorUser())->get('/panel')
            ->assertSee('Tedaviler')
            ->assertDontSee('Kullanıcılar')
            ->assertDontSee('Ödemeler');

        $this->actingAs($this->admin())->get('/panel')
            ->assertSee('Kullanıcılar')
            ->assertSee('Klinik Bilgileri');
    }
}
