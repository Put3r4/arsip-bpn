# Tabel Penelusuran Audit (Traceability Matrix)
## Migrasi Sistem Informasi Arsip Kantah ATR/BPN Kabupaten Sumbawa
**CodeIgniter 3 HMVC → Laravel 13 + Tailwind CSS (Blade + Alpine.js)**

---

## 1. Matriks Penelusuran 15 Temuan Kritis (`_audit/00-ringkasan.md`)

| No | Temuan Kritis di CI3 Lama | Status | Solusi & Implementasi di Laravel 13 | File Uji & Verifikasi Otomatis |
| :---: | :--- | :---: | :--- | :--- |
| **1** | **Anomali Tabel `tb_pegawai` (0 Baris)**<br>Data peminjam riil merujuk ke tabel `login.id`. | **TUNTAS** | Tabel `tb_pegawai` dihapus. Seluruh pegawai peminjam dilebur ke tabel `users` (`borrower_id` foreign key ke `users.id`, dilengkapi `section_id` & `role`). | `Tests\Unit\Models\EloquentRelationsAndEnumsTest`<br>`Tests\Feature\DocumentLoan\DocumentLoanTest` |
| **2** | **Bug Riwayat Pengembalian (`Kembali.php:114`)**<br>Query `INNER JOIN tb_pegawai` menghasilkan 0 baris data di antarmuka. | **TUNTAS** | Relasi Eloquent `belongsTo(User::class, 'borrower_id')` dan `belongsTo(Section::class)` menampilkan seluruh data riwayat secara akurat. | `Tests\Feature\DocumentLoan\LoanAuditTest::test_audit_01_admin_can_view_returned_loans_history` |
| **3** | **Ketiadaan Lapisan Model (0 Model di 33 Modul)**<br>Seluruh kueri berupa raw SQL string concatenation di controller. | **TUNTAS** | Membangun 10 Model Eloquent lengkap (`User`, `ArchiveDocument`, `DocumentLoan`, `Section`, `StorageRack`, `DocumentType`, `LandRightType`, `ServiceActivity`, `District`, `Village`) dengan type-hinting, relasi, cast enum, dan action classes. | `Tests\Unit\Models\EloquentRelationsAndEnumsTest` |
| **4** | **Bug Operator Precedence SQL di `pinjam2`**<br>`where id_peg = $uid and status = 1 or status = 4` membocorkan berkas booking staf lain. | **TUNTAS** | Scope query Eloquent ketat pada endpoint `/loans/data`: bila bukan staf arsip, kueri diproteksi `where('borrower_id', $user->id)` tanpa klausa `OR` bocor. | `Tests\Feature\DocumentLoan\LoanAuditTest::test_audit_02_employee_loans_list_strictly_scoped_no_leakage` |
| **5** | **Kerentanan SQL Injection Otentikasi & AJAX**<br>Login dan parameter ID rentan terhadap injeksi raw string `' OR '1'='1`. | **TUNTAS** | Menggunakan PDO parameterized query via Laravel Eloquent, Form Request validation, serta whitelist sorting column pada seluruh endpoint tabel. | `Tests\Feature\Auth\AuthenticationTest::test_auth_04_sql_injection_attempt_rejected`<br>`Tests\Feature\ArchiveDocument\ArchiveDocumentTest::test_whitelist_sort_rejects_unwhitelisted_column_and_defaults_safely` |
| **6** | **Password Pengguna Berupa Plaintext**<br>Seluruh password akun disimpan tanpa hash (`"1234"`). | **TUNTAS** | Password dienkripsi menggunakan Bcrypt (rounds: 12). Command impor legacy (`arsip:import-legacy`) secara otomatis menghash seluruh password lama tanpa menuliskannya ke log/file. | `Tests\Feature\Legacy\ImportLegacyCommandTest::test_imported_users_passwords_are_bcrypted_and_can_login` |
| **7** | **Proteksi CSRF Dimatikan Total**<br>`$config['csrf_protection'] = FALSE;` di CI3. | **TUNTAS** | CSRF protection bawaan Laravel aktif di seluruh form (`@csrf`) dan panggilan AJAX Alpine.js (`X-CSRF-TOKEN` via `<meta name="csrf-token">`). | `Tests\Feature\Security\SecurityHeadersAndHardeningTest::test_csrf_meta_token_rendered_in_layouts` |
| **8** | **Ketiadaan Otorisasi Backend (*Security Through Obscurity*)**<br>Hak akses hanya disembunyikan di menu tampilan visual. | **TUNTAS** | Sistem RBAC 3 role (`super_admin`, `archivist`, `employee`) ditegakkan di level backend via middleware `can:`, Gate, dan Laravel Policies (`UserPolicy`, `ArchiveDocumentPolicy`, `DocumentLoanPolicy`, `MasterDataPolicy`). | `Tests\Feature\Auth\AccessMatrixTest`<br>`Tests\Feature\DocumentLoan\LoanPolicyTest` |
| **9** | **Redirect Login Salah Arah untuk Pegawai**<br>Pegawai biasa selalu diarahkan ke dashboard admin `m_inventory`. | **TUNTAS** | Seluruh pengguna diarahkan ke `/dashboard`, dengan antarmuka dan perhitungan statistik yang disesuaikan secara dinamis berdasarkan peran pengguna. | `Tests\Feature\Auth\AuthenticationTest::test_auth_01_successful_login_with_valid_credentials_redirects_to_dashboard`<br>`Tests\Feature\Dashboard\DashboardTest` |
| **10** | **Bug Logika Persetujuan Pinjam Merusak `tgl_kembali`**<br>`update tb_pinjam set status = 1, tgl_kembali = '$date'` saat approval. | **TUNTAS** | Action `ApproveLoan` memastikan `return_date` TETAP NULL saat approval. `return_date` dan `received_by` hanya diisi saat proses pengembalian fisik (`ReturnLoan`). Impor legacy menormalkan record salah ini. | `Tests\Feature\DocumentLoan\DocumentLoanTest::test_loan_04_approve_leaves_return_date_null`<br>`Tests\Feature\Legacy\ImportLegacyCommandTest::test_approval_bug_return_dates_are_normalized_to_null` |
| **11** | **Seluruh 11 File PDF Fisik adalah Berkas Yatim**<br>Semua baris dokumen di database lama memiliki `link = ""`. | **TUNTAS** | Command `arsip:legacy-files-report` mengaudit direktori fisik, mencadangkan 8 berkas warkah ke `storage/app/legacy_backup/`, dan menandai berkas yatim tanpa mengotori database. | `Tests\Feature\Legacy\LegacyFilesReportCommandTest` |
| **12** | **Berkas Pribadi Pelamar Tercampur di Folder Publik**<br>3 file PDF pribadi pelamar kerja tahun 2021 tersimpan di folder web publik. | **TUNTAS** | Sistem penyimpanan berkas baru memakai disk privat (`storage/app/private/documents/`) yang diakses via streaming controller berizin. File pribadi lama diklasifikasikan sebagai "hapus manual" dan tidak dipublikasikan. | `Tests\Feature\Legacy\LegacyFilesReportCommandTest::test_it_classifies_personal_files_for_manual_deletion_and_does_not_copy_them`<br>`Tests\Feature\ArchiveDocument\FileSecurityTest` |
| **13** | **16 Modul & Folder Dead Code Sisa Template Lama**<br>Template POS retail dan kasir laundry (`category`, `member`, `paket`, dll.). | **TUNTAS** | Seluruh modul dead code dan tabel usang ditinggalkan di arsip lama (`arsip/`) dan tidak dimigrasikan ke Laravel 13. | `database/migrations/` (Hanya 10 tabel domain relevan yang dibuat) |
| **14** | **Skalabilitas DataTables Klien Mengancam Kinerja**<br>Client-side DataTables membekukan browser pada puluhan ribu warkah. | **TUNTAS** | Diganti dengan komponen `x-data-table` server-side paginated berbasis Alpine.js + JSON Resource API. Teruji cepat melayani 20.000 dokumen dengan payload kecil (<15 KB) dan tanpa masalah N+1 queries. | `Tests\Feature\Performance\CatalogScaleTest::test_catalog_scales_to_20000_documents_with_fast_response_and_small_payload` |
| **15** | **String Hardcoded Brand Vendor Lama**<br>Title 'PT Garam (Persero)' dan URL 'toko_haris' tersisa di kode lama. | **TUNTAS** | Seluruh nama brand lama dibersihkan 100%. Judul aplikasi sekarang terpusat menggunakan konfigurasi resmi `config('app.name')`. | `Tests\Feature\Security\SecurityHeadersAndHardeningTest::test_app_name_is_used_as_page_title` |

