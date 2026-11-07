# PAKET PROMPT MIGRASI — Arsip Kantah ATR/BPN Sumbawa
**CodeIgniter 3 HMVC → Laravel 13 + Tailwind (Blade + Alpine.js)**

## Cara pakai

1. Project Laravel 13 ada di root. Folder CI lama ada di `arsip/` (read-only, referensi). Laporan audit ada di `arsip/_audit/`.
2. Jalankan `git init` dan commit awal sebelum mulai, supaya tiap fase bisa dibatalkan.
3. Untuk setiap fase, tempel **BAGIAN A** lalu **prompt fase** yang sesuai ke Antigravity. Jangan lompat fase. Satu fase baru boleh dimulai setelah `php artisan test` hijau dan hasilnya di-commit.
4. Jika Antigravity berhenti di tengah, lanjutkan dengan "lanjutkan fase N dari langkah X".

---

# BAGIAN A — Konteks, Keputusan Final, dan Aturan (tempel di SETIAP fase)

## A1. Peran dan sumber kebenaran

Kamu adalah engineer Laravel senior yang memigrasikan Sistem Informasi Arsip Kantah ATR/BPN Kabupaten Sumbawa dari CodeIgniter 3 ke Laravel 13. Sumber kebenaran berurutan adalah (1) keputusan di Bagian A ini, (2) laporan `arsip/_audit/*.md`, (3) kode lama di `arsip/`. Jika ketiganya bertentangan, verifikasi kode lama, jangan menebak, lalu laporkan perbedaannya. Jika masih ambigu, berhenti dan ajukan pertanyaan.

## A2. Keputusan teknis

- **Stack** — Laravel 13, Tailwind CSS (versi bawaan Vite project), Alpine.js, PHPUnit. Tanpa jQuery, tanpa Livewire, tanpa Inertia, tanpa starter kit. Semua aset dibundel Vite (jaringan kantor LAN, tanpa CDN dan tanpa web font eksternal).
- **Strategi** — port 1:1 perilaku bisnis, ditulis ulang dengan konvensi Laravel. Fitur baru tidak ditambahkan (tanpa QR Code, tanpa notifikasi jatuh tempo, tanpa ekspor). Pengecualian hanya perbaikan bug dan penyesuaian yang tertulis di dokumen ini.
- **Bahasa** — antarmuka dan pesan validasi dalam bahasa Indonesia (`APP_LOCALE=id`, file `lang/id`). Kode, nama tabel, route, dan variabel dalam bahasa Inggris. Timezone `Asia/Makassar`.
- **Database** — MySQL/MariaDB, charset `utf8mb4_unicode_ci`.
- **Dead code** — 16 modul/folder dan tabel `tb_member, tb_outlet, tb_paket, reg_districts, provinces, regencies, tb_pegawai` tidak dimigrasi dan tidak diimpor (terbukti di `_audit/02`).

## A3. Skema final

Dasar skema ada di `_audit/04-database-pemetaan-skema.md`, dengan penyesuaian berikut.

- `users` — tambah `nip` (string, nullable), `is_active` (boolean, default true), `theme` (string, default `samudra`). `role` memakai enum PHP `UserRole` (`super_admin`, `archivist`, `employee`). `username` unik. Pegawai peminjam adalah `users` (tabel `tb_pegawai` dihapus).
- `archive_documents` — pakai `softDeletes()`. Tambah `original_filename`, `file_size` (unsignedBigInteger), `mime_type`, selain `file_path`. Tahun bernilai `"-"` di data lama menjadi NULL.
- `document_loans` — status enum `pending, borrowed, returned, cancelled`. Tambah `approved_by`, `approved_at`, `received_by` (user yang menerima pengembalian), `cancelled_at`. `return_date` nullable dan wajib NULL selama status `pending` atau `borrowed`.
- Status dokumen — enum PHP `DocumentStatus` (`available, booked, borrowed, lost, invalid`).
- Index minimal pada `archive_documents` untuk `document_number`, `year`, `district_id`, `village_id`, `status`, `document_type_id`, `land_right_type_id`, dan pada `document_loans` untuk `status`, `borrower_id`, `archive_document_id`.
- Foreign key ketat di semua relasi. `districts.id` char(7), `villages.id` char(10), `villages.district_id` ke `districts`.

## A4. Role dan hak akses (keputusan final)

