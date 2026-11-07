<?php

namespace Tests\Feature\Master;

use App\Models\DocumentLoan;
use App\Models\ServiceActivity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceActivityTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_view_service_activities_page_and_data(): void
    {
        $archivist = User::factory()->archivist()->create();
        ServiceActivity::factory()->count(3)->create();

        $pageResponse = $this->actingAs($archivist)->get('/master/service-activities');
        $pageResponse->assertOk();

        $dataResponse = $this->actingAs($archivist)->getJson('/master/service-activities/data');
        $dataResponse->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'loans_count', 'can_delete', 'created_at'],
                ],
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);
    }

    public function test_employee_cannot_access_service_activities(): void
    {
        $employee = User::factory()->employee()->create();

        $this->actingAs($employee)->get('/master/service-activities')->assertForbidden();
        $this->actingAs($employee)->getJson('/master/service-activities/data')->assertForbidden();
        $this->actingAs($employee)->postJson('/master/service-activities', ['name' => 'KEGIATAN TEST'])->assertForbidden();
    }

    public function test_staff_can_create_service_activity(): void
    {
        $archivist = User::factory()->archivist()->create();

        $response = $this->actingAs($archivist)->postJson('/master/service-activities', [
            'name' => 'Pengecekan Sertipikat',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('service_activities', [
            'name' => 'Pengecekan Sertipikat',
        ]);
    }

    public function test_service_activity_creation_validates_name_uniqueness(): void
    {
        $archivist = User::factory()->archivist()->create();
        ServiceActivity::factory()->create(['name' => 'KEGIATAN DUPLIKAT']);

        $response = $this->actingAs($archivist)->postJson('/master/service-activities', [
            'name' => 'KEGIATAN DUPLIKAT',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_staff_can_update_service_activity(): void
    {
        $archivist = User::factory()->archivist()->create();
        $activity = ServiceActivity::factory()->create(['name' => 'KEGIATAN LAMA']);

        $response = $this->actingAs($archivist)->patchJson("/master/service-activities/{$activity->id}", [
            'name' => 'KEGIATAN BARU',
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('service_activities', [
            'id' => $activity->id,
            'name' => 'KEGIATAN BARU',
        ]);
    }

    public function test_service_activity_with_loans_cannot_be_deleted(): void
    {
        $archivist = User::factory()->archivist()->create();
        $activity = ServiceActivity::factory()->create(['name' => 'KEGIATAN TERPAKAi']);
        DocumentLoan::factory()->create(['service_activity_id' => $activity->id]);

        $response = $this->actingAs($archivist)->deleteJson("/master/service-activities/{$activity->id}");

        $response->assertStatus(422)
            ->assertJson([
                'status' => 'error',
                'message' => 'Kegiatan pelayanan tidak dapat dihapus karena masih digunakan oleh transaksi peminjaman.',
            ]);

        $this->assertDatabaseHas('service_activities', [
            'id' => $activity->id,
        ]);
    }

    public function test_empty_service_activity_can_be_deleted(): void
    {
        $archivist = User::factory()->archivist()->create();
        $activity = ServiceActivity::factory()->create(['name' => 'KEGIATAN KOSONG']);

        $response = $this->actingAs($archivist)->deleteJson("/master/service-activities/{$activity->id}");

        $response->assertOk()
            ->assertJson(['status' => 'success']);

        $this->assertDatabaseMissing('service_activities', [
            'id' => $activity->id,
        ]);
    }
}
