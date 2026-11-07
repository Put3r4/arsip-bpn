<?php

namespace Tests\Feature\User;

use App\Enums\UserRole;
use App\Models\DocumentLoan;
use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_view_users_page_and_data_endpoint(): void
    {
        $admin = User::factory()->superAdmin()->create();
        User::factory()->count(3)->create();

        // View Blade Page
        $response = $this->actingAs($admin)->get('/users');
        $response->assertStatus(200);
        $response->assertSee('Manajemen Pengguna');

        // JSON Data Table Endpoint
        $jsonResponse = $this->actingAs($admin)->getJson('/users/data');
        $jsonResponse->assertStatus(200);
        $jsonResponse->assertJsonStructure([
            'data' => [
                '*' => ['id', 'name', 'username', 'nip', 'role', 'role_label', 'is_active', 'section_name', 'can_delete', 'is_self']
            ],
            'meta' => ['current_page', 'last_page', 'per_page', 'total']
        ]);
    }

    public function test_super_admin_can_create_user_with_valid_data(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $section = Section::factory()->create();

        $response = $this->actingAs($admin)->post('/users', [
            'name' => 'Budi Santoso',
            'username' => 'budi_santoso',
            'nip' => '198501012010011001',
            'role' => UserRole::EMPLOYEE->value,
            'section_id' => $section->id,
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'is_active' => '1',
        ]);

        $response->assertRedirect('/users');
        $this->assertDatabaseHas('users', [
            'username' => 'budi_santoso',
            'name' => 'Budi Santoso',
            'role' => UserRole::EMPLOYEE->value,
            'section_id' => $section->id,
            'is_active' => true,
        ]);

        $createdUser = User::where('username', 'budi_santoso')->first();
        $this->assertTrue(Hash::check('password123', $createdUser->password));
    }

    public function test_user_creation_validates_required_fields_and_min_password_length(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $response = $this->actingAs($admin)->post('/users', [
            'name' => '',
            'username' => '',
            'role' => 'invalid_role',
            'password' => 'short', // Kurang dari 8 karakter
            'password_confirmation' => 'short',
        ]);

        $response->assertSessionHasErrors(['name', 'username', 'role', 'password']);
    }

    public function test_username_must_be_unique(): void
    {
        $admin = User::factory()->superAdmin()->create();
        User::factory()->create(['username' => 'existing_user']);

        $response = $this->actingAs($admin)->post('/users', [
            'name' => 'Duplikat Pengguna',
            'username' => 'existing_user',
            'role' => UserRole::EMPLOYEE->value,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors('username');
    }

    public function test_super_admin_can_update_user(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $section = Section::factory()->create();
        $user = User::factory()->employee()->create([
            'username' => 'user_lama_test',
            'name' => 'Nama Lama',
            'section_id' => $section->id,
        ]);

        $response = $this->actingAs($admin)->patch("/users/{$user->id}", [
            'name' => 'Nama Baru Diperbarui',
            'username' => 'user_lama_test',
            'nip' => '199505052020011002',
            'role' => UserRole::ARCHIVIST->value,
            'section_id' => $section->id,
            'is_active' => '1',
        ]);

        $response->assertRedirect('/users');
        $this->assertSame('Nama Baru Diperbarui', $user->fresh()->name);
        $this->assertSame(UserRole::ARCHIVIST, $user->fresh()->role);
    }

    public function test_super_admin_can_reset_user_password(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $user = User::factory()->employee()->create([
            'password' => Hash::make('old_password'),
        ]);

        $response = $this->actingAs($admin)->post("/users/{$user->id}/reset-password", [
            'password' => 'new_secret_password',
            'password_confirmation' => 'new_secret_password',
        ]);

        $response->assertRedirect('/users');
        $this->assertTrue(Hash::check('new_secret_password', $user->fresh()->password));
    }

    public function test_super_admin_cannot_delete_self(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $response = $this->actingAs($admin)->delete("/users/{$admin->id}");

        $response->assertRedirect('/users');
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_user_with_loan_history_cannot_be_deleted(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $employee = User::factory()->employee()->create();

        // Buat riwayat peminjaman untuk pegawai ini
        DocumentLoan::factory()->create([
            'borrower_id' => $employee->id,
        ]);

        $response = $this->actingAs($admin)->delete("/users/{$employee->id}");

        $response->assertRedirect('/users');
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('users', ['id' => $employee->id]);
    }

    public function test_user_with_loan_history_returns_422_when_deleted_via_json(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $employee = User::factory()->employee()->create();

        DocumentLoan::factory()->create([
            'borrower_id' => $employee->id,
        ]);

        $response = $this->actingAs($admin)->deleteJson("/users/{$employee->id}");

        $response->assertStatus(422);
        $response->assertJson([
            'status' => 'error',
            'message' => 'Pengguna yang memiliki riwayat peminjaman tidak dapat dihapus. Silakan nonaktifkan akun.',
        ]);
        $this->assertDatabaseHas('users', ['id' => $employee->id]);
    }

    public function test_user_without_loans_can_be_deleted(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $user = User::factory()->employee()->create();

        $response = $this->actingAs($admin)->delete("/users/{$user->id}");

        $response->assertRedirect('/users');
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    /**
     * Item 2: Tolak (422) menonaktifkan akun jika satu-satunya super_admin aktif.
     */
    public function test_cannot_deactivate_the_last_active_super_admin(): void
    {
        $admin = User::factory()->superAdmin()->create([
            'is_active' => true,
        ]);

        $response = $this->actingAs($admin)->patchJson("/users/{$admin->id}", [
            'name' => $admin->name,
            'username' => $admin->username,
            'role' => UserRole::SUPER_ADMIN->value,
            'is_active' => false,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('role');
        $this->assertTrue($admin->fresh()->is_active);
    }

    /**
     * Item 2: Tolak (422) mengubah role jika satu-satunya super_admin aktif.
     */
    public function test_cannot_change_role_of_the_last_active_super_admin(): void
    {
        $admin = User::factory()->superAdmin()->create([
            'is_active' => true,
        ]);

        $section = Section::factory()->create();

        $response = $this->actingAs($admin)->patchJson("/users/{$admin->id}", [
            'name' => $admin->name,
            'username' => $admin->username,
            'role' => UserRole::EMPLOYEE->value,
            'section_id' => $section->id,
            'is_active' => true,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('role');
        $this->assertSame(UserRole::SUPER_ADMIN, $admin->fresh()->role);
    }

    /**
     * Item 2: Tolak (422) menghapus akun jika satu-satunya super_admin aktif.
     */
    public function test_cannot_delete_the_last_active_super_admin(): void
    {
        $admin1 = User::factory()->superAdmin()->create();

        $response = $this->actingAs($admin1)->deleteJson("/users/{$admin1->id}");

        $response->assertStatus(422);
        $this->assertDatabaseHas('users', ['id' => $admin1->id]);
    }

    /**
     * Item 2: Jika ada dua super admin aktif, aksi diizinkan pada salah satunya.
     */
    public function test_can_deactivate_or_demote_super_admin_when_another_active_super_admin_exists(): void
    {
        $admin1 = User::factory()->superAdmin()->create(['is_active' => true]);
        $admin2 = User::factory()->superAdmin()->create(['is_active' => true]);
        $section = Section::factory()->create();

        // Admin1 menonaktifkan Admin2 -> diizinkan karena masih ada Admin1 yang aktif
        $resDeact = $this->actingAs($admin1)->patchJson("/users/{$admin2->id}", [
            'name' => $admin2->name,
            'username' => $admin2->username,
            'role' => UserRole::SUPER_ADMIN->value,
            'is_active' => false,
        ]);
        $resDeact->assertStatus(200);
        $this->assertFalse($admin2->fresh()->is_active);

        // Aktifkan kembali Admin2
        $admin2->update(['is_active' => true]);

        // Admin1 mengubah role Admin2 menjadi employee -> diizinkan karena masih ada Admin1
        $resDemote = $this->actingAs($admin1)->patchJson("/users/{$admin2->id}", [
            'name' => $admin2->name,
            'username' => $admin2->username,
            'role' => UserRole::EMPLOYEE->value,
            'section_id' => $section->id,
            'is_active' => true,
        ]);
        $resDemote->assertStatus(200);
        $this->assertSame(UserRole::EMPLOYEE, $admin2->fresh()->role);

        // Buat Admin3 tanpa loan dan bukan diri sendiri, untuk dites penghapusan saat masih ada Admin1
        $admin3 = User::factory()->superAdmin()->create(['is_active' => true]);
        $resDelete = $this->actingAs($admin1)->deleteJson("/users/{$admin3->id}");
        $resDelete->assertStatus(200);
        $this->assertDatabaseMissing('users', ['id' => $admin3->id]);
    }

    /**
     * Item 6: section_id wajib diisi jika role adalah employee atau archivist.
     */
    public function test_section_id_is_required_for_employee_and_archivist(): void
    {
        $admin = User::factory()->superAdmin()->create();

        // 1. Employee tanpa section_id -> gagal validasi
        $resEmp = $this->actingAs($admin)->postJson('/users', [
            'name' => 'Pegawai Tanpa Seksi',
            'username' => 'pegawai_nosection',
            'role' => UserRole::EMPLOYEE->value,
            'section_id' => null,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);
        $resEmp->assertStatus(422);
        $resEmp->assertJsonValidationErrors('section_id');

        // 2. Archivist tanpa section_id -> gagal validasi
        $resArc = $this->actingAs($admin)->postJson('/users', [
            'name' => 'Petugas Tanpa Seksi',
            'username' => 'petugas_nosection',
            'role' => UserRole::ARCHIVIST->value,
            'section_id' => null,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);
        $resArc->assertStatus(422);
        $resArc->assertJsonValidationErrors('section_id');
    }

    /**
     * Item 6: section_id opsional untuk super_admin.
     */
    public function test_section_id_is_optional_for_super_admin(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $resAdmin = $this->actingAs($admin)->postJson('/users', [
            'name' => 'Admin Baru Tanpa Seksi',
            'username' => 'admin_nosection',
            'role' => UserRole::SUPER_ADMIN->value,
            'section_id' => null,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $resAdmin->assertStatus(201);
        $this->assertDatabaseHas('users', [
            'username' => 'admin_nosection',
            'section_id' => null,
            'role' => UserRole::SUPER_ADMIN->value,
        ]);
    }

    /**
     * Item 6: Update role ke employee atau archivist mewajibkan section_id.
     */
    public function test_updating_user_to_employee_requires_section_id(): void
    {
        $admin1 = User::factory()->superAdmin()->create();
        $admin2 = User::factory()->superAdmin()->create(['section_id' => null]);

        $resUpdate = $this->actingAs($admin1)->patchJson("/users/{$admin2->id}", [
            'name' => $admin2->name,
            'username' => $admin2->username,
            'role' => UserRole::EMPLOYEE->value,
            'section_id' => null,
            'is_active' => true,
        ]);

        $resUpdate->assertStatus(422);
        $resUpdate->assertJsonValidationErrors('section_id');
    }
}
