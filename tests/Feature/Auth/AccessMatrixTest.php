<?php

namespace Tests\Feature\Auth;

use App\Enums\UserRole;
use App\Models\DocumentLoan;
use App\Models\Section;
use App\Models\User;
use App\Policies\SectionPolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
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
        $section = Section::factory()->create();
        $targetUser = User::factory()->employee()->create([
            'section_id' => $section->id,
            'password' => Hash::make('password123'),
        ]);

        $uri = str_replace(
            ['{section}', '{user}'],
            [$section->id, $targetUser->id],
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

        $this->assertSame(
            $expectedStatus,
            $response->status(),
            "Gagal pada [Role: {$role}] {$method} {$uri} -> Status aktual: {$response->status()}, diharapkan: {$expectedStatus}"
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
                'employee' => 302, // Redirect ke /dashboard
                'archivist' => 302,
                'super_admin' => 302,
            ], false],
            ['POST', '/logout', [
                'guest' => 302, // Ke login
                'employee' => 302, // Ke login
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
            'delete' => $policy->delete($actor, $section), // Seksi kosong dapat dihapus jika super_admin
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
}
