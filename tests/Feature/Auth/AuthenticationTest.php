<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        RateLimiter::clear('testuser|127.0.0.1');
        parent::tearDown();
    }

    /**
     * AUTH-01: Login Berhasil Kredensial Valid
     */
    public function test_auth_01_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'username' => 'pegawai_sumbawa',
            'password' => Hash::make('password123'),
            'is_active' => true,
        ]);

        $response = $this->post('/login', [
            'username' => 'pegawai_sumbawa',
            'password' => 'password123',
        ]);

        $response->assertStatus(302);
        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($user);
    }

    /**
     * AUTH-02: Login Gagal Password Salah
     */
    public function test_auth_02_user_cannot_login_with_incorrect_password(): void
    {
        User::factory()->create([
            'username' => 'pegawai_sumbawa',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->from('/login')->post('/login', [
            'username' => 'pegawai_sumbawa',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(302);
        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    /**
     * AUTH-03: Login Gagal Field Kosong
     */
    public function test_auth_03_login_fails_when_fields_are_empty(): void
    {
        $response = $this->from('/login')->post('/login', []);

        $response->assertStatus(302);
        $response->assertRedirect('/login');
        $response->assertSessionHasErrors(['username', 'password']);
        $this->assertGuest();
    }

    /**
     * AUTH-04: Percobaan SQL Injection pada Login
     */
    public function test_auth_04_sql_injection_attempt_is_safely_rejected(): void
    {
        User::factory()->create([
            'username' => 'pegawai_asli',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->from('/login')->post('/login', [
            'username' => "' OR '1'='1",
            'password' => 'random-pass',
        ]);

        $response->assertStatus(302);
        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    /**
     * AUTH-05: Logout Pengguna
     */
    public function test_auth_05_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $response->assertStatus(302);
        $response->assertRedirect('/login');
        $this->assertGuest();
    }

    /**
     * User dengan is_active = false tidak dapat login meskipun kredensial benar.
     */
    public function test_inactive_user_cannot_login(): void
    {
        User::factory()->inactive()->create([
            'username' => 'pegawai_nonaktif',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->from('/login')->post('/login', [
            'username' => 'pegawai_nonaktif',
            'password' => 'password123',
        ]);

        $response->assertStatus(302);
        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    /**
     * Pastikan login user nonaktif, password salah, dan username tidak ada menghasilkan pesan error yang sama persis.
     */
    public function test_login_error_message_is_identical_for_nonexistent_user_wrong_password_and_inactive_user(): void
    {
        User::factory()->create([
            'username' => 'user_aktif',
            'password' => Hash::make('password_benar'),
            'is_active' => true,
        ]);

        User::factory()->inactive()->create([
            'username' => 'user_nonaktif',
            'password' => Hash::make('password_benar'),
        ]);

        // 1. Username tidak ada
        $res1 = $this->post('/login', [
            'username' => 'user_tidak_ada_sama_sekali',
            'password' => 'apapun',
        ]);
        $res1->assertSessionHasErrors(['username' => trans('auth.failed')]);

        // 2. Password salah
        $res2 = $this->post('/login', [
            'username' => 'user_aktif',
            'password' => 'password_salah',
        ]);
        $res2->assertSessionHasErrors(['username' => trans('auth.failed')]);

        // 3. User nonaktif
        $res3 = $this->post('/login', [
            'username' => 'user_nonaktif',
            'password' => 'password_benar',
        ]);
        $res3->assertSessionHasErrors(['username' => trans('auth.failed')]);
    }

    /**
     * Middleware mengeluarkan user dengan is_active = false pada request berikutnya (invalidasi sesi).
     */
    public function test_middleware_logs_out_deactivated_user_on_next_request_with_generic_message(): void
    {
        $user = User::factory()->create([
            'is_active' => true,
        ]);

        // Login saat masih aktif
        $this->actingAs($user);
        $this->get('/dashboard')->assertStatus(200);

        // Administrator menonaktifkan user ini di database
        $user->update(['is_active' => false]);

        // Request berikutnya harus ditendang keluar ke /login dengan pesan generik
        $response = $this->get('/dashboard');

        $response->assertStatus(302);
        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('username');
        $this->assertSame(trans('auth.failed'), session('errors')->first('username'));
        $this->assertGuest();
    }

    /**
     * Throttle: 5 percobaan per menit per kombinasi username + IP.
     */
    public function test_login_attempts_are_throttled_after_five_failures(): void
    {
        User::factory()->create([
            'username' => 'throttled_user',
            'password' => Hash::make('password123'),
        ]);

        // Kirim 5 request dengan password salah
        for ($i = 0; $i < 5; $i++) {
            $response = $this->post('/login', [
                'username' => 'throttled_user',
                'password' => 'wrong_attempt',
            ]);
            $response->assertSessionHasErrors('username');
        }

        // Percobaan ke-6 harus di-throttle
        $response = $this->post('/login', [
            'username' => 'throttled_user',
            'password' => 'password123', // Bahkan dengan password yang benar
        ]);

        $response->assertStatus(302);
        $response->assertSessionHasErrors('username');
        $errorMessage = session('errors')->first('username');
        $this->assertStringContainsString('Terlalu banyak percobaan masuk', $errorMessage);
        $this->assertGuest();
    }

    /**
     * Regenerasi session saat login untuk mencegah session fixation.
     */
    public function test_session_is_regenerated_upon_login(): void
    {
        User::factory()->create([
            'username' => 'pegawai_secure',
            'password' => Hash::make('password123'),
        ]);

        $this->get('/login');
        $originalSessionId = session()->getId();

        $this->post('/login', [
            'username' => 'pegawai_secure',
            'password' => 'password123',
        ]);

        $newSessionId = session()->getId();
        $this->assertNotEquals($originalSessionId, $newSessionId);
    }
}
