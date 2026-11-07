<?php

namespace Tests\Feature\DocumentLoan;

use App\Enums\LoanStatus;
use App\Models\ArchiveDocument;
use App\Models\DocumentLoan;
use App\Models\Section;
use App\Models\ServiceActivity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoanAuditTest extends TestCase
{
    use RefreshDatabase;

    private Section $sectionA;
    private Section $sectionB;
    private ServiceActivity $activity;
    private User $employeeA;
    private User $employeeB;
    private User $archivist;

    protected function setUp(): void
    {
        parent::setUp();

        $this->sectionA = Section::factory()->create(['name' => 'Seksi Penetapan Hak dan Pendaftaran']);
        $this->sectionB = Section::factory()->create(['name' => 'Seksi Survei dan Pemetaan']);
        $this->activity = ServiceActivity::factory()->create(['name' => 'Roya']);

        $this->employeeA = User::factory()->employee()->create([
            'section_id' => $this->sectionA->id,
            'name' => 'Pegawai Alfa',
            'nip' => '198901012015011001',
        ]);

        $this->employeeB = User::factory()->employee()->create([
            'section_id' => $this->sectionB->id,
            'name' => 'Pegawai Beta',
            'nip' => '199202022018022002',
        ]);

        $this->archivist = User::factory()->archivist()->create([
            'section_id' => $this->sectionA->id,
            'name' => 'Petugas Arsip',
        ]);
    }

    /**
     * AUDIT-01: Tampilkan Riwayat Berkas Selesai (Pengembalian).
     * Admin/staf membuka halaman riwayat pengembalian berkas (/loans/returned atau /loans/data?tab=returned).
     * Seluruh data transaksi yang telah selesai tampil beserta nama peminjam dan seksi.
     * (Perbaikan bug CI3: di CI3 query join tb_pegawai salah dan menghasilkan 0 baris).
     */
    public function test_audit_01_admin_can_view_returned_loans_history(): void
    {
        $document = ArchiveDocument::factory()->available()->create([
            'document_number' => 'DOC-AUDIT-01',
        ]);

        $returnedLoan = DocumentLoan::factory()->returned()->create([
            'archive_document_id' => $document->id,
            'borrower_id' => $this->employeeA->id,
            'service_activity_id' => $this->activity->id,
            'loan_date' => now()->subDays(5)->toDateString(),
            'return_date' => now()->toDateString(),
            'received_by' => $this->archivist->id,
        ]);

        // 1. Akses halaman web /loans/returned
        $pageResponse = $this->actingAs($this->archivist)->get('/loans/returned');
        $pageResponse->assertStatus(200);

        // 2. Akses endpoint server-side /loans/data?tab=returned
        $dataResponse = $this->actingAs($this->archivist)->getJson('/loans/data?tab=returned');
        $dataResponse->assertStatus(200)
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $returnedLoan->id)
            ->assertJsonPath('data.0.document.document_number', 'DOC-AUDIT-01')
            ->assertJsonPath('data.0.borrower.name', 'Pegawai Alfa')
            ->assertJsonPath('data.0.borrower.section', 'Seksi Penetapan Hak dan Pendaftaran')
            ->assertJsonPath('data.0.status', LoanStatus::RETURNED->value)
            ->assertJsonPath('data.0.receiver.name', 'Petugas Arsip');
    }

    /**
     * AUDIT-02: Filter Peminjaman Milik Sendiri.
     * Pegawai membuka /my-loans atau /loans/data.
     * Hanya menampilkan berkas yang dipinjam oleh user bersangkutan (borrower_id = auth()->id()).
     * Booking pegawai lain TIDAK BOCOR (Perbaikan bug operator SQL di CI3).
     */
    public function test_audit_02_employee_loans_list_strictly_scoped_no_leakage(): void
    {
        $docA = ArchiveDocument::factory()->booked()->create(['document_number' => 'DOC-MILIK-A']);
        $docB = ArchiveDocument::factory()->booked()->create(['document_number' => 'DOC-MILIK-B']);

        // Loan milik Pegawai A (Pending)
        $loanA = DocumentLoan::factory()->pending()->create([
            'archive_document_id' => $docA->id,
            'borrower_id' => $this->employeeA->id,
            'service_activity_id' => $this->activity->id,
        ]);

        // Loan milik Pegawai B (Pending)
        $loanB = DocumentLoan::factory()->pending()->create([
            'archive_document_id' => $docB->id,
            'borrower_id' => $this->employeeB->id,
            'service_activity_id' => $this->activity->id,
        ]);

        // Pegawai A membuka rute alias /my-loans
        $pageResponse = $this->actingAs($this->employeeA)->get('/my-loans');
        $pageResponse->assertStatus(200);

        // Pegawai A memanggil endpoint data JSON
        $dataResponse = $this->actingAs($this->employeeA)->getJson('/loans/data?tab=active');

        $dataResponse->assertStatus(200);
        $returnedData = $dataResponse->json('data');

        // Pastikan hanya data milik Pegawai A yang ada
        $this->assertCount(1, $returnedData);
        $this->assertEquals($loanA->id, $returnedData[0]['id']);
        $this->assertEquals('DOC-MILIK-A', $returnedData[0]['document']['document_number']);
        $this->assertEquals('Pegawai Alfa', $returnedData[0]['borrower']['name']);

        // Pastikan dokumen milik Pegawai B tidak bocor sedikitpun
        $dataIds = collect($returnedData)->pluck('id')->all();
        $this->assertNotContains($loanB->id, $dataIds);
    }

    /**
     * AUDIT-03: Jejak Audit Berkas (Loan History per Dokumen).
     * Staf arsip dapat melihat riwayat peminjaman untuk dokumen spesifik: /documents/{id}/history.
     * Menampilkan kronologis seluruh pihak yang pernah meminjam berkas tersebut dari waktu ke waktu.
     */
    public function test_audit_03_staff_can_view_document_loan_history_chronologically(): void
    {
        $document = ArchiveDocument::factory()->available()->create([
            'document_number' => 'DOC-HISTORI-LENGKAP',
        ]);

        // Transaksi 1 (lama): Sudah dikembalikan oleh Pegawai B
        $loan1 = DocumentLoan::factory()->returned()->create([
            'archive_document_id' => $document->id,
            'borrower_id' => $this->employeeB->id,
            'loan_date' => now()->subDays(30)->toDateString(),
            'return_date' => now()->subDays(25)->toDateString(),
        ]);

        // Transaksi 2 (dibatalkan): Dibatalkan oleh Pegawai A
        $loan2 = DocumentLoan::factory()->cancelled()->create([
            'archive_document_id' => $document->id,
            'borrower_id' => $this->employeeA->id,
            'loan_date' => now()->subDays(10)->toDateString(),
            'cancelled_at' => now()->subDays(9),
        ]);

        // 1. Staf memanggil endpoint JSON /documents/{document}/history
        $jsonResponse = $this->actingAs($this->archivist)->getJson("/documents/{$document->id}/history");

        $jsonResponse->assertStatus(200)
            ->assertJsonPath('document.document_number', 'DOC-HISTORI-LENGKAP')
            ->assertJsonCount(2, 'history');

        // Urutan kronologis terbaru berada di urutan pertama (id desc)
        $history = $jsonResponse->json('history');
        $this->assertEquals($loan2->id, $history[0]['id']);
        $this->assertEquals(LoanStatus::CANCELLED->value, $history[0]['status']);
        $this->assertEquals($loan1->id, $history[1]['id']);
        $this->assertEquals(LoanStatus::RETURNED->value, $history[1]['status']);

        // 2. Staf juga bisa mengakses tampilan HTML
        $htmlResponse = $this->actingAs($this->archivist)->get("/documents/{$document->id}/history");
        $htmlResponse->assertStatus(200);
    }

    /**
     * Pegawai biasa tidak berhak melihat riwayat audit dokumen spesifik (403).
     * Sesuai A4: Riwayat peminjam per dokumen hanya untuk super_admin dan archivist.
     */
    public function test_audit_03_employee_cannot_view_document_loan_history(): void
    {
        $document = ArchiveDocument::factory()->available()->create();

        $response = $this->actingAs($this->employeeA)->getJson("/documents/{$document->id}/history");
        $response->assertStatus(403);

        $htmlResponse = $this->actingAs($this->employeeA)->get("/documents/{$document->id}/history");
        $htmlResponse->assertStatus(403);
    }
}
