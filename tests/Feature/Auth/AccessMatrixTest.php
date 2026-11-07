<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\ArchiveDocument;
use App\Models\District;
use App\Models\DocumentLoan;
use App\Models\DocumentType;
use App\Models\LandRightType;
use App\Models\Section;
use App\Models\ServiceActivity;
use App\Models\StorageRack;
use App\Models\User;
use App\Models\Village;
use App\Policies\ArchiveDocumentPolicy;
use App\Policies\DocumentTypePolicy;
use App\Policies\LandRightTypePolicy;
use App\Policies\SectionPolicy;
use App\Policies\ServiceActivityPolicy;
use App\Policies\StorageRackPolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AccessMatrixTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Uji matriks akses seluruh rute aplikasi untuk 4 peran:
     * guest, employee, archivist, super_admin.
     */
    #[DataProvider('completeRoutesMatrixProvider')]
    public function test_complete_routes_access_matrix(
        string $role,
        string $method,
        string $routeTemplate,
        int $expectedStatus,
        bool $isJson = false
    ): void {
        Storage::fake('local');

        $section = Section::factory()->create();
        $targetUser = User::factory()->employee()->create([
            'section_id' => $section->id,
            'password' => Hash::make('password123'),
        ]);

        $district = District::factory()->create(['id' => '5204020']);
        $village = Village::factory()->create(['id' => '5204020001', 'district_id' => $district->id]);
        $storageRack = StorageRack::factory()->create();
        $documentType = DocumentType::factory()->create();
        $landRightType = LandRightType::factory()->create();
        $serviceActivity = ServiceActivity::factory()->create();

        $uploaded = UploadedFile::fake()->create('matrix_test.pdf', 100, 'application/pdf');
        $storedPath = $uploaded->storeAs('documents/2026/11', 'matrix_test.pdf', 'local');

        $document = ArchiveDocument::factory()->available()->create([
            'district_id' => $district->id,
            'village_id' => $village->id,
            'storage_rack_id' => $storageRack->id,
            'document_type_id' => $documentType->id,
            'land_right_type_id' => $landRightType->id,
            'file_path' => $storedPath,
            'original_filename' => 'matrix_test.pdf',
            'mime_type' => 'application/pdf',
        ]);

        $uri = str_replace(
            [
                '{section}',
                '{user}',
                '{document}',
                '{district}',
                '{storage_rack}',
                '{document_type}',
                '{land_right_type}',
                '{service_activity}',
            ],
            [
                $section->id,
                $targetUser->id,
                $document->id,
                $district->id,
                $storageRack->id,
                $documentType->id,
                $landRightType->id,
                $serviceActivity->id,
            ],
            $routeTemplate
        );

        $payload = [];
        if ($method === 'POST' || $method === 'PATCH' || $method === 'PUT') {
            if ($routeTemplate === '/profile/password') {
                $payload = [
                    'current_password' => 'password123',
                    'password' => 'new_pass_888',
                    'password_confirmation' => 'new_pass_888',
                ];
            } elseif ($routeTemplate === '/profile/theme') {
                $payload = ['theme' => 'marun'];
            } elseif ($routeTemplate === '/master/sections') {
                $payload = ['name' => 'Seksi Baru Matrix ' . uniqid()];
            } elseif ($routeTemplate === '/master/sections/{section}') {
                $payload = ['name' => 'Seksi Update Matrix ' . uniqid()];
            } elseif ($routeTemplate === '/users') {
                $payload = [
                    'name' => 'User Matrix',
                    'username' => 'matrix_user_' . uniqid(),
                    'role' => UserRole::EMPLOYEE->value,
                    'section_id' => $section->id,
                    'password' => 'password123',
                    'password_confirmation' => 'password123',
                ];
            } elseif ($routeTemplate === '/users/{user}') {
                $payload = [
                    'name' => 'User Matrix Updated',
                    'username' => $targetUser->username,
                    'role' => UserRole::EMPLOYEE->value,
                    'section_id' => $section->id,
                    'is_active' => true,
                ];
            } elseif ($routeTemplate === '/users/{user}/reset-password') {
                $payload = [
                    'password' => 'new_password123',
                    'password_confirmation' => 'new_password123',
                ];
            } elseif ($routeTemplate === '/documents') {
                $payload = [
                    'document_number' => 'DOC-MX-' . uniqid(),
                    'year' => 2024,
                    'document_type_id' => $documentType->id,
                    'land_right_type_id' => $landRightType->id,
                    'storage_rack_id' => $storageRack->id,
                    'district_id' => $district->id,
                    'village_id' => $village->id,
                ];
            } elseif ($routeTemplate === '/documents/{document}') {
                $payload = [
                    'document_number' => 'DOC-MX-UP-' . uniqid(),
                    'year' => 2024,
                    'document_type_id' => $documentType->id,
                    'land_right_type_id' => $landRightType->id,
                    'storage_rack_id' => $storageRack->id,
                    'district_id' => $district->id,
                    'village_id' => $village->id,
                ];
            } elseif ($routeTemplate === '/documents/{document}/status') {
                $payload = ['status' => 'lost'];
            } elseif ($routeTemplate === '/master/storage-racks' || $routeTemplate === '/master/storage-racks/{storage_rack}') {
                $payload = ['name' => 'Rack Matrix ' . uniqid()];
            } elseif ($routeTemplate === '/master/document-types' || $routeTemplate === '/master/document-types/{document_type}') {
                $payload = ['name' => 'DocType Matrix ' . uniqid()];
            } elseif ($routeTemplate === '/master/land-right-types' || $routeTemplate === '/master/land-right-types/{land_right_type}') {
                $payload = ['name' => 'RightType Matrix ' . uniqid()];
            } elseif ($routeTemplate === '/master/service-activities' || $routeTemplate === '/master/service-activities/{service_activity}') {
                $payload = ['name' => 'Activity Matrix ' . uniqid()];
            }
        }

        if ($role === 'guest') {
            $response = $isJson
                ? $this->json($method, $uri, $payload)
                : $this->call($method, $uri, $payload);
        } else {
            $userRole = UserRole::from($role);
            $authUser = User::factory()->create([
                'role' => $userRole,
                'section_id' => $section->id,
                'password' => Hash::make('password123'),
            ]);

            $response = $isJson
                ? $this->actingAs($authUser)->json($method, $uri, $payload)
                : $this->actingAs($authUser)->call($method, $uri, $payload);
        }

        $actualStatus = method_exists($response, 'status') ? $response->status() : $response->getStatusCode();

        $this->assertSame(
            $expectedStatus,
            $actualStatus,
            "Gagal pada [Role: {$role}] {$method} {$uri} -> Status aktual: {$actualStatus}, diharapkan: {$expectedStatus}"
        );
    }

    public static function completeRoutesMatrixProvider(): array
    {
        $routes = [
            // Rute Umum & Tamu
            ['GET', '/', [
                'guest' => 302,
                'employee' => 302,
                'archivist' => 302,
                'super_admin' => 302,
            ], false],
            ['GET', '/login', [
                'guest' => 200,
                'employee' => 302,
                'archivist' => 302,
                'super_admin' => 302,
            ], false],
            ['POST', '/logout', [
                'guest' => 302,
                'employee' => 302,
                'archivist' => 302,
                'super_admin' => 302,
            ], false],

            // Rute Dashboard
            ['GET', '/dashboard', [
                'guest' => 302,
                'employee' => 200,
                'archivist' => 200,
                'super_admin' => 200,
            ], false],

            // Rute Profil
            ['GET', '/profile', [
                'guest' => 302,
                'employee' => 200,
                'archivist' => 200,
                'super_admin' => 200,
            ], false],
            ['PATCH', '/profile/password', [
                'guest' => 302,
                'employee' => 302,
                'archivist' => 302,
                'super_admin' => 302,
            ], false],
            ['PATCH', '/profile/theme', [
                'guest' => 401,
                'employee' => 200,
                'archivist' => 200,
                'super_admin' => 200,
            ], true],

            // Katalog Berkas & Preview File Scan (Semua Role Terotentikasi)
            ['GET', '/documents', [
                'guest' => 302,
                'employee' => 200,
                'archivist' => 200,
                'super_admin' => 200,
            ], false],
            ['GET', '/documents/data', [
                'guest' => 302,
                'employee' => 200,
                'archivist' => 200,
                'super_admin' => 200,
            ], false],
            ['GET', '/documents/{document}/file', [
                'guest' => 302,
                'employee' => 200,
                'archivist' => 200,
                'super_admin' => 200,
            ], false],
            ['GET', '/documents/{document}/preview', [
                'guest' => 302,
                'employee' => 200,
                'archivist' => 200,
                'super_admin' => 200,
            ], false],

            // Dropdown Berantai Wilayah & Seksi
            ['GET', '/api/districts/{district}/villages', [
                'guest' => 302,
                'employee' => 200,
                'archivist' => 200,
                'super_admin' => 200,
            ], false],
            ['GET', '/api/sections/{section}/users', [
                'guest' => 302,
                'employee' => 200,
                'archivist' => 200,
                'super_admin' => 200,
            ], false],

            // Dokumen Mutasi (Staf Arsip Saja)
            ['POST', '/documents', [
                'guest' => 302,
                'employee' => 403,
                'archivist' => 302,
                'super_admin' => 302,
            ], false],
            ['PATCH', '/documents/{document}', [
                'guest' => 302,
                'employee' => 403,
                'archivist' => 302,
                'super_admin' => 302,
            ], false],
            ['PATCH', '/documents/{document}/status', [
                'guest' => 302,
                'employee' => 403,
                'archivist' => 302,
                'super_admin' => 302,
            ], false],
            ['DELETE', '/documents/{document}', [
                'guest' => 302,
                'employee' => 403,
                'archivist' => 302,
                'super_admin' => 302,
            ], false],

            // Master Rak Penyimpanan (Staf Arsip)
            ['GET', '/master/storage-racks', [
                'guest' => 302,
                'employee' => 403,
                'archivist' => 200,
                'super_admin' => 200,
            ], false],
            ['GET', '/master/storage-racks/data', [
                'guest' => 302,
                'employee' => 403,
                'archivist' => 200,
                'super_admin' => 200,
            ], false],
            ['POST', '/master/storage-racks', [
                'guest' => 302,
                'employee' => 403,
                'archivist' => 302,
                'super_admin' => 302,
            ], false],
            ['PATCH', '/master/storage-racks/{storage_rack}', [
                'guest' => 302,
                'employee' => 403,
                'archivist' => 302,
                'super_admin' => 302,
            ], false],
            ['DELETE', '/master/storage-racks/{storage_rack}', [
                'guest' => 302,
                'employee' => 403,
                'archivist' => 302,
                'super_admin' => 302,
            ], false],

            // Master Jenis Berkas (Staf Arsip)
            ['GET', '/master/document-types', [
                'guest' => 302,
                'employee' => 403,
                'archivist' => 200,
                'super_admin' => 200,
            ], false],
            ['GET', '/master/document-types/data', [
                'guest' => 302,
                'employee' => 403,
                'archivist' => 200,
                'super_admin' => 200,
            ], false],
            ['POST', '/master/document-types', [
                'guest' => 302,
                'employee' => 403,
                'archivist' => 302,
                'super_admin' => 302,
            ], false],
            ['PATCH', '/master/document-types/{document_type}', [
                'guest' => 302,
                'employee' => 403,
                'archivist' => 302,
                'super_admin' => 302,
            ], false],
            ['DELETE', '/master/document-types/{document_type}', [
                'guest' => 302,
                'employee' => 403,
                'archivist' => 302,
                'super_admin' => 302,
            ], false],

            // Master Jenis Hak (Staf Arsip)
            ['GET', '/master/land-right-types', [
                'guest' => 302,
                'employee' => 403,
                'archivist' => 200,
                'super_admin' => 200,
            ], false],
            ['GET', '/master/land-right-types/data', [
                'guest' => 302,
                'employee' => 403,
                'archivist' => 200,
                'super_admin' => 200,
            ], false],
            ['POST', '/master/land-right-types', [
                'guest' => 302,
                'employee' => 403,
                'archivist' => 302,
                'super_admin' => 302,
            ], false],
            ['PATCH', '/master/land-right-types/{land_right_type}', [
                'guest' => 302,
                'employee' => 403,
                'archivist' => 302,
                'super_admin' => 302,
            ], false],
            ['DELETE', '/master/land-right-types/{land_right_type}', [
                'guest' => 302,
                'employee' => 403,
                'archivist' => 302,
                'super_admin' => 302,
            ], false],

            // Master Kegiatan Pelayanan (Staf Arsip)
            ['GET', '/master/service-activities', [
                'guest' => 302,
                'employee' => 403,
                'archivist' => 200,
                'super_admin' => 200,
            ], false],
            ['GET', '/master/service-activities/data', [
                'guest' => 302,
                'employee' => 403,
                'archivist' => 200,
                'super_admin' => 200,
            ], false],
            ['POST', '/master/service-activities', [
                'guest' => 302,
                'employee' => 403,
                'archivist' => 302,
                'super_admin' => 302,
            ], false],
            ['PATCH', '/master/service-activities/{service_activity}', [
                'guest' => 302,
                'employee' => 403,
                'archivist' => 302,
                'super_admin' => 302,
            ], false],
            ['DELETE', '/master/service-activities/{service_activity}', [
                'guest' => 302,
                'employee' => 403,
                'archivist' => 302,
                'super_admin' => 302,
            ], false],

            // Rute Master Seksi (Khusus Super Admin)
            ['GET', '/master/sections', [
                'guest' => 302,
                'employee' => 403,
                'archivist' => 403,
                'super_admin' => 200,
            ], false],
            ['GET', '/master/sections/data', [
                'guest' => 302,
                'employee' => 403,
                'archivist' => 403,
                'super_admin' => 200,
            ], false],
            ['POST', '/master/sections', [
                'guest' => 302,
                'employee' => 403,
                'archivist' => 403,
                'super_admin' => 302,
            ], false],
            ['PATCH', '/master/sections/{section}', [
                'guest' => 302,
                'employee' => 403,
                'archivist' => 403,
                'super_admin' => 302,
            ], false],
            ['DELETE', '/master/sections/{section}', [
                'guest' => 302,
                'employee' => 403,
                'archivist' => 403,
                'super_admin' => 302,
            ], false],

            // Rute Manajemen Pengguna (Khusus Super Admin)
            ['GET', '/users', [
                'guest' => 302,
                'employee' => 403,
                'archivist' => 403,
                'super_admin' => 200,
            ], false],
            ['GET', '/users/data', [
                'guest' => 302,
                'employee' => 403,
                'archivist' => 403,
                'super_admin' => 200,
            ], false],
            ['POST', '/users', [
                'guest' => 302,
                'employee' => 403,
                'archivist' => 403,
                'super_admin' => 302,
            ], false],
            ['PATCH', '/users/{user}', [
                'guest' => 302,
                'employee' => 403,
                'archivist' => 403,
                'super_admin' => 302,
            ], false],
            ['POST', '/users/{user}/reset-password', [
                'guest' => 302,
                'employee' => 403,
                'archivist' => 403,
                'super_admin' => 302,
            ], false],
            ['DELETE', '/users/{user}', [
                'guest' => 302,
                'employee' => 403,
                'archivist' => 403,
                'super_admin' => 302,
            ], false],
        ];

        $matrix = [];
        $roles = ['guest', 'employee', 'archivist', 'super_admin'];

        foreach ($routes as [$method, $uri, $expectedPerRole, $isJson]) {
            foreach ($roles as $role) {
                $testName = "{$role} - {$method} {$uri}";
                $matrix[$testName] = [
                    $role,
                    $method,
                    $uri,
                    $expectedPerRole[$role],
                    $isJson,
                ];
            }
        }

        return $matrix;
    }

    /**
     * Uji matriks setiap method UserPolicy pada ketiga role.
     */
    #[DataProvider('userPolicyMethodsProvider')]
    public function test_user_policy_methods(string $role, string $method, bool $expectedResult): void
    {
        $policy = new UserPolicy();
        $section = Section::factory()->create();

        $actor = User::factory()->create([
            'role' => UserRole::from($role),
            'section_id' => $section->id,
            'is_active' => true,
        ]);

        $target = User::factory()->employee()->create([
            'section_id' => $section->id,
            'is_active' => true,
        ]);

        $result = match ($method) {
            'viewAny' => $policy->viewAny($actor),
            'view' => $policy->view($actor, $target),
            'create' => $policy->create($actor),
            'update' => $policy->update($actor, $target),
            'resetPassword' => $policy->resetPassword($actor, $target),
            'delete' => $policy->delete($actor, $target),
        };

        $this->assertSame(
            $expectedResult,
            $result,
            "UserPolicy::{$method} untuk role {$role} menghasilkan nilai yang berbeda dari ekspektasi."
        );
    }

    public static function userPolicyMethodsProvider(): array
    {
        $methods = ['viewAny', 'view', 'create', 'update', 'resetPassword', 'delete'];
        $matrix = [];

        foreach ($methods as $method) {
            $matrix["employee - {$method}"] = ['employee', $method, false];
            $matrix["archivist - {$method}"] = ['archivist', $method, false];
            $matrix["super_admin - {$method}"] = ['super_admin', $method, true];
        }

        return $matrix;
    }

    /**
     * Uji matriks setiap method SectionPolicy pada ketiga role.
     */
    #[DataProvider('sectionPolicyMethodsProvider')]
    public function test_section_policy_methods(string $role, string $method, bool $expectedResult): void
    {
        $policy = new SectionPolicy();
        $section = Section::factory()->create();

        $actor = User::factory()->create([
            'role' => UserRole::from($role),
            'is_active' => true,
        ]);

        $result = match ($method) {
            'viewAny' => $policy->viewAny($actor),
            'view' => $policy->view($actor, $section),
            'create' => $policy->create($actor),
            'update' => $policy->update($actor, $section),
            'delete' => $policy->delete($actor, $section),
        };

        $this->assertSame(
            $expectedResult,
            $result,
            "SectionPolicy::{$method} untuk role {$role} menghasilkan nilai yang berbeda dari ekspektasi."
        );
    }

    public static function sectionPolicyMethodsProvider(): array
    {
        $methods = ['viewAny', 'view', 'create', 'update', 'delete'];
        $matrix = [];

        foreach ($methods as $method) {
            $matrix["employee - {$method}"] = ['employee', $method, false];
            $matrix["archivist - {$method}"] = ['archivist', $method, false];
            $matrix["super_admin - {$method}"] = ['super_admin', $method, true];
        }

        return $matrix;
    }

    /**
     * Uji matriks setiap method ArchiveDocumentPolicy pada ketiga role.
     */
    #[DataProvider('archiveDocumentPolicyMethodsProvider')]
    public function test_archive_document_policy_methods(string $role, string $method, bool $expectedResult): void
    {
        $policy = new ArchiveDocumentPolicy();
        $section = Section::factory()->create();

        $actor = User::factory()->create([
            'role' => UserRole::from($role),
            'section_id' => $section->id,
            'is_active' => true,
        ]);

        $document = ArchiveDocument::factory()->available()->create();

        $result = match ($method) {
            'viewAny' => $policy->viewAny($actor),
            'view' => $policy->view($actor, $document),
            'viewFile' => $policy->viewFile($actor, $document),
            'create' => $policy->create($actor),
            'update' => $policy->update($actor, $document),
            'updateStatus' => $policy->updateStatus($actor, $document),
            'delete' => $policy->delete($actor, $document),
        };

        $this->assertSame(
            $expectedResult,
            $result,
            "ArchiveDocumentPolicy::{$method} untuk role {$role} menghasilkan nilai yang berbeda dari ekspektasi."
        );
    }

    public static function archiveDocumentPolicyMethodsProvider(): array
    {
        $readMethods = ['viewAny', 'view', 'viewFile'];
        $writeMethods = ['create', 'update', 'updateStatus', 'delete'];
        $matrix = [];

        foreach ($readMethods as $method) {
            $matrix["employee - {$method}"] = ['employee', $method, true];
            $matrix["archivist - {$method}"] = ['archivist', $method, true];
            $matrix["super_admin - {$method}"] = ['super_admin', $method, true];
        }

        foreach ($writeMethods as $method) {
            $matrix["employee - {$method}"] = ['employee', $method, false];
            $matrix["archivist - {$method}"] = ['archivist', $method, true];
            $matrix["super_admin - {$method}"] = ['super_admin', $method, true];
        }

        return $matrix;
    }
}
