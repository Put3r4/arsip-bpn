<?php

namespace Tests\Feature\ArchiveDocument;

use App\Models\ArchiveDocument;
use App\Models\District;
use App\Models\DocumentType;
use App\Models\LandRightType;
use App\Models\StorageRack;
use App\Models\User;
use App\Models\Village;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FileSecurityTest extends TestCase
{
    use RefreshDatabase;

    private ArchiveDocument $documentWithFile;
    private ArchiveDocument $documentMissingFile;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $district = District::factory()->create(['id' => '5204020']);
        $village = Village::factory()->create([
            'id' => '5204020001',
            'district_id' => $district->id,
        ]);
        $docType = DocumentType::factory()->create();
        $landRight = LandRightType::factory()->create();
        $rack = StorageRack::factory()->create();

        // Dokumen dengan file valid di disk
        $uploaded = UploadedFile::fake()->create('warkah_lunyuk.pdf', 300, 'application/pdf');
        $storedPath = $uploaded->storeAs('documents/2026/11', 'warkah_lunyuk.pdf', 'local');

        $this->documentWithFile = ArchiveDocument::factory()->available()->create([
            'document_number' => 'FILE-SEC-01',
            'district_id' => $district->id,
            'village_id' => $village->id,
            'document_type_id' => $docType->id,
            'land_right_type_id' => $landRight->id,
            'storage_rack_id' => $rack->id,
            'file_path' => $storedPath,
            'original_filename' => 'warkah_lunyuk.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 300 * 1024,
        ]);

        // Dokumen dengan path di DB tetapi filenya tidak ada di disk
        $this->documentMissingFile = ArchiveDocument::factory()->available()->create([
            'document_number' => 'FILE-SEC-03',
            'district_id' => $district->id,
            'village_id' => $village->id,
            'document_type_id' => $docType->id,
            'land_right_type_id' => $landRight->id,
            'storage_rack_id' => $rack->id,
            'file_path' => 'documents/2026/11/ghost_file.pdf',
            'original_filename' => 'ghost_file.pdf',
            'mime_type' => 'application/pdf',
        ]);
    }

    /**
     * FILE-01: Akses Pratinjau PDF Terotentikasi (Happy Path)
     * User login membuka /documents/{id}/file.
     * Response 200, Content-Disposition: inline, Content-Type: application/pdf.
     */
    public function test_file_01_authenticated_user_can_preview_scan_file(): void
    {
        $employee = User::factory()->employee()->create();

        $response = $this->actingAs($employee)->get("/documents/{$this->documentWithFile->id}/file");

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('inline', $response->headers->get('Content-Disposition'));
    }

    /**
     * FILE-01 (Alias preview endpoint): GET /documents/{id}/preview
     */
    public function test_file_01_preview_alias_endpoint_works(): void
    {
        $employee = User::factory()->employee()->create();

        $response = $this->actingAs($employee)->get("/documents/{$this->documentWithFile->id}/preview");

        $response->assertOk();
        $this->assertStringContainsString('inline', $response->headers->get('Content-Disposition'));
    }

    /**
     * FILE-02: Akses Pratinjau PDF Pengguna Anonim (Hak Akses)
     * Guest (tanpa login) mencoba mengakses /documents/{id}/file -> redirect 302 ke login.
     */
    public function test_file_02_guest_cannot_preview_document_file(): void
    {
        $response = $this->get("/documents/{$this->documentWithFile->id}/file");

        $response->assertRedirect('/login');
    }

    /**
     * FILE-03: Akses File Dokumen yang Rusak/Hilang di Disk (Kasus Tepi)
     * Record ada di DB tetapi filenya hilang di disk -> 404 Not Found elegan, bukan 500 fatal.
     */
    public function test_file_03_missing_file_on_disk_returns_404_not_found(): void
    {
        $employee = User::factory()->employee()->create();

        $response = $this->actingAs($employee)->get("/documents/{$this->documentMissingFile->id}/file");

        $response->assertNotFound();
    }
}