---

## 2. Matriks Penelusuran Kode Skenario Uji (`_audit/09-skenario-uji.md`)

| Kode Uji | Skenario Uji | Status | Method Uji di Laravel 13 |
| :---: | :--- | :---: | :--- |
| `AUTH-01` | Login Berhasil Kredensial Valid | **LULUS** | `Tests\Feature\Auth\AuthenticationTest::test_auth_01_successful_login_with_valid_credentials_redirects_to_dashboard` |
| `AUTH-02` | Login Gagal Password Salah | **LULUS** | `Tests\Feature\Auth\AuthenticationTest::test_auth_02_login_fails_with_invalid_password` |
| `AUTH-03` | Login Gagal Field Kosong | **LULUS** | `Tests\Feature\Auth\AuthenticationTest::test_auth_03_login_fails_with_empty_fields` |
| `AUTH-04` | Percobaan SQL Injection pada Login | **LULUS** | `Tests\Feature\Auth\AuthenticationTest::test_auth_04_sql_injection_attempt_rejected` |
| `AUTH-05` | Logout Pengguna & Regenerasi Sesi | **LULUS** | `Tests\Feature\Auth\AuthenticationTest::test_auth_05_authenticated_user_can_logout` |
| `DOC-01` | Admin Tambah Berkas dengan Scan PDF | **LULUS** | `Tests\Feature\ArchiveDocument\ArchiveDocumentTest::test_doc_01_archivist_can_create_document_with_valid_pdf` |
| `DOC-02` | Admin Tambah Berkas Tanpa File Scan | **LULUS** | `Tests\Feature\ArchiveDocument\ArchiveDocumentTest::test_doc_02_archivist_can_create_document_without_file` |
| `DOC-03` | Upload File Ekstensi Terlarang (.exe/.php) Ditolak | **LULUS** | `Tests\Feature\ArchiveDocument\ArchiveDocumentTest::test_doc_03_forbidden_file_extension_is_rejected` |
| `DOC-04` | Upload File Melebihi Batas (Maks 20 MB) Ditolak | **LULUS** | `Tests\Feature\ArchiveDocument\ArchiveDocumentTest::test_doc_04_file_exceeding_20mb_is_rejected` |
| `DOC-05` | Pegawai Biasa Tambah Dokumen Ditolak (403) | **LULUS** | `Tests\Feature\ArchiveDocument\ArchiveDocumentTest::test_doc_05_employee_cannot_create_document` |
| `DOC-06` | Admin Ubah Status Fisik Berkas ('lost'/'invalid') | **LULUS** | `Tests\Feature\ArchiveDocument\ArchiveDocumentTest::test_doc_06_archivist_can_update_physical_status` |
| `DOC-07` | Hapus Dokumen dengan Pinjaman Aktif Ditolak | **LULUS** | `Tests\Feature\ArchiveDocument\ArchiveDocumentTest::test_doc_07_cannot_delete_document_with_active_loan` |
| `LOAN-01` | Pegawai Booking Berkas Tersedia | **LULUS** | `Tests\Feature\DocumentLoan\DocumentLoanTest::test_loan_01_employee_can_book_available_document` |
| `LOAN-02` | Pegawai Booking Berkas Non-Tersedia Ditolak | **LULUS** | `Tests\Feature\DocumentLoan\DocumentLoanTest::test_loan_02_booking_non_available_document_fails` |
| `LOAN-03` | Race Condition: Double Booking Bersamaan | **LULUS** | `Tests\Feature\DocumentLoan\DocumentLoanTest::test_loan_03_concurrent_double_booking_prevention` |
| `LOAN-04` | Admin ACC/Approval (return_date TETAP NULL) | **LULUS** | `Tests\Feature\DocumentLoan\DocumentLoanTest::test_loan_04_approve_leaves_return_date_null` |
| `LOAN-05` | Pegawai Coba ACC Booking Sendiri Ditolak (403) | **LULUS** | `Tests\Feature\DocumentLoan\DocumentLoanTest::test_loan_05_employee_cannot_approve_loan` |
| `LOAN-06` | Admin Proses Pengembalian Berkas | **LULUS** | `Tests\Feature\DocumentLoan\DocumentLoanTest::test_loan_06_archivist_can_return_document` |
| `LOAN-07` | Pembatalan Booking oleh Pegawai/Admin | **LULUS** | `Tests\Feature\DocumentLoan\DocumentLoanTest::test_loan_07_cancel_pending_loan` |
| `AUDIT-01` | Tampilkan Riwayat Berkas Selesai Dikembalikan | **LULUS** | `Tests\Feature\DocumentLoan\LoanAuditTest::test_audit_01_admin_can_view_returned_loans_history` |
| `AUDIT-02` | Filter Peminjaman Milik Sendiri (Tanpa Bocor) | **LULUS** | `Tests\Feature\DocumentLoan\LoanAuditTest::test_audit_02_employee_loans_list_strictly_scoped_no_leakage` |
| `AUDIT-03` | Jejak Audit Kronologis per Dokumen | **LULUS** | `Tests\Feature\DocumentLoan\LoanAuditTest::test_audit_03_staff_can_view_document_loan_history_chronologically` |
| `WIL-01` | API Desa Berdasarkan Kecamatan | **LULUS** | `Tests\Feature\Wilayah\WilayahApiTest::test_wil_01_api_returns_villages_for_valid_district` |
| `WIL-02` | API Desa Kecamatan Tidak Valid (404) | **LULUS** | `Tests\Feature\Wilayah\WilayahApiTest::test_wil_02_api_returns_404_for_invalid_district` |
| `FILE-01` | Akses Pratinjau PDF Terotentikasi | **LULUS** | `Tests\Feature\ArchiveDocument\FileSecurityTest::test_file_01_authenticated_user_can_preview_scan_file` |
| `FILE-02` | Akses Pratinjau PDF Pengguna Anonim Ditolak | **LULUS** | `Tests\Feature\ArchiveDocument\FileSecurityTest::test_file_02_guest_cannot_preview_document_file` |
| `FILE-03` | Akses File Dokumen Hilang di Disk (404 Rapi) | **LULUS** | `Tests\Feature\ArchiveDocument\FileSecurityTest::test_file_03_missing_file_on_disk_returns_404_not_found` |

---

## 3. Kesimpulan Audit Akhir
- **Total Temuan Kritis**: 15 / 15 Selesai Tertutup (**100%**).
- **Total Kode Skenario Uji**: 27 / 27 Selesai Tertutup (**100%**).
- **Baris Belum Tertutup**: **0 (Nihil)**.
