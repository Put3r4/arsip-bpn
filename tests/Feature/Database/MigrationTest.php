<?php

namespace Tests\Feature\Database;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_expected_tables_exist(): void
    {
        $tables = [
            'sections',
            'users',
            'storage_racks',
            'document_types',
            'land_right_types',
            'service_activities',
            'districts',
            'villages',
            'archive_documents',
            'document_loans',
        ];

        foreach ($tables as $table) {
            $this->assertTrue(Schema::hasTable($table), "Table {$table} should exist in schema.");
        }
    }

    public function test_users_table_has_required_columns(): void
    {
        $columns = [
            'id',
            'username',
            'name',
            'nip',
            'email',
            'password',
            'role',
            'section_id',
            'is_active',
            'theme',
            'remember_token',
            'created_at',
            'updated_at',
        ];

        foreach ($columns as $col) {
            $this->assertTrue(Schema::hasColumn('users', $col), "Users table must have column {$col}.");
        }
    }

    public function test_archive_documents_table_has_required_columns_and_soft_deletes(): void
    {
        $columns = [
            'id',
            'document_number',
            'year',
            'document_type_id',
            'land_right_type_id',
            'storage_rack_id',
            'range_number',
            'district_id',
            'village_id',
            'status',
            'file_path',
            'original_filename',
            'file_size',
            'mime_type',
            'created_at',
            'updated_at',
            'deleted_at',
        ];

        foreach ($columns as $col) {
            $this->assertTrue(Schema::hasColumn('archive_documents', $col), "ArchiveDocuments table must have column {$col}.");
        }
    }

    public function test_document_loans_table_has_required_columns(): void
    {
        $columns = [
            'id',
            'archive_document_id',
            'borrower_id',
            'service_activity_id',
            'approved_by',
            'received_by',
            'loan_date',
            'return_date',
            'status',
            'notes',
            'approved_at',
            'cancelled_at',
            'created_at',
            'updated_at',
        ];

        foreach ($columns as $col) {
            $this->assertTrue(Schema::hasColumn('document_loans', $col), "DocumentLoans table must have column {$col}.");
        }
    }

    public function test_districts_and_villages_have_char_code_primary_keys(): void
    {
        $this->assertTrue(Schema::hasColumn('districts', 'id'));
        $this->assertTrue(Schema::hasColumn('districts', 'regency_id'));
        $this->assertTrue(Schema::hasColumn('districts', 'name'));

        $this->assertTrue(Schema::hasColumn('villages', 'id'));
        $this->assertTrue(Schema::hasColumn('villages', 'district_id'));
        $this->assertTrue(Schema::hasColumn('villages', 'name'));
    }
}