Tiga role, diimplementasikan dengan enum + Policy/Gate + middleware `can:` pada route. Tanpa Spatie. `super_admin` adalah superset dari `archivist` (kantor kecil, mencegah kebuntuan, dan pelaku selalu tercatat lewat `approved_by` / `received_by`).

| Kemampuan | super_admin | archivist | employee |
| :--- | :---: | :---: | :---: |
| Dashboard (isi berbeda per role) | ya | ya | ya |
| Cari katalog, lihat status dan lokasi rak | ya | ya | ya |
| Pratinjau file scan | ya | ya | ya |
| Tambah/ubah/hapus dokumen, upload, ubah status fisik | ya | ya | tidak |
| Master rak, jenis berkas, jenis hak, kegiatan | ya | ya | tidak |
| Master seksi dan manajemen user | ya | tidak | tidak |
| Booking untuk diri sendiri | ya | ya | ya |
| Booking atas nama pegawai lain (pilih seksi lalu pegawai) | ya | ya | tidak |
| Approve peminjaman dan proses pengembalian | ya | ya | tidak |
| Batalkan booking berstatus pending | semua | semua | hanya miliknya |
| Lihat daftar peminjaman aktif dan riwayat pengembalian | semua | semua | hanya miliknya |
| Riwayat peminjam per dokumen (jejak audit) | ya | ya | tidak |

## A5. Aturan bisnis dan perbaikan bug (wajib)

Alur status dokumen dan peminjaman mengikuti `_audit/03`. Perubahan dari perilaku lama adalah sebagai berikut.

1. Booking membuat loan `pending` dan dokumen `booked`. Booking hanya boleh untuk dokumen `available`. Seluruh proses berada dalam `DB::transaction` dengan `lockForUpdate` pada baris dokumen, lalu status dicek ulang di dalam transaksi (mencegah double booking).
2. Approve (`pending` → `borrowed`) mengisi `approved_by` dan `approved_at`, dokumen menjadi `borrowed`, dan `return_date` TETAP NULL.
3. Return (`borrowed` → `returned`) mengisi `return_date` hari ini dan `received_by`, dokumen kembali `available`.
4. Cancel hanya untuk `pending` (status menjadi `cancelled`, `cancelled_at` terisi, dokumen kembali `available`). Peminjaman `borrowed` tidak bisa dibatalkan, harus lewat Return. Tidak ada hard delete transaksi.
5. Ubah status fisik manual hanya ke `available`, `lost`, atau `invalid`, dan ditolak jika dokumen punya loan aktif (`pending` atau `borrowed`).
6. Hapus dokumen memakai soft delete dan ditolak jika punya loan aktif.
7. Field `notes` (keterangan) tersedia untuk semua role. `service_activity_id` wajib.
8. Daftar peminjaman milik pegawai memakai filter `borrower_id = auth()->id()`, tanpa kebocoran data pegawai lain (bug `AND ... OR` di CI3).
9. Halaman riwayat pengembalian harus menampilkan data (bug join `tb_pegawai` di CI3).
10. Setelah login semua role diarahkan ke `/dashboard`, dengan isi dashboard sesuai role. Dashboard admin menghitung pending, borrowed, dan total dokumen. Dashboard pegawai menghitung pending dan borrowed milik sendiri.
11. Modul lama `search`, `owner`, `peminjaman`, `peminjaman2` digabung menjadi satu halaman **Katalog Berkas** (filter, status, lokasi rak, aksi booking, dan modal riwayat bagi staf). Modul `pinjam`, `pinjam2`, `kembali` menjadi halaman **Peminjaman** (tab aktif, tab riwayat pengembalian).
12. Dropdown berantai Kecamatan → Desa dan Seksi → Pegawai memakai endpoint JSON (`/api/districts/{district}/villages`, `/api/sections/{section}/users`), dengan model binding sehingga id tidak valid menghasilkan 404.

## A6. File dan penyimpanan

- Disk privat (`storage/app/private/documents/{YYYY}/{MM}/{uuid}.{ext}`), tanpa symlink publik.
- Validasi upload memakai Form Request. Ekstensi dan MIME yang diizinkan adalah `pdf, jpg, jpeg, png`. Ukuran maksimal 20 MB. Dokumentasikan penyesuaian `upload_max_filesize` dan `post_max_size` di README.
- Pratinjau lewat `GET /documents/{document}/file` (auth + policy), memakai `Storage::response()` dengan `Content-Disposition: inline`. File hilang di disk menghasilkan 404 yang rapi.
- Gagal upload harus menampilkan error validasi, tidak boleh menyimpan data diam-diam (bug lama).
- Saat dokumen diganti file barunya atau dihapus permanen, file lama ikut dibersihkan dengan aman.

