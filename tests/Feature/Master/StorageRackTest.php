<?php

namespace Tests\Feature\Master;

use App\Models\ArchiveDocument;
use App\Models\StorageRack;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorageRackTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_view_storage_racks_page_and_data(): void
    {
        $archivist = User::factory()->archivist()->create();
        StorageRack::factory()->count(3)->create();

        $pageResponse = $this->actingAs($archivist)->get('/master/storage-racks');
        $pageResponse->assertOk();

        $dataResponse = $this->actingAs($archivist)->getJson('/master/storage-racks/data');
        $dataResponse->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'documents_count', 'can_delete', 'created_at'],
                ],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);
    }

    public function test_employee_cannot_access_storage_racks(): void
    {
        $employee = User::factory()->employee()->create();

        $this->actingAs($employee)->get('/master/storage-racks')->assertForbidden();
        $this->actingAs($employee)->getJson('/master/storage-racks/data')->assertForbidden();
        $this->actingAs($employee)->postJson('/master/storage-racks', ['name' => 'RAK TEST'])->assertForbidden();
    }

    public function test_staff_can_create_storage_rack(): void
    {
        $archivist = User::factory()->archivist()->create();

        $response = $this->actingAs($archivist)->postJson('/master/storage-racks', [
            'name' => 'RAK 99',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('storage_racks', [
            'name' => 'RAK 99',
        ]);
    }

    public function test_storage_rack_creation_validates_name_uniqueness(): void
    {
        $archivist = User::factory()->archivist()->create();
        StorageRack::factory()->create(['name' => 'RAK DUPLIKAT']);

        $response = $this->actingAs($archivist)->postJson('/master/storage-racks', [
            'name' => 'RAK DUPLIKAT',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_staff_can_update_storage_rack(): void
    {
        $archivist = User::factory()->archivist()->create();
        $rack = StorageRack::factory()->create(['name' => 'RAK LAMA']);

        $response = $this->actingAs($archivist)->patchJson("/master/storage-racks/{$rack->id}", [
            'name' => 'RAK BARU',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('storage_racks', [
            'id' => $rack->id,
            'name' => 'RAK BARU',
        ]);
    }

    public function test_storage_rack_with_documents_cannot_be_deleted(): void
    {
        $archivist = User::factory()->archivist()->create();
        $rack = StorageRack::factory()->create(['name' => 'RAK BERISI']);
        ArchiveDocument::factory()->create(['storage_rack_id' => $rack->id]);

        $response = $this->actingAs($archivist)->deleteJson("/master/storage-racks/{$rack->id}");

        $response->assertStatus(422)
            ->assertJson([
                'status' => 'error',
                'message' => 'Rak penyimpanan tidak dapat dihapus karena masih digunakan oleh dokumen arsip.',
            ]);

        $this->assertDatabaseHas('storage_racks', [
            'id' => $rack->id,
        ]);
    }

    public function test_empty_storage_rack_can_be_deleted(): void
    {
        $archivist = User::factory()->archivist()->create();
        $rack = StorageRack::factory()->create(['name' => 'RAK KOSONG']);

        $response = $this->actingAs($archivist)->deleteJson("/master/storage-racks/{$rack->id}");

        $response->assertOk()
            ->assertJson(['status' => 'success']);

        $this->assertDatabaseMissing('storage_racks', [
            'id' => $rack->id,
        ]);
    }
}
