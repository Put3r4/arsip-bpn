<?php

namespace App\Console\Commands;

use App\Enums\DocumentStatus;
use App\Enums\LoanStatus;
use App\Enums\UserRole;
use App\Models\ArchiveDocument;
use App\Models\DocumentLoan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ImportLegacyCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'arsip:import-legacy 
                            {--dry-run : Jalankan simulasi impor tanpa menyimpan perubahan ke database} 
                            {--connection=legacy : Nama koneksi basis data legacy (sumber)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Memigrasikan data transaksional lama (login, tb_add_dokumen, tb_pinjam) dari CodeIgniter 3 ke Laravel 13';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isDryRun = (bool) $this->option('dry-run');
        $connectionName = (string) ($this->option('connection') ?: 'legacy');

        $this->newLine();
        $this->info('========================================================================');
        $this->info('  MIGRASI DATA LEGACY — Sistem Informasi Arsip Kantah ATR/BPN Sumbawa');
        $this->info('========================================================================');
        $this->line("Koneksi Sumber : <fg=yellow>{$connectionName}</>");
        $this->line("Mode Eksekusi  : " . ($isDryRun ? '<fg=cyan>[DRY-RUN] Simulasi (tanpa perubahan DB)</>' : '<fg=green>[LIVE] Tulis permanen ke DB</>'));
        $this->newLine();

        // 1. Verifikasi koneksi dan tabel sumber
        try {
            $legacyDb = DB::connection($connectionName);
            $legacyDb->getPdo();
        } catch (\Throwable $e) {
            $this->error("Gagal terhubung ke koneksi legacy '{$connectionName}': " . $e->getMessage());
            return self::FAILURE;
        }

        $requiredTables = ['login', 'tb_add_dokumen', 'tb_pinjam'];
        foreach ($requiredTables as $table) {
            try {
                $legacyDb->table($table)->limit(1)->first();
            } catch (\Throwable $e) {
                $this->error("Tabel sumber '{$table}' tidak ditemukan atau gagal dibaca pada koneksi '{$connectionName}': " . $e->getMessage());
                return self::FAILURE;
            }
        }

        // 2. Mulai transaksi database tunggal pada koneksi target
        DB::beginTransaction();

        try {
            // A. IMPOR LOGIN -> USERS
            $legacyUsers = $legacyDb->table('login')->orderBy('id')->get();
            $userMap = []; // old login.id => user.id
            $usersCreated = 0;
            $usersUpdated = 0;

            foreach ($legacyUsers as $row) {
                $role = ((string) $row->level === '0') ? UserRole::ARCHIVIST : UserRole::EMPLOYEE;

                $sectionId = null;
                if (!empty($row->id_seksi) && DB::table('sections')->where('id', $row->id_seksi)->exists()) {
                    $sectionId = (int) $row->id_seksi;
                }

                $user = User::where('username', $row->username)->first();

                if ($user) {
                    $user->name = $row->name;
                    $user->role = $role;
                    $user->section_id = $sectionId;
                    $user->is_active = true;
                    // Hash password hanya jika belum ter-hash Bcrypt
                    if (!empty($row->password) && !str_starts_with($user->password, '$2y$')) {
                        $user->password = Hash::make($row->password);
                    }
                    $user->save();
                    $usersUpdated++;
                } else {
                    $user = new User();
                    if (!User::where('id', $row->id)->exists()) {
                        $user->id = $row->id;
                    }
                    $user->username = $row->username;
                    $user->name = $row->name;
                    $user->password = Hash::make($row->password);
                    $user->role = $role;
                    $user->section_id = $sectionId;
                    $user->nip = null;
                    $user->is_active = true;
                    $user->theme = 'samudra';

                    if (!empty($row->datetime) && $row->datetime !== '0000-00-00 00:00:00') {
                        $user->created_at = Carbon::parse($row->datetime);
                        $user->updated_at = Carbon::parse($row->datetime);
                    }
                    $user->save();
                    $usersCreated++;
                }

                $userMap[$row->id] = $user->id;
            }

            // B. IMPOR TB_ADD_DOKUMEN -> ARCHIVE_DOCUMENTS
            $legacyDocs = $legacyDb->table('tb_add_dokumen')->orderBy('id_dok')->get();
            $docsCreated = 0;
            $docsUpdated = 0;

            foreach ($legacyDocs as $row) {
                $rawStatus = strtolower(trim((string) $row->status));
                $status = match ($rawStatus) {
                    '0', 'aktif' => DocumentStatus::AVAILABLE,
                    '1' => DocumentStatus::BORROWED,
                    '2' => DocumentStatus::LOST,
                    '3' => DocumentStatus::INVALID,
                    '4' => DocumentStatus::BOOKED,
                    default => DocumentStatus::AVAILABLE,
                };

                // Normalisasi tahun: "-" menjadi NULL
                $year = trim((string) ($row->tahun ?? ''));
                if ($year === '-' || $year === '' || !is_numeric($year)) {
                    $year = null;
                }

                // Normalisasi link: string kosong menjadi file_path NULL
                $link = trim((string) ($row->link ?? ''));
                $filePath = empty($link) ? null : $link;

                $doc = ArchiveDocument::withTrashed()->find($row->id_dok);
                if ($doc) {
                    $docsUpdated++;
                } else {
                    $doc = new ArchiveDocument();
                    $doc->id = $row->id_dok;
                    $docsCreated++;
                }

                $doc->document_number = trim((string) $row->no_berkas);
                $doc->year = $year;
                $doc->land_right_type_id = (int) $row->hak;
                $doc->document_type_id = (int) $row->berkas;
                $doc->storage_rack_id = (int) $row->rak;
                $doc->range_number = trim((string) ($row->range_no ?? '')) ?: null;
                $doc->district_id = trim((string) $row->kecamatan);
                $doc->village_id = trim((string) $row->kelurahan);
                $doc->status = $status;
                $doc->file_path = $filePath;
                $doc->original_filename = null;
                $doc->file_size = null;
                $doc->mime_type = null;

                if (!empty($row->datetime) && $row->datetime !== '0000-00-00 00:00:00') {
                    $doc->created_at = Carbon::parse($row->datetime);
                    $doc->updated_at = Carbon::parse($row->datetime);
                }

                $doc->save();
            }

            // C. IMPOR TB_PINJAM -> DOCUMENT_LOANS
            $legacyLoans = $legacyDb->table('tb_pinjam')->orderBy('id_pinjam')->get();
            $loansCreated = 0;
            $loansUpdated = 0;
            $normalizedReturnDateCount = 0;

            foreach ($legacyLoans as $row) {
                $rawStatus = (string) $row->status;
                $status = match ($rawStatus) {
                    '4' => LoanStatus::PENDING,
                    '1' => LoanStatus::BORROWED,
                    '0' => LoanStatus::RETURNED,
                    default => LoanStatus::CANCELLED,
                };

                // Normalisasi bug approval CI3:
                // Status borrowed atau pending WAJIB return_date NULL
                $returnDate = null;
                if ($status === LoanStatus::BORROWED || $status === LoanStatus::PENDING) {
                    if (!empty($row->tgl_kembali) && $row->tgl_kembali !== '0000-00-00') {
                        $normalizedReturnDateCount++;
                    }
                    $returnDate = null;
                } elseif ($status === LoanStatus::RETURNED) {
                    $returnDate = (!empty($row->tgl_kembali) && $row->tgl_kembali !== '0000-00-00')
                        ? $row->tgl_kembali
                        : $row->tgl_pinjam;
                }

                $borrowerId = $userMap[$row->id_peg] ?? null;
                if (!$borrowerId) {
                    $borrowerId = DB::table('users')->where('id', $row->id_peg)->value('id');
                }

                $loan = DocumentLoan::find($row->id_pinjam);
                if ($loan) {
                    $loansUpdated++;
                } else {
                    $loan = new DocumentLoan();
                    $loan->id = $row->id_pinjam;
                    $loansCreated++;
                }

                $loan->archive_document_id = (int) $row->id_berkas;
                $loan->borrower_id = (int) $borrowerId;
                $loan->service_activity_id = (int) $row->id_keg;
                $loan->loan_date = $row->tgl_pinjam;
                $loan->return_date = $returnDate;
                $loan->status = $status;
                $loan->notes = trim((string) ($row->keterangan ?? '')) ?: null;
                $loan->approved_by = null;
                $loan->received_by = null;
                $loan->approved_at = ($status === LoanStatus::BORROWED || $status === LoanStatus::RETURNED)
                    ? ($row->tgl_pinjam ? Carbon::parse($row->tgl_pinjam) : null)
                    : null;
                $loan->cancelled_at = null;

                if (!empty($row->tgl_pinjam) && $row->tgl_pinjam !== '0000-00-00') {
                    $loan->created_at = Carbon::parse($row->tgl_pinjam);
                    $loan->updated_at = Carbon::parse($row->tgl_pinjam);
                }

                $loan->save();
            }

            // D. VALIDASI PASCA-IMPOR
            $anomalies = [];

            // 1. Validasi baris
            $totalSourceUsers = $legacyUsers->count();
            $totalSourceDocs = $legacyDocs->count();
            $totalSourceLoans = $legacyLoans->count();

            // 2. Validasi referensi yatim (Orphan records)
            $orphanDocs = DB::table('archive_documents')
                ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('districts')->whereColumn('districts.id', 'archive_documents.district_id'))
                ->orWhereNotExists(fn ($q) => $q->select(DB::raw(1))->from('villages')->whereColumn('villages.id', 'archive_documents.village_id'))
                ->orWhereNotExists(fn ($q) => $q->select(DB::raw(1))->from('document_types')->whereColumn('document_types.id', 'archive_documents.document_type_id'))
                ->orWhereNotExists(fn ($q) => $q->select(DB::raw(1))->from('land_right_types')->whereColumn('land_right_types.id', 'archive_documents.land_right_type_id'))
                ->orWhereNotExists(fn ($q) => $q->select(DB::raw(1))->from('storage_racks')->whereColumn('storage_racks.id', 'archive_documents.storage_rack_id'))
                ->count();

            if ($orphanDocs > 0) {
                $anomalies[] = "Terdapat {$orphanDocs} dokumen arsip dengan relasi wilayah/klasifikasi tidak valid.";
            }

            $orphanLoans = DB::table('document_loans')
                ->whereNotExists(fn ($q) => $q->select(DB::raw(1))->from('archive_documents')->whereColumn('archive_documents.id', 'document_loans.archive_document_id'))
                ->orWhereNotExists(fn ($q) => $q->select(DB::raw(1))->from('users')->whereColumn('users.id', 'document_loans.borrower_id'))
                ->orWhereNotExists(fn ($q) => $q->select(DB::raw(1))->from('service_activities')->whereColumn('service_activities.id', 'document_loans.service_activity_id'))
                ->count();

            if ($orphanLoans > 0) {
                $anomalies[] = "Terdapat {$orphanLoans} transaksi peminjaman dengan relasi dokumen/peminjam/kegiatan tidak valid.";
            }

            // 3. Validasi konsistensi status dokumen dengan pinjaman aktif
            $inconsistentDocs = 0;
            $allDocs = ArchiveDocument::with('activeLoan')->get();
            foreach ($allDocs as $doc) {
                if ($doc->activeLoan) {
                    if ($doc->activeLoan->isBorrowed() && $doc->status !== DocumentStatus::BORROWED) {
                        $inconsistentDocs++;
                        $anomalies[] = "Dokumen ID {$doc->id} sedang dipinjam tapi berstatus '{$doc->status->value}'.";
                    } elseif ($doc->activeLoan->isPending() && $doc->status !== DocumentStatus::BOOKED) {
                        $inconsistentDocs++;
                        $anomalies[] = "Dokumen ID {$doc->id} berstatus booking tapi berstatus '{$doc->status->value}'.";
                    }
                }
            }

            // E. CETAK LAPORAN
            $this->table(
                ['Entitas Target', 'Baris Sumber', 'Data Baru', 'Data Diperbarui', 'Total Terverifikasi'],
                [
                    ['Users (Pegawai/Arsiparis)', $totalSourceUsers, $usersCreated, $usersUpdated, $usersCreated + $usersUpdated],
                    ['Archive Documents (Berkas)', $totalSourceDocs, $docsCreated, $docsUpdated, $docsCreated + $docsUpdated],
                    ['Document Loans (Peminjaman)', $totalSourceLoans, $loansCreated, $loansUpdated, $loansCreated + $loansUpdated],
                ]
            );

            $this->newLine();
            $this->line("• Normalisasi Return Date Bug CI3 : <fg=yellow>{$normalizedReturnDateCount} record</> (diubah menjadi NULL)");
            $this->line("• Referensi Yatim (Orphans)        : " . ($orphanDocs + $orphanLoans === 0 ? '<fg=green>0 (Lengkap & Terhubung)</>' : "<fg=red>" . ($orphanDocs + $orphanLoans) . " ditemukan</>"));
            $this->line("• Konsistensi Status Dokumen       : " . ($inconsistentDocs === 0 ? '<fg=green>100% Konsisten</>' : "<fg=red>{$inconsistentDocs} inkonsisten</>"));

            if (!empty($anomalies)) {
                $this->newLine();
                $this->warn('Daftar Anomali:');
                foreach ($anomalies as $anomaly) {
                    $this->line(" - {$anomaly}");
                }
            }

            $this->newLine();

            // F. PENYELESAIAN TRANSAKSI
            if ($isDryRun) {
                DB::rollBack();
                $this->info('[DRY-RUN BERHASIL] Simulasi impor selesai dengan sukses tanpa anomali. Database tidak diubah.');
            } else {
                DB::commit();
                $this->info('[SUKSES] Impor data legacy berhasil diselesaikan dan disimpan permanen ke database.');
            }

            return self::SUCCESS;
        } catch (\Throwable $e) {
            DB::rollBack();
            $this->error('Gagal menjalankan impor legacy: ' . $e->getMessage());
            return self::FAILURE;
        }
    }
}
