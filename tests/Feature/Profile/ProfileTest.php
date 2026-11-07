<?php

namespace Tests\Feature\Profile;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_profile_page(): void
    {
        $user = User::factory()->create([
            'name' => 'Siti Aminah',
            'username' => 'siti_aminah',
        ]);

        $response = $this->actingAs($user)->get('/profile');

        $response->assertStatus(200);
        $response->assertSee('Pengaturan Akun & Profil', false);
        $response->assertSee('Siti Aminah');
        $response->assertSee('@siti_aminah');
    }

    public function test_user_can_update_password_with_correct_current_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('old_password_123'),
        ]);

        $response = $this->actingAs($user)->patch('/profile/password', [
            'current_password' => 'old_password_123',
            'password' => 'new_secure_pass123',
            'password_confirmation' => 'new_secure_pass123',
        ]);

        $response->assertRedirect('/profile');
        $this->assertTrue(Hash::check('new_secure_pass123', $user->fresh()->password));
    }

    public function test_user_cannot_update_password_with_incorrect_current_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('old_password_123'),
        ]);

        $response = $this->actingAs($user)->patch('/profile/password', [
            'current_password' => 'wrong_current_password',
            'password' => 'new_secure_pass123',
            'password_confirmation' => 'new_secure_pass123',
        ]);

        $response->assertSessionHasErrors('current_password');
        $this->assertTrue(Hash::check('old_password_123', $user->fresh()->password));
    }

    public function test_user_cannot_reuse_same_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('old_password_123'),
        ]);

        $response = $this->actingAs($user)->patch('/profile/password', [
            'current_password' => 'old_password_123',
            'password' => 'old_password_123',
            'password_confirmation' => 'old_password_123',
        ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_theme_update_endpoint_persists_theme_to_user_record(): void
    {
        $user = User::factory()->create(['theme' => 'samudra']);

        $response = $this->actingAs($user)->patchJson('/profile/theme', [
            'theme' => 'marun',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'theme' => 'marun',
        ]);

        $this->assertSame('marun', $user->fresh()->theme);
    }

    public function test_theme_update_rejects_unwhitelisted_values(): void
    {
        $user = User::factory()->create(['theme' => 'samudra']);

        $response = $this->actingAs($user)->patchJson('/profile/theme', [
            'theme' => 'cyberpunk_neon',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('theme');
        $this->assertSame('samudra', $user->fresh()->theme);
    }

    public function test_guest_is_redirected_when_accessing_profile(): void
    {
        $response = $this->get('/profile');
        $response->assertRedirect('/login');

        $patchResponse = $this->patch('/profile/password', []);
        $patchResponse->assertRedirect('/login');

        $themeResponse = $this->patchJson('/profile/theme', ['theme' => 'marun']);
        $themeResponse->assertStatus(401);
    }

    /**
     * Endpoint PATCH /profile/password dengan field ekstra tidak mengubah role, is_active, username, atau section_id.
     */
    public function test_password_update_ignores_extra_mass_assignment_fields(): void
    {
        $user = User::factory()->employee()->create([
            'username' => 'pegawai_asli',
            'role' => \App\Enums\UserRole::EMPLOYEE,
            'is_active' => true,
            'section_id' => null,
            'password' => Hash::make('old_password_123'),
        ]);

        $response = $this->actingAs($user)->patch('/profile/password', [
            'current_password' => 'old_password_123',
            'password' => 'new_password_888',
            'password_confirmation' => 'new_password_888',
            // Field ekstra yang dicoba disusupkan:
            'role' => \App\Enums\UserRole::SUPER_ADMIN->value,
            'is_active' => false,
            'username' => 'hacked_username',
            'section_id' => 999,
        ]);

        $response->assertRedirect('/profile');

        $fresh = $user->fresh();
        $this->assertTrue(Hash::check('new_password_888', $fresh->password));
        $this->assertSame(\App\Enums\UserRole::EMPLOYEE, $fresh->role);
        $this->assertTrue($fresh->is_active);
        $this->assertSame('pegawai_asli', $fresh->username);
        $this->assertNull($fresh->section_id);
    }

    /**
     * Endpoint PATCH /profile/theme dengan field ekstra tidak mengubah role, is_active, username, atau section_id.
     */
    public function test_theme_update_ignores_extra_mass_assignment_fields(): void
    {
        $user = User::factory()->employee()->create([
            'username' => 'pegawai_asli2',
            'role' => \App\Enums\UserRole::EMPLOYEE,
            'is_active' => true,
            'section_id' => null,
            'theme' => 'samudra',
        ]);

        $response = $this->actingAs($user)->patchJson('/profile/theme', [
            'theme' => 'marun',
            // Field ekstra:
            'role' => \App\Enums\UserRole::SUPER_ADMIN->value,
            'is_active' => false,
            'username' => 'hacked_username2',
            'section_id' => 888,
        ]);

        $response->assertStatus(200);

        $fresh = $user->fresh();
        $this->assertSame('marun', $fresh->theme);
        $this->assertSame(\App\Enums\UserRole::EMPLOYEE, $fresh->role);
        $this->assertTrue($fresh->is_active);
        $this->assertSame('pegawai_asli2', $fresh->username);
        $this->assertNull($fresh->section_id);
    }
}
