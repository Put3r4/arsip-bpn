<?php

namespace Tests\Feature\Master;

use App\Models\ArchiveDocument;
use App\Models\LandRightType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandRightTypeTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_view_land_right_types_page_and_data(): void
    {
        $archivist = User::factory()->archivist()->create();
        LandRightType::factory()->count(3)->create();

        $pageResponse = $this->actingAs($archivist)->get('/master/land-right-types');
        $pageResponse->assertOk();

        $dataResponse = $this->actingAs($archivist)->getJson('/master/land-right-types/data');
        $dataResponse->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'documents_count', 'can_delete', 'created_at'],
                ],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);
    }

    public function test_employee_cannot_access_land_right_types(): void
    {
        $employee = User::factory()->employee()->create();

        $this->actingAs($employee)->get('/master/land-right-types')->assertForbidden();
        $this->actingAs($employee)->getJson('/master/land-right-types/data')->assertForbidden();
        $this->actingAs($employee)->postJson('/master/land-right-types', ['name' => 'HAK TEST'])->assertForbidden();
    }

    public function test_staff_can_create_land_right_type(): void
    {
        $archivist = User::factory()->archivist()->create();

        $response = $this->actingAs($archivist)->postJson('/master/land-right-types', [
            'name' => 'Hak Wakaf (HW)',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('land_right_types', [
            'name' => 'Hak Wakaf (HW)',
        ]);
    }

    public function test_land_right_type_creation_validates_name_uniqueness(): void
    {
        $archivist = User::factory()->archivist()->create();
        LandRightType::factory()->create(['name' => 'HAK DUPLIKAT']);

        $response = $this->actingAs($archivist)->postJson('/master/land-right-types', [
            'name' => 'HAK DUPLIKAT',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_staff_can_update_land_right_type(): void
    {
        $archivist = User::factory()->archivist()->create();
        $right = LandRightType::factory()->create(['name' => 'HAK LAMA']);

        $response = $this->actingAs($archivist)->patchJson("/master/land-right-types/{$right->id}", [
            'name' => 'HAK BARU',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('land_right_types', [
            'id' => $right->id,
            'name' => 'HAK BARU',
        ]);
    }

    public function test_land_right_type_with_documents_cannot_be_deleted(): void
    {
        $archivist = User::factory()->archivist()->create();
        $right = LandRightType::factory()->create(['name' => 'HAK BERISI']);
        ArchiveDocument::factory()->create(['land_right_type_id' => $right->id]);

        $response = $this->actingAs($archivist)->deleteJson("/master/land-right-types/{$right->id}");

        $response->assertStatus(422)
            ->assertJson([
                'status' => 'error',
                'message' => 'Jenis hak tanah tidak dapat dihapus karena masih digunakan oleh dokumen arsip.',
            ]);

        $this->assertDatabaseHas('land_right_types', [
            'id' => $right->id,
        ]);
    }

    public function test_empty_land_right_type_can_be_deleted(): void
    {
        $archivist = User::factory()->archivist()->create();
        $right = LandRightType::factory()->create(['name' => 'HAK KOSONG']);

        $response = $this->actingAs($archivist)->deleteJson("/master/land-right-types/{$right->id}");

        $response->assertOk()
            ->assertJson(['status' => 'success']);

        $this->assertDatabaseMissing('land_right_types', [
            'id' => $right->id,
        ]);
    }
}
