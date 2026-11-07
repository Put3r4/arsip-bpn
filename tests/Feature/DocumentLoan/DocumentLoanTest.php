<?php

namespace Tests\Feature\DocumentLoan;

use App\Enums\DocumentStatus;
use App\Enums\LoanStatus;
use App\Models\ArchiveDocument;
use App\Models\DocumentLoan;
use App\Models\Section;
use App\Models\ServiceActivity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentLoanTest extends TestCase
{
    use RefreshDatabase;

    private Section $section;
    private ServiceActivity $activity;
    private User $employee;
    private User $archivist;
    private User $superAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->section = Section::factory()->create(['name' => 'Seksi Penetapan Hak']);
        $this->activity = ServiceActivity::factory()->create(['name' => 'Pengecekan Sertipikat']);

        $this->employee = User::factory()->employee()->create([
            'section_id' => $this->section->id,
            'name' => 'Pegawai Uji',
        ]);

        $this->archivist = User::factory()->archivist()->create([
            'section_id' => $this->section->id,
            'name' => 'Petugas Arsip',
        ]);

        $this->superAdmin = User::factory()->superAdmin()->create([
            'name' => 'Super Admin',
        ]);
    }

    /**
     * LOAN-01: Pegawai booking berkas berstatus available.
     * Sesuai A5 butir 1: pinjam pending terbuat, peminjam terkunci otomatis ke user login,
     * status dokumen berubah menjadi booked.
     */
    public function test_loan_01_employee_can_book_available_document(): void
    {
        $document = ArchiveDocument::factory()->available()->create([
            'document_number' => 'DOC-001',
        ]);

        $response = $this->actingAs($this->employee)->postJson('/loans', [
            'archive_document_id' => $document->id,
            'service_activity_id' => $this->activity->id,
            'notes' => 'Pengecekan rutin permohonan sertipikat',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'status' => 'success',
                'message' => 'Permohonan peminjaman berkas berhasil diajukan.',
            ]);

        $this->assertDatabaseHas('document_loans', [
            'archive_document_id' => $document->id,
            'borrower_id' => $this->employee->id,
            'service_activity_id' => $this->activity->id,
            'status' => LoanStatus::PENDING->value,
            'return_date' => null,
            'notes' => 'Pengecekan rutin permohonan sertipikat',
        ]);

        $this->assertDatabaseHas('archive_documents', [
            'id' => $document->id,
            'status' => DocumentStatus::BOOKED->value,
        ]);
    }

    /**
     * LOAN-02: Pegawai mencoba booking berkas yang tidak berstatus available.
     * Validasi gagal (422), dokumen tidak dapat dipinjam.
     */
    public function test_loan_02_employee_cannot_book_unavailable_document(): void
    {
        $borrowedDoc = ArchiveDocument::factory()->borrowed()->create();
        $lostDoc = ArchiveDocument::factory()->lost()->create();
        $bookedDoc = ArchiveDocument::factory()->booked()->create();

        foreach ([$borrowedDoc, $lostDoc, $bookedDoc] as $doc) {
            $response = $this->actingAs($this->employee)->postJson('/loans', [
                'archive_document_id' => $doc->id,
                'service_activity_id' => $this->activity->id,
            ]);

            $response->assertStatus(422)
                ->assertJsonStructure(['status', 'message']);
        }
    }

    /**
     * LOAN-03: Race condition pencegahan double booking.
     * Catatan: SQLite in-memory tidak mendukung lockForUpdate tabel/baris riil,
     * sehingga pengujian memvalidasi penjagaan status secara berurutan:
     * Request pertama berhasil mengunci status menjadi booked, request kedua
     * untuk ID dokumen yang sama langsung ditolak dengan status 422.
     */
    public function test_loan_03_race_condition_guard_double_booking_fails(): void
    {
        $document = ArchiveDocument::factory()->available()->create();

        $employeeB = User::factory()->employee()->create([
            'section_id' => $this->section->id,
        ]);

        // Request pertama oleh Pegawai A berhasil
        $firstResponse = $this->actingAs($this->employee)->postJson('/loans', [
            'archive_document_id' => $document->id,
            'service_activity_id' => $this->activity->id,
            'notes' => 'Booking pegawai A',
        ]);

        $firstResponse->assertStatus(201);
        $this->assertSame(DocumentStatus::BOOKED, $document->fresh()->status);

        // Request kedua oleh Pegawai B untuk dokumen yang sama harus gagal
        $secondResponse = $this->actingAs($employeeB)->postJson('/loans', [
            'archive_document_id' => $document->id,
            'service_activity_id' => $this->activity->id,
            'notes' => 'Booking pegawai B mendahului',
        ]);

        $secondResponse->assertStatus(422)
            ->assertJson([
                'status' => 'error',
            ]);

        // Pastikan hanya ada 1 record loan terbuat untuk dokumen ini
        $this->assertEquals(1, DocumentLoan::where('archive_document_id', $document->id)->count());
    }

    /**
     * LOAN-04: Admin menyetujui peminjaman via POST /loans/{id}/approve.
     * Sesuai A5 butir 2: status pinjam menjadi borrowed, status dokumen menjadi borrowed,
     * approved_by dan approved_at terisi, dan return_date TETAP NULL (Perbaikan bug CI3).
     */
    public function test_loan_04_admin_can_approve_pending_loan_and_return_date_remains_null(): void
    {
        $document = ArchiveDocument::factory()->booked()->create();
        $loan = DocumentLoan::factory()->pending()->create([
            'archive_document_id' => $document->id,
            'borrower_id' => $this->employee->id,
            'service_activity_id' => $this->activity->id,
        ]);

        $response = $this->actingAs($this->archivist)->postJson("/loans/{$loan->id}/approve");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Peminjaman berkas berhasil disetujui.',
            ]);

        $loan->refresh();
        $this->assertSame(LoanStatus::BORROWED, $loan->status);
        $this->assertEquals($this->archivist->id, $loan->approved_by);
        $this->assertNotNull($loan->approved_at);
        $this->assertNull($loan->return_date, 'CRITICAL: return_date wajib NULL saat status borrowed!');

        $this->assertSame(DocumentStatus::BORROWED, $document->fresh()->status);
    }

    /**
     * LOAN-05: Pegawai biasa mencoba menyetujui peminjaman (ACC) sendiri.
     * Ditolak dengan status 403 Forbidden.
     */
    public function test_loan_05_employee_cannot_approve_loan(): void
    {
        $document = ArchiveDocument::factory()->booked()->create();
        $loan = DocumentLoan::factory()->pending()->create([
            'archive_document_id' => $document->id,
            'borrower_id' => $this->employee->id,
        ]);

        $response = $this->actingAs($this->employee)->postJson("/loans/{$loan->id}/approve");
        $response->assertStatus(403);
    }

    /**
     * LOAN-06: Admin memproses pengembalian berkas via POST /loans/{id}/return.
     * Sesuai A5 butir 3: status pinjam menjadi returned, return_date terisi hari ini,
     * received_by terisi, status dokumen kembali available.
     */
    public function test_loan_06_admin_can_process_return_of_borrowed_document(): void
    {
        $document = ArchiveDocument::factory()->borrowed()->create();
        $loan = DocumentLoan::factory()->borrowed()->create([
            'archive_document_id' => $document->id,
            'borrower_id' => $this->employee->id,
            'service_activity_id' => $this->activity->id,
        ]);

        $response = $this->actingAs($this->archivist)->postJson("/loans/{$loan->id}/return");

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Pengembalian berkas fisik berhasil diproses.',
            ]);

        $loan->refresh();
        $this->assertSame(LoanStatus::RETURNED, $loan->status);
        $this->assertEquals(now()->toDateString(), $loan->return_date->toDateString());
        $this->assertEquals($this->archivist->id, $loan->received_by);

        $this->assertSame(DocumentStatus::AVAILABLE, $document->fresh()->status);
    }

    /**
     * Pegawai biasa tidak berhak memproses pengembalian berkas fisik (403).
     */
    public function test_employee_cannot_process_return_of_document(): void
    {
        $document = ArchiveDocument::factory()->borrowed()->create();
        $loan = DocumentLoan::factory()->borrowed()->create([
            'archive_document_id' => $document->id,
            'borrower_id' => $this->employee->id,
        ]);

        $response = $this->actingAs($this->employee)->postJson("/loans/{$loan->id}/return");
        $response->assertStatus(403);
    }

    /**
     * LOAN-07: Pembatalan booking oleh Pegawai (milik sendiri) dan oleh Staf.
     * Sesuai A5 butir 4: status pinjam menjadi cancelled, cancelled_at terisi,
     * status dokumen kembali available.
     */
    public function test_loan_07_cancellation_of_pending_booking_by_employee_or_admin(): void
    {
        // 1. Pegawai membatalkan booking miliknya sendiri
        $doc1 = ArchiveDocument::factory()->booked()->create();
        $loan1 = DocumentLoan::factory()->pending()->create([
            'archive_document_id' => $doc1->id,
            'borrower_id' => $this->employee->id,
        ]);

        $response1 = $this->actingAs($this->employee)->postJson("/loans/{$loan1->id}/cancel");
        $response1->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'Permohonan peminjaman berkas berhasil dibatalkan.',
            ]);

        $loan1->refresh();
        $this->assertSame(LoanStatus::CANCELLED, $loan1->status);
        $this->assertNotNull($loan1->cancelled_at);
        $this->assertSame(DocumentStatus::AVAILABLE, $doc1->fresh()->status);

        // 2. Staf arsip membatalkan booking pending milik pegawai lain
        $doc2 = ArchiveDocument::factory()->booked()->create();
        $loan2 = DocumentLoan::factory()->pending()->create([
            'archive_document_id' => $doc2->id,
            'borrower_id' => $this->employee->id,
        ]);

        $response2 = $this->actingAs($this->archivist)->postJson("/loans/{$loan2->id}/cancel");
        $response2->assertStatus(200);

        $loan2->refresh();
        $this->assertSame(LoanStatus::CANCELLED, $loan2->status);
        $this->assertSame(DocumentStatus::AVAILABLE, $doc2->fresh()->status);
    }

    /**
     * Pegawai tidak boleh membatalkan booking milik pegawai lain (403).
     */
    public function test_employee_cannot_cancel_other_users_booking(): void
    {
        $otherEmployee = User::factory()->employee()->create([
            'section_id' => $this->section->id,
        ]);

        $doc = ArchiveDocument::factory()->booked()->create();
        $loan = DocumentLoan::factory()->pending()->create([
            'archive_document_id' => $doc->id,
            'borrower_id' => $otherEmployee->id,
        ]);

        $response = $this->actingAs($this->employee)->postJson("/loans/{$loan->id}/cancel");
        $response->assertStatus(403);
    }

    /**
     * Sesuai A5 butir 4: Peminjaman berstatus borrowed TIDAK BISA dibatalkan, harus lewat Return.
     */
    public function test_borrowed_loan_cannot_be_cancelled(): void
    {
        $doc = ArchiveDocument::factory()->borrowed()->create();
        $loan = DocumentLoan::factory()->borrowed()->create([
            'archive_document_id' => $doc->id,
            'borrower_id' => $this->employee->id,
        ]);

        // Staf mencoba cancel pinjaman yang sudah borrowed
        $response = $this->actingAs($this->archivist)->postJson("/loans/{$loan->id}/cancel");
        $response->assertStatus(422)
            ->assertJson([
                'status' => 'error',
            ]);

        $this->assertSame(LoanStatus::BORROWED, $loan->fresh()->status);
    }

    /**
     * Sesuai A4: Staf arsip boleh booking atas nama pegawai lain (pilih seksi lalu pegawai).
     */
    public function test_staff_can_book_for_other_employees(): void
    {
        $document = ArchiveDocument::factory()->available()->create();

        $response = $this->actingAs($this->archivist)->postJson('/loans', [
            'archive_document_id' => $document->id,
            'borrower_id' => $this->employee->id,
            'service_activity_id' => $this->activity->id,
            'notes' => 'Di-bookingkan oleh staf arsip',
        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('document_loans', [
            'archive_document_id' => $document->id,
            'borrower_id' => $this->employee->id,
            'status' => LoanStatus::PENDING->value,
        ]);
    }

    /**
     * Sesuai A4: Pegawai biasa yang mencoba memanipulasi form dan mengirim borrower_id orang lain
     * akan otomatis dipaksa terkunci ke auth()->id().
     */
    public function test_employee_cannot_book_for_others(): void
    {
        $otherEmployee = User::factory()->employee()->create([
            'section_id' => $this->section->id,
        ]);
        $document = ArchiveDocument::factory()->available()->create();

        $response = $this->actingAs($this->employee)->postJson('/loans', [
            'archive_document_id' => $document->id,
            'borrower_id' => $otherEmployee->id, // Mencoba memanipulasi
            'service_activity_id' => $this->activity->id,
        ]);

        $response->assertStatus(201);

        // Terkunci otomatis ke ID employee yang sedang login
        $this->assertDatabaseHas('document_loans', [
            'archive_document_id' => $document->id,
            'borrower_id' => $this->employee->id,
        ]);
    }

    /**
     * Sesuai A5 butir 5 & 6: Ubah status fisik dan hapus dokumen ditolak jika ada loan aktif.
     */
    public function test_active_loan_prevents_document_deletion_and_physical_status_update(): void
    {
        $docWithPending = ArchiveDocument::factory()->booked()->create();
        DocumentLoan::factory()->pending()->create([
            'archive_document_id' => $docWithPending->id,
        ]);

        $docWithBorrowed = ArchiveDocument::factory()->borrowed()->create();
        DocumentLoan::factory()->borrowed()->create([
            'archive_document_id' => $docWithBorrowed->id,
        ]);

        foreach ([$docWithPending, $docWithBorrowed] as $doc) {
            // Hapus harus ditolak (422)
            $delResponse = $this->actingAs($this->archivist)->deleteJson("/documents/{$doc->id}");
            $delResponse->assertStatus(422);
            $this->assertNotSoftDeleted($doc);

            // Ubah status fisik harus ditolak (422)
            $statusResponse = $this->actingAs($this->archivist)->patchJson("/documents/{$doc->id}/status", [
                'status' => 'lost',
            ]);
            $statusResponse->assertStatus(422);
        }
    }
}
