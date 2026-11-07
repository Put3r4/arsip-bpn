<?php

namespace Tests\Feature\Dashboard;

use App\Models\ArchiveDocument;
use App\Models\DocumentLoan;
use App\Models\Section;
use App\Models\ServiceActivity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardStatsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Dashboard admin/archivist menghitung pending, borrowed, dan total dokumen untuk seluruh kantor.
     * Dashboard pegawai hanya menghitung pending dan borrowed miliknya sendiri.
     */
    public function test_dashboard_counts_are_properly_scoped_per_role(): void
    {
        $section = Section::factory()->create();
        $activity = ServiceActivity::factory()->create();

        $employeeA = User::factory()->employee()->create(['section_id' => $section->id]);
        $employeeB = User::factory()->employee()->create(['section_id' => $section->id]);
        $archivist = User::factory()->archivist()->create(['section_id' => $section->id]);
        $superAdmin = User::factory()->superAdmin()->create();

        // Dokumen
        $doc1 = ArchiveDocument::factory()->available()->create();
        $doc2 = ArchiveDocument::factory()->booked()->create();
        $doc3 = ArchiveDocument::factory()->booked()->create();
        $doc4 = ArchiveDocument::factory()->borrowed()->create();
        $doc5 = ArchiveDocument::factory()->borrowed()->create();

        // 2 pending: 1 milik Employee A, 1 milik Employee B
        DocumentLoan::factory()->pending()->create([
            'archive_document_id' => $doc2->id,
            'borrower_id' => $employeeA->id,
            'service_activity_id' => $activity->id,
        ]);
        DocumentLoan::factory()->pending()->create([
            'archive_document_id' => $doc3->id,
            'borrower_id' => $employeeB->id,
            'service_activity_id' => $activity->id,
        ]);

        // 2 borrowed: 1 milik Employee A, 1 milik Employee B
        DocumentLoan::factory()->borrowed()->create([
            'archive_document_id' => $doc4->id,
            'borrower_id' => $employeeA->id,
            'service_activity_id' => $activity->id,
        ]);
        DocumentLoan::factory()->borrowed()->create([
            'archive_document_id' => $doc5->id,
            'borrower_id' => $employeeB->id,
            'service_activity_id' => $activity->id,
        ]);

        // 1. Dashboard Super Admin (Total Pending: 2, Total Borrowed: 2, Total Dokumen: 5)
        $adminResponse = $this->actingAs($superAdmin)->get('/dashboard');
        $adminResponse->assertStatus(200)
            ->assertViewHas('pendingCount', 2)
            ->assertViewHas('borrowedCount', 2)
            ->assertViewHas('totalDocuments', 5);

        // 2. Dashboard Petugas Arsip (Total Pending: 2, Total Borrowed: 2, Total Dokumen: 5)
        $archivistResponse = $this->actingAs($archivist)->get('/dashboard');
        $archivistResponse->assertStatus(200)
            ->assertViewHas('pendingCount', 2)
            ->assertViewHas('borrowedCount', 2)
            ->assertViewHas('totalDocuments', 5);

        // 3. Dashboard Pegawai Alfa (Pending miliknya: 1, Borrowed miliknya: 1, Total Dokumen: 5)
        $employeeResponse = $this->actingAs($employeeA)->get('/dashboard');
        $employeeResponse->assertStatus(200)
            ->assertViewHas('pendingCount', 1)
            ->assertViewHas('borrowedCount', 1)
            ->assertViewHas('totalDocuments', 5);
    }
}
