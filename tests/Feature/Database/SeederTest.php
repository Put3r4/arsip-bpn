<?php

namespace Tests\Feature\Database;

use App\Enums\UserRole;
use App\Models\District;
use App\Models\DocumentType;
use App\Models\LandRightType;
use App\Models\Section;
use App\Models\ServiceActivity;
use App\Models\StorageRack;
use App\Models\User;
use App\Models\Village;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeders_seed_exact_expected_row_counts(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(24, District::count(), 'Districts must have 24 rows.');
        $this->assertSame(166, Village::count(), 'Villages must have 166 rows.');
        $this->assertSame(7, Section::count(), 'Sections must have 7 rows.');
        $this->assertSame(4, StorageRack::count(), 'Storage racks must have 4 rows.');
        $this->assertSame(6, DocumentType::count(), 'Document types must have 6 rows.');
        $this->assertSame(5, LandRightType::count(), 'Land right types must have 5 rows.');
        $this->assertSame(12, ServiceActivity::count(), 'Service activities must have 12 rows.');
        $this->assertSame(1, User::count(), 'Users must have exactly 1 super admin row.');
    }

    public function test_seeders_are_idempotent(): void
    {
        // First run
        $this->seed(DatabaseSeeder::class);

        $initialDistricts = District::count();
        $initialVillages = Village::count();
        $initialSections = Section::count();
        $initialRacks = StorageRack::count();
        $initialDocTypes = DocumentType::count();
        $initialRights = LandRightType::count();
        $initialActivities = ServiceActivity::count();
        $initialUsers = User::count();

        // Second run
        $this->seed(DatabaseSeeder::class);

        $this->assertSame($initialDistricts, District::count(), 'Districts count must not change on duplicate run.');
        $this->assertSame($initialVillages, Village::count(), 'Villages count must not change on duplicate run.');
        $this->assertSame($initialSections, Section::count(), 'Sections count must not change on duplicate run.');
        $this->assertSame($initialRacks, StorageRack::count(), 'Storage racks count must not change on duplicate run.');
        $this->assertSame($initialDocTypes, DocumentType::count(), 'Doc types count must not change on duplicate run.');
        $this->assertSame($initialRights, LandRightType::count(), 'Land rights count must not change on duplicate run.');
        $this->assertSame($initialActivities, ServiceActivity::count(), 'Service activities count must not change on duplicate run.');
        $this->assertSame($initialUsers, User::count(), 'Users count must not change on duplicate run.');
    }

    public function test_super_admin_is_created_with_env_credentials(): void
    {
        $this->seed(DatabaseSeeder::class);

        $expectedUsername = env('SEED_ADMIN_USERNAME', 'superadmin');
        $expectedPassword = env('SEED_ADMIN_PASSWORD', 'Password_1234!');

        $admin = User::where('username', $expectedUsername)->first();

        $this->assertNotNull($admin, 'Super admin user must exist in database.');
        $this->assertSame(UserRole::SUPER_ADMIN, $admin->role);
        $this->assertTrue($admin->is_active);
        $this->assertSame('samudra', $admin->theme);
        $this->assertTrue(Hash::check($expectedPassword, $admin->password));
    }

    public function test_seeders_preserve_explicit_legacy_ids(): void
    {
        $this->seed(DatabaseSeeder::class);

        // Sections: non-contiguous legacy IDs (1, 2, 4, 5, 7, 8, 9)
        $section4 = Section::find(4);
        $this->assertNotNull($section4, 'Section ID 4 must exist as in legacy data.');
        $this->assertSame('Seksi 4', $section4->name);

        $section7 = Section::find(7);
        $this->assertNotNull($section7, 'Section ID 7 must exist as in legacy data.');
        $this->assertSame('Seksi 3', $section7->name);

        $this->assertNull(Section::find(3), 'Section ID 3 did not exist in legacy DB and must not exist.');

        // Storage Racks: starts from 5, not 1 (5, 6, 7, 8)
        $rack5 = StorageRack::find(5);
        $this->assertNotNull($rack5, 'StorageRack ID 5 must exist.');
        $this->assertSame('001', $rack5->name);
        $this->assertNull(StorageRack::find(1), 'StorageRack ID 1 must not exist.');

        // Document Types: starts from 2 (2, 3, 4, 5, 6, 7)
        $docType2 = DocumentType::find(2);
        $this->assertNotNull($docType2, 'DocumentType ID 2 must exist.');
        $this->assertSame('Buku Tanah', $docType2->name);
        $this->assertNull(DocumentType::find(1), 'DocumentType ID 1 must not exist.');

        // Land Right Types: (2, 3, 6, 8, 9)
        $right2 = LandRightType::find(2);
        $this->assertNotNull($right2);
        $this->assertSame('HM', $right2->name);
        $right6 = LandRightType::find(6);
        $this->assertNotNull($right6);
        $this->assertSame('HGB', $right6->name);

        // Service Activities: (1, 2, 4, 5, 6, 8, 9, 10, 11, 12, 13, 14)
        $activity1 = ServiceActivity::find(1);
        $this->assertNotNull($activity1);
        $this->assertSame('Roya', $activity1->name);
        $activity4 = ServiceActivity::find(4);
        $this->assertNotNull($activity4);
        $this->assertSame('Pewarisan', $activity4->name);

        // Districts and Villages: string codes
        $district = District::find('5204020');
        $this->assertNotNull($district);
        $this->assertSame('Lunyuk', $district->name);

        $village = Village::find('5204020001');
        $this->assertNotNull($village);
        $this->assertSame('Pada Suka', $village->name);
        $this->assertSame('5204020', $village->district_id);
    }
}
