<?php

namespace Tests\Feature\Security;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityHeadersAndHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_web_routes_include_foundational_security_headers(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-XSS-Protection', '1; mode=block');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
    }

    public function test_authenticated_routes_preserve_security_headers(): void
    {
        $user = User::factory()->superAdmin()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-XSS-Protection', '1; mode=block');
    }

    public function test_csrf_meta_token_rendered_in_layouts(): void
    {
        $guestResponse = $this->get('/login');
        $guestResponse->assertSee('name="csrf-token"', false);

        $user = User::factory()->superAdmin()->create();
        $authResponse = $this->actingAs($user)->get('/dashboard');
        $authResponse->assertSee('name="csrf-token"', false);
    }

    public function test_app_name_is_used_as_page_title(): void
    {
        config(['app.name' => 'Sistem Informasi Arsip BPN Sumbawa']);

        $response = $this->get('/login');
        $response->assertSee('<title>Masuk - Sistem Informasi Arsip BPN Sumbawa</title>', false);
    }
}
