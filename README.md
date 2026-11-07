# Sistem Informasi Arsip Kantah ATR/BPN Kabupaten Sumbawa

Aplikasi pengelolaan warkah dan buku tanah digital berbasis **Laravel 13**, **Tailwind CSS**, dan **Alpine.js**. Sistem ini merupakan hasil migrasi dan penulisan ulang arsitektur 1:1 dari CodeIgniter 3 HMVC lama ke standar modern Laravel dengan pengerasan keamanan, optimasi kinerja, dan pembersihan utang teknis.

---

## 1. Persyaratan Sistem

- **Sistem Operasi**: Windows 10/11 (Direkomendasikan via [Laravel Herd for Windows](https://herd.laravel.com/windows))
- **PHP**: Versi `^8.2` (PHP 8.2, 8.3, atau 8.4)
- **Ekstensi PHP Wajib**:
  - `pdo_mysql` / `mysqli` (Koneksi MariaDB/MySQL)
  - `fileinfo` (MIME sniffing upload scan berkas)
  - `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `curl`
- **Basis Data**: MySQL 8.0+ atau MariaDB 10.4+ (Default Laravel Herd: MariaDB `utf8mb4_unicode_ci`)
- **Node.js & NPM**: Node.js `^20.x` / `^22.x` & NPM `^10.x`
- **Composer**: Composer 2.7+

---

## 2. Pengaturan Batas Ukuran File PHP (Wajib untuk Scan Warkah)

Karena warkah dan buku tanah berukuran hingga 20 MB per berkas, sesuaikan konfigurasi PHP Anda:

1. Di Laravel Herd Windows, klik kanan ikon Herd di tray -> **Settings** -> **PHP** -> klik **Open php.ini** (atau buka file `php.ini` aktif Anda).
2. Temukan dan ubah direktif berikut:
   ```ini
   upload_max_filesize = 25M
   post_max_size = 30M
   memory_limit = 256M
   max_execution_time = 120
   ```
3. Simpan file dan restart PHP/Nginx melalui tray Laravel Herd.

---

## 3. Langkah Instalasi di Laravel Herd (Windows)

Jika proyek berada di direktori Herd (`%USERPROFILE%\Herd\arsip-bpn`):

### 1. Salin Berkas Konfigurasi Lingkungan
```powershell
cp .env.example .env
```

### 2. Pasang Dependensi PHP & JavaScript
```powershell
composer install
npm install
```

### 3. Generate Application Encryption Key
```powershell
php artisan key:generate
```

### 4. Konfigurasi Basis Data di `.env`
Buka `.env` dan pastikan konfigurasi basis data sesuai dengan database Anda (secara bawaan di Laravel Herd port 3306 dengan user `root` tanpa password):
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=arsip_bpn
DB_USERNAME=root
DB_PASSWORD=

# Akun Super Admin yang akan dibuat saat Seeding
SEED_ADMIN_USERNAME=superadmin
SEED_ADMIN_PASSWORD=password123
```

> **Catatan**: Buat database `arsip_bpn` terlebih dahulu di MySQL/MariaDB (misalnya lewat TablePlus, HeidiSQL, atau `mysql -u root -e "CREATE DATABASE IF NOT EXISTS arsip_bpn CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"`).

### 5. Jalankan Migrasi dan Seeder Master Data
```powershell
php artisan migrate:fresh --seed
```
Perintah ini akan membuat seluruh tabel dan mengimpor data master resmi:
- 24 Kecamatan Kabupaten Sumbawa
- 166 Desa / Kelurahan
- 7 Seksi Organisasi Kantah BPN Sumbawa
- 4 Master Rak Penyimpanan
- 6 Jenis Berkas Warkah
- 5 Jenis Hak Tanah
- 12 Kegiatan Pelayanan Pertanahan
- 1 Akun Super Admin (`superadmin`)

### 6. Kompilasi Aset Frontend (Tailwind CSS & Alpine.js)
```powershell
npm run build
```
*(Selama pengembangan aktif, Anda dapat menjalankan `npm run dev`)*.

---

## 4. Langkah Impor Data Transaksional Legacy (CI3)

Jika Anda ingin mentransfer data berkas dan riwayat peminjaman dari database CodeIgniter 3 lama (`arsip_audit`):

1. Pastikan database `arsip_audit` telah dimuat di MySQL lokal Anda.
2. Pastikan kredensial di `.env` sudah benar:
   ```env
   DB_LEGACY_HOST=127.0.0.1
   DB_LEGACY_PORT=3306
   DB_LEGACY_DATABASE=arsip_audit
   DB_LEGACY_USERNAME=root
   DB_LEGACY_PASSWORD=
   ```
3. Jalankan simulasi impor terlebih dahulu (*dry-run*):
   ```powershell
   php artisan arsip:import-legacy --dry-run
   ```
4. Eksekusi impor permanen:
   ```powershell
   php artisan arsip:import-legacy
   ```
   *Command ini otomatis:*
   - Mengimpor akun pengguna dan mengonversi password plaintext menjadi Bcrypt hash.
   - Mengimpor berkas arsip dengan mapping status yang valid (`available`, `borrowed`, `lost`, `invalid`, `booked`).
   - Mengimpor riwayat peminjaman serta memperbaiki **bug persetujuan pinjam** (mereset `return_date` yang keliru terisi menjadi NULL untuk status aktif).
5. Buat laporan audit fisik file PDF lama dan cadangkan berkas warkah:
   ```powershell
   php artisan arsip:legacy-files-report
   ```

---

## 5. Cara Menjalankan Aplikasi & Login

### Menjalankan Server
Jika menggunakan **Laravel Herd**:
Aplikasi otomatis dapat diakses melalui browser di alamat:
```
http://arsip-bpn.test
```
*(Jika ingin menggunakan SSL bawaan Herd: jalankan `herd secure arsip-bpn` lalu buka `https://arsip-bpn.test`)*.

Jika tanpa Herd / standalone Artisan:
```powershell
php artisan serve
```
Lalu akses `http://127.0.0.1:8000`.

---

### Informasi Akun & Kredensial Login

Setelah migrasi dan seeder dijalankan, Anda dapat login dengan akun berikut:

#### 1. Akun Super Admin (Bawaan Seeder `.env`)
- **Username**: `superadmin` *(atau sesuai `SEED_ADMIN_USERNAME` di `.env`)*
- **Password**: `password123` *(atau sesuai `SEED_ADMIN_PASSWORD` di `.env`)*
- **Wewenang**: Hak akses penuh (Manajemen User, Master Seksi, Master Rak/Klasifikasi, Pengelolaan Berkas, Approval & Pengembalian Pinjaman, Seluruh Riwayat Jejak Audit).

#### 2. Akun Hasil Impor Legacy (Jika Impor Dijalankan)
Data lama memiliki 3 akun yang dimigrasikan:
| Username | Peran (*Role*) | Seksi Organisasi | Password Awal Legacy |
| :--- | :--- | :--- | :--- |
| `admin` | **archivist** (Staf Arsip) | Penetapan Hak dan Pendaftaran | `1234` |
| `wahyu` | **employee** (Pegawai) | Pengadaan Tanah dan Pengembangan | `1234` |
| `testing` | **employee** (Pegawai) | Penataan dan Pemberdayaan | `1234` |

> [!TIP]
> Demi keamanan, seluruh pengguna disarankan segera mengganti password bawaan melalui menu **Profil Pengguna** (`/profile`) setelah pertama kali login.

---

## 6. Matriks Hak Akses & Peran (*Role-Based Access Control*)

Sistem menerapkan 3 peran pengguna dengan pemisahan wewenang ketat di level backend Policy dan Middleware:

| Kemampuan Sistem | Super Admin (`super_admin`) | Petugas Arsip (`archivist`) | Pegawai Seksi (`employee`) |
| :--- | :---: | :---: | :---: |
| **Dashboard Statistik** | Lengkap seluruh kantor | Lengkap seluruh kantor | Terisolasi milik sendiri |
| **Pencarian Katalog Berkas** | Ya | Ya | Ya |
| **Pratinjau File Scan (PDF/Gambar)** | Ya | Ya | Ya |
| **Booking Berkas untuk Diri Sendiri** | Ya | Ya | Ya |
| **Booking atas Nama Pegawai Lain** | Ya (Pilih Seksi & Staf) | Ya (Pilih Seksi & Staf) | Tidak |
| **Tambah, Ubah, Hapus Berkas & Upload Scan** | Ya | Ya | Tidak |
| **Ubah Status Fisik Berkas (Hilang/Rusak)** | Ya | Ya | Tidak |
| **Master Rak, Klasifikasi, Hak & Kegiatan** | Ya | Ya | Tidak |
| **Approve Permohonan Pinjam Berkas** | Ya | Ya | Tidak |
| **Proses Pengembalian Fisik Berkas** | Ya | Ya | Tidak |
| **Riwayat Jejak Audit per Berkas** | Ya | Ya | Tidak |
| **Batalkan Booking Berstatus Pending** | Semua booking | Semua booking | Hanya miliknya |
| **Lihat Daftar Pinjaman Aktif & Selesai** | Seluruh kantor | Seluruh kantor | Hanya miliknya |
| **Manajemen Seksi & Akun Pengguna** | Ya | Tidak | Tidak |

---

## 7. Pemilihan Tema Tampilan Antarmuka

Aplikasi menyediakan tiga preset tema yang memenuhi standar aksesibilitas WCAG AA (kontras minimal 4.5:1). Tema tersimpan di akun masing-masing pengguna dan berganti seketika (*instant switch* via Alpine.js):
- **Samudra** *(Default)*: Sidebar biru dongker (`#003049`), aksen biru cerah (`#669bbc`), latar belakang lembut (`#fdf0d5`).
- **Marun**: Nuansa merah marun warkah pertanahan (`#780000`).
- **Senja**: Kombinasi marun dan dongker.

Pengguna dapat memilih tema melalui dropdown profil di sudut kanan atas topbar.

---

## 8. Menjalankan Pengujian Otomatis (*Test Suite*)

Seluruh fitur memiliki pengujian otomatis (PHPUnit Feature & Unit Test):

```powershell
# Jalankan seluruh rangkaian tes
php artisan test

# Jalankan pengujian skala performa 20.000 dokumen
php artisan test tests/Feature/Performance/CatalogScaleTest.php

# Jalankan pengujian matriks keamanan dan hak akses
php artisan test tests/Feature/Auth/AccessMatrixTest.php
```

Tabel penelusuran lengkap 15 temuan audit dan 27 kode skenario uji terdokumentasi di [AUDIT_TRACEABILITY.md](file:///c:/Users/Saputra%20Budiman/Herd/arsip-bpn/AUDIT_TRACEABILITY.md).

---

## 9. Lisensi & Hak Cipta

Dikembangkan untuk **Kantor Pertanahan (Kantah) ATR/BPN Kabupaten Sumbawa, Provinsi Nusa Tenggara Barat**.
Hak Cipta © 2026.
