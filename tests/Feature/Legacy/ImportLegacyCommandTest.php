<?php

namespace Tests\Feature\Legacy;

use App\Enums\DocumentStatus;
use App\Enums\LoanStatus;
use App\Enums\UserRole;
use App\Models\ArchiveDocument;
use App\Models\DocumentLoan;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ImportLegacyCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed master data (districts, villages, sections, document types, storage racks, etc.)
        $this->seed(DatabaseSeeder::class);
    }

    protected function setupLegacyFixtureConnection(): string
    {
        $conn = 'legacy_fixture_' . uniqid();

        config([
            "database.connections.{$conn}" => [
                'driver' => 'sqlite',
                'database' => ':memory:',
                'prefix' => '',
            ],
        ]);

        Schema::connection($conn)->create('login', function ($table) {
            $table->increments('id');
            $table->string('username', 50);
            $table->string('password', 255);
            $table->string('name', 100);
            $table->dateTime('datetime')->nullable();
            $table->string('level', 10);
            $table->integer('id_seksi')->nullable();
        });

        Schema::connection($conn)->create('tb_add_dokumen', function ($table) {
            $table->increments('id_dok');
            $table->string('no_berkas', 50);
            $table->string('tahun', 10)->nullable();
            $table->integer('hak');
            $table->integer('berkas');
            $table->string('range_no', 50)->nullable();
            $table->string('kecamatan', 20);
            $table->string('kelurahan', 20);
            $table->string('peng', 50)->nullable();
            $table->string('status', 10);
            $table->string('datetime', 50)->nullable();
            $table->integer('rak');
            $table->string('link', 255)->nullable();
        });

        Schema::connection($conn)->create('tb_pinjam', function ($table) {
            $table->increments('id_pinjam');
            $table->integer('id_peg');
            $table->integer('id_berkas');
            $table->integer('id_keg');
            $table->date('tgl_pinjam');
            $table->date('tgl_kembali')->nullable();
            $table->string('status', 10);
            $table->string('tahun', 10)->nullable();
            $table->string('no_berkas', 50)->nullable();
            $table->text('keterangan')->nullable();
        });

        $this->seedLegacyFixtureData($conn);

        return $conn;
    }

    protected function seedLegacyFixtureData(string $conn): void
    {
        DB::connection($conn)->table('login')->insert([
            ['id' => 1, 'username' => 'kimparana', 'password' => '1234', 'name' => 'Rezky', 'datetime' => '2020-06-22 00:00:00', 'level' => '0', 'id_seksi' => 2],
            ['id' => 2, 'username' => 'testing', 'password' => '1234', 'name' => 'Tes', 'datetime' => '2020-06-22 00:00:00', 'level' => '0', 'id_seksi' => 2],
            ['id' => 7, 'username' => 'wahyu', 'password' => '1234', 'name' => 'wahyu', 'datetime' => '2025-09-17 08:49:01', 'level' => '1', 'id_seksi' => 2],
        ]);

        DB::connection($conn)->table('tb_add_dokumen')->insert([
            ['id_dok' => 1, 'no_berkas' => '00001', 'tahun' => '-', 'hak' => 2, 'berkas' => 2, 'range_no' => '1-100', 'kecamatan' => '5204020', 'kelurahan' => '5204020001', 'peng' => '', 'status' => '1', 'datetime' => '2025-09-10', 'rak' => 6, 'link' => ''],
            ['id_dok' => 2, 'no_berkas' => '00002', 'tahun' => '-', 'hak' => 2, 'berkas' => 2, 'range_no' => '1-100', 'kecamatan' => '5204020', 'kelurahan' => '5204020001', 'peng' => '', 'status' => '1', 'datetime' => '2025-09-10', 'rak' => 6, 'link' => ''],
            ['id_dok' => 3, 'no_berkas' => '00003', 'tahun' => '-', 'hak' => 2, 'berkas' => 2, 'range_no' => '1-100', 'kecamatan' => '5204020', 'kelurahan' => '5204020001', 'peng' => '', 'status' => '0', 'datetime' => '2025-09-10', 'rak' => 5, 'link' => ''],
            ['id_dok' => 4, 'no_berkas' => '00004', 'tahun' => '-', 'hak' => 2, 'berkas' => 2, 'range_no' => '1-100', 'kecamatan' => '5204020', 'kelurahan' => '5204020001', 'peng' => '', 'status' => '0', 'datetime' => '2025-09-10', 'rak' => 5, 'link' => ''],
            ['id_dok' => 5, 'no_berkas' => '00010', 'tahun' => '-', 'hak' => 2, 'berkas' => 2, 'range_no' => '1-100', 'kecamatan' => '5204062', 'kelurahan' => '5204062002', 'peng' => '', 'status' => '0', 'datetime' => '2025-10-17', 'rak' => 6, 'link' => ''],
        ]);

        DB::connection($conn)->table('tb_pinjam')->insert([
            ['id_pinjam' => 1, 'id_peg' => 7, 'id_berkas' => 1, 'id_keg' => 1, 'tgl_pinjam' => '2025-09-17', 'tgl_kembali' => '2025-10-08', 'status' => '1', 'tahun' => '2025', 'no_berkas' => '1', 'keterangan' => ''],
            ['id_pinjam' => 2, 'id_peg' => 2, 'id_berkas' => 2, 'id_keg' => 2, 'tgl_pinjam' => '2025-09-17', 'tgl_kembali' => '2025-09-17', 'status' => '1', 'tahun' => '2025', 'no_berkas' => '2', 'keterangan' => ''],
            ['id_pinjam' => 3, 'id_peg' => 7, 'id_berkas' => 3, 'id_keg' => 2, 'tgl_pinjam' => '2025-09-21', 'tgl_kembali' => '2025-10-08', 'status' => '0', 'tahun' => '2025', 'no_berkas' => '2001', 'keterangan' => ''],
            ['id_pinjam' => 4, 'id_peg' => 7, 'id_berkas' => 5, 'id_keg' => 1, 'tgl_pinjam' => '2025-10-17', 'tgl_kembali' => '2025-10-17', 'status' => '0', 'tahun' => '2025', 'no_berkas' => '2001', 'keterangan' => ''],
        ]);
    }

    public function test_dry_run_mode_does_not_persist_any_changes(): void
    {
        $conn = $this->setupLegacyFixtureConnection();

        $initialUsersCount = User::count();
        $this->assertSame(0, ArchiveDocument::count());
        $this->assertSame(0, DocumentLoan::count());

        $this->artisan('arsip:import-legacy', [
            '--dry-run' => true,
            '--connection' => $conn,
        ])
            ->expectsOutputToContain('[DRY-RUN BERHASIL]')
            ->assertSuccessful();

        $this->assertSame($initialUsersCount, User::count(), 'User count must remain unchanged in dry-run mode.');
        $this->assertSame(0, ArchiveDocument::count(), 'ArchiveDocument count must remain 0 in dry-run mode.');
        $this->assertSame(0, DocumentLoan::count(), 'DocumentLoan count must remain 0 in dry-run mode.');
    }

    public function test_import_legacy_is_idempotent(): void
    {
        $conn = $this->setupLegacyFixtureConnection();

        // Putaran pertama
        $this->artisan('arsip:import-legacy', ['--connection' => $conn])
            ->expectsOutputToContain('[SUKSES]')
            ->assertSuccessful();

        $usersAfterFirstRun = User::count();
        $docsAfterFirstRun = ArchiveDocument::count();
        $loansAfterFirstRun = DocumentLoan::count();

        $this->assertSame(4, $usersAfterFirstRun, '1 super admin + 3 legacy users = 4 users.');
        $this->assertSame(5, $docsAfterFirstRun, '5 legacy documents imported.');
        $this->assertSame(4, $loansAfterFirstRun, '4 legacy loans imported.');

        // Putaran kedua (idempotency check)
        $this->artisan('arsip:import-legacy', ['--connection' => $conn])
            ->expectsOutputToContain('[SUKSES]')
            ->assertSuccessful();

        $this->assertSame($usersAfterFirstRun, User::count(), 'User count must not change on duplicate import.');
        $this->assertSame($docsAfterFirstRun, ArchiveDocument::count(), 'ArchiveDocument count must not change on duplicate import.');
        $this->assertSame($loansAfterFirstRun, DocumentLoan::count(), 'DocumentLoan count must not change on duplicate import.');
    }

    public function test_status_mapping_for_documents_and_loans(): void
    {
        $conn = $this->setupLegacyFixtureConnection();

        // Tambahkan dokumen dengan ragam status (0, aktif, 1, 2, 3, 4)
        DB::connection($conn)->table('tb_add_dokumen')->insert([
            ['id_dok' => 10, 'no_berkas' => 'DOC-AKTIF', 'tahun' => '2024', 'hak' => 2, 'berkas' => 2, 'range_no' => '1', 'kecamatan' => '5204020', 'kelurahan' => '5204020001', 'peng' => '', 'status' => 'aktif', 'datetime' => '2025-01-01', 'rak' => 5, 'link' => ''],
            ['id_dok' => 11, 'no_berkas' => 'DOC-LOST', 'tahun' => '2024', 'hak' => 2, 'berkas' => 2, 'range_no' => '2', 'kecamatan' => '5204020', 'kelurahan' => '5204020001', 'peng' => '', 'status' => '2', 'datetime' => '2025-01-01', 'rak' => 5, 'link' => ''],
            ['id_dok' => 12, 'no_berkas' => 'DOC-INVALID', 'tahun' => '2024', 'hak' => 2, 'berkas' => 2, 'range_no' => '3', 'kecamatan' => '5204020', 'kelurahan' => '5204020001', 'peng' => '', 'status' => '3', 'datetime' => '2025-01-01', 'rak' => 5, 'link' => ''],
            ['id_dok' => 13, 'no_berkas' => 'DOC-BOOKED', 'tahun' => '2024', 'hak' => 2, 'berkas' => 2, 'range_no' => '4', 'kecamatan' => '5204020', 'kelurahan' => '5204020001', 'peng' => '', 'status' => '4', 'datetime' => '2025-01-01', 'rak' => 5, 'link' => ''],
        ]);

        // Tambahkan pinjaman berstatus 4 (pending)
        DB::connection($conn)->table('tb_pinjam')->insert([
            ['id_pinjam' => 10, 'id_peg' => 7, 'id_berkas' => 13, 'id_keg' => 1, 'tgl_pinjam' => '2025-01-01', 'tgl_kembali' => null, 'status' => '4', 'tahun' => '2025', 'no_berkas' => 'DOC-BOOKED', 'keterangan' => 'Booking pending'],
        ]);

        $this->artisan('arsip:import-legacy', ['--connection' => $conn])->assertSuccessful();

        $this->assertSame(DocumentStatus::AVAILABLE, ArchiveDocument::find(10)->status);
        $this->assertSame(DocumentStatus::LOST, ArchiveDocument::find(11)->status);
        $this->assertSame(DocumentStatus::INVALID, ArchiveDocument::find(12)->status);
        $this->assertSame(DocumentStatus::BOOKED, ArchiveDocument::find(13)->status);

        $this->assertSame(LoanStatus::PENDING, DocumentLoan::find(10)->status);
        $this->assertSame(LoanStatus::BORROWED, DocumentLoan::find(1)->status);
        $this->assertSame(LoanStatus::RETURNED, DocumentLoan::find(3)->status);
    }

    public function test_return_date_is_normalized_to_null_for_borrowed_and_pending_loans(): void
    {
        $conn = $this->setupLegacyFixtureConnection();

        $this->artisan('arsip:import-legacy', ['--connection' => $conn])
            ->expectsOutputToContain('Normalisasi Return Date Bug CI3 : 2 record')
            ->assertSuccessful();

        // Pinjaman 1 dan 2 berstatus borrowed (1) di data lama memiliki tgl_kembali palsu karena bug approval CI3
        $loan1 = DocumentLoan::find(1);
        $this->assertSame(LoanStatus::BORROWED, $loan1->status);
        $this->assertNull($loan1->return_date, 'return_date untuk peminjaman borrowed WAJIB bernilai NULL.');

        $loan2 = DocumentLoan::find(2);
        $this->assertSame(LoanStatus::BORROWED, $loan2->status);
        $this->assertNull($loan2->return_date, 'return_date untuk peminjaman borrowed WAJIB bernilai NULL.');

        // Pinjaman 3 berstatus returned (0) mempertahankan tgl_kembali
        $loan3 = DocumentLoan::find(3);
        $this->assertSame(LoanStatus::RETURNED, $loan3->status);
        $this->assertNotNull($loan3->return_date);
        $this->assertSame('2025-10-08', $loan3->return_date->format('Y-m-d'));
    }

    public function test_legacy_passwords_are_bcrypt_hashed_and_can_login(): void
    {
        $conn = $this->setupLegacyFixtureConnection();

        $this->artisan('arsip:import-legacy', ['--connection' => $conn])->assertSuccessful();

        $kimparana = User::where('username', 'kimparana')->first();
        $this->assertNotNull($kimparana);
        $this->assertSame(UserRole::ARCHIVIST, $kimparana->role);
        $this->assertNotSame('1234', $kimparana->password, 'Password di basis data tidak boleh plaintext!');
        $this->assertTrue(Hash::check('1234', $kimparana->password), 'Password ter-hash Bcrypt harus cocok dengan 1234.');

        $wahyu = User::where('username', 'wahyu')->first();
        $this->assertNotNull($wahyu);
        $this->assertSame(UserRole::EMPLOYEE, $wahyu->role);
        $this->assertTrue(Hash::check('1234', $wahyu->password));

        // Uji login HTTP nyata
        $response = $this->post('/login', [
            'username' => 'kimparana',
            'password' => '1234',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($kimparana);
    }

    public function test_dash_year_in_documents_becomes_null(): void
    {
        $conn = $this->setupLegacyFixtureConnection();

        $this->artisan('arsip:import-legacy', ['--connection' => $conn])->assertSuccessful();

        $doc1 = ArchiveDocument::find(1);
        $this->assertNull($doc1->year, 'Tahun bernilai "-" pada data legacy harus dikonversi menjadi NULL.');

        $doc5 = ArchiveDocument::find(5);
        $this->assertNull($doc5->year, 'Tahun bernilai "-" pada data legacy harus dikonversi menjadi NULL.');
    }

    public function test_empty_link_becomes_null_file_path(): void
    {
        $conn = $this->setupLegacyFixtureConnection();

        $this->artisan('arsip:import-legacy', ['--connection' => $conn])->assertSuccessful();

        foreach (ArchiveDocument::all() as $doc) {
            $this->assertNull($doc->file_path, 'Link berkas kosong harus menjadi file_path NULL.');
        }
    }

    public function test_post_import_validation_reports_zero_orphans_and_consistent_status(): void
    {
        $conn = $this->setupLegacyFixtureConnection();

        $this->artisan('arsip:import-legacy', ['--connection' => $conn])
            ->expectsOutputToContain('Referensi Yatim (Orphans)        : 0 (Lengkap & Terhubung)')
            ->expectsOutputToContain('Konsistensi Status Dokumen       : 100% Konsisten')
            ->assertSuccessful();
    }

    public function test_post_import_validation_detects_status_inconsistency(): void
    {
        $conn = $this->setupLegacyFixtureConnection();

        // Buat anomali di legacy data: Dokumen 3 berstatus 'available' (0) tetapi pinjaman 3 diubah menjadi 'borrowed' (1)
        DB::connection($conn)->table('tb_pinjam')
            ->where('id_pinjam', 3)
            ->update(['status' => '1']);

        $this->artisan('arsip:import-legacy', ['--connection' => $conn])
            ->expectsOutputToContain('inkonsisten')
            ->assertSuccessful();
    }

    public function test_import_against_actual_arsip_audit_database(): void
    {
        try {
            DB::connection('legacy')->getPdo();
        } catch (\Throwable $e) {
            $this->markTestSkipped('Koneksi MySQL legacy (arsip_audit) tidak tersedia di lingkungan ini.');
        }

        $this->artisan('arsip:import-legacy', ['--connection' => 'legacy'])
            ->expectsOutputToContain('Users (Pegawai/Arsiparis)   | 3')
            ->expectsOutputToContain('Archive Documents (Berkas)  | 5')
            ->expectsOutputToContain('Document Loans (Peminjaman) | 4')
            ->expectsOutputToContain('Normalisasi Return Date Bug CI3 : 2 record')
            ->expectsOutputToContain('Referensi Yatim (Orphans)        : 0')
            ->expectsOutputToContain('Konsistensi Status Dokumen       : 100% Konsisten')
            ->assertSuccessful();

        $this->assertSame(4, User::count());
        $this->assertSame(5, ArchiveDocument::count());
        $this->assertSame(4, DocumentLoan::count());
    }
}
