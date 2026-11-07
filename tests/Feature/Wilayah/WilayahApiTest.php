<?php

namespace Tests\Feature\Wilayah;

use App\Models\District;
use App\Models\Section;
use App\Models\User;
use App\Models\Village;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WilayahApiTest extends TestCase
{
    use RefreshDatabase;

    /**
     * WIL-01: API Desa Berdasarkan Kecamatan (Happy Path)
     * GET /api/districts/5204020/villages -> 200 JSON list desa di Lunyuk.
     */
    public function test_wil_01_api_returns_villages_for_valid_district(): void
    {
        $employee = User::factory()->employee()->create();
        $district = District::factory()->create([
            'id' => '5204020',
            'name' => 'LUNYUK',
        ]);

        Village::factory()->create([
            'id' => '5204020001',
            'district_id' => $district->id,
            'name' => 'LUNYUK REA',
        ]);
        Village::factory()->create([
            'id' => '5204020002',
            'district_id' => $district->id,
            'name' => 'LUNYUK ODE',
        ]);

        $response = $this->actingAs($employee)->getJson("/api/districts/{$district->id}/villages");

        $response->assertOk()
            ->assertJsonCount(2)
            ->assertJsonFragment(['id' => '5204020001', 'name' => 'LUNYUK REA'])
            ->assertJsonFragment(['id' => '5204020002', 'name' => 'LUNYUK ODE']);
    }

    /**
     * WIL-02: API Desa Kecamatan Tidak Valid (Kasus Tepi)
     * GET /api/districts/9999999/villages -> 404 Not Found.
     */
    public function test_wil_02_api_returns_404_for_invalid_district(): void
    {
        $employee = User::factory()->employee()->create();

        $response = $this->actingAs($employee)->getJson('/api/districts/9999999/villages');

        $response->assertNotFound();
    }

    /**
     * Pengguna anonim (guest) dilarang mengakses API wilayah (harus login).
     */
    public function test_guest_is_redirected_when_accessing_wilayah_api(): void
    {
        $response = $this->get('/api/districts/5204020/villages');

        $response->assertRedirect('/login');
    }

    /**
     * API Pegawai Berdasarkan Seksi (A5 butir 12).
     * GET /api/sections/{section}/users -> 200 JSON list pegawai aktif.
     */
    public function test_api_returns_active_users_for_valid_section(): void
    {
        $archivist = User::factory()->archivist()->create();
        $section = Section::factory()->create(['name' => 'Seksi Survei dan Pemetaan']);

        $user1 = User::factory()->employee()->create([
            'section_id' => $section->id,
            'name' => 'Budi Santoso',
            'is_active' => true,
        ]);
        $user2 = User::factory()->employee()->create([
            'section_id' => $section->id,
            'name' => 'Dewi Sartika',
            'is_active' => true,
        ]);
        // User nonaktif tidak boleh masuk
        User::factory()->employee()->create([
            'section_id' => $section->id,
            'name' => 'Pegawai Nonaktif',
            'is_active' => false,
        ]);

        $response = $this->actingAs($archivist)->getJson("/api/sections/{$section->id}/users");

        $response->assertOk()
            ->assertJsonCount(2)
            ->assertJsonFragment(['id' => $user1->id, 'name' => 'Budi Santoso'])
            ->assertJsonFragment(['id' => $user2->id, 'name' => 'Dewi Sartika'])
            ->assertJsonMissing(['name' => 'Pegawai Nonaktif']);
    }

    /**
     * API Seksi ID tidak valid menghasilkan 404.
     */
    public function test_api_returns_404_for_invalid_section(): void
    {
        $archivist = User::factory()->archivist()->create();

        $response = $this->actingAs($archivist)->getJson('/api/sections/999999/users');

        $response->assertNotFound();
    }
}
