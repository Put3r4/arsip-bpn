<?php

namespace Tests\Feature\Master;

use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SectionManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_view_sections_page_and_data_endpoint(): void
    {
        $admin = User::factory()->superAdmin()->create();
        Section::factory()->count(3)->create();

        // View Blade Page
        $response = $this->actingAs($admin)->get('/master/sections');
        $response->assertStatus(200);
        $response->assertSee('Master Seksi Organisasi');

        // JSON Data Table Endpoint
        $jsonResponse = $this->actingAs($admin)->getJson('/master/sections/data');
        $jsonResponse->assertStatus(200);
        $jsonResponse->assertJsonStructure([
            'data' => [
                '*' => ['id', 'name', 'users_count', 'can_delete', 'created_at']
            ],
            'meta' => ['current_page', 'last_page', 'per_page', 'total']
        ]);
    }

    public function test_super_admin_can_create_section_with_valid_data(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $response = $this->actingAs($admin)->post('/master/sections', [
            'name' => 'Seksi Survei dan Pemetaan Baru',
        ]);

        $response->assertRedirect('/master/sections');
        $this->assertDatabaseHas('sections', [
            'name' => 'Seksi Survei dan Pemetaan Baru',
        ]);
    }

    public function test_section_name_must_be_unique(): void
    {
        $admin = User::factory()->superAdmin()->create();
        Section::factory()->create(['name' => 'Seksi Tata Usaha']);

        $response = $this->actingAs($admin)->post('/master/sections', [
            'name' => 'Seksi Tata Usaha',
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_super_admin_can_update_section(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $section = Section::factory()->create(['name' => 'Seksi Lama']);

        $response = $this->actingAs($admin)->patch("/master/sections/{$section->id}", [
            'name' => 'Seksi Diperbarui',
        ]);

        $response->assertRedirect('/master/sections');
        $this->assertSame('Seksi Diperbarui', $section->fresh()->name);
    }

    public function test_section_with_assigned_users_cannot_be_deleted(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $section = Section::factory()->create(['name' => 'Seksi Penting']);
        User::factory()->create(['section_id' => $section->id]);

        $response = $this->actingAs($admin)->delete("/master/sections/{$section->id}");

        $response->assertRedirect('/master/sections');
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('sections', ['id' => $section->id]);
    }

    public function test_section_with_assigned_users_returns_422_when_requested_via_json(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $section = Section::factory()->create(['name' => 'Seksi Terhubung']);
        User::factory()->create(['section_id' => $section->id]);

        $response = $this->actingAs($admin)->deleteJson("/master/sections/{$section->id}");

        $response->assertStatus(422);
        $response->assertJson([
            'status' => 'error',
            'message' => 'Seksi tidak dapat dihapus karena masih digunakan oleh pengguna.',
        ]);
        $this->assertDatabaseHas('sections', ['id' => $section->id]);
    }

    public function test_empty_section_can_be_deleted(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $section = Section::factory()->create(['name' => 'Seksi Kosong']);

        $response = $this->actingAs($admin)->delete("/master/sections/{$section->id}");

        $response->assertRedirect('/master/sections');
        $this->assertDatabaseMissing('sections', ['id' => $section->id]);
    }
}
