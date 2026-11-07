<?php

namespace Tests\Feature\ArchiveDocument;

use App\Enums\DocumentStatus;
use App\Models\ArchiveDocument;
use App\Models\District;
use App\Models\DocumentLoan;
use App\Models\DocumentType;
use App\Models\LandRightType;
use App\Models\StorageRack;
use App\Models\User;
use App\Models\Village;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ArchiveDocumentTest extends TestCase
{
    use RefreshDatabase;

    private District $district;
    private Village $village;
    private DocumentType $docType;
    private LandRightType $landRight;
    private StorageRack $rack;

    protected function setUp(): void
    {
        parent::setUp();

        $this->district = District::factory()->create(['id' => '5204020']);
        $this->village = Village::factory()->create([
            'id' => '5204020001',
            'district_id' => $this->district->id,
        ]);
        $this->docType = DocumentType::factory()->create();
        $this->landRight = LandRightType::factory()->create();
        $this->rack = StorageRack::factory()->create();
    }

    /**
     * DOC-01: Admin Tambah Berkas dengan PDF (Happy Path)
     * Admin/archivist mengirim POST /documents dengan PDF valid (1 MB).
     * Dokumen tersimpan status 'available', file tersimpan di private disk.
     */
    public function test_doc_01_archivist_can_create_document_with_valid_pdf(): void
    {
        Storage::fake('local');
        $archivist = User::factory()->archivist()->create();

        $file = UploadedFile::fake()->create('warkah_tanah.pdf', 1024, 'application/pdf');

        $response = $this->actingAs($archivist)->post('/documents', [
            'document_number' => '12345',
            'year' => 2024,
            'document_type_id' => $this->docType->id,
            'land_right_type_id' => $this->landRight->id,
            'storage_rack_id' => $this->rack->id,
            'range_number' => '01-20',
            'district_id' => $this->district->id,
            'village_id' => $this->village->id,
            'file' => $file,
        ]);

        $response->assertRedirect('/documents');

        $document = ArchiveDocument::where('document_number', '12345')->first();
        $this->assertNotNull($document);
        $this->assertSame(DocumentStatus::AVAILABLE, $document->status);
        $this->assertNotNull($document->file_path);
        $this->assertSame('warkah_tanah.pdf', $document->original_filename);
        $this->assertSame('application/pdf', $document->mime_type);
        $this->assertGreaterThan(0, $document->file_size);

        Storage::disk('local')->assertExists($document->file_path);
    }

    /**
     * DOC-02: Admin Tambah Berkas Tanpa File (Happy Path)
     * Admin mengirim POST /documents tanpa file upload.
     * Dokumen tersimpan dengan file_path = NULL, status 'available'.
     */
    public function test_doc_02_archivist_can_create_document_without_file(): void
    {
        $archivist = User::factory()->archivist()->create();

        $response = $this->actingAs($archivist)->post('/documents', [
            'document_number' => '67890',
            'year' => 2023,
            'document_type_id' => $this->docType->id,
            'land_right_type_id' => $this->landRight->id,
            'storage_rack_id' => $this->rack->id,
            'range_number' => '21-40',
            'district_id' => $this->district->id,
            'village_id' => $this->village->id,
        ]);

        $response->assertRedirect('/documents');

        $document = ArchiveDocument::where('document_number', '67890')->first();
        $this->assertNotNull($document);
        $this->assertSame(DocumentStatus::AVAILABLE, $document->status);
        $this->assertNull($document->file_path);
        $this->assertNull($document->original_filename);
    }

    /**
     * DOC-03: Upload File Ekstensi Terlarang (Validasi)
     * Upload .exe atau .php harus ditolak validasi (422) dan file tidak tersimpan ke disk.
     */
    public function test_doc_03_forbidden_file_extension_is_rejected(): void
    {
        Storage::fake('local');
        $archivist = User::factory()->archivist()->create();

        $forbiddenFile = UploadedFile::fake()->create('malicious.php', 100, 'application/x-php');

        $response = $this->actingAs($archivist)->postJson('/documents', [
            'document_number' => '99991',
            'year' => 2024,
            'document_type_id' => $this->docType->id,
            'land_right_type_id' => $this->landRight->id,
            'storage_rack_id' => $this->rack->id,
            'district_id' => $this->district->id,
            'village_id' => $this->village->id,
            'file' => $forbiddenFile,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file']);

        $this->assertDatabaseMissing('archive_documents', [
            'document_number' => '99991',
        ]);
    }

    /**
     * DOC-04: Upload File Melebihi Batas 20 MB (Validasi)
     * File > 20 MB (20480 KB) harus ditolak validasi (422).
     */
    public function test_doc_04_file_exceeding_20mb_is_rejected(): void
    {
        Storage::fake('local');
        $archivist = User::factory()->archivist()->create();

        // 25 MB = 25600 KB
        $largeFile = UploadedFile::fake()->create('large_warkah.pdf', 25600, 'application/pdf');

        $response = $this->actingAs($archivist)->postJson('/documents', [
            'document_number' => '99992',
            'year' => 2024,
            'document_type_id' => $this->docType->id,
            'land_right_type_id' => $this->landRight->id,
            'storage_rack_id' => $this->rack->id,
            'district_id' => $this->district->id,
            'village_id' => $this->village->id,
            'file' => $largeFile,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    /**
     * DOC-05: Pegawai Biasa Tambah Dokumen (Hak Akses)
     * User dengan role employee mengirim POST /documents -> 403 Forbidden.
     */
    public function test_doc_05_employee_cannot_create_document(): void
    {
        $employee = User::factory()->employee()->create();

        $response = $this->actingAs($employee)->post('/documents', [
            'document_number' => '55555',
            'year' => 2024,
            'document_type_id' => $this->docType->id,
            'land_right_type_id' => $this->landRight->id,
            'storage_rack_id' => $this->rack->id,
            'district_id' => $this->district->id,
            'village_id' => $this->village->id,
        ]);

        $response->assertForbidden();
    }

    /**
     * DOC-06: Admin Ubah Status Fisik Berkas (Happy Path)
     * Admin mengirim PATCH /documents/{id}/status dengan status 'lost' atau 'invalid'.
     */
    public function test_doc_06_archivist_can_update_physical_status(): void
    {
        $archivist = User::factory()->archivist()->create();
        $document = ArchiveDocument::factory()->available()->create([
            'district_id' => $this->district->id,
            'village_id' => $this->village->id,
            'document_type_id' => $this->docType->id,
            'land_right_type_id' => $this->landRight->id,
            'storage_rack_id' => $this->rack->id,
        ]);

        $response = $this->actingAs($archivist)->patchJson("/documents/{$document->id}/status", [
            'status' => 'lost',
        ]);

        $response->assertOk();
        $this->assertSame(DocumentStatus::LOST, $document->fresh()->status);

        // Ubah lagi ke invalid
        $this->actingAs($archivist)->patchJson("/documents/{$document->id}/status", [
            'status' => 'invalid',
        ])->assertOk();
        $this->assertSame(DocumentStatus::INVALID, $document->fresh()->status);

        // Ubah kembali ke available
        $this->actingAs($archivist)->patchJson("/documents/{$document->id}/status", [
            'status' => 'available',
        ])->assertOk();
        $this->assertSame(DocumentStatus::AVAILABLE, $document->fresh()->status);
    }

    /**
     * DOC-07: Hapus Dokumen yang Sedang Dipinjam (Kasus Tepi)
     * Hapus dokumen yang memiliki pinjam aktif ditolak (422).
     */
    public function test_doc_07_cannot_delete_document_with_active_loan(): void
    {
        $archivist = User::factory()->archivist()->create();
        $document = ArchiveDocument::factory()->borrowed()->create([
            'district_id' => $this->district->id,
            'village_id' => $this->village->id,
            'document_type_id' => $this->docType->id,
            'land_right_type_id' => $this->landRight->id,
            'storage_rack_id' => $this->rack->id,
        ]);

        // Berikan pinjaman aktif berstatus borrowed
        DocumentLoan::factory()->borrowed()->create([
            'archive_document_id' => $document->id,
        ]);

        $response = $this->actingAs($archivist)->deleteJson("/documents/{$document->id}");

        $response->assertStatus(422)
            ->assertJson([
                'status' => 'error',
                'message' => 'Dokumen tidak dapat dihapus karena sedang dalam peminjaman aktif.',
            ]);

        $this->assertNotSoftDeleted($document);
    }

    /**
     * Status fisik dokumen tidak dapat diubah jika memiliki pinjaman aktif (A5 butir 5).
     */
    public function test_cannot_update_physical_status_when_active_loan_exists(): void
    {
        $archivist = User::factory()->archivist()->create();
        $document = ArchiveDocument::factory()->borrowed()->create([
            'district_id' => $this->district->id,
            'village_id' => $this->village->id,
            'document_type_id' => $this->docType->id,
            'land_right_type_id' => $this->landRight->id,
            'storage_rack_id' => $this->rack->id,
        ]);

        DocumentLoan::factory()->borrowed()->create([
            'archive_document_id' => $document->id,
        ]);

        $response = $this->actingAs($archivist)->patchJson("/documents/{$document->id}/status", [
            'status' => 'lost',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'status' => 'error',
                'message' => 'Status fisik dokumen tidak dapat diubah karena sedang dalam peminjaman aktif.',
            ]);
    }

    /**
     * Dokumen tanpa pinjaman aktif dapat dihapus (soft delete).
     */
    public function test_document_without_active_loans_can_be_soft_deleted(): void
    {
        $archivist = User::factory()->archivist()->create();
        $document = ArchiveDocument::factory()->available()->create([
            'district_id' => $this->district->id,
            'village_id' => $this->village->id,
            'document_type_id' => $this->docType->id,
            'land_right_type_id' => $this->landRight->id,
            'storage_rack_id' => $this->rack->id,
        ]);

        $response = $this->actingAs($archivist)->deleteJson("/documents/{$document->id}");

        $response->assertOk()
            ->assertJson(['status' => 'success']);

        $this->assertSoftDeleted($document);
    }

    /**
     * Normalisasi input: tahun "-" atau string kosong menjadi NULL, trim spasi.
     */
    public function test_input_normalization_sets_dash_year_to_null_and_trims(): void
    {
        $archivist = User::factory()->archivist()->create();

        $response = $this->actingAs($archivist)->postJson('/documents', [
            'document_number' => '  77788  ',
            'year' => '-',
            'document_type_id' => $this->docType->id,
            'land_right_type_id' => $this->landRight->id,
            'storage_rack_id' => $this->rack->id,
            'range_number' => '  05-15  ',
            'district_id' => $this->district->id,
            'village_id' => $this->village->id,
        ]);

        $response->assertCreated();

        $doc = ArchiveDocument::where('document_number', '77788')->first();
        $this->assertNotNull($doc);
        $this->assertSame('77788', $doc->document_number);
        $this->assertSame('05-15', $doc->range_number);
        $this->assertNull($doc->year);
    }

    /**
     * Keamanan: Whitelist sort mencegah error SQL dan SQL Injection.
     */
    public function test_whitelist_sort_rejects_unwhitelisted_column_and_defaults_safely(): void
    {
        $employee = User::factory()->employee()->create();

        // Sort dengan nilai tidak dikenal / liar
        $response = $this->actingAs($employee)->getJson('/documents/data?sort=malicious_col&direction=asc');

        $response->assertOk();
    }

    /**
     * Filter kombinasi di Katalog Berkas (Kecamatan, Desa, No Berkas, Tahun, Jenis Hak, Jenis Berkas, Status).
     */
    public function test_combined_filters_in_catalog(): void
    {
        $employee = User::factory()->employee()->create();

        $otherDistrict = District::factory()->create(['id' => '5204030']);
        $otherVillage = Village::factory()->create(['id' => '5204030001', 'district_id' => $otherDistrict->id]);

        // Dokumen target
        $targetDoc = ArchiveDocument::factory()->available()->create([
            'document_number' => 'FILTER100',
            'year' => '2022',
            'district_id' => $this->district->id,
            'village_id' => $this->village->id,
            'document_type_id' => $this->docType->id,
            'land_right_type_id' => $this->landRight->id,
            'storage_rack_id' => $this->rack->id,
        ]);

        // Dokumen lain yang tidak sesuai
        ArchiveDocument::factory()->available()->create([
            'document_number' => 'OTHER999',
            'year' => '2020',
            'district_id' => $otherDistrict->id,
            'village_id' => $otherVillage->id,
            'document_type_id' => $this->docType->id,
            'land_right_type_id' => $this->landRight->id,
            'storage_rack_id' => $this->rack->id,
        ]);

        $response = $this->actingAs($employee)->getJson('/documents/data?' . http_build_query([
            'district_id' => $this->district->id,
            'village_id' => $this->village->id,
            'document_number' => 'FILTER',
            'year' => '2022',
            'document_type_id' => $this->docType->id,
            'land_right_type_id' => $this->landRight->id,
            'status' => 'available',
        ]));

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $targetDoc->id);
    }

    /**
     * Update dokumen dengan file baru membersihkan file lama dari disk.
     */
    public function test_updating_document_with_new_file_replaces_and_deletes_old_file(): void
    {
        Storage::fake('local');
        $archivist = User::factory()->archivist()->create();

        $oldFile = UploadedFile::fake()->create('old_scan.pdf', 500, 'application/pdf');
        $oldPath = $oldFile->storeAs('documents/2026/11', 'old_scan.pdf', 'local');

        $document = ArchiveDocument::factory()->available()->create([
            'document_number' => 'DOC-REPLACE',
            'district_id' => $this->district->id,
            'village_id' => $this->village->id,
            'document_type_id' => $this->docType->id,
            'land_right_type_id' => $this->landRight->id,
            'storage_rack_id' => $this->rack->id,
            'file_path' => $oldPath,
            'original_filename' => 'old_scan.pdf',
        ]);

        Storage::disk('local')->assertExists($oldPath);

        $newFile = UploadedFile::fake()->create('new_scan.pdf', 600, 'application/pdf');

        $response = $this->actingAs($archivist)->patchJson("/documents/{$document->id}", [
            'document_number' => 'DOC-REPLACE-UPDATED',
            'year' => 2024,
            'document_type_id' => $this->docType->id,
            'land_right_type_id' => $this->landRight->id,
            'storage_rack_id' => $this->rack->id,
            'district_id' => $this->district->id,
            'village_id' => $this->village->id,
            'file' => $newFile,
        ]);

        $response->assertOk();

        // Old file must be gone
        Storage::disk('local')->assertMissing($oldPath);

        // New file must exist
        $freshDoc = $document->fresh();
        $this->assertNotSame($oldPath, $freshDoc->file_path);
        Storage::disk('local')->assertExists($freshDoc->file_path);
        $this->assertSame('new_scan.pdf', $freshDoc->original_filename);
    }
}