## A7. Antarmuka dan tema

- **Layout kolom tetap** — top bar tetap (tinggi 64px), sidebar kiri tetap (lebar 256px di desktop, drawer di mobile), area konten dalam container dengan grid 12 kolom dan span baku. Kartu statistik dashboard memakai span tetap (misalnya 3 kartu `md:col-span-4`). Tabel selalu lebar penuh di dalam card, dengan scroll horizontal internal pada layar sempit.
- **Palet merek** — `#780000`, `#c1121f`, `#fdf0d5`, `#003049`, `#669bbc`. Definisikan sebagai design token (CSS variable lewat `@theme` / variabel CSS) dengan nama semantik (`primary`, `primary-strong`, `accent`, `surface`, `danger`, `danger-strong`), bukan nama warna.
- **Tema yang dapat dipilih** — tiga preset disimpan di `users.theme` dan diterapkan lewat atribut `data-theme` pada `<html>`.
  - `samudra` (default) — sidebar `#003049`, aksen `#669bbc`, latar `#fdf0d5`, bahaya `#c1121f`.
  - `marun` — sidebar `#780000`, aksen `#c1121f`, latar `#fdf0d5`.
  - `senja` — sidebar `#003049`, aksen `#c1121f`, latar `#fdf0d5`.
  Pemilih tema ada di menu pengguna, berubah langsung tanpa reload (Alpine) dan disimpan lewat `PATCH /profile/theme` dengan validasi whitelist.
- **Kontras** — semua pasangan teks/latar minimal WCAG AA (4.5:1). Warna `#669bbc` tidak boleh dipakai sebagai latar teks putih, gunakan teks `#003049`. Badge status boleh memakai warna fungsional di luar palet (hijau, kuning, abu, merah) supaya status mudah dibedakan.
- **Komponen Blade reusable** — layout app dan guest, sidebar, topbar, card, stat-card, badge status, button, input, select, modal, toast/flash, dan `x-data-table`.

## A8. Tabel data (pengganti DataTables)

Tanpa jQuery/DataTables. Buat satu komponen `x-data-table` berbasis Alpine yang mengambil data dari endpoint JSON dengan paginasi, pencarian, filter, dan sorting di sisi server.

- Request memakai query `page`, `per_page` (maksimal 100), `search`, `sort`, `direction`, serta filter spesifik halaman.
- Response berbentuk `{ "data": [...], "meta": { "current_page", "last_page", "per_page", "total" } }`, dibuat dengan API Resource. Backend tidak boleh merender HTML tombol, tombol aksi dirender di frontend sesuai izin yang dikirim (`can` per baris).
- Kolom yang boleh diurutkan di-whitelist. Nilai `sort` di luar whitelist ditolak atau diabaikan (cegah injeksi). Hindari N+1 dengan eager loading.

## A9. Pengujian

- PHPUnit (kelas berbasis `TestCase`, bukan Pest). Fitur tanpa tes dianggap belum selesai.
- Gunakan `RefreshDatabase`, factories, `Storage::fake('local')`. SQLite in-memory boleh dipakai untuk tes, tetapi `lockForUpdate` tidak berefek di SQLite, jadi tes double booking menguji penjagaan status secara berurutan (request kedua harus gagal setelah yang pertama), dan sebutkan keterbatasan ini di komentar tes.
- Setiap route punya minimal satu feature test. Setiap method Policy diuji untuk ketiga role dengan data provider (matriks role × aksi × hasil yang diharapkan sesuai A4).
- Kode uji dan skenario merujuk `_audit/09-skenario-uji.md` (AUTH, DOC, LOAN, AUDIT, WIL, FILE), disesuaikan dengan keputusan A4 dan A5.

## A10. Aturan kerja

