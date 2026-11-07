<?php

namespace Tests\Feature\Performance;

use App\Enums\DocumentStatus;
use App\Models\District;
use App\Models\DocumentType;
use App\Models\LandRightType;
use App\Models\StorageRack;
use App\Models\User;
use App\Models\Village;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CatalogScaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_scales_to_20000_documents_with_fast_response_and_small_payload(): void
    {
        // 1. Setup master references
        $user = User::factory()->archivist()->create();
        $district = District::factory()->create(['id' => '5204010', 'name' => 'Lunyuk']);
        $village = Village::factory()->create(['id' => '5204010001', 'district_id' => $district->id, 'name' => 'Lunyuk Barat']);
        $rack = StorageRack::factory()->create(['name' => 'Rak A']);
        $docType = DocumentType::factory()->create(['name' => 'Buku Tanah']);
        $landRight = LandRightType::factory()->create(['name' => 'Hak Milik']);

        // 2. Batch insert 20,000 documents efficiently (10 batches of 2,000)
        $totalRecords = 20000;
        $batchSize = 2000;
        $now = Carbon::now();

        for ($b = 0; $b < ($totalRecords / $batchSize); $b++) {
            $batch = [];
            for ($i = 1; $i <= $batchSize; $i++) {
                $num = ($b * $batchSize) + $i;
                $batch[] = [
                    'document_number' => sprintf('DOC-%05d', $num),
                    'year' => (string) (2000 + ($num % 25)),
                    'storage_rack_id' => $rack->id,
                    'document_type_id' => $docType->id,
                    'land_right_type_id' => $landRight->id,
                    'range_number' => sprintf('%02d-%02d', ($num % 50), ($num % 50) + 5),
                    'district_id' => $district->id,
                    'village_id' => $village->id,
                    'status' => DocumentStatus::AVAILABLE->value,
                    'file_path' => null,
                    'original_filename' => null,
                    'file_size' => null,
                    'mime_type' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            DB::table('archive_documents')->insert($batch);
        }

        $this->assertSame($totalRecords, DB::table('archive_documents')->count());

        // 3. Measure query count and response time on paginated catalog data
        DB::enableQueryLog();
        $startTime = microtime(true);

        $response = $this->actingAs($user)
            ->getJson('/documents/data?page=1&per_page=10');

        $durationMs = (microtime(true) - $startTime) * 1000;
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // 4. Assertions
        $response->assertStatus(200);
        $response->assertJsonPath('meta.total', 20000);
        $response->assertJsonCount(10, 'data');

        // Payload size verification: must be lightweight (~5KB - 15KB)
        $payloadSizeBytes = strlen($response->getContent());
        $this->assertLessThan(
            30 * 1024,
            $payloadSizeBytes,
            "Payload size ({$payloadSizeBytes} bytes) is too large for 10 records."
        );

        // Performance timing: query processing should finish quickly (< 1500ms even on scale test)
        $this->assertLessThan(
            1500,
            $durationMs,
            "Response took {$durationMs}ms, exceeding performance threshold."
        );

        // Query count verification: NO N+1 query problem!
        // Should be at most 9 queries (count, fetch 10 rows, eager load 6 relations + auth)
        $this->assertLessThanOrEqual(
            9,
            count($queries),
            'Query count indicates N+1 query vulnerability: '.count($queries).' queries executed.'
        );

        // 5. Test indexed search across 20,000 records
        $searchResponse = $this->actingAs($user)
            ->getJson('/documents/data?search=DOC-19999');

        $searchResponse->assertStatus(200);
        $searchResponse->assertJsonPath('meta.total', 1);
        $searchResponse->assertJsonPath('data.0.document_number', 'DOC-19999');
    }
}
