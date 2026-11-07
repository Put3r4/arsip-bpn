<?php

namespace Tests\Feature\Legacy;

use App\Console\Commands\LegacyFilesReportCommand;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class LegacyFilesReportCommandTest extends TestCase
{
    protected string $tempSourceDir;
    protected string $tempTargetDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempSourceDir = storage_path('framework/testing/legacy_source_' . uniqid());
        $this->tempTargetDir = storage_path('framework/testing/legacy_target_' . uniqid());

        File::ensureDirectoryExists($this->tempSourceDir);
        File::ensureDirectoryExists($this->tempTargetDir);
    }

    protected function tearDown(): void
    {
        if (File::isDirectory($this->tempSourceDir)) {
            File::deleteDirectory($this->tempSourceDir);
        }

        if (File::isDirectory($this->tempTargetDir)) {
            File::deleteDirectory($this->tempTargetDir);
        }

        parent::tearDown();
    }

    public function test_legacy_files_report_lists_all_11_files(): void
    {
        // Siapkan 11 berkas uji coba (8 warkah, 3 pribadi)
        foreach (LegacyFilesReportCommand::AUDITED_FILES as $filename => $meta) {
            file_put_contents($this->tempSourceDir . DIRECTORY_SEPARATOR . $filename, str_repeat('A', 100));
        }

        $this->artisan('arsip:legacy-files-report', [
            '--dir' => $this->tempSourceDir,
            '--target' => $this->tempTargetDir,
        ])
            ->expectsOutputToContain('LAPORAN INVENTARIS FILE PDF LEGACY & CADANGAN WARKAH PERTANAHAN')
            ->expectsOutputToContain('Total Berkas Terdaftar : 11 berkas')
            ->expectsOutputToContain('Warkah Dicadangkan     : 8 berkas')
            ->expectsOutputToContain('Berkas Pribadi Pegawai : 3 berkas')
            ->assertSuccessful();
    }

    public function test_warkah_files_are_copied_to_target_backup(): void
    {
        // Buat file warkah
        $warkahFiles = [
            'BW_to_Searchable_PDF_1.pdf',
            'banggo.pdf',
            'banggo1.pdf',
            'dore_peti_buku_2.pdf',
            'dore_peti_buku_21.pdf',
            'dore_peti_buku_22.pdf',
            'dore_peti_buku_23.pdf',
            'kwangko_buku_20001.pdf',
        ];

        foreach ($warkahFiles as $filename) {
            file_put_contents($this->tempSourceDir . DIRECTORY_SEPARATOR . $filename, 'warkah-content-' . $filename);
        }

        $this->artisan('arsip:legacy-files-report', [
            '--dir' => $this->tempSourceDir,
            '--target' => $this->tempTargetDir,
        ])->assertSuccessful();

        foreach ($warkahFiles as $filename) {
            $targetPath = $this->tempTargetDir . DIRECTORY_SEPARATOR . $filename;
            $this->assertFileExists($targetPath, "Berkas warkah {$filename} harus tersalin ke folder cadangan.");
            $this->assertSame('warkah-content-' . $filename, file_get_contents($targetPath));
        }
    }

    public function test_personal_files_are_never_copied_to_target_backup(): void
    {
        // Buat berkas pribadi dan warkah di direktori sumber
        foreach (LegacyFilesReportCommand::AUDITED_FILES as $filename => $meta) {
            file_put_contents($this->tempSourceDir . DIRECTORY_SEPARATOR . $filename, 'sample-' . $filename);
        }

        $this->artisan('arsip:legacy-files-report', [
            '--dir' => $this->tempSourceDir,
            '--target' => $this->tempTargetDir,
        ])
            ->expectsOutputToContain('Berkas Pribadi Pegawai')
            ->assertSuccessful();

        // Verifikasi ketat: tidak ada satu pun file pribadi yang disalin ke direktori target!
        foreach (LegacyFilesReportCommand::PERSONAL_FILES as $personalFile) {
            $targetPath = $this->tempTargetDir . DIRECTORY_SEPARATOR . $personalFile;
            $this->assertFileDoesNotExist(
                $targetPath,
                "File pribadi {$personalFile} TIDAK BOLEH disalin ke target cadangan (A10 butir 2)."
            );
        }
    }

    public function test_personal_files_are_marked_as_hapus_manual(): void
    {
        foreach (LegacyFilesReportCommand::AUDITED_FILES as $filename => $meta) {
            file_put_contents($this->tempSourceDir . DIRECTORY_SEPARATOR . $filename, 'test-content');
        }

        $this->artisan('arsip:legacy-files-report', [
            '--dir' => $this->tempSourceDir,
            '--target' => $this->tempTargetDir,
        ])
            ->expectsOutputToContain('Surat_Bebas_Narkoba_dan_Hasil_Lab.pdf')
            ->expectsOutputToContain('Surat_Pernyataan.pdf')
            ->expectsOutputToContain('Surat_Pernyataan_5_Poin.pdf')
            ->assertSuccessful();
    }

    public function test_default_execution_against_actual_project_legacy_files(): void
    {
        $backupDir = storage_path('app/legacy_backup');

        $this->artisan('arsip:legacy-files-report')
            ->assertSuccessful();

        $this->assertDirectoryExists($backupDir);

        // Pastikan tidak ada file pribadi di storage/app/legacy_backup
        foreach (LegacyFilesReportCommand::PERSONAL_FILES as $personalFile) {
            $this->assertFileDoesNotExist($backupDir . DIRECTORY_SEPARATOR . $personalFile);
        }
    }
}