1. Folder `arsip/` read-only. Jangan mengubah atau menghapus isinya.
2. Jangan menghapus atau menyalin file pribadi `Surat_Bebas_Narkoba_dan_Hasil_Lab.pdf`, `Surat_Pernyataan.pdf`, `Surat_Pernyataan_5_Poin.pdf`. Cukup laporkan agar pemilik menghapusnya manual.
3. Verifikasi syarat versi Laravel 13 (PHP, ekstensi, Node) lewat `composer`/dokumentasi resmi. Jangan mengandalkan ingatan untuk versi package. Pasang package seminimal mungkin dan sebutkan alasannya.
4. Password tidak pernah ditulis dalam kode, seeder, JSON, atau log. Akun super admin awal dibuat dari variabel `.env` (`SEED_ADMIN_USERNAME`, `SEED_ADMIN_PASSWORD`).
5. Logika bisnis ditaruh di Action/Service class (misalnya `BookDocument`, `ApproveLoan`, `ReturnLoan`, `CancelLoan`), bukan di controller. Validasi memakai Form Request. Otorisasi memakai Policy.
6. Jangan lanjut ke langkah berikutnya jika `php artisan test` merah.
7. Akhiri tiap fase dengan laporan berisi apa yang dikerjakan, daftar file utama, hasil tes (jumlah lulus), deviasi dari dokumen ini beserta alasannya, dan pertanyaan terbuka. Lalu berhenti dan tunggu perintah fase berikutnya.

---

# FASE 1 — Fondasi, Skema, Tema, dan Seeder

**Tujuan.** Project siap, skema database dan model lengkap, shell UI dengan tema, data master ter-seed.

**Kerjakan**

1. Cek lingkungan (PHP, Composer, Node, MySQL/MariaDB di Herd) dan baca seluruh `arsip/_audit/*.md`. Tampilkan rencana kerja singkat sebelum mulai.
2. Konfigurasi `.env.example`, locale `id`, timezone `Asia/Makassar`, koneksi DB `utf8mb4`, disk `documents` privat, file `lang/id` untuk pesan validasi dan auth.
3. Pasang Alpine.js lewat npm dan konfigurasikan Tailwind + Vite. Bangun design token, tiga tema (A7), layout shell (top bar tetap, sidebar tetap, grid 12 kolom), dan komponen Blade dasar. Halaman login dan dashboard masih placeholder.
4. Buat migrasi sesuai A3 dengan urutan dependensi, lengkap dengan enum PHP, model beserta relasi/cast/scope, dan factories.
5. Seed data. Muat `arsip/arsip.sql` ke database sementara (koneksi `legacy`, konfigurasi via `.env`, jangan commit kredensial), ekspor tabel wilayah dan master ke file JSON di `database/seeders/data/` (tanpa password dan tanpa data transaksional), lalu commit file JSON itu. Seeder membaca JSON saja dan TIDAK boleh bergantung pada koneksi legacy. Tabel yang di-seed adalah `districts` (24), `villages` (166), `sections` (7), `storage_racks` (4), `document_types` (6), `land_right_types` (5), `service_activities` (12). ID master lama dipertahankan agar relasi data lama valid. Seeder user hanya membuat satu `super_admin` dari `.env`.

**Tes wajib.** Struktur migrasi, relasi Eloquent, mapping enum, jumlah baris hasil seeder, seeder idempoten (dijalankan dua kali tidak menggandakan data), dan token tema ada di build.

**Selesai jika.** `php artisan migrate:fresh --seed` sukses, `npm run build` sukses, `php artisan test` hijau, shell UI tampil dengan ketiga tema.

---

# FASE 2 — Autentikasi, RBAC, Manajemen User

**Tujuan.** Login aman berbasis username, tiga role, policy, serta manajemen user dan seksi.

**Kerjakan**

1. Login custom (tanpa starter kit). Input `username` dan `password`, pesan error generik, throttle 5 percobaan per menit per kombinasi username + IP, regenerasi sesi saat login dan logout via POST, user `is_active = false` tidak bisa login. Semua role diarahkan ke `/dashboard`.
2. Enum `UserRole`, helper pada `User` (misalnya `isStaff()` untuk archivist dan super_admin), Policy dan Gate sesuai matriks A4, dipasang di route lewat middleware `can:`.
3. Halaman `/dashboard` dengan shell dan sapaan sesuai role (angka statistik diisi di Fase 4).
4. CRUD **Seksi** dan **User** (khusus super_admin) lewat `x-data-table` (komponen versi dasar dibangun di fase ini jika belum ada). Aturan user: `username` unik, password minimal 8 karakter, reset password oleh super_admin, tidak boleh menghapus diri sendiri, user yang punya riwayat peminjaman tidak boleh dihapus (cukup dinonaktifkan), seksi yang masih dipakai user tidak boleh dihapus.
5. Profil — ubah password sendiri (butuh password lama) dan pemilih tema (A7) dengan `PATCH /profile/theme`.

