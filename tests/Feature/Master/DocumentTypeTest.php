<?php

namespace Tests\Feature\Master;

use App\Models\ArchiveDocument;
use App\Models\DocumentType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentTypeTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_view_document_types_page_and_data(): void
    {
        $archivist = User::factory()->archivist()->create();
        DocumentType::factory()->count(3)->create();

        $pageResponse = $this->actingAs($archivist)->get('/master/document-types');
        $pageResponse->assertOk();

        $dataResponse = $this->actingAs($archivist)->getJson('/master/document-types/data');
        $dataResponse->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'documents_count', 'can_delete', 'created_at'],
                ],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);
    }

    public function test_employee_cannot_access_document_types(): void
    {
        $employee = User::factory()->employee()->create();

        $this->actingAs($employee)->get('/master/document-types')->assertForbidden();
        $this->actingAs($employee)->getJson('/master/document-types/data')->assertForbidden();
        $this->actingAs($employee)->postJson('/master/document-types', ['name' => 'TIPE TEST'])->assertForbidden();
    }

    public function test_staff_can_create_document_type(): void
    {
        $archivist = User::factory()->archivist()->create();

        $response = $this->actingAs($archivist)->postJson('/master/document-types', [
            'name' => 'Warkah Khusus',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('document_types', [
            'name' => 'Warkah Khusus',
        ]);
    }

    public function test_document_type_creation_validates_name_uniqueness(): void
    {
        $archivist = User::factory()->archivist()->create();
        DocumentType::factory()->create(['name' => 'TIPE DUPLIKAT']);

        $response = $this->actingAs($archivist)->postJson('/master/document-types', [
            'name' => 'TIPE DUPLIKAT',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_staff_can_update_document_type(): void
    {
        $archivist = User::factory()->archivist()->create();
        $type = DocumentType::factory()->create(['name' => 'TIPE LAMA']);

        $response = $this->actingAs($archivist)->patchJson("/master/document-types/{$type->id}", [
            'name' => 'TIPE BARU',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('document_types', [
            'id' => $type->id,
            'name' => 'TIPE BARU',
        ]);
    }

    public function test_document_type_with_documents_cannot_be_deleted(): void
    {
        $archivist = User::factory()->archivist()->create();
        $type = DocumentType::factory()->create(['name' => 'TIPE BERISI']);
        ArchiveDocument::factory()->create(['document_type_id' => $type->id]);

        $response = $this->actingAs($archivist)->deleteJson("/master/document-types/{$type->id}");

        $response->assertStatus(422)
            ->assertJson([
                'status' => 'error',
                'message' => 'Jenis berkas tidak dapat dihapus karena masih digunakan oleh dokumen arsip.',
            ]);

        $this->assertDatabaseHas('document_types', [
            'id' => $type->id,
        ]);
    }

    public function test_empty_document_type_can_be_deleted(): void
    {
        $archivist = User::factory()->archivist()->create();
        $type = DocumentType::factory()->create(['name' => 'TIPE KOSONG']);

        $response = $this->actingAs($archivist)->deleteJson("/master/document-types/{$type->id}");

        $response->assertOk()
            ->assertJson(['status' => 'success']);

        $this->assertDatabaseMissing('document_types', [
            'id' => $type->id,
        ]);
    }
}
