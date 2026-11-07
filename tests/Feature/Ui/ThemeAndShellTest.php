<?php

namespace Tests\Feature\Ui;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ThemeAndShellTest extends TestCase
{
    use RefreshDatabase;

    public function test_root_redirects_to_dashboard(): void
    {
        $response = $this->get('/');
        $response->assertRedirect('/dashboard');
    }

    public function test_dashboard_renders_with_shell_and_tokens(): void
    {
        $response = $this->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('ARSIP BPN');
        $response->assertSee('Kantah Kabupaten Sumbawa');
        $response->assertSee('Dashboard Ringkasan');
        $response->assertSee('Tema Samudra');
        $response->assertSee('Tema Marun');
        $response->assertSee('Tema Senja');
    }

    public function test_login_page_renders_successfully(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertSee('Sistem Informasi Arsip');
        $response->assertSee('Nama Pengguna (Username)');
        $response->assertSee('Kata Sandi');
    }

    public function test_theme_patch_endpoint_accepts_whitelisted_themes(): void
    {
        $user = User::factory()->create(['theme' => 'samudra']);

        // Authenticated user updates theme to 'marun'
        $response = $this->actingAs($user)->patchJson('/profile/theme', [
            'theme' => 'marun',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'theme' => 'marun',
        ]);

        $this->assertSame('marun', $user->fresh()->theme);

        // Update to 'senja'
        $response = $this->actingAs($user)->patchJson('/profile/theme', [
            'theme' => 'senja',
        ]);
        $response->assertStatus(200);
        $this->assertSame('senja', $user->fresh()->theme);
    }

    public function test_theme_patch_endpoint_rejects_unwhitelisted_theme(): void
    {
        $user = User::factory()->create(['theme' => 'samudra']);

        $response = $this->actingAs($user)->patchJson('/profile/theme', [
            'theme' => 'invalid_neon_theme',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['theme']);
        $this->assertSame('samudra', $user->fresh()->theme);
    }

    public function test_built_css_contains_theme_tokens(): void
    {
        $buildDir = public_path('build/assets');
        $this->assertTrue(File::isDirectory($buildDir), 'Vite build assets directory must exist.');

        $cssFiles = File::glob($buildDir . '/*.css');
        $this->assertNotEmpty($cssFiles, 'Vite build must produce compiled CSS file.');

        $cssContent = File::get($cssFiles[0]);

        $this->assertStringContainsString('--color-primary', $cssContent, 'CSS must contain semantic token --color-primary');
        $this->assertStringContainsString('--color-accent', $cssContent, 'CSS must contain semantic token --color-accent');
        $this->assertStringContainsString('--color-surface', $cssContent, 'CSS must contain semantic token --color-surface');
        $this->assertStringContainsString('--color-danger', $cssContent, 'CSS must contain semantic token --color-danger');
        $this->assertStringContainsString('data-theme', $cssContent, 'CSS must contain data-theme rules');
    }
}