**Tes wajib.** AUTH-01 sampai AUTH-05, login user nonaktif, throttle, percobaan SQL injection pada username, matriks akses untuk semua route yang sudah ada (tiga role + guest), validasi CRUD user dan seksi, guard hapus diri sendiri dan hapus data yang masih dipakai, tema di luar whitelist ditolak.

**Selesai jika.** Semua tes hijau dan guest tidak bisa mengakses halaman apa pun selain `/login`.

---

# FASE 3 — Master Data dan Katalog Berkas

**Tujuan.** Master klasifikasi, API wilayah, katalog berkas lengkap dengan upload dan pratinjau aman.

**Kerjakan**

1. CRUD master **Rak, Jenis Berkas, Jenis Hak, Kegiatan** (archivist dan super_admin). Data yang masih direferensikan dokumen atau peminjaman tidak boleh dihapus (pesan jelas).
2. Endpoint JSON wilayah dan seksi sesuai A5 butir 12 (butuh login).
3. **Katalog Berkas** (`/documents`) dengan `x-data-table` server-side. Filter berupa kecamatan, desa (berantai), nomor berkas (partial), jenis hak, jenis berkas, dan tahun. Kolom berupa nomor, tahun, rak, jenis berkas, jenis hak, nomor urut/range, kecamatan, desa, waktu input, dan status (badge). Tombol aksi muncul sesuai izin per baris. Tombol Booking dibuat di Fase 4.
4. Tambah, ubah, hapus dokumen, dan ubah status fisik sesuai A5 butir 5 dan 6, termasuk upload sesuai A6 (modal di Alpine) dan pratinjau `GET /documents/{document}/file`.
5. Normalisasi input (trim, tahun kosong menjadi NULL) di Form Request.

**Tes wajib.** DOC-01 sampai DOC-07 (termasuk ekstensi terlarang, ukuran lebih dari 20 MB, pegawai ditolak 403), FILE-01 sampai FILE-03, WIL-01 dan WIL-02, whitelist `sort` (nilai liar tidak menimbulkan error SQL), filter kombinasi di katalog, serta aturan hapus data master yang masih dipakai.

**Selesai jika.** Katalog dapat dipakai penuh oleh archivist, employee hanya bisa membaca dan mempratinjau, dan semua tes hijau.

---

# FASE 4 — Peminjaman, Pengembalian, Riwayat, Dashboard

**Tujuan.** Siklus hidup peminjaman lengkap dengan semua perbaikan bug.

**Kerjakan**

1. Action class `BookDocument`, `ApproveLoan`, `ReturnLoan`, `CancelLoan` sesuai A5 butir 1 sampai 4, masing-masing dalam transaksi dan mengecek status ulang.
2. Modal Booking di Katalog. Pegawai terkunci ke dirinya sendiri, sedangkan staf memilih Seksi lalu Pegawai (endpoint `/api/sections/{section}/users`). Field kegiatan wajib dan keterangan opsional.
3. Halaman **Peminjaman** dengan tab Aktif (pending dan borrowed, dengan tombol Approve, Kembalikan, dan Batalkan sesuai izin) dan tab Riwayat Pengembalian. Staf melihat semua data, pegawai hanya miliknya.
4. Modal **Riwayat per dokumen** (staf saja) berisi semua peminjaman kronologis (peminjam, seksi, kegiatan, tanggal pinjam, tanggal kembali, status termasuk `cancelled`, serta approver dan penerima).
5. Dashboard dengan angka sesuai A5 butir 10. Kartu admin mengarah ke tab Peminjaman yang relevan.

**Tes wajib.** LOAN-01 sampai LOAN-07, AUDIT-01 sampai AUDIT-03, `return_date` tetap NULL setelah approve, pegawai tidak bisa approve atau return (403), pegawai tidak bisa membatalkan milik orang lain, borrowed tidak bisa dibatalkan, ubah status dan hapus dokumen ditolak saat ada loan aktif, daftar milik sendiri tidak bocor, angka dashboard benar untuk tiap role, serta matriks Policy lengkap A4.

