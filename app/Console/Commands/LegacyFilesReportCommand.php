<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class LegacyFilesReportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'arsip:legacy-files-report 
                            {--dir= : Direktori sumber file legacy (default: arsip/asset/dokumen)} 
                            {--target= : Direktori target salinan cadangan warkah (default: storage/app/legacy_backup)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mendaftar 11 file PDF legacy, menandai 3 file pribadi untuk hapus manual, dan mencadangkan 8 file warkah';

    /**
     * 11 File warisan yang terdata pada audit fisik (arsip/_audit/07-file-upload.md).
     *
     * @var array<string, array{type: string, expected_size: int, desc: string}>
     */
    public const AUDITED_FILES = [
        'BW_to_Searchable_PDF_1.pdf' => ['type' => 'warkah', 'expected_size' => 896370, 'desc' => 'Dokumen Uji Scan OCR'],
        'banggo.pdf' => ['type' => 'warkah', 'expected_size' => 1929496, 'desc' => 'Scan Warkah Tanah Desa Banggo'],
        'banggo1.pdf' => ['type' => 'warkah', 'expected_size' => 1929496, 'desc' => 'Duplikat Warkah Desa Banggo'],
        'dore_peti_buku_2.pdf' => ['type' => 'warkah', 'expected_size' => 1351731, 'desc' => 'Scan Buku Tanah Desa Dore'],
        'dore_peti_buku_21.pdf' => ['type' => 'warkah', 'expected_size' => 1351731, 'desc' => 'Duplikat Buku Tanah Desa Dore'],
        'dore_peti_buku_22.pdf' => ['type' => 'warkah', 'expected_size' => 1351731, 'desc' => 'Duplikat Buku Tanah Desa Dore'],
        'dore_peti_buku_23.pdf' => ['type' => 'warkah', 'expected_size' => 1351731, 'desc' => 'Duplikat Buku Tanah Desa Dore'],
        'kwangko_buku_20001.pdf' => ['type' => 'warkah', 'expected_size' => 1377634, 'desc' => 'Scan Buku Tanah Desa Kwangko'],
        'Surat_Bebas_Narkoba_dan_Hasil_Lab.pdf' => ['type' => 'personal', 'expected_size' => 107959, 'desc' => 'Berkas Pribadi Pegawai (Anomali)'],
        'Surat_Pernyataan.pdf' => ['type' => 'personal', 'expected_size' => 168594, 'desc' => 'Berkas Pribadi Pegawai (Anomali)'],
        'Surat_Pernyataan_5_Poin.pdf' => ['type' => 'personal', 'expected_size' => 168594, 'desc' => 'Berkas Pribadi Pegawai (Anomali)'],
    ];

    /**
     * Daftar nama file pribadi yang tidak boleh disalin dan wajib dihapus manual (A10 butir 2).
     *
     * @var array<int, string>
     */
    public const PERSONAL_FILES = [
        'Surat_Bebas_Narkoba_dan_Hasil_Lab.pdf',
        'Surat_Pernyataan.pdf',
        'Surat_Pernyataan_5_Poin.pdf',
    ];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $sourceDir = $this->option('dir') ?: base_path('arsip/asset/dokumen');
        $targetDir = $this->option('target') ?: storage_path('app/legacy_backup');

        $this->newLine();
        $this->info('========================================================================');
        $this->info('  LAPORAN INVENTARIS FILE PDF LEGACY & CADANGAN WARKAH PERTANAHAN');
        $this->info('========================================================================');
        $this->line("Direktori Sumber : <fg=yellow>{$sourceDir}</>");
        $this->line("Direktori Target : <fg=yellow>{$targetDir}</>");
        $this->newLine();

        File::ensureDirectoryExists($targetDir);

        // Kumpulkan file dari direktori sumber (jika ada) dan gabungkan dengan daftar audit
        $diskFiles = [];
        if (File::isDirectory($sourceDir)) {
            foreach (File::files($sourceDir) as $file) {
                $diskFiles[$file->getFilename()] = $file;
            }
        }

        // Tentukan daftar berkas yang akan dilaporkan:
        // Prioritaskan 11 file audit, lalu tambahkan file PDF lain yang ada di disk jika ada
        $allFilenames = array_unique(array_merge(array_keys(self::AUDITED_FILES), array_keys($diskFiles)));

        $tableRows = [];
        $no = 1;
        $warkahCopiedCount = 0;
        $personalFilesCount = 0;

        foreach ($allFilenames as $filename) {
            $isPersonal = $this->isPersonalFile($filename);
            $auditMeta = self::AUDITED_FILES[$filename] ?? null;

            // Ukuran file
            $fileOnDisk = $diskFiles[$filename] ?? null;
            if ($fileOnDisk) {
                $sizeBytes = $fileOnDisk->getSize();
                $diskPath = $fileOnDisk->getPathname();
            } else {
                // Cek kemungkinan file berada di direktori arsip/asset/dist/img/
                $altPath = base_path('arsip/asset/dist/img/' . $filename);
                if (File::exists($altPath)) {
                    $sizeBytes = File::size($altPath);
                    $diskPath = $altPath;
                } else {
                    $sizeBytes = $auditMeta['expected_size'] ?? 0;
                    $diskPath = null;
                }
            }

            $sizeFormatted = $this->formatBytes($sizeBytes);

            if ($isPersonal) {
                $personalFilesCount++;
                $category = '<fg=yellow>Berkas Pribadi Pegawai</>';
                $action = '<fg=red;options=bold>Hapus manual</>';
                $status = '<fg=yellow>TIDAK DISALIN (Privasi dijaga)</>';
            } else {
                $category = 'Scan Warkah Pertanahan';
                $action = '<fg=green>Salin ke legacy_backup</>';

                if ($diskPath && File::exists($diskPath)) {
                    $targetPath = $targetDir . DIRECTORY_SEPARATOR . $filename;
                    File::copy($diskPath, $targetPath);
                    $status = '<fg=green>Tersalin ke cadangan</>';
                    $warkahCopiedCount++;
                } else {
                    $status = '<fg=red>Berkas fisik tidak ditemukan di disk</>';
                }
            }

            $tableRows[] = [
                $no++,
                $filename,
                $sizeFormatted,
                $category,
                $action,
                $status,
            ];
        }

        $this->table(
            ['No', 'Nama Berkas', 'Ukuran', 'Kategori Berkas', 'Tindakan Direkomendasikan', 'Status Cadangan'],
            $tableRows
        );

        $this->newLine();
        $this->line("• Total Berkas Terdaftar : <fg=cyan>" . count($allFilenames) . " berkas</>");
        $this->line("• Warkah Dicadangkan     : <fg=green>{$warkahCopiedCount} berkas</> ke storage/app/legacy_backup/");
        $this->line("• Berkas Pribadi Pegawai : <fg=yellow>{$personalFilesCount} berkas</> (Ditandai 'Hapus manual', TIDAK DISALIN ke cadangan)");
        $this->newLine();

        $this->warn('PENTING (Kepatuhan Privasi Data Pegawai — A10 butir 2):');
        $this->line('File berkas pribadi pegawai tidak disalin ke folder cadangan maupun katalog.');
        $this->line('Pemilik sistem wajib menghapus berkas pribadi tersebut secara manual.');
        $this->newLine();

        return self::SUCCESS;
    }

    /**
     * Memeriksa apakah file tergolong file pribadi pegawai.
     */
    protected function isPersonalFile(string $filename): bool
    {
        if (in_array($filename, self::PERSONAL_FILES, true)) {
            return true;
        }

        return str_starts_with($filename, 'Surat_');
    }

    /**
     * Format byte ke satuan yang mudah dibaca.
     */
    protected function formatBytes(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        }

        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        }

        return $bytes . ' B';
    }
}