**Selesai jika.** Alur booking → approve → return dan booking → cancel berjalan end-to-end di UI dan di tes.

---

# FASE 5 — Impor Data Legacy

**Tujuan.** Memindahkan data transaksional lama dengan aman dan dapat diulang.

**Kerjakan**

1. Artisan command `arsip:import-legacy` dengan opsi `--dry-run` (hanya melaporkan) dan `--connection=legacy`. Command bersifat idempoten (aman dijalankan ulang) dan memakai satu transaksi.
2. Impor `login` → `users` dengan password lama di-hash (Bcrypt) saat impor, tidak pernah ditulis ke file atau log. Level 0 menjadi `archivist` dan level 1 menjadi `employee`. Seksi dipetakan lewat `id_seksi`. Super admin awal tetap berasal dari seeder `.env`.
3. Impor `tb_add_dokumen` → `archive_documents` dengan mapping status (`0` dan `aktif` → `available`, `1` → `borrowed`, `2` → `lost`, `3` → `invalid`, `4` → `booked`) dan tahun `"-"` menjadi NULL. Kolom `link` kosong menjadi `file_path` NULL.
4. Impor `tb_pinjam` → `document_loans` dengan mapping status (`4` → `pending`, `1` → `borrowed`, `0` → `returned`) dan `id_peg` → `borrower_id` (merujuk `login.id`). **Penting.** Pada data lama, peminjaman berstatus `1` memiliki `tgl_kembali` yang terisi salah akibat bug approval, jadi set `return_date` menjadi NULL untuk status `borrowed` dan `pending`, dan catat jumlahnya di laporan.
5. Validasi pasca-impor (jumlah baris vs sumber, tidak ada referensi yatim, dan konsistensi status dokumen dengan loan terakhir) lalu cetak laporan ringkas.
6. File PDF lama. Jangan memetakan otomatis. Buat command `arsip:legacy-files-report` yang hanya mendaftar 11 file di `arsip/asset/dokumen/` (nama, ukuran), menandai 3 file pribadi sebagai "hapus manual", dan menyalin 8 file warkah ke `storage/app/legacy_backup/` (cadangan, bukan bagian katalog).

**Tes wajib.** Command diuji dengan fixture database legacy kecil (mode dry-run tidak mengubah apa pun, idempoten, mapping status benar, `return_date` dinormalkan, password ter-hash dan bisa login, tahun `"-"` menjadi NULL, laporan file tidak menyalin file pribadi).

**Selesai jika.** Impor atas `arsip_audit` menghasilkan 3 user, 5 dokumen, 4 peminjaman dengan laporan tanpa anomali.

---

# FASE 6 — Pengerasan dan Penutupan

**Tujuan.** Siap pakai dan siap diserahterimakan.

**Kerjakan**

1. Tinjau keamanan. Sesi aman (`SESSION_SECURE_COOKIE` untuk produksi), CSRF aktif di semua form dan request Alpine (header `X-CSRF-TOKEN`), output Blade ter-escape, rate limit login, header keamanan dasar, dan `APP_DEBUG=false` pada contoh produksi.
2. Kinerja. Periksa N+1 pada semua endpoint tabel, validasi index sesuai A3, dan uji katalog dengan 20.000 baris dokumen dari factory (respons tabel tetap cepat dan payload kecil).
3. Bersih-bersih. Hapus sisa placeholder, route yang tidak terpakai, dan string hardcoded lama. Pastikan tidak ada "PT Garam" atau "toko_haris". Judul aplikasi memakai `config('app.name')`.
4. Dokumentasi `README.md`. Instalasi di Laravel Herd (Windows), ekstensi PHP yang dibutuhkan, `.env`, `php artisan migrate --seed`, langkah impor legacy, pengaturan `upload_max_filesize` dan `post_max_size`, cara menjalankan tes, serta matriks role.
5. Audit akhir. Buat tabel penelusuran dari setiap temuan di `_audit/00-ringkasan.md` dan setiap kode di `_audit/09-skenario-uji.md` ke implementasi dan tes yang menutupnya. Tandai apa pun yang belum tertutup.

**Selesai jika.** `php artisan test` hijau penuh, `npm run build` sukses, `migrate:fresh --seed` lalu impor legacy berjalan bersih, dan tabel penelusuran tidak punya baris yang belum tertutup tanpa alasan tertulis.
